<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Support\Str;

class QrCode extends Model
{
    protected $fillable = [
        'etablissement_id', 'salle_id', 'code_unique', 'contenu_qr',
        'actif', 'date_impression', 'date_desactivation', 'motif_desactivation',
    ];
    protected $casts = ['actif' => 'boolean', 'date_impression' => 'date', 'date_desactivation' => 'date'];

    public function etablissement(): BelongsTo { return $this->belongsTo(Etablissement::class); }
    public function salle(): BelongsTo { return $this->belongsTo(Salle::class); }
    public function pointages(): HasMany { return $this->hasMany(Pointage::class); }

    public static function genererPourSalle(Salle $salle): self
    {
        $salle->qrCodes()->update(['actif' => false, 'date_desactivation' => now()]);

        $code = hash('sha256', $salle->id . '-' . $salle->etablissement_id . '-' . Str::random(32));
        $contenu = json_encode([
            'app' => 'aviaschoolpay',
            'etab' => $salle->etablissement_id,
            'salle' => $salle->id,
            'code' => $code,
            'v' => 1,
        ]);

        return static::create([
            'etablissement_id' => $salle->etablissement_id,
            'salle_id' => $salle->id,
            'code_unique' => $code,
            'contenu_qr' => $contenu,
            'actif' => true,
        ]);
    }
}
