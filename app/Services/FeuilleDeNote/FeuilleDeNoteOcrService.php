<?php

namespace App\Services\FeuilleDeNote;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Service OCR pour feuille de note papier (photo).
 *
 * Extrait du document :
 *   - en-tête (école, classe, matière, prof, date — informatif)
 *   - noms d'évaluations (colonnes en haut de tableau)
 *   - notes par élève via matricule
 *
 * Le résultat est ensuite confirmé/édité par le prof dans une page de preview
 * avant d'être persisté.
 */
class FeuilleDeNoteOcrService
{
    /**
     * @param Collection $eleves  élèves de la classe (utilisés pour aider à matcher les matricules)
     */
    public function extract(string $disk, string $path, Collection $eleves): array
    {
        $mime = Storage::disk($disk)->mimeType($path) ?: 'application/octet-stream';

        $matricules = $eleves
            ->map(fn ($e) => $e->matricule_interne ?: $e->matricule_desps)
            ->filter()->take(60)->values()->all();

        if (Str::startsWith($mime, 'image/')) {
            $payload = $this->callVision($disk, $path, $mime, $matricules);
        } elseif ($mime === 'application/pdf') {
            $payload = $this->callVisionPdf($disk, $path, $matricules);
        } else {
            throw new RuntimeException("Type de fichier non pris en charge : {$mime}");
        }

        return $this->normalize($payload, $eleves);
    }

    // ── Appel API ──────────────────────────────────────────────────────────

    private function callVision(string $disk, string $path, string $mime, array $matricules): array
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
                        ['type' => 'input_text', 'text' => $this->prompt($matricules)],
                        ['type' => 'input_image', 'image_url' => $dataUrl, 'detail' => 'high'],
                    ],
                ]],
            ])
            ->throw()
            ->json();

        return $this->extractJson($this->extractText($response));
    }

    private function callVisionPdf(string $disk, string $path, array $matricules): array
    {
        if (!class_exists(\Imagick::class)) {
            return [];
        }

        $tmpPdf = tempnam(sys_get_temp_dir(), 'fdn_pdf_') . '.pdf';
        file_put_contents($tmpPdf, Storage::disk($disk)->get($path));

        $imagick = new \Imagick();
        $imagick->setResolution(200, 200);
        $imagick->readImage($tmpPdf . '[0-1]');

        $contents = [['type' => 'input_text', 'text' => $this->prompt($matricules)]];
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

    // ── Prompt ─────────────────────────────────────────────────────────────

    private function prompt(array $matricules): string
    {
        $matriculesList = empty($matricules)
            ? '(aucune liste fournie — extrais les matricules visibles)'
            : implode(', ', array_slice($matricules, 0, 60));

        return <<<PROMPT
Tu analyses une **feuille de notes manuscrite** (photo) d'une classe scolaire en Côte d'Ivoire.

OBJECTIF :
- Extraire les NOTES d'INTERROGATIONS / DEVOIRS pour chaque élève à partir des colonnes du tableau.
- Identifier chaque élève par son MATRICULE (colonne dédiée généralement).
- Le tableau a en colonnes : N°, MATRICULE, NOM ET PRÉNOM, GENRE (M/F), puis plusieurs colonnes de notes, puis MOY (à ignorer).

MATRICULES DE LA CLASSE (référence pour t'aider à corriger les chiffres mal lus) :
{$matriculesList}

RÈGLES :
1. Les notes sont des nombres entre 0 et 20 (ou autre barème visible). Format décimal possible (ex: 12.5, 14,75).
2. Si une cellule est vide → omets-la.
3. Si la cellule contient "ABS", "ABSENT" → mets la valeur "ABS".
4. Si la cellule contient "DISP", "DISPENSÉ" → mets "DISP".
5. Pour chaque colonne de notes, donne-lui un titre court : "Interro 1", "Devoir 1", etc. (ou le titre manuscrit en haut si lisible).
6. Si le barème (sur quoi sont notées les notes) est visible, indique-le, sinon 20 par défaut.
7. Confidence : entier 0-100 (qualité globale de ton extraction).

RÉPONDS STRICTEMENT en JSON, sans texte autour :

{
  "header": {
    "ecole": "string|null",
    "classe": "string|null",
    "matiere": "string|null",
    "professeur": "string|null",
    "date": "string|null"
  },
  "colonnes": [
    { "titre": "Interro 1", "type": "INTERRO", "note_sur": 20 },
    { "titre": "Devoir 1",  "type": "DEVOIR",  "note_sur": 20 }
  ],
  "eleves": [
    {
      "matricule": "15195226N",
      "nom": "ATTIOUA AMOIN CHANTAL",
      "sexe": "F",
      "notes": ["12.5", "14", "ABS", "16"]
    }
  ],
  "confidence_score": 0,
  "notes_extraction": "Commentaire sur la qualité (flou, manque de contraste, etc.)"
}

L'ordre des notes par élève DOIT correspondre exactement à l'ordre des colonnes.
PROMPT;
    }

    // ── Normalisation ──────────────────────────────────────────────────────

    private function normalize(array $data, Collection $eleves): array
    {
        $colonnes = collect($data['colonnes'] ?? [])->map(fn ($c) => [
            'titre'    => trim((string) ($c['titre'] ?? 'Colonne')),
            'type'     => strtoupper(trim((string) ($c['type'] ?? 'DEVOIR'))),
            'note_sur' => is_numeric($c['note_sur'] ?? null) ? (float) $c['note_sur'] : 20,
        ])->values()->all();

        // Map matricule (uppercase trim) -> eleve
        $byMatricule = [];
        foreach ($eleves as $e) {
            if ($e->matricule_interne) $byMatricule[strtoupper(trim($e->matricule_interne))] = $e;
            if ($e->matricule_desps)   $byMatricule[strtoupper(trim($e->matricule_desps))] = $e;
        }

        $rows = [];
        foreach (($data['eleves'] ?? []) as $row) {
            $matricule = strtoupper(trim((string) ($row['matricule'] ?? '')));
            if ($matricule === '') continue;

            $eleve = $byMatricule[$matricule] ?? null;
            // si pas trouvé, on tente une recherche floue par nom
            if (!$eleve && !empty($row['nom'])) {
                $needle = strtoupper(preg_replace('/\s+/', '', $row['nom']));
                foreach ($eleves as $e) {
                    $hay = strtoupper(preg_replace('/\s+/', '', ($e->nom ?? '') . ($e->prenom ?? '')));
                    if ($hay !== '' && (str_contains($hay, $needle) || str_contains($needle, $hay))) {
                        $eleve = $e; break;
                    }
                }
            }

            $notes = $row['notes'] ?? [];
            if (!is_array($notes)) $notes = [];

            $rows[] = [
                'matricule_ocr' => $matricule,
                'matricule_match' => $eleve?->matricule_interne ?? $eleve?->matricule_desps,
                'eleve_id'      => $eleve?->id,
                'nom_ocr'       => $row['nom'] ?? null,
                'nom_match'     => $eleve ? trim($eleve->nom . ' ' . $eleve->prenom) : null,
                'sexe'          => $row['sexe'] ?? null,
                'notes'         => array_values($notes),
            ];
        }

        return [
            'header'         => $data['header'] ?? [],
            'colonnes'       => $colonnes,
            'eleves'         => $rows,
            'confidence'     => (int) ($data['confidence_score'] ?? 0),
            'notes_extraction' => $data['notes_extraction'] ?? null,
        ];
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
