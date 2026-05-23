<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Etablissement;

class Notification extends Model
{
    protected $fillable = [
        'user_id', 'etablissement_id', 'titre', 'message', 'canal', 'type',
        'lien_action', 'lue', 'lue_at', 'envoyee', 'envoyee_at', 'metadata',
    ];
    protected $casts = ['lue' => 'boolean', 'envoyee' => 'boolean', 'lue_at' => 'datetime', 'envoyee_at' => 'datetime', 'metadata' => 'json'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public function etablissement(): BelongsTo { return $this->belongsTo(Etablissement::class); }

    public function scopeNonLues($query) { return $query->where('lue', false); }

    public function marquerCommeLue(): void
    {
        $this->update(['lue' => true, 'lue_at' => now()]);
    }
}
