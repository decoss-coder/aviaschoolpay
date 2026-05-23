<?php

namespace App\Services\Edt;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class OpenAiVacataireOcrService
{
    public function extractFromStoredFile(string $disk, string $path): array
    {
        $mime = Storage::disk($disk)->mimeType($path) ?: 'application/octet-stream';

        if (Str::startsWith($mime, 'image/')) {
            return $this->extractFromImage($disk, $path, $mime);
        }

        if ($mime === 'application/pdf') {
            return $this->extractFromPdf($disk, $path);
        }

        throw new RuntimeException("Type de fichier non pris en charge pour OCR : {$mime}");
    }

    protected function extractFromImage(string $disk, string $path, string $mime): array
    {
        $binary = Storage::disk($disk)->get($path);
        $dataUrl = 'data:' . $mime . ';base64,' . base64_encode($binary);

        $response = Http::withToken(config('services.openai.api_key'))
            ->timeout(config('services.openai.timeout', 60))
            ->acceptJson()
            ->post('https://api.openai.com/v1/responses', [
                'model' => config('services.openai.ocr_model', 'gpt-4.1-mini'),
                'input' => [[
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'input_text',
                            'text' => $this->ocrPrompt(),
                        ],
                        [
                            'type' => 'input_image',
                            'image_url' => $dataUrl,
                            'detail' => 'high',
                        ],
                    ],
                ]],
            ])
            ->throw()
            ->json();

        $text = $this->extractTextFromResponse($response);
        $json = $this->extractJson($text);

        return $this->normalizePayload($json);
    }

    protected function extractFromPdf(string $disk, string $path): array
    {
        if (!class_exists(\Imagick::class)) {
            return [
                'teacher_name' => null,
                'source_notes' => 'PDF importé, mais Imagick n’est pas disponible pour conversion OCR automatique.',
                'confidence_score' => 0,
                'slots' => [],
            ];
        }

        $tmpPdf = tempnam(sys_get_temp_dir(), 'edt_pdf_') . '.pdf';
        file_put_contents($tmpPdf, Storage::disk($disk)->get($path));

        $imagick = new \Imagick();
        $imagick->setResolution(180, 180);
        $imagick->readImage($tmpPdf . '[0-2]');

        $contents = [[
            'type' => 'input_text',
            'text' => $this->ocrPrompt(),
        ]];

        foreach ($imagick as $page) {
            $page->setImageFormat('jpeg');
            $blob = $page->getImageBlob();
            $contents[] = [
                'type' => 'input_image',
                'image_url' => 'data:image/jpeg;base64,' . base64_encode($blob),
                'detail' => 'high',
            ];
        }

        $imagick->clear();
        $imagick->destroy();
        @unlink($tmpPdf);

        $response = Http::withToken(config('services.openai.api_key'))
            ->timeout(config('services.openai.timeout', 60))
            ->acceptJson()
            ->post('https://api.openai.com/v1/responses', [
                'model' => config('services.openai.ocr_model', 'gpt-4.1-mini'),
                'input' => [[
                    'role' => 'user',
                    'content' => $contents,
                ]],
            ])
            ->throw()
            ->json();

        $text = $this->extractTextFromResponse($response);
        $json = $this->extractJson($text);

        return $this->normalizePayload($json);
    }

    protected function ocrPrompt(): string
    {
        return <<<'PROMPT'
Analyse ce document d'emploi du temps d'un enseignant vacataire.

Objectif :
1. extraire les disponibilités ou indisponibilités par jour et plage horaire,
2. détecter le nom éventuel du professeur,
3. retourner STRICTEMENT un JSON valide, sans texte autour.

Format attendu :
{
  "teacher_name": "string|null",
  "source_notes": "string|null",
  "confidence_score": 0,
  "slots": [
    {
      "jour": "lundi|mardi|mercredi|jeudi|vendredi|samedi|dimanche",
      "heure_debut": "HH:MM",
      "heure_fin": "HH:MM",
      "etat": "indisponible|disponible|prefere|a_eviter",
      "commentaire": "string|null"
    }
  ]
}

Règles :
- Si le document est un emploi du temps externe, considère les plages occupées comme "indisponible".
- Si le document mentionne explicitement des préférences, utilise "prefere".
- Les heures doivent être au format 24h HH:MM.
- Si une information est incertaine, garde un commentaire.
- confidence_score doit être un entier entre 0 et 100.
PROMPT;
    }

    protected function extractTextFromResponse(array $response): string
    {
        if (!empty($response['output_text'])) {
            return (string) $response['output_text'];
        }

        $texts = [];

        foreach (($response['output'] ?? []) as $output) {
            foreach (($output['content'] ?? []) as $content) {
                if (($content['type'] ?? null) === 'output_text' && !empty($content['text'])) {
                    $texts[] = $content['text'];
                }
            }
        }

        return trim(implode("\n", $texts));
    }

    protected function extractJson(string $text): array
    {
        $text = trim($text);

        if ($text === '') {
            return [];
        }

        $start = strpos($text, '{');
        $end = strrpos($text, '}');

        if ($start === false || $end === false || $end <= $start) {
            return [];
        }

        $json = substr($text, $start, $end - $start + 1);
        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }

    protected function normalizePayload(array $data): array
    {
        $slots = collect($data['slots'] ?? [])
            ->map(function ($slot) {
                return [
                    'jour' => strtolower(trim((string) ($slot['jour'] ?? ''))),
                    'heure_debut' => substr((string) ($slot['heure_debut'] ?? ''), 0, 5),
                    'heure_fin' => substr((string) ($slot['heure_fin'] ?? ''), 0, 5),
                    'etat' => $slot['etat'] ?? 'indisponible',
                    'commentaire' => $slot['commentaire'] ?? null,
                ];
            })
            ->filter(function ($slot) {
                return in_array($slot['jour'], ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'], true)
                    && preg_match('/^\d{2}:\d{2}$/', $slot['heure_debut'])
                    && preg_match('/^\d{2}:\d{2}$/', $slot['heure_fin'])
                    && in_array($slot['etat'], ['indisponible', 'disponible', 'prefere', 'a_eviter'], true);
            })
            ->values()
            ->all();

        return [
            'teacher_name' => $data['teacher_name'] ?? null,
            'source_notes' => $data['source_notes'] ?? null,
            'confidence_score' => (int) ($data['confidence_score'] ?? 0),
            'slots' => $slots,
        ];
    }
}