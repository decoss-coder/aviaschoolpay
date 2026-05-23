<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Paiement extends Model
{
    protected $fillable = [
        'etablissement_id', 'inscription_id', 'eleve_id', 'echeance_id', 'encaisse_par',
        'reference', 'reference_transaction', 'montant', 'montant_inscription', 'montant_scolarite',
        'date_paiement', 'date_validation', 'mode', 'poste_cible', 'canal_paiement', 'statut',
        'paydunya_token', 'paydunya_invoice_url', 'wave_checkout_url', 'paydunya_response_code',
        'paydunya_response_text', 'paydunya_metadata', 'paydunya_callback_at',
        'numero_recu', 'recu_pdf_path', 'recu_envoye_sms', 'observations', 'motif_annulation',
    ];
    protected $casts = [
        'date_paiement' => 'date',
        'date_validation' => 'datetime',
        'paydunya_metadata' => 'json',
        'paydunya_callback_at' => 'datetime',
        'recu_envoye_sms' => 'boolean',
    ];

    public function etablissement(): BelongsTo { return $this->belongsTo(Etablissement::class); }
    public function inscription(): BelongsTo { return $this->belongsTo(Inscription::class); }
    public function eleve(): BelongsTo { return $this->belongsTo(Eleve::class); }
    public function echeance(): BelongsTo { return $this->belongsTo(Echeance::class); }
    public function encaissePar(): BelongsTo { return $this->belongsTo(User::class, 'encaisse_par'); }

    public function estConfirme(): bool { return $this->statut === 'confirme'; }
    public function estMobileMoney(): bool { return in_array($this->mode, ['orange_money', 'mtn_money', 'moov_money', 'wave']); }

    public static function genererReference(int $etablissementId): string
    {
        $prefix = strtoupper(substr(md5($etablissementId), 0, 3));
        return sprintf('PAY-%s-%s-%04d', $prefix, now()->format('Ymd'), rand(1, 9999));
    }

    public function scopeConfirmes($query) { return $query->where('statut', 'confirme'); }

    public function scopePourAnnee($query, int $anneeScolaireId)
    {
        return $query->whereHas('inscription', fn ($q) => $q->where('annee_scolaire_id', $anneeScolaireId));
    }

    public function scopeCanal($query, string $canal)
    {
        return $query->where('canal_paiement', $canal);
    }

    public function libellePoste(): string
    {
        return match ($this->poste_cible) {
            'inscription' => 'Inscription',
            'scolarite' => 'Scolarité',
            default => 'Mixte',
        };
    }
}
