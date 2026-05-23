<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CodePinJournalier extends Model
{
    protected $table = 'codes_pin_journaliers';
    protected $fillable = ['etablissement_id', 'date', 'code_pin', 'heure_generation', 'heure_expiration', 'envoye_sms'];
    protected $casts = ['date' => 'date', 'envoye_sms' => 'boolean'];

    public function etablissement(): BelongsTo { return $this->belongsTo(Etablissement::class); }
}
