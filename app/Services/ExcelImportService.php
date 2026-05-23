<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Service d'extraction de données depuis un fichier Excel ou CSV.
 *
 * Rôle : lire le fichier brut et retourner un tableau de lignes
 * avec des clés normalisées (matricule, nom_complet, sexe, etc.).
 *
 * La VALIDATION et la NORMALISATION sont ensuite faites par EleveParserService.
 *
 * Fonctionnalités :
 * - Auto-détection de la ligne d'en-tête (peut être à n'importe quel ligne 1-5)
 * - Matching intelligent des colonnes par mots-clés (FR/EN, variantes)
 * - Support .xlsx, .xls, .csv, .ods
 * - Tolérant aux fichiers avec lignes vides, en-têtes multiples, cellules fusionnées
 */
class ExcelImportService
{
    /**
     * Dictionnaire de détection automatique des colonnes.
     * Chaque champ cible a une liste de mots-clés possibles.
     * L'ordre compte : plus spécifique d'abord.
     */
    private const COLUMN_ALIASES = [
        'matricule' => [
            'matricule desps', 'matricule_desps', 'matricule d\'etat', 'matricule etat',
            'n° matricule', 'numero matricule', 'matricule', 'desps', 'matricule officiel',
        ],
        'nom_complet' => [
            'nom et prenom', 'nom et prenoms', 'nom prenom', 'nom prenoms',
            'noms et prenoms', 'nom complet', 'identite', 'identité',
            'eleve', 'élève', 'nom_prenom',
        ],
        'nom' => [
            'nom de famille', 'nom famille', 'nom', 'surname', 'last name',
        ],
        'prenom' => [
            'prénoms', 'prenoms', 'prénom', 'prenom', 'first name', 'given name',
        ],
        'sexe' => [
            'genre', 'sexe', 'sex', 'gender', 'm/f', 'h/f', 'garcon/fille',
        ],
        'date_naissance' => [
            'date de naissance', 'date naissance', 'date_naissance',
            'né(e) le', 'ne le', 'née le', 'birth date', 'dob',
        ],
        'lieu_naissance' => [
            'lieu de naissance', 'lieu naissance', 'lieu_naissance',
            'né(e) à', 'ne a', 'née à', 'birth place',
        ],
        'nationalite' => [
            'nationalité', 'nationalite', 'nationality',
        ],
        'telephone' => [
            'téléphone', 'telephone', 'tel', 'tél', 'portable', 'contact', 'phone',
        ],
        'adresse' => [
            'adresse', 'address', 'domicile', 'résidence',
        ],
        'parent_nom' => [
            'nom du parent', 'parent', 'tuteur', 'nom parent', 'nom tuteur',
            'père/mère', 'responsable',
        ],
        'parent_telephone' => [
            'téléphone parent', 'tel parent', 'contact parent',
            'tél tuteur', 'téléphone tuteur', 'phone parent',
        ],
        'parent_lien' => [
            'lien parent', 'lien de parenté', 'parente', 'parenté', 'relation',
        ],
        'ecole_precedente' => [
            'école précédente', 'ecole precedente', 'ancienne école',
            'établissement d\'origine', 'school',
        ],
    ];

    /**
     * Extensions acceptées
     */
    public const EXTENSIONS_AUTORISEES = ['xlsx', 'xls', 'csv', 'ods'];
    public const TAILLE_MAX_MO = 10;

