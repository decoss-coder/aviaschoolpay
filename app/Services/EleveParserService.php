<?php

namespace App\Services;

use App\Models\Eleve;
use App\Models\Classe;

/**
 * Service central de normalisation et validation des données d'élèves.
 *
 * Utilisé par TOUS les modes d'import :
 * - ExcelImportService
 * - PdfImportService
 * - OcrImportService
 * - SaisieRapideService
 *
 * Reçoit un tableau de données brutes, retourne :
 * - Les lignes normalisées et prêtes à être enregistrées
 * - Les lignes en erreur avec messages explicites
 *
 * Règles d'extraction nom/prénom :
 *   "NOM PRENOM1 PRENOM2" → nom = NOM, prenoms = PRENOM1 PRENOM2
 *   Premier mot = nom (règle universelle, l'utilisateur corrige les cas ambigus dans le preview).
 */
class EleveParserService
{
    /**
     * Regex matricule DESPS officiel : 8 chiffres + 1 lettre majuscule
     */
    public const REGEX_MATRICULE_DESPS = '/^[0-9]{8}[A-Z]$/';

    /**
     * Normalise un lot de données brutes.
     *
     * @param array $lignesBrutes Tableau de lignes avec clés au moins :
     *                            - 'matricule' (optionnel)
     *                            - 'nom_complet' (nom + prénoms ensemble)
     *                            - 'sexe' (optionnel, variantes acceptées)
     *                            - Autres champs optionnels
     * @param int $etablissementId
     * @return array [
     *   'valides' => [...lignes normalisées],
     *   'erreurs' => [...avec message explicite],
     *   'stats' => ['total', 'valides', 'erreurs']
     * ]
     */
    public function normaliserLot(array $lignesBrutes, int $etablissementId): array
    {
        $valides = [];
        $erreurs = [];
        $matriculesVus = [];

        foreach ($lignesBrutes as $index => $ligne) {
            $numeroLigne = $index + 1;

            try {
                $normalisee = $this->normaliserLigne($ligne, $etablissementId);

                // Détection des doublons DESPS uniquement si le matricule DESPS est officiel/valide.
                // Si le matricule est absent ou mal lu par l'IA/OCR, la ligne reste importable avec un matricule interne.
                if ($normalisee['matricule_desps']) {
                    if (isset($matriculesVus[$normalisee['matricule_desps']])) {
                        $erreurs[] = $this->formaterErreur($numeroLigne, $ligne,
                            "Matricule DESPS en double dans le fichier (ligne {$matriculesVus[$normalisee['matricule_desps']]})"
                        );
                        continue;
                    }
                    $matriculesVus[$normalisee['matricule_desps']] = $numeroLigne;
                }

                // Vérification d'existence en base uniquement sur matricule DESPS valide.
                if ($normalisee['matricule_desps']) {
                    $existe = Eleve::where('etablissement_id', $etablissementId)
                        ->where('matricule_desps', $normalisee['matricule_desps'])
                        ->exists();
                    if ($existe) {
                        $erreurs[] = $this->formaterErreur($numeroLigne, $ligne,
                            "Un élève avec le matricule DESPS {$normalisee['matricule_desps']} existe déjà dans l'école."
                        );
                        continue;
                    }
                }

                $normalisee['_ligne'] = $numeroLigne;
                $valides[] = $normalisee;

            } catch (\Exception $e) {
                $erreurs[] = $this->formaterErreur($numeroLigne, $ligne, $e->getMessage());
            }
        }

        $valides = $this->attribuerMatriculesInternesPreview($valides, $etablissementId);

        return [
            'valides' => $valides,
            'erreurs' => $erreurs,
            'stats' => [
                'total' => count($lignesBrutes),
                'valides' => count($valides),
                'erreurs' => count($erreurs),
            ],
        ];
    }

