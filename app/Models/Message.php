<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'etablissement_id', 'expediteur_id', 'destinataire_id', 'classe_id',
        'type_destinataire', 'sujet', 'contenu', 'piece_jointe_path', 'lu', 'lu_at', 'important',
    ];
    protected $casts = ['lu' => 'boolean', 'lu_at' => 'datetime', 'important' => 'boolean'];

    public function expediteur(): BelongsTo { return $this->belongsTo(User::class, 'expediteur_id'); }
    public function destinataire(): BelongsTo { return $this->belongsTo(User::class, 'destinataire_id'); }
}
