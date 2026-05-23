<?php

namespace App\Services\FeuilleDeNote;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Service OCR pour un cahier d'appel papier (photo).
 *
 * Détecte les cases cochées P/A/R/E/D par élève et par jour.
 * Retourne un preview que le prof valide avant enregistrement.
 */
class CahierAppelOcrService
{
    public function extract(string $disk, string $path, Collection $eleves, Carbon $semaine): array
    {
        $mime = Storage::disk($disk)->mimeType($path) ?: 'application/octet-stream';

        $matricules = $eleves
            ->map(fn ($e) => $e->matricule_interne ?: $e->matricule_desps)
            ->filter()->take(60)->values()->all();

        $jours = [];
        for ($i = 0; $i < 6; $i++) {
            $jours[] = $semaine->copy()->addDays($i)->locale('fr')->isoFormat('ddd D/MM');
        }

        if (Str::startsWith($mime, 'image/')) {
            $payload = $this->callVision($disk, $path, $mime, $matricules, $jours);
        } elseif ($mime === 'application/pdf') {
            $payload = $this->callVisionPdf($disk, $path, $matricules, $jours);
        } else {
            throw new RuntimeException("Type de fichier non pris en charge : {$mime}");
        }

        return $this->normalize($payload, $eleves, $semaine);
    }

    private function callVision(string $disk, string $path, string $mime, array $matricules, array $jours): array
    {
        $binary  = Storage::disk($disk)->get($path);
        $dataUrl = 'data:' . $mime . ';base64,' . base64_encode($binary);

        $response = Http::withToken(config('services.openai.api_key'))
            ->timeout(config('services.openai.timeout', 90))
            ->acceptJson()
            ->post('https://api.openai.com/v1/responses', [
                'model' => config('services.openai.ocr_model', 'gpt-4.1-mini'),
                'input' => [[
                    'role' => 'user',
                    'content' => [
                        ['type' => 'input_text',  'text' => $this->prompt($matricules, $jours)],
                        ['type' => 'input_image', 'image_url' => $dataUrl, 'detail' => 'high'],
                    ],
                ]],
            ])
            ->throw()
            ->json();

        return $this->extractJson($this->extractText($response));
    }

    private function callVisionPdf(string $disk, string $path, array $matricules, array $jours): array
    {
        if (!class_exists(\Imagick::class)) return [];

        $tmpPdf = tempnam(sys_get_temp_dir(), 'capp_pdf_') . '.pdf';
        file_put_contents($tmpPdf, Storage::disk($disk)->get($path));

        $imagick = new \Imagick();
        $imagick->setResolution(200, 200);
        $imagick->readImage($tmpPdf . '[0-1]');

        $contents = [['type' => 'input_text', 'text' => $this->prompt($matricules, $jours)]];
        foreach ($imagick as $page) {
            $page->setImageFormat('jpeg');
            $contents[] = [
                'type' => 'input_image',
                'image_url' => 'data:image/jpeg;base64,' . base64_encode($page->getImageBlob()),
                'detail' => 'high',
            ];
        }
        $imagick->clear(); $imagick->destroy();
        @unlink($tmpPdf);

        $response = Http::withToken(config('services.openai.api_key'))
            ->timeout(config('services.openai.timeout', 90))
            ->acceptJson()
            ->post('https://api.openai.com/v1/responses', [
                'model' => config('services.openai.ocr_model', 'gpt-4.1-mini'),
                'input' => [['role' => 'user', 'content' => $contents]],
            ])
            ->throw()
            ->json();

        return $this->extractJson($this->extractText($response));
    }