    /**
     * Normalise une ligne unique.
     * @throws \Exception si ligne invalide
     */
    public function normaliserLigne(array $ligne, int $etablissementId): array
    {
        // 1. NOM + PRÉNOMS
        $nomComplet = $this->nettoyerTexte($ligne['nom_complet'] ?? '');
        if (empty($nomComplet)) {
            throw new \Exception("Nom et prénoms manquants.");
        }

        [$nom, $prenoms] = $this->separerNomPrenoms($nomComplet);
        if (empty($nom) || empty($prenoms)) {
            throw new \Exception("Impossible de séparer le nom et les prénoms. Format attendu : « NOM Prenom1 Prenom2 ».");
        }

        // 2. MATRICULE DESPS
        // Le DESPS reste officiel seulement s'il respecte exactement 8 chiffres + 1 lettre.
        // S'il est vide, trop court, mal orthographié ou mal lu par l'IA, on ne bloque plus la ligne :
        // on l'importe avec un matricule interne et on affiche la substitution dans le preview.
        $matriculeOriginal = $this->nettoyerMatricule($ligne['matricule'] ?? '');
        $matriculeDespsValide = $matriculeOriginal !== '' && preg_match(self::REGEX_MATRICULE_DESPS, $matriculeOriginal);
        $matriculeDesps = $matriculeDespsValide ? $matriculeOriginal : null;
        $matriculeDespsInvalide = $matriculeOriginal !== '' && !$matriculeDespsValide;

        // 3. SEXE
        // Si l'IA/OCR ne lit pas le sexe, on ne rejette plus la ligne :
        // on laisse la cellule vide pour correction manuelle dans l'aperçu.
        $sexe = $this->normaliserSexe($ligne['sexe'] ?? $ligne['genre'] ?? '');

        // 4. DATE DE NAISSANCE (optionnel)
        $dateNaissance = null;
        if (!empty($ligne['date_naissance'])) {
            $dateNaissance = $this->parserDate($ligne['date_naissance']);
        }

        // 5. RESTE (tout optionnel)
        return [
            'matricule_desps' => $matriculeDesps,
            'matricule_desps_original' => $matriculeOriginal ?: null,
            'matricule_desps_invalide' => $matriculeDespsInvalide,
            'matricule_interne' => null,
            'matricule_interne_auto' => !$matriculeDesps,
            'matricule_remplacement_label' => null,
            'nom' => mb_strtoupper($nom),
            'prenom' => $this->formatterPrenoms($prenoms),
            'sexe' => $sexe ?: null,
            'sexe_a_corriger' => $sexe ? false : true,
            'date_naissance' => $dateNaissance,
            'lieu_naissance' => $this->nettoyerTexte($ligne['lieu_naissance'] ?? null),
            'nationalite' => $this->nettoyerTexte($ligne['nationalite'] ?? 'Ivoirienne'),
            'telephone' => $this->nettoyerTexte($ligne['telephone'] ?? null),
            'adresse' => $this->nettoyerTexte($ligne['adresse'] ?? null),
            'parent_nom' => $this->nettoyerTexte($ligne['parent_nom'] ?? null),
            'parent_telephone' => $this->nettoyerTexte($ligne['parent_telephone'] ?? null),
            'parent_lien' => $this->nettoyerTexte($ligne['parent_lien'] ?? null),
            'ecole_precedente' => $this->nettoyerTexte($ligne['ecole_precedente'] ?? null),
            'etablissement_id' => $etablissementId,
            'statut' => 'pre_inscrit',
            'actif' => true,
        ];
    }

    /**
     * Attribue un matricule interne prévisible aux lignes sans DESPS officiel.
     * Le code est visible dans l'aperçu avant validation puis réutilisé à l'enregistrement.
     */
    public function attribuerMatriculesInternesPreview(array $lignes, int $etablissementId): array
    {
        $base = Eleve::genererMatricule($etablissementId);
        $prefixe = substr($base, 0, -4);
        $numero = (int) substr($base, -4);

        foreach ($lignes as &$ligne) {
            $desps = trim((string) ($ligne['matricule_desps'] ?? ''));
            $interne = trim((string) ($ligne['matricule_interne'] ?? ''));

            if ($desps === '' && $interne === '') {
                $ligne['matricule_interne'] = $prefixe . str_pad((string) $numero, 4, '0', STR_PAD_LEFT);
                $ligne['matricule_interne_auto'] = true;
                $ligne['matricule_remplacement_label'] = 'Remplacé par matricule interne';
                $numero++;
            } elseif ($desps === '') {
                $ligne['matricule_interne_auto'] = true;
                $ligne['matricule_remplacement_label'] = 'Remplacé par matricule interne';
            } else {
                $ligne['matricule_interne_auto'] = (bool) ($ligne['matricule_interne_auto'] ?? false);
                $ligne['matricule_remplacement_label'] = $ligne['matricule_remplacement_label'] ?? null;
            }
        }
        unset($ligne);

        return $lignes;
    }

    /**
     * Sépare "ATTIOUA AMOIN CHANTAL" en ["ATTIOUA", "AMOIN CHANTAL"]
     * Règle : premier mot = nom, reste = prénoms.
     * L'utilisateur peut corriger les cas ambigus (noms composés) dans le preview.
     */
    public function separerNomPrenoms(string $nomComplet): array
    {
        $nomComplet = $this->nettoyerTexte($nomComplet);
        $nomComplet = preg_replace('/\s+/', ' ', trim($nomComplet));

        if (empty($nomComplet)) return ['', ''];

        $parts = explode(' ', $nomComplet);
        if (count($parts) < 2) return [$parts[0], ''];

        $nom = array_shift($parts);
        $prenoms = implode(' ', $parts);

        return [$nom, $prenoms];
    }

