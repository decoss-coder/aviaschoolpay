<?php

namespace App\Http\Controllers;

use App\Models\Classe;
use App\Models\Eleve;
use App\Models\MoyenneGenerale;
use App\Models\MoyenneMatiere;
use App\Models\Trimestre;
use App\Services\Scolarite\AnneeScolaireContext;
use Illuminate\Http\Request;

/**
 * Hub Notes & Bulletins (direction).
 * Centralise les accès aux outils : saisie via portail enseignant,
 * bulletins via admin RH, moyennes via grille admin.
 */
class NoteWebController extends Controller
{
    public function index(Request $request)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab, 403);

        $annee = AnneeScolaireContext::courantePourEtablissement((int) $etab->id);
        $trimestres = $annee
            ? Trimestre::where('annee_scolaire_id', $annee->id)->orderBy('numero')->get()
            : collect();
        $trimestreActif = $trimestres->first(fn ($t) => $t->en_cours) ?? $trimestres->first();

        $classes = $annee
            ? Classe::where('etablissement_id', $etab->id)
                ->where('annee_scolaire_id', $annee->id)
                ->with('niveau')
                ->orderBy('niveau_id')
                ->orderBy('nom')
                ->get()
            : collect();

        // Filtrage strict année courante
        $totalEleves = $annee
            ? Eleve::where('etablissement_id', $etab->id)->where('actif', true)
                ->inscritsCetteAnnee($annee->id)->count()
            : 0;

        $moyenneGenerale = $trimestreActif
            ? MoyenneGenerale::whereHas('classe', fn ($q) => $q->where('etablissement_id', $etab->id))
                ->where('trimestre_id', $trimestreActif->id)
                ->avg('moyenne_generale')
            : null;

        $stats = [
            'total_classes' => $classes->count(),
            'total_eleves' => $totalEleves,
            'moyenne_generale' => $moyenneGenerale ? round((float) $moyenneGenerale, 2) : null,
            'eleves_en_difficulte' => $trimestreActif
                ? MoyenneGenerale::whereHas('classe', fn ($q) => $q->where('etablissement_id', $etab->id))
                    ->where('trimestre_id', $trimestreActif->id)
                    ->where('moyenne_generale', '<', 10)
                    ->count()
                : 0,
        ];

        return view('notes.index', compact(
            'classes', 'trimestres', 'trimestreActif', 'stats', 'annee'
        ));
    }

    /**
     * Saisie : redirige vers la grille de notes du portail enseignant
     * (l'écran unifié pour la saisie des notes par classe/évaluation).
     */
    public function saisie(Request $request, $classe, $trimestre)
    {
        return redirect()->route('mon-espace.grille-notes.index', ['classe' => $classe])
            ->with('info', 'Saisie via la grille de notes — trimestre ' . $trimestre);
    }

    /**
     * Bulletins : redirige vers la page admin RH (calcul + export PDF).
     */
    public function bulletins(Request $request, $classe, $trimestre)
    {
        return redirect()->route('admin.rh.bulletins.index')
            ->with('info', 'Bulletins disponibles dans la direction RH.');
    }
}
