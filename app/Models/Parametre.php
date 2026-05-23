<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Parametre extends Model
{
    protected $fillable = ['etablissement_id', 'cle', 'valeur', 'type', 'description'];

    public function etablissement(): BelongsTo { return $this->belongsTo(Etablissement::class); }
}