    /**
     * Normalise les variantes de sexe en M ou F.
     */
    public function normaliserSexe(?string $valeur): ?string
    {
        if (empty($valeur)) return null;

        $v = mb_strtoupper(trim($valeur));
        $v = rtrim($v, '.');

        $garcons = ['M', 'MASCULIN', 'GARCON', 'GARÇON', 'H', 'HOMME', 'MALE'];
        $filles = ['F', 'FEMININ', 'FÉMININ', 'FILLE', 'FEMME', 'FEMALE'];

        if (in_array($v, $garcons, true)) return 'M';
        if (in_array($v, $filles, true)) return 'F';

        return null;
    }

    /**
     * Nettoie un matricule : supprime espaces, uppercase.
     */
    public function nettoyerMatricule(?string $valeur): string
    {
        if (empty($valeur)) return '';
        return mb_strtoupper(preg_replace('/\s+/', '', trim($valeur)));
    }

    /**
     * Parse une date dans différents formats.
     * @return string|null Date au format Y-m-d ou null si invalide
     */
    public function parserDate(?string $valeur): ?string
    {
        if (empty($valeur)) return null;

        $valeur = trim($valeur);

        // Formats à essayer
        $formats = [
            'd/m/Y', 'd-m-Y', 'd.m.Y',
            'Y-m-d', 'Y/m/d',
            'd/m/y', 'd-m-y',
        ];

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $valeur);
            if ($date && $date->format($format) === $valeur) {
                // Vérification de cohérence (entre 1950 et aujourd'hui)
                $year = (int)$date->format('Y');
                if ($year >= 1950 && $year <= (int)date('Y')) {
                    return $date->format('Y-m-d');
                }
            }
        }

        return null;
    }

    /**
     * Nettoie un champ texte (espaces, caractères invisibles)
     */
    public function nettoyerTexte(?string $valeur): ?string
    {
        if (is_null($valeur)) return null;

        // Retire BOM, caractères de contrôle, espaces multiples
        $valeur = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $valeur);
        $valeur = preg_replace('/\s+/', ' ', trim($valeur));

        return empty($valeur) ? null : $valeur;
    }

    /**
     * Formatte les prénoms : "AMOIN CHANTAL" → "Amoin Chantal"
     */
    public function formatterPrenoms(string $prenoms): string
    {
        return mb_convert_case(mb_strtolower($prenoms), MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Formatte une erreur de façon standardisée pour l'affichage.
     */
    private function formaterErreur(int $numeroLigne, array $ligneBrut, string $message): array
    {
        return [
            'ligne' => $numeroLigne,
            'message' => $message,
            'donnees' => $ligneBrut,
        ];
    }

    /**
     * Génère un template Excel téléchargeable (colonnes en français).
     * Retourne un tableau de headers et quelques lignes d'exemple.
     */
    public function getTemplateStructure(): array
    {
        return [
            'headers' => [
                'matricule' => 'Matricule DESPS',
                'nom_complet' => 'Nom et prénoms',
                'sexe' => 'Sexe',
                'date_naissance' => 'Date de naissance',
                'lieu_naissance' => 'Lieu de naissance',
                'nationalite' => 'Nationalité',
                'parent_nom' => 'Nom du parent/tuteur',
                'parent_telephone' => 'Téléphone parent',
                'parent_lien' => 'Lien (père, mère, tuteur)',
            ],
            'exemples' => [
                [
                    '15195226N', 'ATTIOUA AMOIN CHANTAL', 'F', '15/03/2007',
                    'Abidjan', 'Ivoirienne', 'Attioua Jean', '+225 01 02 03 04 05', 'père',
                ],
                [
                    '17510061U', 'BAGATE DAOUDA', 'M', '20/06/2008',
                    'Kongasso', 'Ivoirienne', 'Bagate Moussa', '+225 06 07 08 09 10', 'père',
                ],
            ],
            'notes' => [
                'Le matricule DESPS est optionnel : s’il est vide ou illisible, AviaSchoolPay attribue un matricule interne.',
                'Le matricule DESPS officiel doit contenir 8 chiffres + 1 lettre, ex : 15195226N.',
                'Si le sexe est absent ou illisible, la cellule reste vide dans l’aperçu pour correction manuelle.',
                'Dans "Nom et prénoms", le premier mot est considéré comme le nom de famille',
                'Pour les noms composés (TRA BI, KEI BI, etc.), vous pourrez corriger dans le preview',
                'Le sexe accepte : M, F, Masculin, Féminin, Garçon, Fille',
                'La date accepte : jj/mm/aaaa ou jj-mm-aaaa',
            ],
        ];
    }
}
