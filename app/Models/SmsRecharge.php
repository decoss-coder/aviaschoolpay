<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsRecharge extends Model
{
    protected $table = 'sms_recharges';

    protected $fillable = [
        'etablissement_id', 'demandeur_id', 'reference', 'nb_sms', 'montant_fcfa',
        'prix_unitaire_fcfa', 'wave_checkout_url', 'statut',
        'paye_at', 'credite_at', 'credite_par', 'notes_admin',
    ];

    protected $casts = [
        'paye_at'    => 'datetime',
        'credite_at' => 'datetime',
    ];

    public function etablissement(): BelongsTo { return $this->belongsTo(Etablissement::class); }
    public function demandeur(): BelongsTo { return $this->belongsTo(User::class, 'demandeur_id'); }
    public function crediteParUser(): BelongsTo { return $this->belongsTo(User::class, 'credite_par'); }

    public static function genererReference(): string
    {
        return 'SMS-'.now()->format('Ymd').'-'.strtoupper(substr(uniqid(), -6));
    }
}
