<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Serie extends Model
{
    protected $fillable = ['code', 'libelle'];
    public function classes(): HasMany { return $this->hasMany(Classe::class); }
}
