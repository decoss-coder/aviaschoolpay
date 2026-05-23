@extends('layouts.app')
@section('title', 'Bilan classe — ' . $classe->nom)

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-6">

    {{-- En-tête --}}
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h1 class="font-display text-2xl font-extrabold text-gray-900">
                Bilan classe — {{ $classe->nom }}
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                {{ $periode['label'] }} · du {{ $periode['debut']->format('d/m/Y') }} au {{ $periode['fin']->format('d/m/Y') }}
            </p>
        </div>
        <a href="{{ route('admin.rh.presences.bilan') }}" class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-brand-600 px-3 py-2 rounded-lg border border-gray-200 hover:border-brand-300 bg-white">
            ← Bilans
        </a>
    </div>

    {{-- Filtres trimestre --}}
    <form method="GET" class="flex gap-2 flex-wrap">
        @foreach($trimestres as $t)
            <button name="trimestre_id" value="{{ $t->id }}"
                    class="px-4 py-2 rounded-lg text-sm font-bold border
                    {{ $trimestre_id === $t->id ? 'bg-brand-600 text-white border-brand-600' : 'bg-white text-gray-700 border-gray-200 hover:border-brand-300' }}">
                T{{ $t->numero }}
            </button>
        @endforeach
    </form>

    {{-- Stats classe --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white rounded-2xl shadow-card p-4 text-center">
            <p class="text-xs uppercase font-bold text-gray-500">Élèves</p>
            <p class="text-2xl font-extrabold text-gray-900 mt-1">{{ $bilan['totaux']['nb_eleves'] }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-card p-4 text-center">
            <p class="text-xs uppercase font-bold text-gray-500">Appels</p>
            <p class="text-2xl font-extrabold text-blue-600 mt-1">{{ $bilan['totaux']['total'] }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-card p-4 text-center">
            <p class="text-xs uppercase font-bold text-gray-500">Absences</p>
            <p class="text-2xl font-extrabold text-red-600 mt-1">{{ $bilan['totaux']['absents'] }}</p>
            <p class="text-xs text-gray-500">{{ $bilan['totaux']['non_justifies'] }} non just.</p>
        </div>
        <div class="bg-white rounded-2xl shadow-card p-4 text-center">
            <p class="text-xs uppercase font-bold text-gray-500">Retards</p>
            <p class="text-2xl font-extrabold text-amber-600 mt-1">{{ $bilan['totaux']['retards'] }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-card p-4 text-center">
            <p class="text-xs uppercase font-bold text-gray-500">Heures perdues</p>
            <p class="text-2xl font-extrabold text-purple-700 mt-1">{{ number_format($bilan['totaux']['heures_absence'], 1) }}h</p>
            <p class="text-xs text-gray-500">{{ number_format($bilan['totaux']['moyenne_heures_absence_par_eleve'], 1) }}h / élève</p>
        </div>
    </div>

    {{-- Tableau élèves --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-bold text-gray-800">Bilan par élève</h2>
            <span class="text-xs text-gray-500">Trié par absences décroissant</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">N°</th>
                        <th class="px-4 py-3 text-left">Élève</th>
                        <th class="px-4 py-3 text-left">DSPS</th>
                        <th class="px-4 py-3 text-center">Appels</th>
                        <th class="px-4 py-3 text-center text-red-600">Absences</th>
                        <th class="px-4 py-3 text-center text-amber-600">Retards</th>
                        <th class="px-4 py-3 text-center">Justif.</th>
                        <th class="px-4 py-3 text-center text-purple-700">Heures</th>
                        <th class="px-4 py-3 text-center">Taux</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @php
                        $eleves = collect($bilan['eleves'])->sortByDesc('absents')->values();
                    @endphp
                    @forelse($eleves as $i => $e)
                        <tr class="hover:bg-gray-50/50">
                            <td class="px-4 py-3 text-gray-500">{{ $i + 1 }}</td>
                            <td class="px-4 py-3 font-semibold text-gray-800">{{ $e['eleve']['prenom'] }} {{ strtoupper($e['eleve']['nom']) }}</td>
                            <td class="px-4 py-3 text-xs font-mono text-gray-500">{{ $e['eleve']['matricule_desps'] ?? '—' }}</td>
                            <td class="px-4 py-3 text-center">{{ $e['total'] }}</td>
                            <td class="px-4 py-3 text-center font-bold text-red-600">{{ $e['absents'] }}</td>
                            <td class="px-4 py-3 text-center font-bold text-amber-600">{{ $e['retards'] }}</td>
                            <td class="px-4 py-3 text-center text-xs">
                                {{ $e['justifies'] }} just.
                                @if($e['non_justifies'] > 0)
                                    <br><span class="text-red-600 font-bold">{{ $e['non_justifies'] }} non</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center font-bold text-purple-700">{{ number_format($e['heures_absence'], 1) }}h</td>
                            <td class="px-4 py-3 text-center">
                                @if($e['taux_absence'] > 0)
                                    <span class="text-xs px-2 py-1 rounded-full font-bold
                                        {{ $e['taux_absence'] > 20 ? 'bg-red-100 text-red-700' : ($e['taux_absence'] > 10 ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600') }}">
                                        {{ $e['taux_absence'] }}%
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.rh.presences.bilan.eleve', $e['eleve']['id']) }}?{{ http_build_query(request()->only(['trimestre_id', 'date_debut', 'date_fin'])) }}"
                                   class="text-brand-600 hover:text-brand-700 text-xs font-bold">
                                    Détail →
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="px-4 py-8 text-center text-gray-400">Aucun élève.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