    private function prompt(array $matricules, array $jours): string
    {
        $mat  = implode(', ', array_slice($matricules, 0, 60));
        $jrs  = implode(', ', $jours);

        return <<<PROMPT
Tu analyses une **feuille de cahier d'appel** manuscrite (photo) d'une classe scolaire en Côte d'Ivoire.

OBJECTIF :
Pour chaque élève (identifié par son matricule en colonne) et chaque jour de la semaine (en colonnes), lire le symbole inscrit dans la cellule et le traduire :
- "P" / "✓" / "+" / "Pres" → present
- "A" / "X" / "-" / "Abs" → absent
- "R" / "Ret" → retard
- "E" / "Exc" / "Excusé" → excuse
- "D" / "Disp" / "Dispensé" → dispense
- vide ou non-lisible → ne pas inclure

MATRICULES DE LA CLASSE (référence pour corriger l'OCR) :
{$mat}

JOURS DE LA SEMAINE (ordre des colonnes) :
{$jrs}

RÈGLES :
1. Ignore les colonnes "N°", "Matricule", "Nom", "Sexe", "Total".
2. Pour chaque élève, fournis un objet avec son matricule et un dict { "ddd D/MM" : statut } pour chaque jour rempli.
3. Si une cellule est vide ou ambiguë, ne la mets pas dans la réponse.
4. confidence_score 0-100.

RÉPONDS STRICTEMENT en JSON, sans texte autour :

{
  "eleves": [
    { "matricule": "15195226N", "nom": "ATTIOUA AMOIN", "jours": { "lun. 12/05": "present", "mar. 13/05": "absent" } }
  ],
  "confidence_score": 0,
  "notes_extraction": "string|null"
}
PROMPT;
    }

    private function normalize(array $data, Collection $eleves, Carbon $semaine): array
    {
        $byMatricule = [];
        foreach ($eleves as $e) {
            if ($e->matricule_interne) $byMatricule[strtoupper(trim($e->matricule_interne))] = $e;
            if ($e->matricule_desps)   $byMatricule[strtoupper(trim($e->matricule_desps))] = $e;
        }

        // Map "ddd D/MM" (FR) → date YYYY-MM-DD
        $jourMap = [];
        for ($i = 0; $i < 6; $i++) {
            $d = $semaine->copy()->addDays($i);
            $jourMap[$d->locale('fr')->isoFormat('ddd D/MM')] = $d->toDateString();
            $jourMap[$d->locale('fr')->isoFormat('dddd D/MM')] = $d->toDateString();
            $jourMap[$d->format('D')] = $d->toDateString();
        }

        $rows = [];
        foreach (($data['eleves'] ?? []) as $row) {
            $matricule = strtoupper(trim((string) ($row['matricule'] ?? '')));
            if ($matricule === '') continue;
            $eleve = $byMatricule[$matricule] ?? null;

            $jours = $row['jours'] ?? [];
            $jStandard = [];
            foreach ($jours as $jKey => $statut) {
                $date = $jourMap[strtolower(trim($jKey))] ?? $this->fuzzyMatchDate($jKey, $semaine);
                if (!$date) continue;
                $jStandard[$date] = strtolower(trim($statut));
            }

            $rows[] = [
                'matricule_ocr'  => $matricule,
                'matricule_match'=> $eleve?->matricule_interne ?? $eleve?->matricule_desps,
                'eleve_id'       => $eleve?->id,
                'nom_match'      => $eleve ? trim($eleve->nom . ' ' . $eleve->prenom) : ($row['nom'] ?? null),
                'jours'          => $jStandard,
            ];
        }

        return [
            'eleves'         => $rows,
            'confidence'     => (int) ($data['confidence_score'] ?? 0),
            'notes_extraction' => $data['notes_extraction'] ?? null,
        ];
    }

    private function fuzzyMatchDate(string $jKey, Carbon $semaine): ?string
    {
        // Si l'IA renvoie un format différent : essai par jour de semaine (lun/mar/...)
        $jKey = strtolower($jKey);
        $jours = ['lun' => 0, 'mar' => 1, 'mer' => 2, 'jeu' => 3, 'ven' => 4, 'sam' => 5];
        foreach ($jours as $k => $offset) {
            if (str_starts_with($jKey, $k)) return $semaine->copy()->addDays($offset)->toDateString();
        }
        return null;
    }

    private function extractText(array $response): string
    {
        if (!empty($response['output_text'])) return (string) $response['output_text'];
        $texts = [];
        foreach (($response['output'] ?? []) as $out) {
            foreach (($out['content'] ?? []) as $c) {
                if (($c['type'] ?? null) === 'output_text' && !empty($c['text'])) $texts[] = $c['text'];
            }
        }
        return trim(implode("\n", $texts));
    }

    private function extractJson(string $text): array
    {
        $text = trim($text);
        $start = strpos($text, '{'); $end = strrpos($text, '}');
        if ($start === false || $end === false || $end <= $start) return [];
        $json = substr($text, $start, $end - $start + 1);
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }
}
