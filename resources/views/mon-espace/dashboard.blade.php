@extends('layouts.app')
@section('title', 'Mon espace enseignant')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-8 space-y-6">

    {{-- Bonjour --}}
    <div class="flex items-center gap-4">
        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-brand-400 to-brand-700 flex items-center justify-center text-white font-extrabold text-2xl shadow-brand-glow flex-shrink-0">
            {{ strtoupper(substr($ens->prenom ?? '?', 0, 1)) }}
        </div>
        <div>
            <h1 class="font-display text-2xl font-extrabold text-gray-900">
                Bonjour, {{ $ens->prenom }} {{ $ens->nom }}
            </h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ ucfirst($ens->statut ?? '') }} · {{ $ens->specialite ?? 'Enseignant' }}</p>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-3 gap-4">
        @foreach([
            ['Classes', $nbClasses, 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2', 'brand'],
            ['Évaluations', $nbEvals, 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4', 'blue'],
            ['Devoirs', $nbDevoirs, 'M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z', 'gold'],
        ] as [$label, $val, $icon, $color])
        <div class="bg-white rounded-2xl p-5 shadow-card border border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-{{ $color }}-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-{{ $color }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $icon }}"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-extrabold text-gray-900">{{ $val }}</p>
                    <p class="text-xs text-gray-500 font-medium">{{ $label }}</p>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Aujourd'hui --}}
    <div class="bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-bold text-gray-800">
                Mes cours aujourd'hui
                @if($today)
                    <span class="ml-2 text-xs font-normal text-gray-400 capitalize">{{ $today }}</span>
                @endif
            </h2>
            <a href="{{ route('mon-espace.classes') }}"
               class="text-xs text-brand-600 hover:text-brand-700 font-semibold">Toutes mes classes →</a>
        </div>

        @if($seancesAujourdHui->isEmpty())
            <div class="px-5 py-10 text-center">
                <svg class="w-12 h-12 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <p class="text-sm text-gray-400 font-medium">
                    {{ $today ? 'Pas de cours aujourd\'hui.' : 'Aucun emploi du temps pour ce jour.' }}
                </p>
            </div>
        @else
            <div class="divide-y divide-gray-50">
                @foreach($seancesAujourdHui as $s)
                <div class="flex items-center gap-4 px-5 py-3 hover:bg-gray-50/60 transition">
                    <div class="text-center w-16 flex-shrink-0">
                        <p class="text-xs font-mono font-bold text-brand-600">
                            {{ $s->creneau ? \Carbon\Carbon::parse($s->creneau->heure_debut)->format('H:i') : '—' }}
                        </p>
                        <p class="text-[10px] text-gray-400">
                            {{ $s->creneau ? \Carbon\Carbon::parse($s->creneau->heure_fin)->format('H:i') : '' }}
                        </p>
                    </div>
                    <div class="w-px h-8 bg-brand-200 flex-shrink-0"></div>
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-gray-800 text-sm">{{ $s->matiere->nom ?? '—' }}</p>
                        <p class="text-xs text-gray-500">{{ $s->classe->nom ?? '' }} · {{ $s->salle->nom ?? 'Salle non définie' }}</p>
                    </div>
                    <a href="{{ route('mon-espace.eleves', $s->classe_id) }}"
                       class="text-xs text-brand-600 hover:text-brand-700 font-semibold flex-shrink-0">
                        Voir la classe →
                    </a>
                </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Accès rapides --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        @foreach([
            ['Mes classes', 'mon-espace.classes', 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'],
            ['Pointage', 'pointage.index', 'M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z'],
            ['Mon emploi du temps', 'emploi-du-temps.professeur', 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
            ['Notes & Bulletins', 'notes.index', 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
        ] as [$label, $route, $icon])
        @php try { $url = route($route, $route === 'emploi-du-temps.professeur' ? ['enseignant' => $ens->id] : []); } catch(\Exception $e) { $url = '#'; } @endphp
        <a href="{{ $url }}"
           class="bg-white rounded-2xl p-4 shadow-card border border-gray-100 hover:shadow-card-brand hover:border-brand-200 transition group text-center">
            <svg class="w-8 h-8 text-brand-400 group-hover:text-brand-600 mx-auto mb-2 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="{{ $icon }}"/></svg>
            <p class="text-xs font-bold text-gray-700 group-hover:text-brand-700 transition">{{ $label }}</p>
        </a>
        @endforeach
    </div>

</div>
@endsection
