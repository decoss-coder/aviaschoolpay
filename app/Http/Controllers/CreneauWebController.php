<?php

namespace App\Http\Controllers;

use App\Models\Creneau;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CreneauWebController extends Controller
{
    public function index(Request $request)
    {
        $etab     = $request->user()->etablissement;
        $creneaux = Creneau::where('etablissement_id', $etab->id)
            ->orderBy('ordre')
            ->get();

        $presets = $this->presets();

        return view('emploi-du-temps.creneaux.index', compact('creneaux', 'presets'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'libelle'     => 'required|string|max:50',
            'heure_debut' => 'required|date_format:H:i',
            'heure_fin'   => 'required|date_format:H:i|after:heure_debut',
            'type'        => 'required|in:cours,recreation,pause_dejeuner',
        ]);

        $etabId   = $request->user()->etablissement_id;
        $maxOrdre = Creneau::where('etablissement_id', $etabId)->max('ordre') ?? 0;

        Creneau::create($data + [
            'etablissement_id' => $etabId,
            'ordre'            => $maxOrdre + 1,
        ]);

        return back()->with('success', 'Créneau ajouté.');
    }

    public function applyPreset(Request $request)
    {
        $data = $request->validate([
            'preset' => ['required', Rule::in(array_keys($this->presets()))],
            'mode'   => ['required', Rule::in(['append', 'replace'])],
        ]);

        $etabId = (int) $request->user()->etablissement_id;
        $slots  = $this->presets()[$data['preset']]['slots'];

        $created = 0;
        $skipped = 0;

        DB::transaction(function () use ($etabId, $slots, $data, &$created, &$skipped) {
            $existingQuery = Creneau::where('etablissement_id', $etabId);

            if ($data['mode'] === 'replace') {
                $used = (clone $existingQuery)->whereHas('emploisDuTemps')->exists();

                if ($used) {
                    abort(422, 'Impossible de remplacer : certains créneaux sont déjà utilisés dans des emplois du temps. Utilisez plutôt « ajouter à la suite » ou supprimez les emplois concernés.');
                }

                (clone $existingQuery)->delete();
                $maxOrdre = 0;
            } else {
                $maxOrdre = (int) ((clone $existingQuery)->max('ordre') ?? 0);
            }

            foreach ($slots as $slot) {
                $duplicate = Creneau::where('etablissement_id', $etabId)
                    ->where('heure_debut', $slot['heure_debut'])
                    ->where('heure_fin', $slot['heure_fin'])
                    ->exists();

                if ($duplicate) {
                    $skipped++;
                    continue;
                }

                Creneau::create([
                    'etablissement_id' => $etabId,
                    'libelle'          => $slot['libelle'],
                    'heure_debut'      => $slot['heure_debut'],
                    'heure_fin'        => $slot['heure_fin'],
                    'type'             => $slot['type'],
                    'ordre'            => ++$maxOrdre,
                ]);

                $created++;
            }
        });

        $label = $this->presets()[$data['preset']]['label'];
        $message = "Modèle « {$label} » appliqué : {$created} créneau(x) ajouté(s)";
        if ($skipped > 0) {
            $message .= ", {$skipped} doublon(s) ignoré(s)";
        }
        $message .= '.';

        return back()->with('success', $message);
    }

    public function update(Request $request, Creneau $creneau)
    {
        $this->authorizeEtab($request, $creneau);

        $data = $request->validate([
            'libelle'     => 'required|string|max:50',
            'heure_debut' => 'required|date_format:H:i',
            'heure_fin'   => 'required|date_format:H:i|after:heure_debut',
            'type'        => 'required|in:cours,recreation,pause_dejeuner',
        ]);

        $creneau->update($data);

        return back()->with('success', 'Créneau mis à jour.');
    }

    public function destroy(Request $request, Creneau $creneau)
    {
        $this->authorizeEtab($request, $creneau);

        if ($creneau->emploisDuTemps()->exists()) {
            return back()->with('error', 'Ce créneau est utilisé dans des emplois du temps.');
        }

        $creneau->delete();
        Creneau::where('etablissement_id', $request->user()->etablissement_id)
            ->orderBy('ordre')
            ->get()
            ->each(fn ($c, $i) => $c->update(['ordre' => $i + 1]));

        return back()->with('success', 'Créneau supprimé.');
    }

    public function reorder(Request $request)
    {
        $etabId = $request->user()->etablissement_id;
        $ids    = $request->validate(['ids' => 'required|array'])['ids'];

        foreach ($ids as $i => $id) {
            Creneau::where('id', $id)->where('etablissement_id', $etabId)
                ->update(['ordre' => $i + 1]);
        }

        return response()->json(['ok' => true]);
    }

    private function authorizeEtab(Request $request, Creneau $creneau): void
    {
        abort_if($creneau->etablissement_id !== $request->user()->etablissement_id, 403);
    }

    private function presets(): array
    {
        return [
            'officiel_55' => [
                'label' => 'Journée 55 min — modèle officiel',
                'description' => 'Modèle issu de l’image : cours de 55 min, récréation 15 min et pause déjeuner 1 h 30.',
                'slots' => [
                    ['libelle' => 'C1', 'heure_debut' => '07:10', 'heure_fin' => '08:05', 'type' => 'cours'],
                    ['libelle' => 'C2', 'heure_debut' => '08:05', 'heure_fin' => '09:00', 'type' => 'cours'],
                    ['libelle' => 'C3', 'heure_debut' => '09:00', 'heure_fin' => '09:55', 'type' => 'cours'],
                    ['libelle' => 'Récréation', 'heure_debut' => '09:55', 'heure_fin' => '10:10', 'type' => 'recreation'],
                    ['libelle' => 'C4', 'heure_debut' => '10:10', 'heure_fin' => '11:05', 'type' => 'cours'],
                    ['libelle' => 'C5', 'heure_debut' => '11:05', 'heure_fin' => '12:00', 'type' => 'cours'],
                    ['libelle' => 'Pause déjeuner', 'heure_debut' => '12:00', 'heure_fin' => '13:30', 'type' => 'pause_dejeuner'],
                    ['libelle' => 'C6', 'heure_debut' => '13:30', 'heure_fin' => '14:25', 'type' => 'cours'],
                    ['libelle' => 'C7', 'heure_debut' => '14:25', 'heure_fin' => '15:20', 'type' => 'cours'],
                    ['libelle' => 'C8', 'heure_debut' => '15:20', 'heure_fin' => '16:15', 'type' => 'cours'],
                    ['libelle' => 'C9', 'heure_debut' => '16:15', 'heure_fin' => '17:10', 'type' => 'cours'],
                    ['libelle' => 'C10', 'heure_debut' => '17:10', 'heure_fin' => '18:05', 'type' => 'cours'],
                ],
            ],
            'journee_60' => [
                'label' => 'Journée 1 h — modèle simple',
                'description' => 'Cours de 1 h, récréation au milieu de matinée et pause déjeuner.',
                'slots' => [
                    ['libelle' => 'C1', 'heure_debut' => '07:00', 'heure_fin' => '08:00', 'type' => 'cours'],
                    ['libelle' => 'C2', 'heure_debut' => '08:00', 'heure_fin' => '09:00', 'type' => 'cours'],
                    ['libelle' => 'C3', 'heure_debut' => '09:00', 'heure_fin' => '10:00', 'type' => 'cours'],
                    ['libelle' => 'Récréation', 'heure_debut' => '10:00', 'heure_fin' => '10:15', 'type' => 'recreation'],
                    ['libelle' => 'C4', 'heure_debut' => '10:15', 'heure_fin' => '11:15', 'type' => 'cours'],
                    ['libelle' => 'C5', 'heure_debut' => '11:15', 'heure_fin' => '12:15', 'type' => 'cours'],
                    ['libelle' => 'Pause déjeuner', 'heure_debut' => '12:15', 'heure_fin' => '13:30', 'type' => 'pause_dejeuner'],
                    ['libelle' => 'C6', 'heure_debut' => '13:30', 'heure_fin' => '14:30', 'type' => 'cours'],
                    ['libelle' => 'C7', 'heure_debut' => '14:30', 'heure_fin' => '15:30', 'type' => 'cours'],
                    ['libelle' => 'C8', 'heure_debut' => '15:30', 'heure_fin' => '16:30', 'type' => 'cours'],
                    ['libelle' => 'C9', 'heure_debut' => '16:30', 'heure_fin' => '17:30', 'type' => 'cours'],
                ],
            ],
        ];
    }
}