    /**
     * Lit un fichier et retourne les lignes brutes avec colonnes normalisées.
     *
     * @return array [
     *   'headers_detectes' => ['matricule' => 'Col B', 'nom_complet' => 'Col C', ...],
     *   'colonnes_non_mappees' => ['Remarques', 'Obs'], // Colonnes présentes mais non détectées
     *   'lignes' => [ ['matricule' => '...', 'nom_complet' => '...', ...], ... ],
     *   'meta' => ['total_lignes_brutes', 'ligne_headers', 'format'],
     * ]
     * @throws \Exception
     */
    public function extraire(UploadedFile $fichier): array
    {
        $this->validerFichier($fichier);

        $extension = strtolower($fichier->getClientOriginalExtension());

        // Extraction selon format
        if ($extension === 'csv') {
            $donnees = $this->extraireCSV($fichier);
        } else {
            $donnees = $this->extraireExcel($fichier);
        }

        if (empty($donnees) || count($donnees) < 2) {
            throw new \Exception("Le fichier semble vide ou ne contient pas de données exploitables.");
        }

        // Détection de la ligne d'en-tête (cherche dans les 5 premières lignes)
        $ligneHeaders = $this->detecterLigneHeaders($donnees);
        if ($ligneHeaders === null) {
            throw new \Exception(
                "Impossible de détecter les colonnes. Vérifiez que votre fichier contient une ligne d'en-tête "
                . "avec les libellés Matricule, Nom, Prénoms, Sexe... (ou téléchargez notre modèle)."
            );
        }

        $headers = $donnees[$ligneHeaders];
        $mapping = $this->mapperColonnes($headers);

        // Vérification qu'on a au moins Nom+Prénom (ou Nom complet) et Sexe
        $aIdentite = isset($mapping['nom_complet']) || (isset($mapping['nom']) && isset($mapping['prenom']));
        if (!$aIdentite) {
            throw new \Exception(
                "Impossible de trouver la colonne des noms. Votre fichier doit contenir une colonne "
                . "« Nom et Prénoms » ou deux colonnes séparées « Nom » et « Prénoms »."
            );
        }

        // Extraction des lignes de données
        $lignesBrutes = [];
        for ($i = $ligneHeaders + 1; $i < count($donnees); $i++) {
            $ligne = $donnees[$i];

            // Ignorer les lignes entièrement vides
            if ($this->ligneEstVide($ligne)) continue;

            $ligneNormalisee = $this->extraireLigne($ligne, $mapping);

            // Ignorer si aucune donnée identifiante
            if (empty($ligneNormalisee['nom_complet']) && empty($ligneNormalisee['nom'])) continue;

            $lignesBrutes[] = $ligneNormalisee;
        }

        // Colonnes présentes mais non mappées (pour info dans le preview)
        $indexesMappes = array_values($mapping);
        $colonnesNonMappees = [];
        foreach ($headers as $idx => $header) {
            if (!in_array($idx, $indexesMappes) && !empty(trim((string)$header))) {
                $colonnesNonMappees[] = trim((string)$header);
            }
        }

        return [
            'headers_detectes' => $mapping,
            'colonnes_non_mappees' => $colonnesNonMappees,
            'lignes' => $lignesBrutes,
            'meta' => [
                'total_lignes_brutes' => count($lignesBrutes),
                'ligne_headers' => $ligneHeaders + 1, // 1-indexé pour affichage
                'format' => $extension,
                'nom_fichier' => $fichier->getClientOriginalName(),
            ],
        ];
    }

    /**
     * Valide l'upload (taille, extension, lisibilité)
     */
    private function validerFichier(UploadedFile $fichier): void
    {
        if (!$fichier->isValid()) {
            throw new \Exception("Le fichier n'a pas pu être uploadé correctement.");
        }

        $extension = strtolower($fichier->getClientOriginalExtension());
        if (!in_array($extension, self::EXTENSIONS_AUTORISEES)) {
            throw new \Exception(
                "Format de fichier non supporté (« .{$extension} »). Formats acceptés : "
                . implode(', ', self::EXTENSIONS_AUTORISEES)
            );
        }

        $tailleMo = $fichier->getSize() / 1024 / 1024;
        if ($tailleMo > self::TAILLE_MAX_MO) {
            throw new \Exception(
                "Fichier trop volumineux (" . round($tailleMo, 1) . " Mo). "
                . "Taille maximale : " . self::TAILLE_MAX_MO . " Mo."
            );
        }
    }

    /**
     * Extraction CSV avec détection du séparateur
     */
    private function extraireCSV(UploadedFile $fichier): array
    {
        $contenu = file_get_contents($fichier->getRealPath());

        // Retirer BOM UTF-8 si présent
        $contenu = preg_replace('/^\xEF\xBB\xBF/', '', $contenu);

        // Détection du séparateur (; ou , ou tab)
        $premiereLigne = strtok($contenu, "\n");
        $separateur = ',';
        if (substr_count($premiereLigne, ';') > substr_count($premiereLigne, ',')) {
            $separateur = ';';
        }
        if (substr_count($premiereLigne, "\t") > substr_count($premiereLigne, $separateur)) {
            $separateur = "\t";
        }

        $lignes = [];
        $handle = fopen('data://text/plain;base64,' . base64_encode($contenu), 'r');
        while (($row = fgetcsv($handle, 0, $separateur)) !== false) {
            $lignes[] = array_map(fn($c) => trim((string)$c), $row);
        }
        fclose($handle);

        return $lignes;
    }

