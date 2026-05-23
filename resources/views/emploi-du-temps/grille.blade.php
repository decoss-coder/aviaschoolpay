{{-- resources/views/emploi-du-temps/grille.blade.php --}}
@extends('layouts.app')

@section('title', 'Grille Emploi du Temps')
@section('page-title', 'Grille Emploi du Temps')
@section('page-subtitle', 'Ajustez les séances, imprimez les PDF et préparez les prochaines générations IA')

@section('content')
@php
    $creneauxCoursLabels = $creneaux
        ->filter(fn ($c) => (($c->type ?? 'cours') === 'cours'))
        ->mapWithKeys(function ($c) {
            $debut = $c->heure_debut ? \Carbon\Carbon::parse($c->heure_debut)->format('H:i') : '';
            $fin = $c->heure_fin ? \Carbon\Carbon::parse($c->heure_fin)->format('H:i') : '';
            $label = $c->libelle ?: trim($debut . ' – ' . $fin);

            return [$c->id => $label];
        })
        ->toArray();

    $totalSeances = collect($grid ?? [])
        ->flatMap(fn ($joursGrid) => collect($joursGrid)->map(fn ($items) => collect($items)->count()))
        ->sum();

    $totalConflits = count($conflits ?? []);
@endphp

<div
    x-data="grilleEdt()"
    x-init="init()"
    @keydown.escape.window="closeModal()"
    class="space-y-5"
