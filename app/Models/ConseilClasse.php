<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConseilClasse extends Model
{
    protected $table = 'conseils_classe';

    protected $fillable = [
        'etablissement_id', 'classe_id', 'trimestre_id',
        'date_conseil', 'heure_debut', 'heure_fin', 'lieu',
        'ordre_du_jour', 'participants', 'statut', 'cree_par',
    ];

    protected $casts = [
        'date_conseil' => 'date',
    ];

    public function etablissement(): BelongsTo { return $this->belongsTo(Etablissement::class); }
    public function classe(): BelongsTo { return $this->belongsTo(Classe::class); }
    public function trimestre(): BelongsTo { return $this->belongsTo(Trimestre::class); }
    public function creePar(): BelongsTo { return $this->belongsTo(User::class, 'cree_par'); }
}
