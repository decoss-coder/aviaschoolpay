@extends('layouts.app')
@section('title', 'Système d\'évaluation')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-6 space-y-5">

    <div class="flex items-center gap-2 text-sm text-gray-400">
        <a href="{{ route('admin.rh.dashboard') }}" class="hover:text-brand-600">RH</a>
        <span>/</span>
        <span class="text-gray-700 font-semibold">Système d'évaluation</span>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm font-medium">{{ session('success') }}</div>
    @endif

    <div>
        <h1 class="font-display text-2xl font-extrabold text-gray-900">Système d'évaluation</h1>
        <p class="text-sm text-gray-500 mt-1">Définissez les périodes pédagogiques de votre établissement.</p>
    </div>

    {{-- Configuration --}}
    <form method="POST" action="{{ route('admin.rh.evaluation-system.update') }}"
          class="bg-white rounded-2xl shadow-card border border-gray-100 p-5 space-y-4">
        @csrf
        <h2 class="font-bold text-gray-800 text-sm uppercase tracking-wide">Type de système</h2>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            @foreach([
                'trimestre'     => ['Trimestriel', '3 trimestres', '🗓️'],
                'semestre'      => ['Semestriel',  '2 semestres',  '📅'],
                'quadrimestre'  => ['Quadrimestriel', '4 périodes', '📆'],
            ] as $key => [$label, $sub, $emoji])
            <label class="cursor-pointer">
                <input type="radio" name="systeme_evaluation" value="{{ $key }}" class="peer sr-only"
                       {{ $etab->systeme_evaluation === $key ? 'checked' : '' }}>
                <div class="rounded-2xl border-2 border-gray-200 p-4 text-center transition
                            peer-checked:border-brand-500 peer-checked:bg-brand-50 hover:border-brand-300">
                    <div class="text-3xl mb-1">{{ $emoji }}</div>
                    <p class="font-bold text-sm text-gray-800">{{ $label }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $sub }}</p>
                </div>
            </label>
            @endforeach
        </div>

        <div class="border-t border-gray-100 pt-4">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="regenerer_periodes" value="1"
                       class="w-4 h-4 rounded border-gray-300 text-brand-600 focus:ring-brand-300">
                <span class="text-sm font-semibold text-gray-700">Régénérer automatiquement les périodes pour l'année en cours</span>
            </label>
            <p class="text-xs text-orange-600 mt-1 ml-6">
                ⚠ Les périodes existantes seront supprimées et les évaluations associées peuvent être impactées.
            </p>
        </div>

        <div class="flex justify-end gap-3 border-t border-gray-100 pt-4">
            <button type="submit"
                    class="bg-brand-600 hover:bg-brand-700 text-white text-sm font-bold px-6 py-2 rounded-xl">
                Enregistrer
            </button>
        </div>
    </form>

    {{-- Périodes actuelles + coefficients (système ivoirien) --}}
    @if($annee)
    {{-- Bouton Régénérer (formulaire SÉPARÉ pour éviter l'imbrication HTML invalide) --}}
    <form method="POST" action="{{ route('admin.rh.evaluation-system.regenerer') }}"
          id="form-regenerer-periodes"
          onsubmit="return confirm('Régénérer toutes les périodes ? Les périodes existantes seront supprimées.');">
        @csrf
    </form>

    <form method="POST" action="{{ route('admin.rh.evaluation-system.update-coefs') }}"
          class="bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden">
        @csrf
        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between flex-wrap gap-3">
            <div>
                <h2 class="font-bold text-gray-800 text-sm uppercase tracking-wide">
                    Périodes & coefficients — {{ $annee->libelle }}
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">
                    💡 Système ivoirien standard : <b>T1 × 1</b>, <b>T2 × 2</b>, <b>T3 × 2</b> (total = 5)
                </p>
            </div>
            <div class="flex gap-2">
                {{-- Submit DÉLOCALISÉ vers le formulaire externe via attribut form= --}}
                <button type="submit" form="form-regenerer-periodes"
                        class="text-xs font-bold bg-amber-100 text-amber-700 hover:bg-amber-200 px-3 py-1.5 rounded-lg">
                    🔄 Régénérer
                </button>
            </div>
        </div>
        @if($trimestres->isEmpty())
            <div class="px-5 py-8 text-center text-sm text-gray-400">
                Aucune période créée. Cliquez "Régénérer" pour les créer automatiquement.
            </div>
        @else
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="px-5 py-2 text-left text-xs font-bold text-gray-500 uppercase">Période</th>
                    <th class="px-5 py-2 text-left text-xs font-bold text-gray-500 uppercase">Du / au</th>
                    <th class="px-5 py-2 text-center text-xs font-bold text-gray-500 uppercase w-32">Coefficient *</th>
                    <th class="px-5 py-2 text-center text-xs font-bold text-gray-500 uppercase">Statut</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($trimestres as $t)
                <tr class="hover:bg-gray-50/40">
                    <td class="px-5 py-3 font-bold text-gray-800">{{ $t->libelle }}</td>
                    <td class="px-5 py-3 text-xs text-gray-500">
                        {{ \Carbon\Carbon::parse($t->date_debut)->format('d/m/Y') }} → {{ \Carbon\Carbon::parse($t->date_fin)->format('d/m/Y') }}
                    </td>
                    <td class="px-5 py-3 text-center">
                        <input type="number" name="coefs[{{ $t->id }}]"
                               value="{{ rtrim(rtrim(number_format($t->coefficient ?? 1, 1, '.', ''), '0'), '.') }}"
                               min="0.5" max="10" step="0.5"
                               class="w-20 text-center font-bold rounded-lg border border-gray-200 px-2 py-1.5 text-sm focus:ring-2 focus:ring-brand-300 outline-none">
                    </td>
                    <td class="px-5 py-3 text-center">
                        @if($t->en_cours)
                            <span class="text-xs font-bold bg-green-100 text-green-700 px-2 py-1 rounded-full">En cours</span>
                        @else
                            <span class="text-xs text-gray-400">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="px-5 py-3 border-t border-gray-100 bg-gray-50/30 flex justify-end">
            <button type="submit"
                    class="bg-brand-600 hover:bg-brand-700 text-white text-sm font-bold px-5 py-2 rounded-xl">
                💾 Enregistrer les coefficients
            </button>
        </div>
        @endif
    </form>
    @else
    <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-xl px-4 py-3 text-sm">
        Aucune année scolaire en cours. Créez-en une avant de configurer les périodes.
    </div>
    @endif
</div>
@endsection
