<?php

namespace App\Services\Pointage;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Service OCR pour photo du cahier de texte enseignant.
 *
 * Le cahier de texte contient typiquement par ligne :
 *   - Date du cours
 *   - Créneau horaire (ex: 8h-9h, 7h55-8h50)
 *   - Classe
 *   - Matière
 *   - Contenu / titre de la leçon
 *   - Signature
 *
 * Le service utilise OpenAI Vision pour extraire ces données et permet
 * ensuite la validation du pointage en croisant avec l'EDT.
 */
class CahierTexteOcrService
{
    /**
     * Extrait les informations du cahier de texte depuis une image.
     *
     * @return array{
     *   date: ?string,
     *   creneau: ?array{heure_debut: ?string, heure_fin: ?string, libelle: ?string},
     *   classe: ?string,
     *   matiere: ?string,
     *   contenu: ?string,
     *   signature_presente: bool,
     *   confidence: int,
     *   notes_extraction: ?string,
     *   raw_response: ?string,
     * }
     */
    public function extract(string $disk, string $path): array
    {
        $mime = Storage::disk($disk)->mimeType($path) ?: 'image/jpeg';

        if (! Str::startsWith($mime, 'image/')) {
            throw new RuntimeException("Type de fichier non supporté : {$mime}");
        }

        $binary = Storage::disk($disk)->get($path);
        $dataUrl = 'data:' . $mime . ';base64,' . base64_encode($binary);

        $response = Http::withToken(config('services.openai.api_key'))
            ->timeout(config('services.openai.timeout', 90))
            ->acceptJson()
            ->post('https://api.openai.com/v1/responses', [
                'model' => config('services.openai.ocr_model', 'gpt-4.1-mini'),
                'input' => [[
                    'role' => 'user',
                    'content' => [
                        ['type' => 'input_text', 'text' => $this->prompt()],
                        ['type' => 'input_image', 'image_url' => $dataUrl, 'detail' => 'high'],
                    ],
                ]],
            ])
            ->throw()
            ->json();

        $text = $this->extractText($response);
        $parsed = $this->extractJson($text);

        return $this->normalize($parsed);
    }

    private function prompt(): string
    {
        return <<<PROMPT
Tu analyses une PHOTO de cahier de texte d'un enseignant (Côte d'Ivoire / France).

Extrais STRICTEMENT les informations de l'ENTRÉE LA PLUS RÉCENTE (dernière ligne remplie ou la plus visible) au format JSON :

```json
{
  "date": "AAAA-MM-JJ ou null",
  "creneau": {
    "heure_debut": "HH:MM ou null",
    "heure_fin": "HH:MM ou null",
    "libelle": "ex: 8h-9h ou 1ère heure ou null"
  },
  "classe": "ex: 5e A ou 5ème 1 ou null",
  "matiere": "ex: Mathématiques, SVT, Français ou null",
  "contenu": "résumé du titre/contenu de la leçon, max 200 caractères",
  "signature_presente": true ou false,
  "confidence": entier 0-100 (qualité globale de l'extraction),
  "notes_extraction": "remarques sur ce qui est lisible/illisible"
}
```

Règles :
- Format date : convertis toujours en AAAA-MM-JJ (ex: "15/05/2026" → "2026-05-15")
- Si tu vois "8h" ou "8H", convertis en "08:00"
- Pour les créneaux du collège ivoirien : 7h-8h, 8h-9h, 9h-10h, 10h15-11h10, 11h10-12h05, 14h-14h55, 14h55-15h50, 15h50-16h45
- N'invente AUCUNE valeur : mets null si tu ne lis pas clairement
- La signature peut être un trait, un paraphe, un tampon
- Réponds UNIQUEMENT avec le JSON dans un bloc ```json … ```
PROMPT;
    }

    private function extractText(array $response): string
    {
        // Format API "responses" d'OpenAI
        $output = $response['output'] ?? [];
        foreach ($output as $msg) {
            $contents = $msg['content'] ?? [];
            foreach ($contents as $c) {
                if (($c['type'] ?? '') === 'output_text') {
                    return (string) ($c['text'] ?? '');
                }
            }
        }
        return (string) ($response['output_text'] ?? '');
    }

    private function extractJson(string $text): array
    {
        // Cherche un bloc ```json ... ```
        if (preg_match('/```json\s*(.*?)\s*```/s', $text, $m)) {
            $json = $m[1];
        } elseif (preg_match('/\{.*\}/s', $text, $m)) {
            $json = $m[0];
        } else {
            return [];
        }

        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function normalize(array $data): array
    {
        return [
            'date'      => $this->normalizeDate($data['date'] ?? null),
            'creneau'   => [
                'heure_debut' => $this->normalizeTime($data['creneau']['heure_debut'] ?? null),
                'heure_fin'   => $this->normalizeTime($data['creneau']['heure_fin'] ?? null),
                'libelle'     => $data['creneau']['libelle'] ?? null,
            ],
            'classe'    => $data['classe'] ?? null,
            'matiere'   => $data['matiere'] ?? null,
            'contenu'   => $data['contenu'] ?? null,
            'signature_presente' => (bool) ($data['signature_presente'] ?? false),
            'confidence' => (int) ($data['confidence'] ?? 0),
            'notes_extraction' => $data['notes_extraction'] ?? null,
        ];
    }

    private function normalizeDate(?string $v): ?string
    {
        if (! $v) return null;
        try {
            return \Carbon\Carbon::parse($v)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function normalizeTime(?string $v): ?string
    {
        if (! $v) return null;
        $v = trim($v);
        // "8h" → "08:00", "8h30" → "08:30"
        if (preg_match('/^(\d{1,2})[hH](\d{0,2})$/', $v, $m)) {
            $h = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $min = $m[2] !== '' ? str_pad($m[2], 2, '0', STR_PAD_RIGHT) : '00';
            return "$h:$min";
        }
        // "08:00" déjà bon
        if (preg_match('/^(\d{1,2}):(\d{2})$/', $v, $m)) {
            return str_pad($m[1], 2, '0', STR_PAD_LEFT) . ':' . $m[2];
        }
        return null;
    }
}
