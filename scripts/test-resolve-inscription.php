<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$eleve = App\Models\Eleve::find(1);
$etab = $eleve->etablissement;
$annee = App\Services\Finance\PaiementService::resolveAnneeCourante($etab);

// Supprime inscription test si besoin de re-tester create
$existing = App\Models\Inscription::where('eleve_id', 1)->where('annee_scolaire_id', $annee->id)->first();
if ($existing) {
    echo "Existing inscription #{$existing->id} montant_scolarite={$existing->montant_scolarite}\n";
} else {
    $insc = App\Services\Finance\PaiementService::resolveInscription($etab, $annee, $eleve);
    echo "Created #{$insc->id} ins={$insc->montant_inscription} scol={$insc->montant_scolarite} net={$insc->montant_net}\n";
}
