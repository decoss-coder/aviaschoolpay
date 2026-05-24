<?php

namespace App\Http\Controllers;

use App\Models\Eleve;
use App\Models\Inscription;
use App\Services\Finance\TarificationService;
use App\Services\Scolarite\AnneeScolaireContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EleveStatutRequiredController extends Controller
{
    public function edit(Request $request, Eleve $eleve)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab && (int) $eleve->etablissement_id === (int) $etab->id, 403);

        $redirect = $this->safeRedirect(
            $request->query('redirect'),
            route('eleves.show', $eleve)
        );

        if ($this->hasValidStatutEleve($eleve)) {
            return redirect($redirect);
        }

        return view('eleves.statut-required', compact('eleve', 'redirect'));
    }

    public function update(Request $request, Eleve $eleve)
    {
        $etab = $request->user()->etablissement;
        abort_unless($etab && (int) $eleve->etablissement_id === (int) $etab->id, 403);

        $validated = $request->validate([
            'statut_eleve' => ['required', Rule::in(['AFF', 'NAFF'])],
            'redirect' => ['nullable', 'string', 'max:2048'],
        ]);

        DB::transaction(function () use ($eleve, $etab, $validated) {
            $eleve->update([
                'statut_eleve' => $validated['statut_eleve'],
            ]);

            $annee = AnneeScolaireContext::courantePourEtablissement((int) $etab->id);

            if (! $annee) {
                return;
            }

            $inscription = Inscription::query()
                ->where('eleve_id', $eleve->id)
                ->where('etablissement_id', $etab->id)
                ->where('annee_scolaire_id', $annee->id)
                ->where('statut', 'validee')
                ->latest('date_inscription')
                ->latest('id')
                ->first();

            if ($inscription) {
                TarificationService::synchroniserInscription($inscription, $eleve->fresh());
            }
        });

        $redirect = $this->safeRedirect(
            $validated['redirect'] ?? null,
            route('eleves.show', $eleve)
        );

        return redirect($redirect)
            ->with('success', 'Statut élève mis à jour. Les frais ont été recalculés selon la grille tarifaire.');
    }

    private function hasValidStatutEleve(Eleve $eleve): bool
    {
        return in_array($eleve->statut_eleve, [
            Eleve::STATUT_ELEVE_AFFECTE,
            Eleve::STATUT_ELEVE_NON_AFFECTE,
        ], true);
    }

    private function safeRedirect(?string $redirect, string $fallback): string
    {
        $redirect = trim((string) $redirect);

        if ($redirect === '') {
            return $fallback;
        }

        if (str_starts_with($redirect, url('/'))) {
            return $redirect;
        }

        if (str_starts_with($redirect, '/')) {
            return url($redirect);
        }

        return $fallback;
    }
}
