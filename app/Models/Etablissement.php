<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, HasManyThrough};

class Etablissement extends Model
{
    protected $fillable = [
        'groupe_scolaire_id', 'nom', 'code_desps', 'sigle', 'type', 'statut_juridique',
        'adresse', 'ville', 'commune', 'region', 'drena', 'ddena', 'telephone', 'email',
        'site_web', 'logo_path', 'gps_latitude', 'gps_longitude', 'gps_rayon_metres',
        'directeur_nom', 'directeur_telephone', 'actif', 'systeme_evaluation',
        'wave_actif', 'wave_libelle', 'wave_lien_base', 'wave_configured_at', 'wave_configured_by',
        'paiements_manuels_actifs',
        'sigfne_actif', 'sigfne_login', 'sigfne_token', 'sigfne_plateforme', 'sigfne_derniere_sync',
    ];

    public function nbPeriodes(): int
    {
        return match ($this->systeme_evaluation ?? 'trimestre') {
            'semestre'     => 2,
            'quadrimestre' => 4,
            default        => 3,
        };
    }

    public function labelPeriode(int $numero): string
    {
        return match ($this->systeme_evaluation ?? 'trimestre') {
            'semestre'     => "Semestre {$numero}",
            'quadrimestre' => "Quadrimestre {$numero}",
            default        => "Trimestre {$numero}",
        };
    }

    protected $casts = [
        'gps_latitude' => 'decimal:7', 'gps_longitude' => 'decimal:7', 'actif' => 'boolean',
        'wave_actif' => 'boolean', 'wave_configured_at' => 'datetime',
        'paiements_manuels_actifs' => 'boolean',
        'sigfne_actif' => 'boolean',
        'sigfne_derniere_sync' => 'datetime',
    ];

    public function groupe(): BelongsTo { return $this->belongsTo(GroupeScolaire::class, 'groupe_scolaire_id'); }
    public function anneesScolaires(): HasMany { return $this->hasMany(AnneeScolaire::class); }
    public function anneEnCours(): HasMany { return $this->hasMany(AnneeScolaire::class)->where('en_cours', true); }
    public function users(): HasMany { return $this->hasMany(User::class); }
    public function niveaux(): HasMany { return $this->hasMany(Niveau::class); }
    public function classes(): HasMany { return $this->hasMany(Classe::class); }
    public function salles(): HasMany { return $this->hasMany(Salle::class); }
    public function matieres(): HasMany { return $this->hasMany(Matiere::class); }
    public function eleves(): HasMany { return $this->hasMany(Eleve::class); }
    public function enseignants(): HasMany { return $this->hasMany(Enseignant::class); }
    public function paiements(): HasMany { return $this->hasMany(Paiement::class); }
    public function pointages(): HasMany { return $this->hasMany(Pointage::class); }
    public function qrCodes(): HasMany { return $this->hasMany(QrCode::class); }
    public function parametres(): HasMany { return $this->hasMany(Parametre::class); }

    public function getParametre(string $cle, $default = null)
    {
        $param = $this->parametres()->where('cle', $cle)->first();
        return $param ? $param->valeur : $default;
    }

    public function scopeActif($query) { return $query->where('actif', true); }
}
