<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VirementInterne extends Model
{
    protected $table = 'virements_internes';

    protected $fillable = [
        'etablissement_id', 'compte_source_id', 'compte_destination_id',
        'montant', 'date_virement', 'motif', 'effectue_par',
    ];

    protected $casts = ['date_virement' => 'date'];

    public function compteSource(): BelongsTo { return $this->belongsTo(CompteTresorerie::class, 'compte_source_id'); }
    public function compteDestination(): BelongsTo { return $this->belongsTo(CompteTresorerie::class, 'compte_destination_id'); }
    public function effectuePar(): BelongsTo { return $this->belongsTo(User::class, 'effectue_par'); }
}
