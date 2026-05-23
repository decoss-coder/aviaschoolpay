<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ParentTuteur;
use App\Models\User;
use App\Services\Eleve\EleveInscriptionService;
use App\Services\Finance\ParentScopeService;
use Illuminate\Support\Facades\DB;

$needle = $argv[1] ?? 'Ousman';

echo "=== eleve_parent pivot ===\n";
$pivots = DB::table('eleve_parent')->get();
foreach ($pivots as $row) {
    echo json_encode((array) $row, JSON_UNESCAPED_UNICODE) . "\n";
}

$users = User::where('role', 'parent')
    ->where(function ($q) use ($needle) {
        $q->where('nom', 'like', "%{$needle}%")
            ->orWhere('prenom', 'like', "%{$needle}%")
            ->orWhere('email', 'like', '%' . strtolower($needle) . '%')
            ->orWhere('telephone', 'like', "%{$needle}%");
    })->get();

echo "\n=== USERS parent ({$needle}) ===\n";
foreach ($users as $u) {
    $profils = ParentScopeService::profilsPourUser($u);
    $enfants = ParentScopeService::enfantsPourUser($u);
    echo json_encode([
        'user_id' => $u->id,
        'nom' => $u->nom,
        'prenom' => $u->prenom,
        'telephone' => $u->telephone,
        'tel_norm' => EleveInscriptionService::normalize($u->telephone),
        'profils_count' => $profils->count(),
        'profil_ids' => $profils->pluck('id'),
        'enfants_count' => $enfants->count(),
        'enfant_ids' => $enfants->pluck('id'),
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
}

$parents = ParentTuteur::query()
    ->where('nom', 'like', "%{$needle}%")
    ->orWhere('prenom', 'like', "%{$needle}%")
    ->orWhere('telephone', 'like', "%{$needle}%")
    ->get();

echo "\n=== parents_tuteurs ({$needle}) ===\n";
foreach ($parents as $p) {
    $eleves = $p->eleves()->get(['eleves.id', 'eleves.nom', 'eleves.prenom', 'eleves.actif']);
    echo json_encode([
        'parent_id' => $p->id,
        'user_id' => $p->user_id,
        'nom' => $p->nom,
        'prenom' => $p->prenom,
        'telephone' => $p->telephone,
        'tel_norm' => EleveInscriptionService::normalize($p->telephone),
        'eleves_pivot' => $eleves->map(fn ($e) => [
            'id' => $e->id,
            'nom' => $e->nom,
            'prenom' => $e->prenom,
            'actif' => $e->actif,
        ]),
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
}

// Users with same normalized phone as Ousman
$tel = '0153463635';
$norm = EleveInscriptionService::normalize($tel);
echo "\n=== Users matching phone {$norm} ===\n";
$allParents = User::where('role', 'parent')->get(['id', 'nom', 'prenom', 'telephone']);
foreach ($allParents as $u) {
    if (EleveInscriptionService::normalize($u->telephone) === $norm) {
        echo json_encode(['id' => $u->id, 'nom' => $u->nom, 'prenom' => $u->prenom, 'tel' => $u->telephone], JSON_UNESCAPED_UNICODE) . "\n";
    }
}
