<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\Teacher\Concerns\ResolvesTeacherContext;
use App\Models\Classe;
use App\Models\Creneau;
use App\Models\Eleve;
use App\Models\EmploiDuTemps;
use App\Models\PresenceEleve;
use App\Support\ApiEnvelope;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class TeacherPresenceController extends Controller
{
    use ResolvesTeacherContext;

    /**
     * Retourne la liste des élèves + leurs présences pour une date,
     * éventuellement filtrée par créneau spécifique.
     *
     * Paramètres :
     *   - date         : YYYY-MM-DD (défaut: aujourd'hui)
     *   - creneau_id   : si fourni → présences spécifiques à ce créneau
     *                    sinon    → présences journalières (creneau_id=null)
     */
    public function index(Request $request, Classe $classe): JsonResponse
    {
        $this->assertClasseAssignable($request, $classe);

        $date = $request->filled('date')
            ? Carbon::parse($request->date)->toDateString()
            : today()->toDateString();

        $creneauId = $request->filled('creneau_id') ? (int) $request->creneau_id : null;

        $eleves = Eleve::where('classe_id', $classe->id)
            ->where('actif', true)
            ->orderBy('nom')->orderBy('prenom')
            ->get(['id', 'nom', 'prenom', 'matricule_interne', 'matricule_desps']);

        $q = PresenceEleve::where('classe_id', $classe->id)->whereDate('date', $date);
        if ($creneauId) {
            $q->where('creneau_id', $creneauId);
        } else {
            $q->whereNull('creneau_id')->where('periode', 'journee');
        }
        $presences = $q->get()->keyBy('eleve_id');

        $rows = $eleves->map(function (Eleve $e) use ($presences) {
            $p = $presences->get($e->id);
            return [
                'eleve' => [
                    'id'              => $e->id,
                    'nom'             => $e->nom,
                    'prenom'          => $e->prenom,
                    'matricule'       => $e->matricule_interne ?? $e->matricule_desps,
                    'matricule_desps' => $e->matricule_desps,
                ],
                'presence' => $p ? [
                    'statut'      => $this->mapStatutToApi($p),
                    'observation' => $p->observation,
                ] : null,
            ];
        });

        // Créneau (info pour l'UI)
        $creneau = $creneauId ? Creneau::find($creneauId) : null;

        return ApiEnvelope::success([
            'date'    => $date,
            'classe'  => $classe->only(['id', 'nom']),
            'creneau' => $creneau ? [
                'id'          => $creneau->id,
                'libelle'     => $creneau->libelle,
                'heure_debut' => substr((string) $creneau->heure_debut, 0, 5),
                'heure_fin'   => substr((string) $creneau->heure_fin, 0, 5),
            ] : null,
            'lignes'  => $rows,
        ], 'Présences.');
    }

    /**
     * Résumé d'une journée pour une classe :
     * UNIQUEMENT les créneaux où l'enseignant connecté a un cours
     * dans CETTE classe et DANS SES DISCIPLINES, pour ce jour.
     *
     * Chaque créneau retourné contient :
     *   - matiere    : matière de son cours
     *   - is_mine    : toujours true (filtré)
     *   - done       : true si l'appel a déjà été fait
     *   - presents/absents/retards : counts
     */
    public function dayCreneaux(Request $request, Classe $classe): JsonResponse
    {
        $this->assertClasseAssignable($request, $classe);
        $ens     = $this->enseignant($request);
        $etabId  = $this->etablissementId($request);
        $annee   = $this->anneeCourante($etabId);

        $date = $request->filled('date')
            ? Carbon::parse($request->date)->toDateString()
            : today()->toDateString();

        // Jour de la semaine en français (lundi, mardi, ...)
        $jourFr = strtolower(Carbon::parse($date)->locale('fr')->isoFormat('dddd'));

        // EDT du prof connecté pour ce jour, cette classe (uniquement ses cours)
        $myEdt = $annee
            ? EmploiDuTemps::where('classe_id', $classe->id)
                ->where('annee_scolaire_id', $annee->id)
                ->where('enseignant_id', $ens->id)
                ->where('jour', $jourFr)
                ->where('actif', true)
                ->with(['matiere:id,nom,code', 'creneau', 'salle:id,nom'])
                ->get()
            : collect();

        // Si pas de cours → liste vide
        if ($myEdt->isEmpty()) {
            $nbEleves = Eleve::where('classe_id', $classe->id)->where('actif', true)->count();
            return ApiEnvelope::success([
                'date'       => $date,
                'jour'       => $jourFr,
                'classe'     => $classe->only(['id', 'nom']),
                'nb_eleves'  => $nbEleves,
                'creneaux'   => [],
                'journalier' => $this->journalierStats($classe, $date),
                'empty_message' => "Vous n'avez pas de cours dans cette classe le {$jourFr}.",
            ], 'Journée sans cours.');
        }

        // Présences déjà enregistrées par créneau (pour les créneaux du prof)
        $creneauIds = $myEdt->pluck('creneau_id')->filter()->unique()->all();
        $presences = PresenceEleve::where('classe_id', $classe->id)
            ->whereDate('date', $date)
            ->whereIn('creneau_id', $creneauIds)
            ->get()
            ->groupBy('creneau_id');

        // Trier les EDT par ordre de créneau
        $myEdt = $myEdt->sortBy(fn ($s) => (int) ($s->creneau?->ordre ?? 999))->values();

        $rows = $myEdt->map(function ($s) use ($presences, $ens) {
            $c    = $s->creneau;
            $pres = $presences->get($s->creneau_id) ?? collect();

            return [
                'id'          => $c->id,
                'libelle'     => $c->libelle,
                'heure_debut' => substr((string) $c->heure_debut, 0, 5),
                'heure_fin'   => substr((string) $c->heure_fin, 0, 5),
                'type'        => $c->type ?? 'cours',
                'est_pause'   => false, // jamais : on ne renvoie que mes cours
                'has_edt'     => true,
                'matiere'     => $s->matiere?->only(['id', 'nom', 'code']),
                'enseignant'  => ['id' => $ens->id, 'nom' => $ens->nom, 'prenom' => $ens->prenom],
                'is_mine'     => true,
                'salle'       => $s->salle?->only(['id', 'nom']),
                'done'        => $pres->isNotEmpty(),
                'counts'      => [
                    'total'    => $pres->count(),
                    'presents' => $pres->where('statut', 'present')->count(),
                    'absents'  => $pres->where('statut', 'absent')->count(),
                    'retards'  => $pres->where('statut', 'retard')->count(),
                    'justifies' => $pres->where('justifie', true)->count(),
                ],
            ];
        });

        $nbEleves = Eleve::where('classe_id', $classe->id)->where('actif', true)->count();

        return ApiEnvelope::success([
            'date'       => $date,
            'jour'       => $jourFr,
            'classe'     => $classe->only(['id', 'nom']),
            'nb_eleves'  => $nbEleves,
            'creneaux'   => $rows,
            'journalier' => $this->journalierStats($classe, $date),
        ], 'Journée de présence.');
    }

    /**
     * Résumé journalier agrégé à partir des présences par créneau ET
     * de l'éventuel appel journalier manuel.
     *
     * Pour chaque élève, on retient le statut "le plus grave" sur la journée :
     *   absent > retard > justifie > present
     */
    private function journalierStats(Classe $classe, string $date): array
    {
        // Toutes les présences du jour (créneaux + journalier)
        $all = PresenceEleve::where('classe_id', $classe->id)
            ->whereDate('date', $date)
            ->get();

        if ($all->isEmpty()) {
            return [
                'done' => false, 'total' => 0,
                'presents' => 0, 'absents' => 0, 'retards' => 0, 'justifies' => 0,
                'mode' => 'aucun', 'creneaux_faits' => 0,
            ];
        }

        // Présences manuelles "journée" (creneau_id null)
        $manuelles = $all->where('creneau_id', null);
        $parCreneau = $all->whereNotNull('creneau_id');

        // Combien de créneaux distincts ont été pointés ?
        $creneauxFaits = $parCreneau->pluck('creneau_id')->unique()->count();

        // Hiérarchie statut : absent (3) > retard (2) > justifie/excuse (1) > present (0)
        $rank = function (string $statut, bool $justifie): int {
            if ($statut === 'absent') return 3;
            if ($statut === 'retard') return 2;
            if ($justifie || $statut === 'excuse') return 1;
            return 0; // present
        };

        // Regrouper toutes les présences (journée + créneaux) par élève → garder le pire
        $parEleve = [];
        foreach ($all as $p) {
            $r = $rank($p->statut, (bool) $p->justifie);
            if (! isset($parEleve[$p->eleve_id]) || $r > $parEleve[$p->eleve_id]['rank']) {
                $parEleve[$p->eleve_id] = [
                    'rank'     => $r,
                    'statut'   => $p->statut,
                    'justifie' => (bool) $p->justifie,
                ];
            }
        }

        $presents = 0; $absents = 0; $retards = 0; $justifies = 0;
        foreach ($parEleve as $row) {
            if ($row['rank'] === 0) $presents++;
            elseif ($row['rank'] === 1) $justifies++;
            elseif ($row['rank'] === 2) $retards++;
            elseif ($row['rank'] === 3) $absents++;
        }

        $mode = $manuelles->isNotEmpty() && $parCreneau->isEmpty() ? 'manuel'
              : ($parCreneau->isNotEmpty() && $manuelles->isEmpty() ? 'agrege'
              : 'mixte');

        return [
            'done'           => true,
            'total'          => count($parEleve),
            'presents'       => $presents,
            'absents'        => $absents,
            'retards'        => $retards,
            'justifies'      => $justifies,
            'mode'           => $mode,
            'creneaux_faits' => $creneauxFaits,
        ];
    }

    public function storeBulk(Request $request, Classe $classe): JsonResponse
    {
        $this->assertClasseAssignable($request, $classe);
        $ens = $this->enseignant($request);

        $data = $request->validate([
            'date' => 'required|date',
            'creneau_id' => 'nullable|integer|exists:creneaux,id',
            'presences' => 'required|array|min:1',
            'presences.*.eleve_id' => 'required|exists:eleves,id',
            'presences.*.statut' => 'required|in:present,absent,retard,justifie',
            'presences.*.observation' => 'nullable|string|max:2000',
        ]);

        $date = Carbon::parse($data['date'])->toDateString();
        $creneauId = $data['creneau_id'] ?? null;
        $periode = 'journee';
        if ($creneauId) {
            $cr = Creneau::find($creneauId);
            $h = $cr ? (int) substr((string) $cr->heure_debut, 0, 2) : 0;
            $periode = $h < 12 ? 'matin' : 'apres_midi';
        }

        $counts = ['total' => 0, 'presents' => 0, 'absents' => 0, 'retards' => 0, 'justifies' => 0];

        foreach ($data['presences'] as $row) {
            $ok = Eleve::where('id', $row['eleve_id'])
                ->where('classe_id', $classe->id)
                ->where('actif', true)
                ->exists();
            if (! $ok) {
                return ApiEnvelope::fail('Élève non membre de cette classe.', ['presences' => ['Élève invalide.']], 422);
            }

            [$dbStatut, $justifie] = $this->mapApiStatutToDb($row['statut']);

            PresenceEleve::updateOrCreate(
                [
                    'eleve_id'   => $row['eleve_id'],
                    'date'       => $date,
                    'creneau_id' => $creneauId,
                    'periode'    => $periode,
                ],
                [
                    'classe_id'     => $classe->id,
                    'enseignant_id' => $ens->id,
                    'statut'        => $dbStatut,
                    'justifie'      => $justifie,
                    'observation'   => $row['observation'] ?? null,
                    'saisie_par'    => $request->user()->id,
                ]
            );

            $counts['total']++;
            match ($row['statut']) {
                'present'  => $counts['presents']++,
                'absent'   => $counts['absents']++,
                'retard'   => $counts['retards']++,
                'justifie' => $counts['justifies']++,
                default    => null,
            };
        }

        return ApiEnvelope::success([
            'date'       => $date,
            'creneau_id' => $creneauId,
            'resume'     => $counts,
        ], 'Présences enregistrées.');
    }

    private function mapApiStatutToDb(string $api): array
    {
        return match ($api) {
            'justifie' => ['excuse', true],
            default => [$api, false],
        };
    }

    private function mapStatutToApi(PresenceEleve $p): string
    {
        if ($p->justifie && $p->statut === 'excuse') {
            return 'justifie';
        }

        return $p->statut;
    }
}
