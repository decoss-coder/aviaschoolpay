<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsCredit extends Model
{
    protected $table = 'sms_credits';

    protected $fillable = [
        'etablissement_id', 'solde', 'cumul_recharge', 'cumul_envoye', 'cumul_paye_fcfa',
    ];

    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class);
    }
}