    /**
     * Extraction Excel (.xlsx, .xls, .ods) via PhpSpreadsheet
     */
    private function extraireExcel(UploadedFile $fichier): array
    {
        if (!class_exists('\PhpOffice\PhpSpreadsheet\IOFactory')) {
            throw new \Exception(
                "La librairie PhpSpreadsheet n'est pas installée. "
                . "Lancez : composer require phpoffice/phpspreadsheet"
            );
        }

        try {
            $spreadsheet = IOFactory::load($fichier->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, false);

            // Nettoyage : trim, convert en string
            return array_map(fn($row) => array_map(
                fn($cell) => is_null($cell) ? '' : trim((string)$cell),
                $row
            ), $rows);

        } catch (\Exception $e) {
            throw new \Exception(
                "Impossible de lire le fichier Excel. "
                . "Vérifiez qu'il n'est pas protégé par mot de passe ou corrompu. ({$e->getMessage()})"
            );
        }
    }

    /**
     * Cherche la ligne d'en-tête dans les 5 premières lignes.
     * Une ligne est considérée comme en-tête si elle contient au moins 2 mots-clés connus.
     */
    private function detecterLigneHeaders(array $donnees): ?int
    {
        $maxScan = min(5, count($donnees));

        for ($i = 0; $i < $maxScan; $i++) {
            $mapping = $this->mapperColonnes($donnees[$i]);
            if (count($mapping) >= 2) {
                return $i;
            }
        }

        return null;
    }

    /**
     * Mappe les colonnes du fichier vers nos champs cibles.
     * Retourne ['champ_cible' => index_colonne]
     */
    private function mapperColonnes(array $headers): array
    {
        $mapping = [];

        foreach ($headers as $idx => $header) {
            $headerNormalise = $this->normaliserEntete($header);
            if (empty($headerNormalise)) continue;

            // Pour chaque champ cible, chercher une correspondance
            foreach (self::COLUMN_ALIASES as $champ => $aliases) {
                if (isset($mapping[$champ])) continue; // Déjà mappé

                foreach ($aliases as $alias) {
                    if ($headerNormalise === $alias ||
                        str_contains($headerNormalise, $alias)) {
                        $mapping[$champ] = $idx;
                        break 2; // Sortir des 2 boucles
                    }
                }
            }
        }

        return $mapping;
    }

    /**
     * Normalise un libellé d'en-tête pour le matching (lowercase, sans accents, sans espaces multiples)
     */
    private function normaliserEntete(string $header): string
    {
        $header = mb_strtolower(trim($header), 'UTF-8');

        // Remplacer accents
        $header = strtr($header, [
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'à' => 'a', 'â' => 'a', 'ä' => 'a',
            'î' => 'i', 'ï' => 'i',
            'ô' => 'o', 'ö' => 'o',
            'û' => 'u', 'ü' => 'u', 'ù' => 'u',
            'ç' => 'c',
        ]);

        // Espaces multiples en un seul
        $header = preg_replace('/\s+/', ' ', $header);

        return trim($header);
    }

    /**
     * Extrait les valeurs d'une ligne selon le mapping
     */
    private function extraireLigne(array $ligne, array $mapping): array
    {
        $resultat = [];

        foreach ($mapping as $champ => $idx) {
            $resultat[$champ] = isset($ligne[$idx]) ? trim((string)$ligne[$idx]) : '';
        }

        // Cas spécial : si "nom" et "prenom" séparés, on reconstitue "nom_complet"
        if (!isset($resultat['nom_complet']) && isset($resultat['nom']) && isset($resultat['prenom'])) {
            $resultat['nom_complet'] = trim($resultat['nom'] . ' ' . $resultat['prenom']);
        }

        return $resultat;
    }

    /**
     * Vrai si tous les champs de la ligne sont vides
     */
    private function ligneEstVide(array $ligne): bool
    {
        foreach ($ligne as $cell) {
            if (!empty(trim((string)$cell))) return false;
        }
        return true;
    }
}