<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroupeScolaire extends Model
{
    protected $table = 'groupes_scolaires';
    protected $fillable = ['nom', 'sigle', 'adresse', 'telephone', 'email', 'logo_path', 'actif'];
    protected $casts = ['actif' => 'boolean'];

    public function etablissements(): HasMany { return $this->hasMany(Etablissement::class); }
}
