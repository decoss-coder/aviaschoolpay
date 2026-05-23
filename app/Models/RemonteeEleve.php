<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RemonteeEleve extends Model
{
    protected $table = 'remontee_eleves';
    protected $fillable = ['remontee_sigfne_id', 'eleve_id', 'matricule_desps', 'moyenne_remontee', 'statut', 'message_erreur'];

    public function remonteeSigfne(): BelongsTo { return $this->belongsTo(RemonteeSigfne::class); }
    public function eleve(): BelongsTo { return $this->belongsTo(Eleve::class); }
}
