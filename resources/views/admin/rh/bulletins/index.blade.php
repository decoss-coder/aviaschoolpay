@extends('layouts.app')
@section('title', 'Bulletins')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-4" x-data="bulletinsManager()">

    <div class="flex items-center gap-2 text-sm text-gray-400">
        <a href="{{ route('admin.rh.dashboard') }}" class="hover:text-brand-600">RH</a>
        <span>/</span>
        <span class="text-gray-700 font-semibold">Bulletins</span>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm font-medium">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">
            <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- En-tête --}}
    <div class="flex items-start justify-between gap-3 flex-wrap">
        <div>
            <h1 class="font-display text-2xl font-extrabold text-gray-900">Bulletins de notes</h1>
            <p class="text-sm text-gray-500 mt-1">
                Calculez les moyennes et générez les bulletins par classe et période.
                @if($annee)
                    Année : <b class="text-brand-700">{{ $annee->libelle }}</b>
                @endif
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.rh.evaluation-system.index') }}"
               class="text-xs font-bold bg-indigo-100 hover:bg-indigo-200 text-indigo-700 px-3 py-2 rounded-lg">⚙ Coefs périodes</a>
            <a href="{{ route('admin.rh.disciplines.index') }}"
               class="text-xs font-bold bg-blue-100 hover:bg-blue-200 text-blue-700 px-3 py-2 rounded-lg">📚 Disciplines</a>
        </div>
    </div>

    @if(!$annee)
        <div class="bg-red-50 border border-red-200 rounded-2xl p-6 text-center text-red-700">
            ⚠ Aucune année scolaire active. <a href="{{ route('admin.annees.index') }}" class="underline font-bold">Activez une année</a> pour pouvoir générer des bulletins.
        </div>
    @elseif($trimestres->isEmpty())
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 text-center text-amber-800">
            ⚠ Aucune période créée pour l'année <b>{{ $annee->libelle }}</b>. <a href="{{ route('admin.rh.evaluation-system.index') }}" class="underline font-bold">Configurer les périodes</a>.
        </div>
    @elseif($classes->isEmpty())
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 text-center text-amber-800">
            ⚠ Aucune classe active pour cette année. <a href="{{ route('classes.index') }}" class="underline font-bold">Créer/activer une classe</a>.
        </div>
    @else

    {{-- Sélecteurs --}}
    <form method="GET" class="bg-white rounded-2xl shadow-card border border-gray-100 p-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Classe</label>
                <select name="classe_id" onchange="this.form.submit()"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm font-semibold">
                    <option value="">— Sélectionner une classe —</option>
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}" {{ $classeId == $c->id ? 'selected' : '' }}>{{ $c->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Période</label>
                <select name="trimestre_id" onchange="this.form.submit()"
                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm font-semibold">
                    @foreach($trimestres as $t)
                        <option value="{{ $t->id }}" {{ $trimestreId == $t->id ? 'selected' : '' }}>
                            {{ $t->libelle }} (coef ×{{ rtrim(rtrim(number_format($t->coefficient ?? 1, 1, '.', ''), '0'), '.') }})
                            @if($t->en_cours) ★ @endif
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>

    @if($classeId && $trimestreId)
    <div class="flex flex-wrap gap-2">
        <form method="POST" action="{{ route('admin.rh.bulletins.calculer') }}">
            @csrf
            <input type="hidden" name="classe_id" value="{{ $classeId }}">
            <input type="hidden" name="trimestre_id" value="{{ $trimestreId }}">
            <button type="submit"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold px-5 py-2 rounded-xl flex items-center gap-2">
                🧮 Calculer / Recalculer les moyennes (+ annuelles)
            </button>
        </form>

        @if($moyennesGenerales->isNotEmpty())
        <form method="POST" action="{{ route('admin.rh.bulletins.pdf-classe') }}">
            @csrf
            <input type="hidden" name="classe_id" value="{{ $classeId }}">
            <input type="hidden" name="trimestre_id" value="{{ $trimestreId }}">
            <button type="submit"
                    class="bg-red-600 hover:bg-red-700 text-white text-sm font-bold px-5 py-2 rounded-xl flex items-center gap-2">
                📄 Tous les bulletins (1 par page)
            </button>
        </form>

        {{-- Lot PDF disposition configurable --}}
        <form method="POST" action="{{ route('admin.rh.bulletins.pdf-masse') }}" class="flex gap-2 items-center">
            @csrf
            <input type="hidden" name="classe_id" value="{{ $classeId }}">
            <input type="hidden" name="trimestre_id" value="{{ $trimestreId }}">
            <template x-for="id in selected" :key="id">
                <input type="hidden" name="eleve_ids[]" :value="id">
            </template>
            <select name="disposition"
                    class="text-xs font-semibold rounded-lg border border-gray-200 px-2 py-2 bg-white">
                <option value="1">1 / page A4</option>
                <option value="2">2 / page A4</option>
                <option value="3">3 / page A4</option>
                <option value="4">4 / page A4</option>
            </select>
            <button type="submit"
                    :disabled="selected.length === 0"
                    class="bg-purple-600 hover:bg-purple-700 disabled:opacity-40 disabled:cursor-not-allowed text-white text-sm font-bold px-5 py-2 rounded-xl flex items-center gap-2">
                🖨 Lot PDF (<span x-text="selected.length"></span>)
            </button>
        </form>
        @endif
    </div>
    @endif

    {{-- Liste élèves + résultats --}}
    @if($eleves->isEmpty() && $classeId)
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 text-center text-sm text-amber-700">
            Aucun élève inscrit cette année dans cette classe.
        </div>
    @elseif($eleves->isNotEmpty())
    <div class="bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between flex-wrap gap-2">
            <h2 class="font-bold text-gray-800 text-sm uppercase tracking-wide">
                Résultats — {{ $eleves->count() }} élèves
            </h2>
            <label class="text-xs font-semibold text-gray-600 flex items-center gap-2">
                <input type="checkbox" @change="toggleAll($event.target.checked, eleves)"
                       class="w-4 h-4 rounded border-gray-300">
                Tout sélectionner
            </label>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-500 uppercase w-10">☑</th>
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-500 uppercase">Rang T</th>
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-500 uppercase">Matricule</th>
                        <th class="px-3 py-2 text-left text-xs font-bold text-gray-500 uppercase">Nom et prénom</th>
                        <th class="px-3 py-2 text-center text-xs font-bold text-gray-500 uppercase">Moyenne T /20</th>
                        <th class="px-3 py-2 text-center text-xs font-bold text-gray-500 uppercase">Mention T</th>
                        <th class="px-3 py-2 text-center text-xs font-bold text-gray-500 uppercase">Moy. annuelle</th>
                        <th class="px-3 py-2 text-center text-xs font-bold text-gray-500 uppercase">Abs / Ret</th>
                        <th class="px-3 py-2 text-right text-xs font-bold text-gray-500 uppercase">Bulletin</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($eleves->sortBy(fn($e) => $moyennesGenerales->get($e->id)?->rang ?? 999) as $eleve)
                    @php
                        $g = $moyennesGenerales->get($eleve->id);
                        $a = $moyennesAnnuelles->get($eleve->id);
                        $color = $g?->moyenne_generale >= 14 ? 'green' : ($g?->moyenne_generale >= 10 ? 'amber' : ($g?->moyenne_generale !== null ? 'red' : 'gray'));
                        $mentionLabels = [
                            'felicitations'    => ['🏆 Félicitations', 'bg-purple-100 text-purple-700'],
                            'tableau_honneur'  => ['⭐ Tableau d\'honneur', 'bg-yellow-100 text-yellow-700'],
                            'encouragements'   => ['👍 Encouragements', 'bg-green-100 text-green-700'],
                            'avertissement'    => ['⚠ Avertissement', 'bg-orange-100 text-orange-700'],
                            'blame'            => ['❌ Blâme', 'bg-red-100 text-red-700'],
                            'aucune'           => ['—', 'bg-gray-100 text-gray-500'],
                        ];
                        [$mentionLabel, $mentionClass] = $mentionLabels[$g?->mention ?? 'aucune'] ?? ['—', 'bg-gray-100 text-gray-500'];
                    @endphp
                    <tr class="hover:bg-indigo-50/30">
                        <td class="px-3 py-2">
                            @if($g)
                                <input type="checkbox" :value="{{ $eleve->id }}" x-model="selected"
                                       class="w-4 h-4 rounded border-gray-300">
                            @endif
                        </td>
                        <td class="px-3 py-2 text-center font-bold text-gray-700">
                            @if($g?->rang)
                                @if($g->rang === 1)<span class="text-yellow-500 text-lg">🥇</span>
                                @elseif($g->rang === 2)<span class="text-gray-400 text-lg">🥈</span>
                                @elseif($g->rang === 3)<span class="text-orange-600 text-lg">🥉</span>
                                @else{{ $g->rang }}<sup>e</sup>@endif
                            @else —
                            @endif
                        </td>
                        <td class="px-3 py-2 text-xs font-mono font-bold">{{ $eleve->matricule_desps ?: $eleve->matricule_interne }}</td>
                        <td class="px-3 py-2 font-semibold"><span class="uppercase">{{ $eleve->nom }}</span> {{ $eleve->prenom }}</td>
                        <td class="px-3 py-2 text-center">
                            @if($g?->moyenne_generale !== null)
                                <span class="text-lg font-extrabold text-{{ $color }}-700">{{ number_format($g->moyenne_generale, 2) }}</span>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-center">
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $mentionClass }}">{{ $mentionLabel }}</span>
                        </td>
                        <td class="px-3 py-2 text-center">
                            @if($a?->moyenne_annuelle !== null && $a)
                                @php $cA = $a->moyenne_annuelle >= 14 ? 'green' : ($a->moyenne_annuelle >= 10 ? 'amber' : 'red'); @endphp
                                <span class="font-extrabold text-{{ $cA }}-700">{{ number_format($a->moyenne_annuelle, 2) }}</span>
                                @if($a->rang_annuel)
                                    <span class="text-[10px] text-gray-400 ml-1">({{ $a->rang_annuel }}<sup>e</sup>)</span>
                                @endif
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-center text-xs">
                            @if($g)
                                <span class="text-red-600 font-bold">{{ $g->total_absences ?? 0 }}</span>a /
                                <span class="text-amber-600 font-bold">{{ $g->total_retards ?? 0 }}</span>r
                            @else —
                            @endif
                        </td>
                        <td class="px-3 py-2 text-right">
                            @if($g)
                            <a href="{{ route('admin.rh.bulletins.pdf', ['eleve' => $eleve->id, 'trimestre' => $trimestreId]) }}"
                               target="_blank"
                               class="text-xs font-bold bg-red-100 hover:bg-red-200 text-red-700 px-3 py-1.5 rounded-lg">📄 PDF</a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @if($moyennesGenerales->isNotEmpty())
            <div class="px-5 py-3 bg-indigo-50 border-t border-indigo-100 text-sm text-indigo-800 flex items-center justify-between flex-wrap gap-2">
                <span>📊 Moyenne classe : <b>{{ number_format($moyennesGenerales->first()?->moyenne_classe ?? 0, 2) }}</b></span>
                <span>📈 Premier : <b>{{ number_format($moyennesGenerales->first()?->moyenne_premier ?? 0, 2) }}</b></span>
                <span>📉 Dernier : <b>{{ number_format($moyennesGenerales->first()?->moyenne_dernier ?? 0, 2) }}</b></span>
                <span>👥 Effectif : <b>{{ $moyennesGenerales->first()?->effectif_classe ?? $eleves->count() }}</b></span>
                <span>🎯 Avec moyenne : <b>{{ $moyennesGenerales->count() }}/{{ $eleves->count() }}</b></span>
            </div>
            @endif
        </div>
    </div>
    @endif

    @endif {{-- annee && trimestres && classes --}}
</div>

<script>
function bulletinsManager() {
    return {
        selected: [],
        eleves: @json($eleves->pluck('id')),
        toggleAll(checked, ids) {
            if (checked) {
                this.selected = [...ids];
            } else {
                this.selected = [];
            }
        }
    };
}
</script>
@endsection
