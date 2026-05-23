<?php

namespace App\Http\Controllers\Api\V1\Teacher\Concerns;

use App\Models\Affectation;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Services\Scolarite\AnneeScolaireContext;
use App\Models\Devoir;
use App\Models\Enseignant;
use App\Models\Evaluation;
use App\Models\Trimestre;
use Carbon\Carbon;
use Illuminate\Http\Request;

trait ResolvesTeacherContext
{
    protected function enseignant(Request $request): Enseignant
    {
        $ens = $request->user()->enseignantActif();
        abort_if(! $ens, 403, 'Compte enseignant introuvable pour cette école.');

        return $ens;
    }

    protected function etablissementId(Request $request): int
    {
        $id = $request->user()->ecoleActiveId();
        abort_if(! $id, 422, 'Établissement actif requis.');

        return (int) $id;
    }

    protected function anneeCourante(?int $etabId): ?AnneeScolaire
    {
        if (! $etabId) {
            return null;
        }

        return AnneeScolaireContext::courantePourEtablissement($etabId);
    }

    protected function trimestreCourant(?AnneeScolaire $annee): ?Trimestre
    {
        if (! $annee) {
            return null;
        }

        return Trimestre::where('annee_scolaire_id', $annee->id)->where('en_cours', true)->first()
            ?? Trimestre::where('annee_scolaire_id', $annee->id)->orderBy('numero')->first();
    }

    protected function assertClasseAssignable(Request $request, Classe $classe): void
    {
        $ens = $this->enseignant($request);
        $etabId = $this->etablissementId($request);
        if ((int) $classe->etablissement_id !== $etabId) {
            abort(404);
        }
        $annee = $this->anneeCourante($etabId);
        $q = Affectation::where('enseignant_id', $ens->id)
            ->where('classe_id', $classe->id)
            ->where('active', true);
        if ($annee) {
            $q->where('annee_scolaire_id', $annee->id);
        }
        abort_unless($q->exists(), 404);
    }

    protected function authorizeMatierePourClasse(Request $request, int $classeId, int $matiereId): void
    {
        $ens = $this->enseignant($request);
        $annee = $this->anneeCourante($this->etablissementId($request));
        $q = Affectation::where('enseignant_id', $ens->id)
            ->where('classe_id', $classeId)
            ->where('matiere_id', $matiereId)
            ->where('active', true);
        if ($annee) {
            $q->where('annee_scolaire_id', $annee->id);
        }
        abort_unless($q->exists(), 422, 'Vous n\'enseignez pas cette matière dans cette classe.');
    }

    protected function assertEvaluationOwned(Request $request, Evaluation $evaluation): Evaluation
    {
        $ens = $this->enseignant($request);
        if ((int) $evaluation->enseignant_id !== (int) $ens->id) {
            abort(404);
        }
        if ((int) $evaluation->etablissement_id !== $this->etablissementId($request)) {
            abort(404);
        }

        return $evaluation;
    }

    protected function assertDevoirOwned(Request $request, Devoir $devoir): Devoir
    {
        $ens = $this->enseignant($request);
        if ((int) $devoir->enseignant_id !== (int) $ens->id) {
            abort(404);
        }
        if ((int) $devoir->etablissement_id !== $this->etablissementId($request)) {
            abort(404);
        }

        return $devoir;
    }

    protected function todayFrenchWeekday(): ?string
    {
        $jourFr = strtolower(Carbon::now()->locale('fr')->isoFormat('dddd'));
        $jours = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];

        return in_array($jourFr, $jours, true) ? $jourFr : null;
    }
}
