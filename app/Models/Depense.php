<?php

// ══════════════════════════════════════════════════════════════
// app/Models/Depense.php — MODULE 13
// ══════════════════════════════════════════════════════════════
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\{BelongsTo};

class Depense extends Model
{
    use SoftDeletes;
    protected $table = 'depenses';
    protected $fillable = [
        'etablissement_id', 'exercice_id', 'categorie_id', 'reference', 'libelle', 'description',
        'montant', 'date_depense', 'mode_paiement', 'beneficiaire', 'numero_facture',
        'justificatif_path', 'frequence', 'statut', 'soumise_par', 'approuvee_par',
        'date_approbation', 'motif_rejet', 'ecriture_id', 'comptabilisee', 'observations',
    ];
    protected $casts = ['date_depense' => 'date', 'date_approbation' => 'datetime', 'comptabilisee' => 'boolean'];

    public function etablissement(): BelongsTo { return $this->belongsTo(Etablissement::class); }
    public function categorie(): BelongsTo { return $this->belongsTo(CategorieDepense::class, 'categorie_id'); }
    public function soumisePar(): BelongsTo { return $this->belongsTo(User::class, 'soumise_par'); }
    public function approuveePar(): BelongsTo { return $this->belongsTo(User::class, 'approuvee_par'); }
    public function exercice(): BelongsTo { return $this->belongsTo(ExerciceComptable::class, 'exercice_id'); }
    public function ecriture(): BelongsTo { return $this->belongsTo(EcritureComptable::class, 'ecriture_id'); }

    public static function genererReference(int $etabId): string
    {
        $count = static::where('etablissement_id', $etabId)->whereMonth('date_depense', now()->month)->count();
        return sprintf('DEP-%s-%04d', now()->format('Y-m'), $count + 1);
    }

    public function scopeApprouvees($q) { return $q->where('statut', 'approuvee'); }
    public function scopeEnAttente($q) { return $q->where('statut', 'soumise'); }
    public function scopeMois($q, string $mois) { return $q->where('date_depense', 'like', "$mois%"); }
}
