<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class PdfImportService
{
    public const EXTENSIONS_AUTORISEES = ['pdf'];
    public const TAILLE_MAX_MO = 15;
    public const SEUIL_TEXTE_MINIMUM = 80;

    private const REGEX_LIGNE_ELEVE = '/^\s*(\d{1,3})\s+(\d{8}[A-Z])\s+(.+?)\s+([MF])(?:\s+.*)?$/u';
    private const REGEX_LIGNE_ELEVE_SANS_NUMERO = '/^\s*(\d{8}[A-Z])\s+(.+?)\s+([MF])(?:\s+.*)?$/u';
    private const REGEX_LIGNE_SANS_MATRICULE = '/^\s*(\d{1,3})\s+(.+?)\s+([MF])(?:\s+.*)?$/u';

    public function estDisponible(): bool
    {
        return class_exists(\Smalot\PdfParser\Parser::class) || $this->pdftotextDisponible();
    }

    public function diagnosticDisponibilite(): array
    {
        return [
            'smalot' => class_exists(\Smalot\PdfParser\Parser::class),
            'pdftotext' => $this->pdftotextDisponible(),
        ];
    }

    public function extraire(UploadedFile $fichier): array
    {
        $this->validerFichier($fichier);

        if (!$this->estDisponible()) {
            throw new \Exception(
                "L’import PDF n’est pas encore activé sur ce serveur. "
                . "Installez le package smalot/pdfparser ou activez pdftotext."
            );
        }

        $texteBrut = $this->extraireTexte($fichier);
        $texteBrut = $this->normaliserTexte($texteBrut);

        if (mb_strlen(trim($texteBrut)) < self::SEUIL_TEXTE_MINIMUM) {
            throw new \Exception(
                "Ce PDF semble être un document scanné ou non lisible en texte. "
                . "Utilisez plutôt l'import par photo / OCR."
            );
        }

        $meta = $this->extraireMetadonnees($texteBrut);
        $meta['nom_fichier'] = $fichier->getClientOriginalName();
        $meta['mode'] = 'texte';

        $lignes = $this->extraireLignesEleves($texteBrut);

        if (empty($lignes)) {
            Log::warning('[Import PDF] Aucune ligne détectée', [
                'fichier' => $fichier->getClientOriginalName(),
                'extrait' => mb_substr($texteBrut, 0, 1500),
            ]);

            throw new \Exception(
                "Aucune ligne d'élève détectée dans le PDF. "
                . "Vérifiez que le document contient bien des lignes du type : "
                . "N°, matricule, nom et prénoms, sexe."
            );
        }

        return [
            'lignes' => $lignes,
            'meta' => $meta,
            'texte_brut' => $texteBrut,
        ];
    }

    private function validerFichier(UploadedFile $fichier): void
    {
        if (!$fichier->isValid()) {
            throw new \Exception("Le fichier n'a pas pu être uploadé correctement.");
        }

        $extension = strtolower($fichier->getClientOriginalExtension());
        if ($extension !== 'pdf') {
            throw new \Exception("Format invalide. Seuls les fichiers PDF sont acceptés.");
        }

        $tailleMo = $fichier->getSize() / 1024 / 1024;
        if ($tailleMo > self::TAILLE_MAX_MO) {
            throw new \Exception(
                "Fichier trop volumineux (" . round($tailleMo, 1) . " Mo). "
                . "Taille maximale : " . self::TAILLE_MAX_MO . " Mo."
            );
        }
    }

    private function extraireTexte(UploadedFile $fichier): string
    {
        if (class_exists(\Smalot\PdfParser\Parser::class)) {
            try {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($fichier->getRealPath());

                return $pdf->getText();
            } catch (\Throwable $e) {
                Log::warning('[Import PDF] Smalot a échoué', [
                    'erreur' => $e->getMessage(),
                ]);
            }
        }

        if ($this->pdftotextDisponible()) {
            $tmpFile = tempnam(sys_get_temp_dir(), 'pdf_extract_') . '.txt';

            $cmd = sprintf(
                'pdftotext -layout %s %s 2>&1',
                escapeshellarg($fichier->getRealPath()),
                escapeshellarg($tmpFile)
            );

            exec($cmd, $output, $code);

            if ($code === 0 && file_exists($tmpFile)) {
                $texte = file_get_contents($tmpFile) ?: '';
                @unlink($tmpFile);

                return $texte;
            }

            Log::warning('[Import PDF] pdftotext a échoué', [
                'code' => $code,
                'output' => $output,
            ]);
        }

        throw new \Exception(
            "Aucune librairie d'extraction PDF disponible sur le serveur. "
            . "Installez smalot/pdfparser ou activez pdftotext."
        );
    }

    private function pdftotextDisponible(): bool
    {
        $output = null;
        $code = null;
        @exec('which pdftotext 2>/dev/null', $output, $code);

        return $code === 0 && !empty($output);
    }

    private function normaliserTexte(string $texte): string
    {
        $texte = str_replace(["\r\n", "\r"], "\n", $texte);
        $texte = preg_replace('/[ \t]+/u', ' ', $texte) ?? $texte;
        $texte = preg_replace('/[ ]{2,}/u', ' ', $texte) ?? $texte;
        $texte = preg_replace('/\n{3,}/u', "\n\n", $texte) ?? $texte;

        return trim($texte);
    }

    private function extraireMetadonnees(string $texte): array
    {
        $meta = [
            'etablissement_detecte' => null,
            'classe_detectee' => null,
            'annee_detectee' => null,
        ];

        if (preg_match(
            '/(COLLEGE|COLLÈGE|LYCEE|LYCÉE|ECOLE|ÉCOLE|GROUPE SCOLAIRE|INSTITUT|COMPLEXE|CENTRE EDUCATIF)\s+[A-ZÀ-Ÿ0-9\s\-\']+/iu',
            $texte,
            $matches
        )) {
            $meta['etablissement_detecte'] = trim($matches[0]);
        }

        if (preg_match('/\b(CLASSE|NIVEAU)\s*:?\s*([A-Z0-9ÈÉÊÀ\- ]{2,20})/iu', $texte, $matches)) {
            $meta['classe_detectee'] = trim($matches[2]);
        } elseif (preg_match('/\b(CM2|CM1|CE2|CE1|CP2|CP1|PS|MS|GS|6EME|5EME|4EME|3EME|2NDE|1ERE|TLE|TERMINALE)\b(?:\s*[A-Z0-9]+)?/iu', $texte, $matches)) {
            $meta['classe_detectee'] = trim($matches[0]);
        }

        if (preg_match('/(\d{4})\s*[-\/]\s*(\d{4})/', $texte, $matches)) {
            $an1 = (int) $matches[1];
            $an2 = (int) $matches[2];

            if ($an2 === $an1 + 1 && $an1 >= 2020 && $an1 <= 2050) {
                $meta['annee_detectee'] = "{$an1}-{$an2}";
            }
        }

        return $meta;
    }

    private function extraireLignesEleves(string $texte): array
    {
        $resultat = [];
        $lignes = preg_split('/\n/u', $texte) ?: [];

        foreach ($lignes as $ligneBrute) {
            $ligne = $this->nettoyerLigne($ligneBrute);

            if ($ligne === '' || $this->estLigneAExclure($ligne)) {
                continue;
            }

            if (preg_match(self::REGEX_LIGNE_ELEVE, $ligne, $m)) {
                $nom = $this->nettoyerNomComplet($m[3]);

                if ($this->nomValide($nom)) {
                    $resultat[] = [
                        'matricule' => trim($m[2]),
                        'nom_complet' => $nom,
                        'sexe' => strtoupper(trim($m[4])),
                    ];
                }
                continue;
            }

            if (preg_match(self::REGEX_LIGNE_ELEVE_SANS_NUMERO, $ligne, $m)) {
                $nom = $this->nettoyerNomComplet($m[2]);

                if ($this->nomValide($nom)) {
                    $resultat[] = [
                        'matricule' => trim($m[1]),
                        'nom_complet' => $nom,
                        'sexe' => strtoupper(trim($m[3])),
                    ];
                }
                continue;
            }

            if (preg_match(self::REGEX_LIGNE_SANS_MATRICULE, $ligne, $m)) {
                $nom = $this->nettoyerNomComplet($m[2]);

                if ($this->nomValide($nom)) {
                    $resultat[] = [
                        'matricule' => '',
                        'nom_complet' => $nom,
                        'sexe' => strtoupper(trim($m[3])),
                    ];
                }
            }
        }

        return $this->dedupliquer($resultat);
    }

    private function nettoyerLigne(string $ligne): string
    {
        $ligne = trim($ligne);
        $ligne = preg_replace('/[ \t]+/u', ' ', $ligne) ?? $ligne;
        $ligne = preg_replace('/\s{2,}/u', ' ', $ligne) ?? $ligne;
        $ligne = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $ligne) ?? $ligne;

        return trim($ligne);
    }

    private function estLigneAExclure(string $ligne): bool
    {
        $upper = mb_strtoupper($ligne);

        foreach (['MATRICULE', 'NOM ET PRENOMS', 'NOM & PRENOMS', 'GENRE', 'SEXE', 'CLASSE', 'ETABLISSEMENT', 'ANNEE SCOLAIRE', 'PAGE ', 'DATE ', 'TOTAL'] as $mot) {
            if (str_contains($upper, $mot)) {
                return true;
            }
        }

        return false;
    }

    private function nettoyerNomComplet(string $nom): string
    {
        $nom = trim($nom);
        $nom = preg_replace('/\s+/', ' ', $nom) ?? $nom;
        $nom = preg_replace('/[^\p{L}\p{N}\s\-\']/u', '', $nom) ?? $nom;

        return trim($nom);
    }

    private function nomValide(string $nom): bool
    {
        if ($nom === '') {
            return false;
        }

        $mots = array_values(array_filter(explode(' ', $nom)));

        return count($mots) >= 2;
    }

    private function dedupliquer(array $lignes): array
    {
        $vus = [];
        $resultat = [];

        foreach ($lignes as $ligne) {
            $cle = ($ligne['matricule'] ?: '') . '|' . mb_strtoupper($ligne['nom_complet']) . '|' . ($ligne['sexe'] ?: '');

            if (isset($vus[$cle])) {
                continue;
            }

            $vus[$cle] = true;
            $resultat[] = $ligne;
        }

        return $resultat;
    }
}