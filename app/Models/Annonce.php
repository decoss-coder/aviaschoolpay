<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Annonce extends Model
{
    protected $table = 'annonces';

    protected $fillable = [
        'etablissement_id', 'auteur_id', 'titre', 'contenu', 'type', 'audience',
        'piece_jointe_path', 'date_debut_affichage', 'date_fin_affichage',
        'envoyer_sms', 'envoyer_notification', 'publiee',
    ];

    protected $casts = [
        'date_debut_affichage' => 'date',
        'date_fin_affichage'   => 'date',
        'envoyer_sms'          => 'boolean',
        'envoyer_notification' => 'boolean',
        'publiee'              => 'boolean',
    ];

    public function etablissement(): BelongsTo { return $this->belongsTo(Etablissement::class); }
    public function auteur(): BelongsTo { return $this->belongsTo(User::class, 'auteur_id'); }
}
