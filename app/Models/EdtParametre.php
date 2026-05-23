<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EdtParametre extends Model
{
    // ── Modes de génération ──────────────────────────────────────────
    public const MODE_STRICT_OFFICIEL       = 'strict_officiel';
    public const MODE_PRIVE_EQUILIBRE       = 'prive_equilibre';
    public const MODE_PRIVE_CONTRAINT       = 'prive_contraint';
    public const MODE_PROVISOIRE_VACATAIRES = 'provisoire_vacataires';

    public static function modes(): array
    {
        return [
            self::MODE_STRICT_OFFICIEL       => 'Strict officiel',
            self::MODE_PRIVE_EQUILIBRE       => 'Privé équilibré',
            self::MODE_PRIVE_CONTRAINT       => 'Privé contraint',
            self::MODE_PROVISOIRE_VACATAIRES => 'Provisoire vacataires',
        ];
    }

    // ── Colonnes fillable ────────────────────────────────────────────
    protected $fillable = [
        'etablissement_id',
        'annee_scolaire_id',
        'policy_id',

        // mode
        'mode_generation_defaut',

        // périmètre temps
        'jours_autorises_json',
        'creneaux_autorises_json',
        'salles_autorisees_json',

        // vacataires
        'attendre_horaires_vacataires',
        'bloquer_si_vacataire_sans_horaire',
        'respecter_imports_vacataires',
        'regrouper_heures_vacataires',

        // politique privée
        'autoriser_reduction_heures',
        'max_reduction_minutes_par_classe',
        'max_reduction_minutes_par_matiere',
        'autoriser_matieres_facultatives',

        // priorités
        'prioriser_classes_examen',
        'prioriser_permanents',
        'equilibrer_journees_classes',
        'equilibrer_journees_profs',

        // contraintes pédagogiques
        'respecter_tp_consecutifs',
        'eviter_eps_heures_chaudes',
        'limiter_niveaux_prof',
        'max_niveaux_par_prof',
        'limiter_heures_creuses',
        'max_heures_creuses_prof',

        // tolérances
        'autoriser_trous',
        'tolerer_surcharge_legere',

        // apprentissage
        'activer_apprentissage_ajustements',
        'verrouiller_ajustements_manuels_par_defaut',

        'notes_generation',
        'actif',
        'created_by',
        'updated_by',
    ];

    // ── Casts ────────────────────────────────────────────────────────
    protected $casts = [
        // JSON → tableau PHP automatiquement
        'jours_autorises_json'    => 'array',
        'creneaux_autorises_json' => 'array',
        'salles_autorisees_json'  => 'array',

        // vacataires
        'attendre_horaires_vacataires'      => 'boolean',
        'bloquer_si_vacataire_sans_horaire' => 'boolean',
        'respecter_imports_vacataires'      => 'boolean',
        'regrouper_heures_vacataires'       => 'boolean',

        // politique privée
        'autoriser_reduction_heures'           => 'boolean',
        'max_reduction_minutes_par_classe'     => 'integer',
        'max_reduction_minutes_par_matiere'    => 'integer',
        'autoriser_matieres_facultatives'      => 'boolean',

        // priorités
        'prioriser_classes_examen'   => 'boolean',
        'prioriser_permanents'       => 'boolean',
        'equilibrer_journees_classes'=> 'boolean',
        'equilibrer_journees_profs'  => 'boolean',

        // contraintes pédagogiques
        'respecter_tp_consecutifs'   => 'boolean',
        'eviter_eps_heures_chaudes'  => 'boolean',
        'limiter_niveaux_prof'       => 'boolean',
        'max_niveaux_par_prof'       => 'integer',
        'limiter_heures_creuses'     => 'boolean',
        'max_heures_creuses_prof'    => 'integer',

        // tolérances
        'autoriser_trous'         => 'boolean',
        'tolerer_surcharge_legere'=> 'boolean',

        // apprentissage
        'activer_apprentissage_ajustements'         => 'boolean',
        'verrouiller_ajustements_manuels_par_defaut'=> 'boolean',

        'actif' => 'boolean',
    ];

    // ── Relations ────────────────────────────────────────────────────

    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(EdtPolicy::class, 'policy_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ── Accesseurs pratiques ─────────────────────────────────────────

    /** Jours autorisés (tableau, ou tous les jours EDT par défaut) */
    public function getJoursAutorisesAttribute(): array
    {
        return $this->jours_autorises_json ?? EmploiDuTemps::jours();
    }

    /** IDs des créneaux autorisés (tableau vide = tous) */
    public function getCreneauxAutorisesAttribute(): array
    {
        return $this->creneaux_autorises_json ?? [];
    }

    /** IDs des salles autorisées (tableau vide = toutes) */
    public function getSallesAutorisesAttribute(): array
    {
        return $this->salles_autorisees_json ?? [];
    }

    /** Libellé du mode actif */
    public function getModeLibelleAttribute(): string
    {
        return static::modes()[$this->mode_generation_defaut]
            ?? ucfirst($this->mode_generation_defaut);
    }

    // ── Méthode statique helper ──────────────────────────────────────

    /**
     * Récupère (ou crée par défaut) les paramètres pour un établissement + année.
     */
    public static function pourEtablissement(
        int  $etablissementId,
        ?int $anneeScolaireId = null
    ): static {
        return static::firstOrNew(
            [
                'etablissement_id'  => $etablissementId,
                'annee_scolaire_id' => $anneeScolaireId,
            ],
            [
                'mode_generation_defaut' => self::MODE_PRIVE_EQUILIBRE,
                'actif'                  => true,
            ]
        );
    }

    /**
     * Exporte les paramètres sous forme de tableau plat
     * utilisable directement par EmploiDuTempsIAService.
     */
    public function toServiceParams(): array
    {
        return [
            'mode'                  => $this->mode_generation_defaut,
            'jours'                 => $this->jours_autorises,
            'creneau_ids'           => $this->creneaux_autorises,
            'salle_ids'             => $this->salles_autorises,

            // vacataires
            'attendre_horaires_vacataires'      => $this->attendre_horaires_vacataires,
            'bloquer_si_vacataire_sans_horaire' => $this->bloquer_si_vacataire_sans_horaire,
            'respecter_imports_vacataires'      => $this->respecter_imports_vacataires,
            'regrouper_heures_vacataires'       => $this->regrouper_heures_vacataires,

            // tolérances
            'autoriser_trous'          => $this->autoriser_trous,
            'tolerer_surcharge_legere' => $this->tolerer_surcharge_legere,

            // priorités / contraintes
            'prioriser_classes_examen'  => $this->prioriser_classes_examen,
            'prioriser_permanents'      => $this->prioriser_permanents,
            'equilibrer_journees_classes'=> $this->equilibrer_journees_classes,
            'equilibrer_journees_profs' => $this->equilibrer_journees_profs,
            'max_niveaux_par_prof'      => $this->max_niveaux_par_prof,
            'max_heures_creuses_prof'   => $this->max_heures_creuses_prof,

            // réduction heures
            'autoriser_reduction_heures'        => $this->autoriser_reduction_heures,
            'max_reduction_minutes_par_classe'  => $this->max_reduction_minutes_par_classe,
            'max_reduction_minutes_par_matiere' => $this->max_reduction_minutes_par_matiere,
        ];
    }
}