<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsEnvoi extends Model
{
    protected $table = 'sms_envois';

    protected $fillable = [
        'etablissement_id', 'envoye_par', 'destinataire', 'destinataire_nom',
        'contenu', 'type', 'statut', 'infobip_message_id', 'infobip_response',
        'erreur', 'nb_parties', 'reference_type', 'reference_id', 'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function etablissement(): BelongsTo { return $this->belongsTo(Etablissement::class); }
    public function envoyePar(): BelongsTo { return $this->belongsTo(User::class, 'envoye_par'); }
}
