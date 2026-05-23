<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\Depense;
use App\Models\Eleve;
use App\Models\Enseignant;
use App\Models\Paiement;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->get('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['results' => [], 'count' => 0]);
        }

        $etab = $request->user()->etablissement_id;
        $like = '%'.$q.'%';
        $results = [];

        // ─── ÉLÈVES (max 8) — uniquement inscrits cette année ───
        $eleves = Eleve::where('etablissement_id', $etab)
            ->inscritsCetteAnnee()
            ->where(function ($w) use ($like) {
                $w->where('nom', 'like', $like)
                  ->orWhere('prenom', 'like', $like)
                  ->orWhere('matricule_interne', 'like', $like)
                  ->orWhere('matricule_desps', 'like', $like);
            })
            ->with('classe:id,nom')
            ->limit(8)->get(['id', 'nom', 'prenom', 'matricule_interne', 'classe_id', 'sexe']);

        foreach ($eleves as $e) {
            $results[] = [
                'type'     => 'eleve',
                'type_label' => '🎓 Élève',
                'titre'    => $e->prenom.' '.strtoupper($e->nom),
                'sous'     => ($e->matricule_interne ?? '—').' · '.($e->classe?->nom ?? '—'),
                'url'      => route('eleves.show', $e->id),
                'icon'     => $e->sexe === 'F' ? '👧' : '👦',
                'couleur'  => 'blue',
            ];
        }

        // ─── ENSEIGNANTS (max 5) — tous les actifs (personnel persistant) ───
        $enseignants = Enseignant::where('etablissement_id', $etab)
            ->where('actif', true)
            ->where(function ($w) use ($like) {
                $w->where('nom', 'like', $like)
                  ->orWhere('prenom', 'like', $like)
                  ->orWhere('matricule_mena', 'like', $like)
                  ->orWhere('telephone', 'like', $like);
            })
            ->limit(5)->get(['id', 'nom', 'prenom', 'matricule_mena', 'statut', 'telephone']);

        foreach ($enseignants as $ens) {
            $results[] = [
                'type'     => 'enseignant',
                'type_label' => '👨‍🏫 Enseignant',
                'titre'    => $ens->prenom.' '.strtoupper($ens->nom),
                'sous'     => ($ens->matricule_mena ?? '—').' · '.ucfirst($ens->statut).' · '.$ens->telephone,
                'url'      => '#', // Page enseignant détail si existe
                'icon'     => '👨‍🏫',
                'couleur'  => 'teal',
            ];
        }

        // ─── CLASSES (max 4) ───
        $classes = Classe::where('etablissement_id', $etab)
            ->where('nom', 'like', $like)
            ->with('niveau:id,libelle')
            ->limit(4)->get(['id', 'nom', 'niveau_id', 'capacite', 'effectif']);

        foreach ($classes as $c) {
            $results[] = [
                'type'     => 'classe',
                'type_label' => '🏫 Classe',
                'titre'    => $c->nom,
                'sous'     => ($c->niveau?->libelle ?? '').' · '.$c->effectif.'/'.$c->capacite.' élèves',
                'url'      => '#',
                'icon'     => '🏫',
                'couleur'  => 'violet',
            ];
        }

        // ─── PAIEMENTS (max 4) ───
        $paiements = Paiement::where('etablissement_id', $etab)
            ->where(function ($w) use ($like) {
                $w->where('reference', 'like', $like)
                  ->orWhere('numero_recu', 'like', $like);
            })
            ->with('eleve:id,nom,prenom')
            ->limit(4)->get(['id', 'reference', 'numero_recu', 'montant', 'statut', 'eleve_id']);

        foreach ($paiements as $p) {
            $results[] = [
                'type'     => 'paiement',
                'type_label' => '💳 Paiement',
                'titre'    => ($p->numero_recu ?: $p->reference),
                'sous'     => number_format($p->montant, 0, ',', ' ').' F · '.($p->eleve?->prenom ?? '').' '.($p->eleve?->nom ?? ''),
                'url'      => '#',
                'icon'     => '💳',
                'couleur'  => 'emerald',
            ];
        }

        // ─── DÉPENSES (max 3) ───
        $depenses = Depense::where('etablissement_id', $etab)
            ->where(function ($w) use ($like) {
                $w->where('reference', 'like', $like)
                  ->orWhere('libelle', 'like', $like)
                  ->orWhere('beneficiaire', 'like', $like);
            })
            ->limit(3)->get(['id', 'reference', 'libelle', 'montant', 'statut']);

        foreach ($depenses as $d) {
            $results[] = [
                'type'     => 'depense',
                'type_label' => '💸 Dépense',
                'titre'    => $d->libelle,
                'sous'     => $d->reference.' · '.number_format($d->montant, 0, ',', ' ').' F · '.$d->statut,
                'url'      => route('depenses.show', $d->id),
                'icon'     => '💸',
                'couleur'  => 'rose',
            ];
        }

        // ─── USERS / PARENTS (max 3) ───
        $users = User::where('etablissement_id', $etab)
            ->where('role', '!=', 'super_admin')
            ->where(function ($w) use ($like) {
                $w->where('nom', 'like', $like)
                  ->orWhere('prenom', 'like', $like)
                  ->orWhere('email', 'like', $like)
                  ->orWhere('telephone', 'like', $like);
            })
            ->limit(3)->get(['id', 'nom', 'prenom', 'role', 'email', 'telephone']);

        foreach ($users as $u) {
            $results[] = [
                'type'     => 'user',
                'type_label' => '👤 Utilisateur',
                'titre'    => $u->prenom.' '.$u->nom,
                'sous'     => ucfirst($u->role).' · '.($u->email ?: $u->telephone),
                'url'      => '#',
                'icon'     => '👤',
                'couleur'  => 'amber',
            ];
        }

        return response()->json([
            'q'       => $q,
            'count'   => count($results),
            'results' => $results,
        ]);
    }
}
