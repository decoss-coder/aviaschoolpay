<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OcrImportService
{
    public const EXTENSIONS_AUTORISEES = ['jpg', 'jpeg', 'png', 'webp', 'heic'];
    public const TAILLE_MAX_MO = 20;

    private const MODEL = 'gpt-4o';

    private const SYSTEM_PROMPT = <<<PROMPT
Tu es un assistant spécialisé dans l'extraction de listes d'élèves depuis des documents scolaires ivoiriens.

Tu reçois une photo contenant un tableau scolaire.
Lis le tableau ligne par ligne.

Colonnes importantes à lire quand elles sont visibles :
- N°
- MATRICULE
- NOM ET PRENOMS
- SEXE / S
- STATUT

IMPORTANT :
- La colonne STATUT contient généralement uniquement "AFF" ou "NAFF".
- "AFF" signifie affecté.
- "NAFF" signifie non affecté.
- Tu dois lire explicitement la colonne STATUT pour chaque élève.
- Ne déduis pas raw_statut à partir d’une autre colonne si la cellule STATUT est visible.
- Si la cellule STATUT est absente, vide ou illisible, retourne raw_statut = "" et statut_eleve = "".

RÈGLES STRICTES :
1. Retourne UNIQUEMENT du JSON valide, rien d'autre.
2. Pour chaque élève, identifie : matricule, nom_complet, sexe, raw_statut, statut_eleve.
3. Pour le nom complet, garde TOUS les mots dans l'ordre d'origine.
4. Si tu ne peux pas lire une cellule clairement, mets la valeur à vide "" plutôt que d'inventer.
5. Pour le sexe : "M" ou "F" uniquement. Si ambigu, mets "" et ajoute un avertissement.
6. Ignore les lignes d'en-tête, les lignes vides, les pieds de page.
7. Extrais aussi les métadonnées du document si visibles :
   - Nom de l'école/établissement
   - Classe (ex: "5ème 1", "Tle A2", "CM2")
   - Année scolaire
8. Pour statut_eleve :
   - si raw_statut = "AFF" => statut_eleve = "AFF"
   - si raw_statut = "NAFF" => statut_eleve = "NAFF"
   - sinon => statut_eleve = ""

FORMAT JSON ATTENDU :
{
  "etablissement": "NOM DE L'ECOLE ou null",
  "classe": "NOM DE CLASSE ou null",
  "annee_scolaire": "YYYY-YYYY ou null",
  "eleves": [
    {
      "matricule": "22298276T",
      "nom_complet": "BAGATE OUSMANE",
      "sexe": "M",
      "raw_statut": "AFF",
      "statut_eleve": "AFF",
      "confiance": "haute"
    }
  ],
  "avertissements": [
    "Ligne 5 : matricule illisible, laissé vide",
    "Ligne 12 : sexe ambigu"
  ]
}

Ne rajoute AUCUN commentaire, AUCUN texte hors du JSON.
PROMPT;

    /**
     * @return array{
     *   lignes: array<int, array{
     *     matricule: string,
     *     nom_complet: string,
     *     sexe: string,
     *     raw_statut: string,
     *     statut_eleve: string
     *   }>,
     *   meta: array<string, mixed>,
     *   avertissements: array<int, string>,
     *   cout_usd: float
     * }
     */
    public function extraire(UploadedFile $fichier): array
    {
        $this->validerFichier($fichier);
        $this->validerConfiguration();

        $imageBase64 = $this->preparerImage($fichier);
        $reponse = $this->appelerOpenAI($imageBase64);
        $donnees = $this->parserReponse($reponse);

        $lignes = [];

        foreach (($donnees['eleves'] ?? []) as $eleve) {
            if (empty($eleve['nom_complet'])) {
                continue;
            }

            $rawStatut = strtoupper(trim((string) ($eleve['raw_statut'] ?? '')));
            if (!in_array($rawStatut, ['AFF', 'NAFF'], true)) {
                $rawStatut = '';
            }

            $statutEleve = strtoupper(trim((string) ($eleve['statut_eleve'] ?? '')));
            if (!in_array($statutEleve, ['AFF', 'NAFF'], true)) {
                $statutEleve = in_array($rawStatut, ['AFF', 'NAFF'], true) ? $rawStatut : '';
            }

            $lignes[] = [
                'matricule' => trim((string) ($eleve['matricule'] ?? '')),
                'nom_complet' => trim((string) ($eleve['nom_complet'] ?? '')),
                'sexe' => strtoupper(trim((string) ($eleve['sexe'] ?? ''))),
                'raw_statut' => $rawStatut,
                'statut_eleve' => $statutEleve,
            ];
        }

        if (empty($lignes)) {
            throw new \Exception(
                "Aucun élève détecté sur la photo. "
                . "Vérifiez que la photo est nette et que le tableau est bien visible. "
                . "Astuce : prenez la photo en bonne lumière, cadrage droit, texte lisible."
            );
        }

        return [
            'lignes' => $lignes,
            'meta' => [
                'etablissement_detecte' => $donnees['etablissement'] ?? null,
                'classe_detectee' => $donnees['classe'] ?? null,
                'annee_detectee' => $donnees['annee_scolaire'] ?? null,
                'source_ocr' => self::MODEL,
            ],
            'avertissements' => $donnees['avertissements'] ?? [],
            'cout_usd' => $this->estimerCout($reponse),
        ];
    }

    private function validerFichier(UploadedFile $fichier): void
    {
        if (!$fichier->isValid()) {
            throw new \Exception("Le fichier n'a pas pu être uploadé.");
        }

        $extension = strtolower($fichier->getClientOriginalExtension());
        if (!in_array($extension, self::EXTENSIONS_AUTORISEES, true)) {
            throw new \Exception(
                "Format non supporté. Formats acceptés : " . implode(', ', self::EXTENSIONS_AUTORISEES)
            );
        }

        $tailleMo = $fichier->getSize() / 1024 / 1024;
        if ($tailleMo > self::TAILLE_MAX_MO) {
            throw new \Exception(
                "Image trop volumineuse (" . round($tailleMo, 1) . " Mo). Maximum : " . self::TAILLE_MAX_MO . " Mo."
            );
        }
    }

    private function validerConfiguration(): void
    {
        $apiKey = config('services.openai.api_key');

        if (empty($apiKey)) {
            throw new \Exception(
                "La clé API OpenAI n'est pas configurée. Ajoutez OPENAI_API_KEY dans votre fichier .env."
            );
        }
    }

    private function preparerImage(UploadedFile $fichier): string
    {
        $path = $fichier->getRealPath();
        $extension = strtolower($fichier->getClientOriginalExtension());

        if ($extension === 'heic') {
            return 'data:image/heic;base64,' . base64_encode(file_get_contents($path));
        }

        $image = match ($extension) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($path),
            'png' => @imagecreatefrompng($path),
            'webp' => @imagecreatefromwebp($path),
            default => false,
        };

        if (!$image) {
            return 'data:image/' . $extension . ';base64,' . base64_encode(file_get_contents($path));
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $maxWidth = 1600;

        if ($width > $maxWidth) {
            $ratio = $maxWidth / $width;
            $newWidth = $maxWidth;
            $newHeight = (int) ($height * $ratio);

            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }

        ob_start();
        imagejpeg($image, null, 85);
        $jpegData = ob_get_clean();
        imagedestroy($image);

        return 'data:image/jpeg;base64,' . base64_encode($jpegData);
    }

    private function appelerOpenAI(string $imageBase64): array
    {
        $apiKey = config('services.openai.api_key');
        $timeout = config('services.openai.timeout', 60);

        $response = Http::withToken($apiKey)
            ->timeout($timeout)
            ->acceptJson()
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => self::MODEL,
                'response_format' => ['type' => 'json_object'],
                'max_tokens' => 4000,
                'temperature' => 0.1,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => self::SYSTEM_PROMPT,
                    ],
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => 'Extrais la liste complète des élèves de cette photo au format JSON demandé.',
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => $imageBase64,
                                    'detail' => 'high',
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

        if (!$response->successful()) {
            $errorData = $response->json();
            $errorMsg = $errorData['error']['message'] ?? 'Erreur inconnue';

            Log::error('OCR OpenAI error', [
                'status' => $response->status(),
                'body' => $errorData,
            ]);

            if ($response->status() === 401) {
                throw new \Exception("Clé API OpenAI invalide ou expirée.");
            }

            if ($response->status() === 429) {
                throw new \Exception("Trop de requêtes vers OpenAI. Réessayez dans quelques secondes.");
            }

            if ($response->status() === 400 && str_contains($errorMsg, 'image')) {
                throw new \Exception("L'image n'a pas pu être analysée.");
            }

            throw new \Exception("Échec de l'analyse OCR : " . $errorMsg);
        }

        return $response->json();
    }

    private function parserReponse(array $reponse): array
    {
        $contenu = $reponse['choices'][0]['message']['content'] ?? null;

        if (empty($contenu)) {
            throw new \Exception("Réponse vide de l'API OCR.");
        }

        $donnees = json_decode($contenu, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('OCR JSON parse error', [
                'error' => json_last_error_msg(),
                'content' => $contenu,
            ]);

            throw new \Exception("La réponse OCR n'est pas un JSON valide.");
        }

        if (!isset($donnees['eleves']) || !is_array($donnees['eleves'])) {
            throw new \Exception("Format de réponse OCR inattendu.");
        }

        return $donnees;
    }

    private function estimerCout(array $reponse): float
    {
        $usage = $reponse['usage'] ?? [];
        $inputTokens = $usage['prompt_tokens'] ?? 0;
        $outputTokens = $usage['completion_tokens'] ?? 0;

        $coutInput = ($inputTokens / 1000) * 0.005;
        $coutOutput = ($outputTokens / 1000) * 0.015;

        return round($coutInput + $coutOutput, 4);
    }
}