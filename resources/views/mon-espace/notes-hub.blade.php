@extends('layouts.app')
@section('title', 'Notes & Devoirs — Centre de gestion')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6 space-y-5">

    {{-- En-tête + filtre période --}}
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="font-display text-2xl font-extrabold text-gray-900">Notes & Devoirs</h1>
            <p class="text-sm text-gray-500 mt-1">Centre de gestion de toute votre activité pédagogique</p>
        </div>

        <form method="GET" class="flex items-center gap-2">
            <label class="text-xs font-bold text-gray-500 uppercase">Période :</label>
            <select name="trimestre_id" onchange="this.form.submit()"
                    class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm font-semibold">
                <option value="">Toutes</option>
                @foreach($trimestres as $t)
                    <option value="{{ $t->id }}" {{ $trimId == $t->id ? 'selected' : '' }}>
                        {{ $t->libelle }} @if($t->en_cours) ★ @endif
                    </option>
                @endforeach
            </select>
        </form>
    </div>

    {{-- Stats principales --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-5 shadow-card border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-brand-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-extrabold text-gray-900">{{ $stats['nb_classes'] }}</p>
                    <p class="text-xs text-gray-500 font-medium">Classes · {{ $stats['nb_matieres'] }} matières</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-card border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-extrabold text-gray-900">{{ $stats['nb_evaluations'] }}</p>
                    <p class="text-xs text-gray-500 font-medium">
                        Évaluations
                        @if($stats['evals_a_saisir'] > 0)
                            · <span class="text-orange-600 font-bold">{{ $stats['evals_a_saisir'] }} à saisir</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-card border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gold-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-gold-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-extrabold text-gray-900">{{ $stats['nb_devoirs'] }}</p>
                    <p class="text-xs text-gray-500 font-medium">Devoirs publiés</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-card border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-purple-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-extrabold text-gray-900">{{ $moyennesSaisies->count() }}</p>
                    <p class="text-xs text-gray-500 font-medium">Lots de moyennes saisies</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Actions rapides par classe --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-bold text-gray-800">Mes classes — Actions rapides</h2>
        </div>
        @if($classes->isEmpty())
            <div class="px-5 py-10 text-center text-sm text-gray-400">Aucune classe affectée.</div>
        @else
        <div class="divide-y divide-gray-50">
            @foreach($classes as $classe)
            @php
                $matieres = $affectations->where('classe_id', $classe->id)->pluck('matiere');
            @endphp
            <div class="px-5 py-4 flex items-center justify-between flex-wrap gap-3">
                <div class="flex-1 min-w-0">
                    <p class="font-bold text-gray-900">{{ $classe->nom }}</p>
                    <div class="flex flex-wrap gap-1.5 mt-1">
                        @foreach($matieres as $m)
                            <span class="text-[10px] bg-brand-50 text-brand-700 font-bold px-2 py-0.5 rounded-full">{{ $m->code }}</span>
                        @endforeach
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('mon-espace.evaluations', $classe) }}"
                       class="text-xs font-bold bg-blue-50 hover:bg-blue-100 text-blue-700 px-3 py-2 rounded-lg flex items-center gap-1.5">
                        📋 Évaluations
                    </a>
                    <a href="{{ route('mon-espace.moyennes', $classe) }}"
                       class="text-xs font-bold bg-indigo-50 hover:bg-indigo-100 text-indigo-700 px-3 py-2 rounded-lg flex items-center gap-1.5">
                        📈 Moyennes
                    </a>
                    <a href="{{ route('mon-espace.devoirs', $classe) }}"
                       class="text-xs font-bold bg-gold-50 hover:bg-gold-100 text-gold-700 px-3 py-2 rounded-lg flex items-center gap-1.5">
                        📚 Devoirs
                    </a>
                    <a href="{{ route('mon-espace.feuille-de-note.index', $classe) }}"
                       class="text-xs font-bold bg-purple-50 hover:bg-purple-100 text-purple-700 px-3 py-2 rounded-lg flex items-center gap-1.5">
                        🗒️ Feuille de note
                    </a>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- 2 colonnes : Évaluations + Devoirs --}}
    <div class="grid lg:grid-cols-2 gap-5">

        {{-- Évaluations --}}
        <div class="bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-bold text-gray-800">📋 Mes évaluations</h2>
                <span class="text-xs text-gray-400 font-mono">{{ $evaluations->count() }}</span>
            </div>
            @if($evaluations->isEmpty())
                <div class="px-5 py-10 text-center text-sm text-gray-400">Aucune évaluation pour cette période.</div>
            @else
            <div class="divide-y divide-gray-50 max-h-96 overflow-y-auto">
                @foreach($evaluations->take(15) as $eval)
                @php
                    $statutColor = match($eval->statut) {
                        'cloturee'   => 'bg-green-100 text-green-700',
                        'en_saisie'  => 'bg-amber-100 text-amber-700',
                        'publiee'    => 'bg-blue-100 text-blue-700',
                        default      => 'bg-gray-100 text-gray-500',
                    };
                @endphp
                <div class="px-5 py-3 hover:bg-gray-50/50 transition">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-sm text-gray-800 truncate">{{ $eval->titre }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">
                                {{ $eval->classe?->nom }} · {{ $eval->matiere?->code }}
                                · {{ $eval->typeEvaluation?->nom }}
                                · {{ $eval->date_evaluation?->format('d/m/Y') }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $statutColor }}">
                                {{ strtoupper($eval->statut) }}
                            </span>
                            <span class="text-xs font-mono text-gray-500">{{ $eval->nb_notes }} notes</span>
                            <a href="{{ route('mon-espace.notes', $eval) }}"
                               class="text-xs font-bold text-blue-600 hover:text-blue-800">→</a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Devoirs --}}
        <div class="bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-bold text-gray-800">📚 Mes devoirs publiés</h2>
                <span class="text-xs text-gray-400 font-mono">{{ $devoirs->count() }}</span>
            </div>
            @if($devoirs->isEmpty())
                <div class="px-5 py-10 text-center text-sm text-gray-400">Aucun devoir publié.</div>
            @else
            <div class="divide-y divide-gray-50 max-h-96 overflow-y-auto">
                @foreach($devoirs as $d)
                @php
                    $typeColors = [
                        'devoir'=>'bg-blue-100 text-blue-700',
                        'exercice'=>'bg-brand-100 text-brand-700',
                        'tp'=>'bg-purple-100 text-purple-700',
                        'projet'=>'bg-orange-100 text-orange-700',
                        'lecture'=>'bg-gray-100 text-gray-600',
                        'interrogation'=>'bg-red-100 text-red-700',
                    ];
                @endphp
                <div class="px-5 py-3 hover:bg-gray-50/50 transition">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $typeColors[$d->type] ?? 'bg-gray-100' }}">
                                    {{ ucfirst($d->type) }}
                                </span>
                                <span class="text-[10px] font-bold text-gray-500">{{ $d->matiere?->code }}</span>
                                @if(!$d->publie)
                                    <span class="text-[10px] font-bold bg-gray-200 text-gray-500 px-2 py-0.5 rounded-full">Brouillon</span>
                                @endif
                            </div>
                            <p class="font-semibold text-sm text-gray-800 truncate">{{ $d->titre }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">
                                {{ $d->classe?->nom }} · Publié {{ $d->date_publication?->format('d/m/Y') }}
                                @if($d->date_limite)
                                    · Limite <b class="text-orange-600">{{ $d->date_limite->format('d/m/Y') }}</b>
                                @endif
                            </p>
                        </div>
                        @if($d->fichier_path)
                            <span class="text-blue-500 flex-shrink-0" title="Sujet attaché">📄</span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- Moyennes saisies par matière × classe --}}
    @if($moyennesSaisies->isNotEmpty())
    <div class="bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-bold text-gray-800">📈 Moyennes saisies — {{ $trimestres->firstWhere('id', $trimId)?->libelle }}</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-5 py-2 text-left text-xs font-bold text-gray-500 uppercase">Classe</th>
                        <th class="px-5 py-2 text-left text-xs font-bold text-gray-500 uppercase">Matière</th>
                        <th class="px-5 py-2 text-center text-xs font-bold text-gray-500 uppercase">Élèves saisis</th>
                        <th class="px-5 py-2 text-center text-xs font-bold text-gray-500 uppercase">Moy. classe</th>
                        <th class="px-5 py-2 text-right text-xs font-bold text-gray-500 uppercase"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($moyennesSaisies as $ms)
                    @php
                        $moy = (float) $ms->moy_classe;
                        $color = $moy >= 14 ? 'green' : ($moy >= 10 ? 'amber' : 'red');
                    @endphp
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-5 py-3 font-semibold text-gray-800">{{ $ms->classe?->nom }}</td>
                        <td class="px-5 py-3">{{ $ms->matiere?->nom }}</td>
                        <td class="px-5 py-3 text-center text-xs">{{ $ms->nb }}</td>
                        <td class="px-5 py-3 text-center">
                            <span class="text-base font-extrabold text-{{ $color }}-700">{{ number_format($moy, 2) }}</span>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('mon-espace.moyennes', ['classe' => $ms->classe_id, 'matiere_id' => $ms->matiere_id, 'trimestre_id' => $trimId]) }}"
                               class="text-xs font-bold text-indigo-600 hover:text-indigo-800">Modifier →</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
