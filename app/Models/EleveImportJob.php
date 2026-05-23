<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EleveImportJob extends Model
{
    protected $table = 'eleves_import_jobs';

    protected $fillable = [
        'etablissement_id', 'user_id', 'classe_cible_id',
        'source', 'fichier_original', 'fichier_path', 'fichier_taille',
        'statut',
        'donnees_brutes', 'donnees_normalisees', 'erreurs', 'metadonnees',
        'total_lignes', 'lignes_valides', 'lignes_erreur', 'lignes_importees',
        'progression', 'message_progression',
        'started_at', 'completed_at', 'niveau_id', 
    ];

    protected $casts = [
        'donnees_brutes' => 'array',
        'donnees_normalisees' => 'array',
        'erreurs' => 'array',
        'metadonnees' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function classeCible(): BelongsTo
    {
        return $this->belongsTo(Classe::class, 'classe_cible_id');
    }

    // Helpers pour l'affichage

    public function getSourceLibelleAttribute(): string
    {
        return match($this->source) {
            'excel' => 'Fichier Excel',
            'csv' => 'Fichier CSV',
            'pdf' => 'Fichier PDF',
            'photo_ocr' => 'Photo (OCR)',
            'saisie_rapide' => 'Saisie rapide',
            default => $this->source,
        };
    }

    public function getStatutLibelleAttribute(): string
    {
        return match($this->statut) {
            'upload' => 'En attente',
            'parsing' => 'Analyse en cours',
            'preview' => 'Prêt à valider',
            'importing' => 'Import en cours',
            'completed' => 'Terminé',
            'failed' => 'Échec',
            'cancelled' => 'Annulé',
            default => $this->statut,
        };
    }

    public function getStatutCouleurAttribute(): string
    {
        return match($this->statut) {
            'upload', 'parsing', 'importing' => 'blue',
            'preview' => 'gold',
            'completed' => 'brand',
            'failed' => 'red',
            'cancelled' => 'gray',
            default => 'gray',
        };
    }

    public function estEnCours(): bool
    {
        return in_array($this->statut, ['upload', 'parsing', 'preview', 'importing']);
    }

    public function estTermine(): bool
    {
        return in_array($this->statut, ['completed', 'failed', 'cancelled']);
    }
}