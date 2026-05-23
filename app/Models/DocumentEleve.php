<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentEleve extends Model
{
    protected $table = 'documents_eleves';
    protected $fillable = ['eleve_id', 'type', 'nom_fichier', 'chemin_fichier', 'taille_octets', 'mime_type', 'verifie'];
    protected $casts = ['verifie' => 'boolean'];

    public function eleve(): BelongsTo { return $this->belongsTo(Eleve::class); }
}
