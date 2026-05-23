<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany, HasMany};

class ParentTuteur extends Model
{
    protected $table = 'parents_tuteurs';
    protected $fillable = [
        'user_id', 'etablissement_id', 'nom', 'prenom', 'sexe',
        'telephone', 'telephone_2', 'email', 'adresse', 'profession', 'lien_parente', 'actif',
    ];
    protected $casts = ['actif' => 'boolean'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function etablissement(): BelongsTo { return $this->belongsTo(Etablissement::class); }
    public function eleves(): BelongsToMany { return $this->belongsToMany(Eleve::class, 'eleve_parent', 'parent_id', 'eleve_id')->withPivot('est_contact_principal', 'autorise_recuperation'); }
    public function getNomCompletAttribute(): string { return "{$this->prenom} {$this->nom}"; }
}