>
    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800 shadow-sm">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800 shadow-sm">
            {{ session('error') }}
        </div>
    @endif

    @if($totalSeances === 0 && !empty($anneeActive?->archive_meta['restaurer_le']))
        <div class="rounded-2xl border border-violet-200 bg-violet-50 px-5 py-4 text-sm text-violet-900 shadow-sm">
            <p class="font-bold">Aucun créneau pour cette année restaurée</p>
            <p class="mt-1 text-violet-800">
                Lors de la première archivage, les emplois du temps n'étaient pas inclus dans la sauvegarde et ont été supprimés avec les classes.
                Si vous disposez encore du fichier d'archive (.enc) et de la clé, effectuez une <strong>nouvelle restauration</strong> (les archives créées après mise à jour incluent l'EDT).
                Sinon, il faudra ressaisir ou régénérer l'emploi du temps (lecture seule : consultation uniquement).
            </p>
        </div>
    @endif

    {{-- Synthèse --}}
    <div class="grid grid-cols-2 xl:grid-cols-5 gap-4">
        <div class="bg-white border border-brand-100 rounded-2xl p-4 shadow-card-brand">
            <p class="text-xs font-bold uppercase text-gray-500">Année active</p>
            <p class="mt-2 text-2xl font-extrabold text-gray-900">{{ $anneeActive?->libelle ?? '—' }}</p>
        </div>

        <div class="bg-white border border-brand-100 rounded-2xl p-4 shadow-card-brand">
            <p class="text-xs font-bold uppercase text-gray-500">Vue</p>
            <p class="mt-2 text-2xl font-extrabold text-brand-700">{{ ucfirst($vue ?? 'classe') }}</p>
        </div>

        <div class="bg-white border border-brand-100 rounded-2xl p-4 shadow-card-brand">
            <p class="text-xs font-bold uppercase text-gray-500">Créneaux affichés</p>
            <p class="mt-2 text-2xl font-extrabold text-gray-900">{{ $creneaux->count() }}</p>
        </div>

        <div class="bg-white border border-emerald-100 rounded-2xl p-4 shadow-sm">
            <p class="text-xs font-bold uppercase text-emerald-600">Séances visibles</p>
            <p class="mt-2 text-2xl font-extrabold text-emerald-700">{{ $totalSeances }}</p>
        </div>

        <div class="bg-white border border-red-100 rounded-2xl p-4 shadow-sm">
            <p class="text-xs font-bold uppercase text-red-600">Conflits</p>
            <p class="mt-2 text-2xl font-extrabold text-red-700">{{ $totalConflits }}</p>
        </div>
    </div>

    {{-- Pilotage IA --}}
    <div class="space-y-4">
        <div class="bg-white border border-brand-100 rounded-2xl px-5 py-4 shadow-card-brand">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h3 class="text-base font-extrabold text-gray-900">Pilotage IA et apprentissage</h3>
                    <p class="text-sm text-gray-500">
                        Cette grille est la surface unique d’ajustement après génération IA.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a href="{{ $parametresRoute ?? route('emploi-du-temps.parametres.edit', ['annee_scolaire_id' => $anneeActive?->id]) }}"
                       class="px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-bold text-gray-700 hover:bg-gray-50 transition">
                        Paramètres IA
                    </a>

                    <a href="{{ $iaCreateRoute ?? route('emploi-du-temps.create', ['annee_scolaire_id' => $anneeActive?->id]) }}"
                       class="px-4 py-2.5 bg-violet-600 text-white rounded-xl text-sm font-bold hover:bg-violet-700 transition">
                        Génération IA
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                <div class="rounded-2xl bg-violet-50/60 p-4">
                    <p class="text-[10px] uppercase font-extrabold text-violet-600">Dernière génération appliquée</p>
                    <p class="mt-2 text-sm font-extrabold text-violet-800 break-all">
                        {{ $lastAppliedGenerationUuid ?: 'Aucune génération IA appliquée' }}
                    </p>
                </div>

                <div class="rounded-2xl bg-amber-50/60 p-4">
                    <p class="text-[10px] uppercase font-extrabold text-amber-600">Apprentissage des ajustements</p>
                    <p class="mt-2 text-sm font-extrabold text-amber-800">
                        {{ $adjustmentsLearningEnabled ? 'Activé' : 'Désactivé' }}
                    </p>
                </div>

                <div class="rounded-2xl bg-brand-50/60 p-4">
                    <p class="text-[10px] uppercase font-extrabold text-brand-600">Ajustements détectés</p>
                    <p class="mt-2 text-sm font-extrabold text-brand-800">
                        {{ $adjustmentsCount ?? 0 }}
                    </p>
                </div>
            </div>
        </div>

        @if($adjustmentsLearningEnabled)
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800 shadow-sm">
                <span class="font-extrabold">Apprentissage actif :</span>
                les ajustements effectués dans cette grille pourront être pris en compte par les prochaines générations IA.
            </div>
        @endif
    </div>

    {{-- Barre filtres + actions --}}
    <div class="bg-white border border-brand-100 rounded-2xl px-5 py-4 shadow-card-brand">
        <form method="GET" action="{{ route('emploi-du-temps.grille') }}"
              class="flex flex-wrap items-end gap-3">

            <div>
                <label class="block text-[10px] font-extrabold uppercase text-gray-500 mb-1">Année scolaire</label>
                <select name="annee_scolaire_id" onchange="this.form.submit()"
                        class="px-3 py-2 text-sm border border-brand-100 rounded-xl bg-white">
                    @foreach($annees as $annee)
                        <option value="{{ $annee->id }}" @selected($annee->id == $anneeActive?->id)>
                            {{ $annee->libelle }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-extrabold uppercase text-gray-500 mb-1">Vue par</label>
                <select name="vue" onchange="this.form.submit()"
                        class="px-3 py-2 text-sm border border-brand-100 rounded-xl bg-white">
                    <option value="classe" @selected(($vue ?? 'classe') === 'classe')>Classe</option>
                    <option value="enseignant" @selected(($vue ?? 'classe') === 'enseignant')>Enseignant</option>
                </select>
            </div>

            @if(($vue ?? 'classe') === 'classe')
                <div>
                    <label class="block text-[10px] font-extrabold uppercase text-gray-500 mb-1">Classe</label>
                    <select name="classe_id" onchange="this.form.submit()"
                            class="px-3 py-2 text-sm border border-brand-100 rounded-xl bg-white">
                        <option value="">Toutes</option>
                        @foreach($classes as $classe)
                            <option value="{{ $classe->id }}" @selected($classe->id == request('classe_id'))>
                                {{ $classe->nom }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @else
                <div>
                    <label class="block text-[10px] font-extrabold uppercase text-gray-500 mb-1">Enseignant</label>
                    <select name="enseignant_id" onchange="this.form.submit()"
                            class="px-3 py-2 text-sm border border-brand-100 rounded-xl bg-white">
                        <option value="">Tous</option>
                        @foreach($enseignants as $ens)
                            <option value="{{ $ens->id }}" @selected($ens->id == request('enseignant_id'))>
                                {{ $ens->nom_complet }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="flex flex-wrap gap-2 ml-auto">
                @if(($vue ?? 'classe') === 'classe')
                    @if(request('classe_id'))
                        <a href="{{ route('emploi-du-temps.grille.pdf.classe', array_filter([
                            'annee_scolaire_id' => $anneeActive?->id,
                            'classe_id' => request('classe_id'),
                        ])) }}"
                           class="px-4 py-2 text-sm font-bold text-red-700 border border-red-200 rounded-xl bg-red-50 hover:bg-red-100 transition">
                            PDF classe
                        </a>
                    @endif

                    <a href="{{ route('emploi-du-temps.grille.pdf.classes', array_filter([
                        'annee_scolaire_id' => $anneeActive?->id,
                        'classe_id' => request('classe_id'),
                    ])) }}"
                       class="px-4 py-2 text-sm font-bold text-red-700 border border-red-200 rounded-xl bg-red-50 hover:bg-red-100 transition">
                        PDF classes
                    </a>

                    <button type="button"
                            @click="customPrint.open = true; customPrint.type = 'classes'"
                            class="px-4 py-2 text-sm font-bold text-red-700 border border-red-200 rounded-xl bg-white hover:bg-red-50 transition">
                        PDF classes personnalisé
                    </button>
                @else
                    @if(request('enseignant_id'))
                        <a href="{{ route('emploi-du-temps.grille.pdf.professeur', array_filter([
                            'annee_scolaire_id' => $anneeActive?->id,
                            'enseignant_id' => request('enseignant_id'),
                        ])) }}"
                           class="px-4 py-2 text-sm font-bold text-red-700 border border-red-200 rounded-xl bg-red-50 hover:bg-red-100 transition">
                            PDF professeur
                        </a>
                    @endif

                    <a href="{{ route('emploi-du-temps.grille.pdf.professeurs', array_filter([
                        'annee_scolaire_id' => $anneeActive?->id,
                        'enseignant_id' => request('enseignant_id'),
                    ])) }}"
                       class="px-4 py-2 text-sm font-bold text-red-700 border border-red-200 rounded-xl bg-red-50 hover:bg-red-100 transition">
                        PDF professeurs
                    </a>

                    <button type="button"
                            @click="customPrint.open = true; customPrint.type = 'professeurs'"
                            class="px-4 py-2 text-sm font-bold text-red-700 border border-red-200 rounded-xl bg-white hover:bg-red-50 transition">
                        PDF professeurs personnalisé
                    </button>
                @endif

                <a href="{{ route('emploi-du-temps.index') }}"
                   class="px-4 py-2 text-sm font-bold text-gray-600 border border-gray-200 rounded-xl bg-white hover:bg-gray-50 transition">
                    ← Liste
                </a>

                <a href="{{ $parametresRoute ?? route('emploi-du-temps.parametres.edit', ['annee_scolaire_id' => $anneeActive?->id]) }}"
                   class="px-4 py-2 text-sm font-bold text-gray-700 border border-gray-200 rounded-xl bg-white hover:bg-gray-50 transition">
                    Paramètres IA
                </a>

                <a href="{{ $iaCreateRoute ?? route('emploi-du-temps.create', ['annee_scolaire_id' => $anneeActive?->id]) }}"
                   class="px-4 py-2 text-sm font-bold text-violet-700 border border-violet-200 rounded-xl bg-violet-50 hover:bg-violet-100 transition">
                    Génération IA
                </a>

                <a href="{{ route('emploi-du-temps.conflits', ['annee_scolaire_id' => $anneeActive?->id]) }}"
                   class="px-4 py-2 text-sm font-bold text-amber-700 border border-amber-200 rounded-xl bg-amber-50 hover:bg-amber-100 transition">
                    ⚠ Conflits
                </a>
            </div>
        </form>
    </div>

    {{-- Grille --}}
    <div class="bg-white border border-brand-100 rounded-2xl shadow-card-brand overflow-hidden">
        @if(!empty($conflits))
            <div class="px-5 py-3 bg-red-50 border-b border-red-100 flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-red-500 animate-pulse"></span>
                <span class="text-xs font-bold text-red-700">
                    {{ count($conflits) }} conflit(s) détecté(s)
                </span>
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="w-full border-collapse" style="min-width: 920px">
                <thead>
                    <tr class="bg-gradient-to-b from-brand-50 to-white border-b-2 border-brand-100">
                        <th class="border-r border-brand-100 px-4 py-3 text-left text-[10px] font-extrabold uppercase text-brand-600 w-[120px]">
                            Horaire
                        </th>
                        @foreach($jours as $jour)
                            <th class="border-r border-brand-100 px-3 py-3 text-center text-[11px] font-extrabold uppercase text-brand-700 tracking-wide">
                                {{ ucfirst($jour) }}
                            </th>
                        @endforeach
                    </tr>
                </thead>

                <tbody class="divide-y divide-brand-50">
                    @foreach($creneaux as $creneau)
                        @php
                            $type = $creneau->type ?? 'cours';
                            $isRecreation = $type === 'recreation';
                            $isPause = $type === 'pause_dejeuner';

                            $heureDebut = $creneau->heure_debut ? \Carbon\Carbon::parse($creneau->heure_debut)->format('H:i') : '';
                            $heureFin = $creneau->heure_fin ? \Carbon\Carbon::parse($creneau->heure_fin)->format('H:i') : '';
                        @endphp

                        @if($isRecreation)
                            <tr class="bg-orange-50/60">
                                <td class="border-r border-brand-100 px-4 py-1.5 text-center">
                                    <span class="text-[9px] font-bold text-orange-500 leading-none">
                                        {{ $heureDebut }}<br>{{ $heureFin }}
                                    </span>
                                </td>
                                <td colspan="{{ count($jours) }}" class="py-2 text-center">
                                    <span class="text-[9px] font-extrabold uppercase tracking-[.6em] text-orange-400">
                                        R&nbsp;É&nbsp;C&nbsp;R&nbsp;É&nbsp;A&nbsp;T&nbsp;I&nbsp;O&nbsp;N
                                    </span>
                                </td>
                            </tr>
                        @elseif($isPause)
                            <tr class="bg-amber-50/60">
                                <td class="border-r border-brand-100 px-4 py-1.5 text-center">
                                    <span class="text-[9px] font-bold text-amber-500 leading-none">
                                        {{ $heureDebut }}<br>{{ $heureFin }}
                                    </span>
                                </td>
                                <td colspan="{{ count($jours) }}" class="py-2 text-center">
                                    <span class="text-[9px] font-extrabold uppercase tracking-[.5em] text-amber-400">
                                        P&nbsp;A&nbsp;U&nbsp;S&nbsp;E&nbsp;&nbsp;&nbsp;M&nbsp;I&nbsp;–&nbsp;J&nbsp;O&nbsp;U&nbsp;R&nbsp;N&nbsp;É&nbsp;E
                                    </span>
                                </td>
                            </tr>
                        @else
                            <tr class="group/row hover:bg-brand-50/20 transition-colors">
                                <td class="border-r border-brand-100 px-3 py-2 text-center align-middle">
                                    <span class="text-[10px] font-bold text-gray-600 leading-none">
                                        {{ $heureDebut }}<br>
                                        <span class="text-gray-400">{{ $heureFin }}</span>
                                    </span>
                                </td>

                                @foreach($jours as $jour)
                                    @php
                                        $seances = $grid[$jour][$creneau->id] ?? collect();
                                        $conflit = in_array($jour . '|' . $creneau->id, $conflitKeys ?? []);
                                    @endphp

                                    <td class="border-r border-brand-100 p-1 align-top {{ $conflit ? 'bg-red-50' : '' }}"
                                        style="height: 90px; min-width: 130px;">

                                        @foreach($seances as $seance)
                                            @php
                                                $groupe = strtolower($seance->matiere?->groupe ?? '');
                                                $colorClass = match (true) {
                                                    str_contains($groupe, 'scien') => 'bg-emerald-50 border-emerald-200 text-emerald-900',
                                                    str_contains($groupe, 'lettr') => 'bg-blue-50 border-blue-200 text-blue-900',
                                                    str_contains($groupe, 'math') => 'bg-violet-50 border-violet-200 text-violet-900',
                                                    str_contains($groupe, 'lang') => 'bg-amber-50 border-amber-200 text-amber-900',
                                                    default => 'bg-brand-50 border-brand-200 text-brand-900',
                                                };

                                                $source = $seance->source ?? 'manuel';
                                                $isIa = $source === 'ia';
                                                $isAdjusted = $source === 'ajustement';
                                                $isLocked = (bool) ($seance->locked_by_user ?? false);
                                            @endphp

                                            <div x-data="{ hover: false }"
                                                 @mouseenter="hover = true"
                                                 @mouseleave="hover = false"
                                                 class="relative h-full rounded-lg border {{ $colorClass }} {{ $conflit ? 'ring-1 ring-red-400' : '' }} cursor-pointer transition-all duration-150 hover:shadow-md hover:scale-[1.02]"
                                                 style="padding: 4px 6px; min-height: 78px;">

                                                <div class="absolute top-1 left-1 flex gap-1">
                                                    @if($isIa)
                                                        <span class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[8px] font-extrabold bg-brand-600 text-white">
                                                            IA
                                                        </span>
                                                    @elseif($isAdjusted)
                                                        <span class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[8px] font-extrabold bg-violet-600 text-white">
                                                            AJUSTÉ
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[8px] font-extrabold bg-gray-700 text-white">
                                                            MANUEL
                                                        </span>
                                                    @endif

                                                    @if($isLocked)
                                                        <span class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[8px] font-extrabold bg-amber-500 text-white">
                                                            🔒
                                                        </span>
                                                    @endif
                                                </div>

                                                <div class="flex flex-col items-center justify-center gap-[2px] text-center leading-none h-full pt-3">
                                                    <span class="text-[10px] font-extrabold truncate max-w-full">
                                                        {{ $seance->matiere?->code ?? $seance->matiere?->nom ?? '—' }}
                                                    </span>

                                                    <span class="text-[9px] font-semibold truncate opacity-80 max-w-full">
                                                        {{ $seance->classe?->nom ?? '—' }}
                                                    </span>

                                                    <span class="text-[8px] opacity-60 truncate uppercase max-w-full">
                                                        {{ $seance->salle?->nom ?? '—' }}
                                                    </span>

                                                    @if(($vue ?? 'classe') === 'classe')
                                                        <span class="text-[8px] opacity-50 truncate max-w-full">
                                                            {{ $seance->enseignant?->nom_complet ?? $seance->enseignant?->nom ?? 'Non affecté' }}
                                                        </span>
                                                    @endif

                                                    @if(!empty($seance->ia_score))
                                                        <span class="text-[8px] opacity-50 truncate max-w-full">
                                                            Score IA {{ number_format((float) $seance->ia_score, 0) }}%
                                                        </span>
                                                    @endif
                                                </div>

                                                <div x-show="hover"
                                                     x-transition:enter="transition ease-out duration-100"
                                                     x-transition:enter-start="opacity-0 scale-95"
                                                     x-transition:enter-end="opacity-100 scale-100"
                                                     class="absolute inset-0 flex items-center justify-center gap-1.5 bg-white/90 rounded-lg backdrop-blur-sm">

                                                    <button type="button"
                                                            @click.stop="openEdit({{ json_encode([
                                                                'id'                => $seance->id,
                                                                'annee_scolaire_id' => $seance->annee_scolaire_id,
                                                                'jour'              => $seance->jour,
                                                                'creneau_id'        => $seance->creneau_id,
                                                                'classe_id'         => $seance->classe_id,
                                                                'matiere_id'        => $seance->matiere_id,
                                                                'enseignant_id'     => $seance->enseignant_id,
                                                                'salle_id'          => $seance->salle_id,
                                                                'valide_du'         => $seance->valide_du?->format('Y-m-d'),
                                                                'valide_au'         => $seance->valide_au?->format('Y-m-d'),
                                                                'actif'             => (bool) $seance->actif,
                                                                'locked_by_user'    => (bool) ($seance->locked_by_user ?? false),
                                                                'lock_for_future'   => (bool) ($seance->locked_by_user ?? false),
                                                                'adjustment_reason' => '',
                                                            ]) }})"
                                                            class="p-1.5 bg-brand-600 text-white rounded-lg hover:bg-brand-700 transition shadow-sm"
                                                            title="Modifier">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                        </svg>
                                                    </button>

                                                    <button type="button"
                                                            @click.stop="openDelete({{ $seance->id }}, '{{ addslashes(($seance->matiere?->nom ?? '') . ' – ' . ($seance->classe?->nom ?? '')) }}')"
                                                            class="p-1.5 bg-red-600 text-white rounded-lg hover:bg-red-700 transition shadow-sm"
                                                            title="Supprimer">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                        </svg>
                                                    </button>
                                                </div>

                                                @if($conflit)
                                                    <span class="absolute top-0.5 right-0.5 w-2 h-2 rounded-full bg-red-500 animate-ping"></span>
                                                @endif
                                            </div>
                                        @endforeach

                                        @if($seances->isEmpty())
                                            <button type="button"
                                                    @click="openCreate('{{ $jour }}', {{ $creneau->id }}, {{ $anneeActive?->id ?? 'null' }})"
                                                    class="w-full h-full flex items-center justify-center rounded-lg border-2 border-dashed border-transparent text-gray-300 hover:border-brand-300 hover:text-brand-400 hover:bg-brand-50/40 transition-all duration-200 group/btn">
                                                <svg class="w-5 h-5 group-hover/btn:scale-110 transition-transform"
                                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"/>
                                                </svg>
                                            </button>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="px-5 py-3 border-t border-brand-100 flex flex-wrap items-center gap-4">
            <span class="text-[10px] font-bold uppercase text-gray-400">Légende :</span>

            @foreach([
                ['Sciences', 'bg-emerald-100 text-emerald-700'],
                ['Lettres', 'bg-blue-100 text-blue-700'],
                ['Maths', 'bg-violet-100 text-violet-700'],
                ['Langues', 'bg-amber-100 text-amber-700'],
                ['Autres', 'bg-brand-100 text-brand-700'],
                ['Récréation', 'bg-orange-100 text-orange-600'],
                ['Pause', 'bg-amber-100 text-amber-600'],
            ] as [$nom, $cls])
                <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg text-[10px] font-bold {{ $cls }}">
                    <span class="w-2 h-2 rounded-full bg-current opacity-60"></span>
                    {{ $nom }}
                </span>
            @endforeach

            <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg text-[10px] font-bold bg-brand-600 text-white">IA</span>
            <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg text-[10px] font-bold bg-violet-600 text-white">AJUSTÉ</span>
            <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg text-[10px] font-bold bg-amber-500 text-white">🔒 Verrouillé</span>
        </div>
    </div>

    {{-- Modal création / édition --}}
    <div x-show="modal.open && modal.mode !== 'delete'"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         @click.self="closeModal()"
         class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm px-4"
         style="display:none">

        <div x-show="modal.open && modal.mode !== 'delete'"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden">

            <div class="px-6 py-4 border-b border-brand-100 flex items-center justify-between bg-gradient-to-r from-brand-50 to-white">
                <div>
                    <h3 class="text-base font-extrabold text-gray-900"
                        x-text="modal.mode === 'create' ? 'Nouveau créneau' : 'Modifier le créneau'"></h3>
                    <p class="text-xs text-brand-600 font-semibold mt-0.5"
                       x-text="modal.jourLabel + ' · ' + modal.creneauLabel"></p>
                </div>

                <button @click="closeModal()"
                        class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-xl transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form :action="modal.mode === 'create'
                              ? '{{ route('emploi-du-temps.store') }}'
                              : '{{ url('emploi-du-temps') }}/' + modal.form.id"
                  method="POST"
                  class="px-6 py-5 space-y-4">
                @csrf
                <input type="hidden" name="_method" :value="modal.mode === 'create' ? 'POST' : 'PUT'">
                <input type="hidden" name="annee_scolaire_id" :value="modal.form.annee_scolaire_id">
                <input type="hidden" name="jour" :value="modal.form.jour">
                <input type="hidden" name="creneau_id" :value="modal.form.creneau_id">
                <input type="hidden" name="_redirect" value="{{ request()->fullUrl() }}">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-extrabold uppercase text-gray-500 mb-1">
                            Classe <span class="text-red-500">*</span>
                        </label>
                        <select name="classe_id" x-model="modal.form.classe_id"
                                class="w-full px-3 py-2 text-sm border border-brand-100 rounded-xl focus:ring-2 focus:ring-brand-300 outline-none">
                            <option value="">Sélectionner…</option>
                            @foreach($classes as $classe)
                                <option value="{{ $classe->id }}">{{ $classe->nom }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-extrabold uppercase text-gray-500 mb-1">
                            Matière <span class="text-red-500">*</span>
                        </label>
                        <select name="matiere_id" x-model="modal.form.matiere_id"
                                class="w-full px-3 py-2 text-sm border border-brand-100 rounded-xl focus:ring-2 focus:ring-brand-300 outline-none">
                            <option value="">Sélectionner…</option>
                            @foreach($matieres as $matiere)
                                <option value="{{ $matiere->id }}">{{ $matiere->nom }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-extrabold uppercase text-gray-500 mb-1">
                            Enseignant <span class="text-gray-400 normal-case">optionnel</span>
                        </label>
                        <select name="enseignant_id" x-model="modal.form.enseignant_id"
                                class="w-full px-3 py-2 text-sm border border-brand-100 rounded-xl focus:ring-2 focus:ring-brand-300 outline-none">
                            <option value="">Non affecté pour l’instant</option>
                            @foreach($enseignants as $ens)
                                <option value="{{ $ens->id }}">{{ $ens->nom_complet }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-extrabold uppercase text-gray-500 mb-1">
                            Salle <span class="text-red-500">*</span>
                        </label>
                        <select name="salle_id" x-model="modal.form.salle_id"
                                class="w-full px-3 py-2 text-sm border border-brand-100 rounded-xl focus:ring-2 focus:ring-brand-300 outline-none">
                            <option value="">Sélectionner…</option>
                            @foreach($salles as $salle)
                                <option value="{{ $salle->id }}">{{ $salle->nom }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-end pb-1">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="hidden" name="actif" value="0">
                            <input type="checkbox" name="actif" value="1" x-model="modal.form.actif"
                                   class="w-4 h-4 text-brand-600 rounded">
                            <span class="text-sm font-semibold text-gray-700">Actif</span>
                        </label>
                    </div>

                    <div>
                        <label class="block text-[10px] font-extrabold uppercase text-gray-500 mb-1">Valide du</label>
                        <input type="date" name="valide_du" x-model="modal.form.valide_du"
                               class="w-full px-3 py-2 text-sm border border-brand-100 rounded-xl focus:ring-2 focus:ring-brand-300 outline-none">
                    </div>

                    <div>
                        <label class="block text-[10px] font-extrabold uppercase text-gray-500 mb-1">Valide au</label>
                        <input type="date" name="valide_au" x-model="modal.form.valide_au"
                               class="w-full px-3 py-2 text-sm border border-brand-100 rounded-xl focus:ring-2 focus:ring-brand-300 outline-none">
                    </div>

                    <div class="md:col-span-2 rounded-xl border border-brand-100 bg-brand-50/40 p-3">
                        <label class="inline-flex items-center gap-2">
                            <input type="hidden" name="lock_for_future" value="0">
                            <input type="checkbox" name="lock_for_future" value="1" x-model="modal.form.lock_for_future">
                            <span class="text-sm font-semibold text-gray-700">
                                Conserver cet ajustement pour les prochaines générations IA
                            </span>
                        </label>

                        <div class="mt-3">
                            <label class="block text-[10px] font-extrabold uppercase text-gray-500 mb-1">
                                Motif de l’ajustement
                            </label>
                            <input type="text"
                                   name="adjustment_reason"
                                   x-model="modal.form.adjustment_reason"
                                   class="w-full px-3 py-2 text-sm border border-brand-100 rounded-xl"
                                   placeholder="Ex : professeur disponible seulement le mardi matin">
                        </div>
                    </div>
                </div>

                <div class="flex justify-between pt-2">
                    <button type="button"
                            @click="closeModal()"
                            class="px-5 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-bold text-gray-600 hover:bg-gray-50 transition">
                        Annuler
                    </button>

                    <button type="submit"
                            class="px-6 py-2.5 bg-gradient-to-r from-brand-500 via-brand-600 to-brand-700 text-white rounded-xl text-sm font-bold shadow-brand-glow hover:shadow-lg transition-all">
                        <span x-text="modal.mode === 'create' ? 'Enregistrer' : 'Mettre à jour'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal suppression --}}
    <div x-show="modal.open && modal.mode === 'delete'"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         @click.self="closeModal()"
         class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm px-4"
         style="display:none">

        <div x-show="modal.open && modal.mode === 'delete'"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center">

            <div class="w-12 h-12 bg-red-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </div>

            <h3 class="text-base font-extrabold text-gray-900 mb-1">Supprimer ce créneau ?</h3>
            <p class="text-sm text-gray-500 mb-5" x-text="modal.deleteLabel"></p>

            <form :action="'{{ url('emploi-du-temps') }}/' + modal.deleteId"
                  method="POST"
                  class="space-y-4">
                @csrf
                @method('DELETE')
                <input type="hidden" name="_redirect" value="{{ request()->fullUrl() }}">

                <div>
                    <label class="block text-[10px] font-extrabold uppercase text-gray-500 mb-1 text-left">
                        Motif de suppression
                    </label>
                    <input type="text"
                           name="adjustment_reason"
                           class="w-full px-3 py-2 text-sm border border-brand-100 rounded-xl"
                           placeholder="Ex : créneau non pertinent">
                </div>

                <div class="flex gap-3">
                    <button type="button"
                            @click="closeModal()"
                            class="flex-1 px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-bold text-gray-600 hover:bg-gray-50 transition">
                        Annuler
                    </button>
                    <button type="submit"
                            class="flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl text-sm font-bold transition">
                        Supprimer
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal impression personnalisée --}}
    <div x-show="customPrint.open"
         x-transition
         @click.self="customPrint.open = false"
         class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm px-4"
         style="display:none">

        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-brand-100 flex items-center justify-between bg-gradient-to-r from-brand-50 to-white">
                <div>
                    <h3 class="text-base font-extrabold text-gray-900">Impression PDF personnalisée</h3>
                    <p class="text-xs text-brand-600 font-semibold mt-0.5"
                       x-text="customPrint.type === 'classes' ? 'Sélection multiple de classes' : 'Sélection multiple de professeurs'"></p>
                </div>

                <button type="button"
                        @click="customPrint.open = false"
                        class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-xl transition">
                    ✕
                </button>
            </div>

            <div class="p-6">
                <template x-if="customPrint.type === 'classes'">
                    <form method="POST"
                          action="{{ route('emploi-du-temps.grille.pdf.classes.custom') }}"
                          class="space-y-4">
                        @csrf
                        <input type="hidden" name="annee_scolaire_id" value="{{ $anneeActive?->id }}">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-72 overflow-auto border border-brand-100 rounded-xl p-3">
                            @foreach($classes as $classe)
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                    <input type="checkbox"
                                           name="class_ids[]"
                                           value="{{ $classe->id }}"
                                           @checked(request('classe_id') == $classe->id)>
                                    <span>{{ $classe->nom }}</span>
                                </label>
                            @endforeach
                        </div>

                        <div class="flex justify-end gap-2">
                            <button type="button"
                                    @click="customPrint.open = false"
                                    class="px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-bold text-gray-600 hover:bg-gray-50 transition">
                                Annuler
                            </button>
                            <button type="submit"
                                    class="px-4 py-2.5 bg-red-600 text-white rounded-xl text-sm font-bold hover:bg-red-700 transition">
                                Télécharger le PDF
                            </button>
                        </div>
                    </form>
                </template>

                <template x-if="customPrint.type === 'professeurs'">
                    <form method="POST"
                          action="{{ route('emploi-du-temps.grille.pdf.professeurs.custom') }}"
                          class="space-y-4">
                        @csrf
                        <input type="hidden" name="annee_scolaire_id" value="{{ $anneeActive?->id }}">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-72 overflow-auto border border-brand-100 rounded-xl p-3">
                            @foreach($enseignants as $ens)
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                    <input type="checkbox"
                                           name="enseignant_ids[]"
                                           value="{{ $ens->id }}"
                                           @checked(request('enseignant_id') == $ens->id)>
                                    <span>{{ $ens->nom_complet }}</span>
                                </label>
                            @endforeach
                        </div>

                        <div class="flex justify-end gap-2">
                            <button type="button"
                                    @click="customPrint.open = false"
                                    class="px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-bold text-gray-600 hover:bg-gray-50 transition">
                                Annuler
                            </button>
                            <button type="submit"
                                    class="px-4 py-2.5 bg-red-600 text-white rounded-xl text-sm font-bold hover:bg-red-700 transition">
                                Télécharger le PDF
                            </button>
                        </div>
                    </form>
                </template>
            </div>
        </div>
    </div>
</div>

<script>
function grilleEdt() {
    const joursLabels = @json(collect($jours)->mapWithKeys(fn($j) => [$j => ucfirst($j)])->toArray());
    const creneauxLabels = @json($creneauxCoursLabels);

    const currentFilters = {
        vue: @json($vue ?? 'classe'),
        classe_id: @json(request('classe_id')),
        enseignant_id: @json(request('enseignant_id')),
    };

    const defaultValide = {
        du: '{{ $anneeActive?->date_debut?->format('Y-m-d') ?? '' }}',
        au: '{{ $anneeActive?->date_fin?->format('Y-m-d') ?? '' }}',
    };

    const anneeId = {{ $anneeActive?->id ?? 'null' }};

    return {
        modal: {
            open: false,
            mode: 'create',
            jourLabel: '',
            creneauLabel: '',
            deleteId: null,
            deleteLabel: '',
            form: {
                id: null,
                annee_scolaire_id: anneeId,
                jour: '',
                creneau_id: '',
                classe_id: '',
                matiere_id: '',
                enseignant_id: null,
                salle_id: '',
                valide_du: '',
                valide_au: '',
                actif: true,
                lock_for_future: true,
                adjustment_reason: '',
            },
        },

        customPrint: {
            open: false,
            type: 'classes',
        },

        init() {
            @if($errors->any() && session('modal_mode'))
                this.modal.open = true;
                this.modal.mode = '{{ session('modal_mode') }}';
                this.modal.form = Object.assign(this.modal.form, @json(session('modal_form', [])));
                this.modal.jourLabel = joursLabels[this.modal.form.jour] ?? '';
                this.modal.creneauLabel = creneauxLabels[this.modal.form.creneau_id] ?? '';
            @endif
        },

        openCreate(jour, creneauId, anneeId) {
            const classePrefill =
                currentFilters.vue === 'classe' && currentFilters.classe_id
                    ? String(currentFilters.classe_id)
                    : '';

            const enseignantPrefill =
                currentFilters.vue === 'enseignant' && currentFilters.enseignant_id
                    ? String(currentFilters.enseignant_id)
                    : null;

            this.modal.mode = 'create';
            this.modal.form = {
                id: null,
                annee_scolaire_id: anneeId,
                jour: jour,
                creneau_id: creneauId,
                classe_id: classePrefill,
                matiere_id: '',
                enseignant_id: enseignantPrefill,
                salle_id: '',
                valide_du: defaultValide.du,
                valide_au: defaultValide.au,
                actif: true,
                lock_for_future: true,
                adjustment_reason: '',
            };

            this.modal.jourLabel = joursLabels[jour] ?? jour;
            this.modal.creneauLabel = creneauxLabels[creneauId] ?? creneauId;
            this.modal.open = true;
        },

        openEdit(seance) {
            this.modal.mode = 'edit';
            this.modal.form = {
                ...seance,
                actif: !!seance.actif,
                lock_for_future: ('lock_for_future' in seance)
                    ? !!seance.lock_for_future
                    : !!seance.locked_by_user,
                adjustment_reason: seance.adjustment_reason ?? '',
                enseignant_id: seance.enseignant_id ?? null,
            };
            this.modal.jourLabel = joursLabels[seance.jour] ?? seance.jour;
            this.modal.creneauLabel = creneauxLabels[seance.creneau_id] ?? seance.creneau_id;
            this.modal.open = true;
        },

        openDelete(id, label) {
            this.modal.mode = 'delete';
            this.modal.deleteId = id;
            this.modal.deleteLabel = label;
            this.modal.open = true;
        },

        closeModal() {
            this.modal.open = false;
        },
    };
}
</script>
@endsection