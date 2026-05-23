@extends('layouts.app')

@section('title', 'Générer l\'emploi du temps')
@section('page-title', 'Générer l\'emploi du temps')
@section('page-subtitle', 'Vérifiez les prérequis puis lancez la génération IA')

@section('content')
@php
    $iaConfig = $iaConfig ?? [];
    $lastIaRun = $lastIaRun ?? null;
    $vacataires = collect($vacataires ?? []);
    $vacataireImports = collect($vacataireImports ?? []);
    $vacatairesStats = $vacatairesStats ?? [
        'vacataires_total' => 0,
        'imports_total' => 0,
        'imports_uploades' => 0,
        'imports_parsed' => 0,
        'imports_valides' => 0,
    ];

    $modeLabel = match($iaConfig['mode_generation_defaut'] ?? 'prive_equilibre') {
        'strict_officiel'       => 'Officiel strict',
        'prive_equilibre'       => 'Privé équilibré',
        'prive_contraint'       => 'Privé contraint',
        'provisoire_vacataires' => 'Provisoire vacataires',
        default => ucfirst(str_replace('_', ' ', $iaConfig['mode_generation_defaut'] ?? '')),
    };

    $validatedVacataireIds = $vacataireImports
        ->where('status', 'validated')
        ->pluck('enseignant_id')
        ->map(fn ($v) => (int) $v)
        ->merge(collect($externalCoveredIds ?? []))
        ->unique()
        ->values();

    $vacatairesMissing = $vacataires
        ->filter(fn ($v) => !$validatedVacataireIds->contains((int) $v->id))
        ->values();

    $generationBloquee =
        !empty($iaConfig['attendre_horaires_vacataires'])
        && !empty($iaConfig['bloquer_si_vacataire_sans_horaire'])
        && $vacatairesMissing->isNotEmpty();

    // Prérequis
    $nbClasses  = $statsIa['classes']  ?? 0;
    $nbSalles   = $statsIa['salles']   ?? 0;
    $nbCreneaux = $statsIa['creneaux'] ?? 0;
    $prereqOk   = $nbClasses > 0 && $nbCreneaux > 0;

    $oldPortee       = old('portee', 'globale');
    $oldForce        = old('force_generate_without_vacataires') == '1';
    $selectedClasses = collect(old('scope_classes', []))->map(fn ($v) => (int) $v)->all();
@endphp

<div class="max-w-3xl mx-auto space-y-6">

    {{-- ── Alertes ────────────────────────────────────────────────────── --}}
    @foreach (['success' => 'emerald', 'warning' => 'amber', 'error' => 'red'] as $key => $color)
        @if(session($key))
            <div class="rounded-2xl border border-{{ $color }}-200 bg-{{ $color }}-50 px-5 py-4 text-sm text-{{ $color }}-800">
                {{ session($key) }}
            </div>
        @endif
    @endforeach

    @if($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800">
            <p class="font-bold mb-1">Erreur :</p>
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- ── ÉTAPE 1 : Prérequis ───────────────────────────────────────── --}}
    <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
        <h2 class="text-sm font-extrabold uppercase tracking-wide text-gray-500 mb-4">
            Étape 1 — Prérequis
        </h2>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            {{-- Classes --}}
            <div class="flex items-center gap-3 rounded-xl border px-4 py-3
                {{ $nbClasses > 0 ? 'border-emerald-200 bg-emerald-50' : 'border-red-200 bg-red-50' }}">
                <span class="text-lg {{ $nbClasses > 0 ? 'text-emerald-500' : 'text-red-400' }}">
                    {{ $nbClasses > 0 ? '✓' : '✗' }}
                </span>
                <div>
                    <p class="text-[10px] font-bold uppercase {{ $nbClasses > 0 ? 'text-emerald-600' : 'text-red-500' }}">Classes</p>
                    <p class="text-xl font-extrabold {{ $nbClasses > 0 ? 'text-emerald-800' : 'text-red-700' }}">{{ $nbClasses }}</p>
                </div>
            </div>

            {{-- Créneaux --}}
            <div class="flex items-center gap-3 rounded-xl border px-4 py-3
                {{ $nbCreneaux > 0 ? 'border-emerald-200 bg-emerald-50' : 'border-red-200 bg-red-50' }}">
                <span class="text-lg {{ $nbCreneaux > 0 ? 'text-emerald-500' : 'text-red-400' }}">
                    {{ $nbCreneaux > 0 ? '✓' : '✗' }}
                </span>
                <div>
                    <p class="text-[10px] font-bold uppercase {{ $nbCreneaux > 0 ? 'text-emerald-600' : 'text-red-500' }}">Créneaux</p>
                    <p class="text-xl font-extrabold {{ $nbCreneaux > 0 ? 'text-emerald-800' : 'text-red-700' }}">{{ $nbCreneaux }}</p>
                </div>
            </div>

            {{-- Salles --}}
            <div class="flex items-center gap-3 rounded-xl border px-4 py-3
                {{ $nbSalles > 0 ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50' }}">
                <span class="text-lg {{ $nbSalles > 0 ? 'text-emerald-500' : 'text-amber-400' }}">
                    {{ $nbSalles > 0 ? '✓' : '!' }}
                </span>
                <div>
                    <p class="text-[10px] font-bold uppercase {{ $nbSalles > 0 ? 'text-emerald-600' : 'text-amber-600' }}">Salles</p>
                    <p class="text-xl font-extrabold {{ $nbSalles > 0 ? 'text-emerald-800' : 'text-amber-700' }}">
                        {{ $nbSalles > 0 ? $nbSalles : '0' }}
                    </p>
                </div>
            </div>

            {{-- Vacataires --}}
            @php
                $nbVac = $vacataires->count();
                $nbVacOk = $nbVac - $vacatairesMissing->count();
                $vacOk = $vacatairesMissing->isEmpty() || empty($iaConfig['attendre_horaires_vacataires']);
            @endphp
            <div class="flex items-center gap-3 rounded-xl border px-4 py-3
                {{ $vacOk ? 'border-emerald-200 bg-emerald-50' : ($generationBloquee ? 'border-red-200 bg-red-50' : 'border-amber-200 bg-amber-50') }}">
                <span class="text-lg {{ $vacOk ? 'text-emerald-500' : ($generationBloquee ? 'text-red-400' : 'text-amber-400') }}">
                    {{ $vacOk ? '✓' : ($generationBloquee ? '✗' : '!') }}
                </span>
                <div>
                    <p class="text-[10px] font-bold uppercase {{ $vacOk ? 'text-emerald-600' : ($generationBloquee ? 'text-red-500' : 'text-amber-600') }}">Vacataires</p>
                    <p class="text-xl font-extrabold {{ $vacOk ? 'text-emerald-800' : ($generationBloquee ? 'text-red-700' : 'text-amber-700') }}">
                        {{ $nbVacOk }}/{{ $nbVac }}
                    </p>
                </div>
            </div>
        </div>

        @if(!$prereqOk)
            <div class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                La génération nécessite au moins <strong>1 classe active</strong> et <strong>1 créneau de cours</strong>.
                <a href="{{ route('emploi-du-temps.parametres.edit') }}" class="underline font-bold ml-1">Configurer →</a>
            </div>
        @endif

        @if($nbSalles === 0)
            <p class="mt-3 text-xs text-amber-700">
                ⚠ Aucune salle configurée — l'IA peut générer sans salles, mais les conflits ne seront pas détectés.
                <a href="{{ route('emploi-du-temps.parametres.edit') }}" class="underline font-semibold">Ajouter des salles →</a>
            </p>
        @endif

        @if($vacatairesMissing->isNotEmpty())
            <div class="mt-4 rounded-xl border {{ $generationBloquee ? 'border-red-200 bg-red-50' : 'border-amber-200 bg-amber-50' }} px-4 py-3 text-sm">
                <p class="font-bold {{ $generationBloquee ? 'text-red-800' : 'text-amber-800' }} mb-1">
                    {{ $generationBloquee ? '🔒 Blocage — vacataires sans horaire :' : '⚠ Vacataires sans horaire (avertissement) :' }}
                </p>
                <div class="flex flex-wrap gap-2 mt-1">
                    @foreach($vacatairesMissing as $v)
                        <a href="{{ route('emploi-du-temps.horaires-externes.index', $v) }}"
                           class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold border
                           {{ $generationBloquee ? 'bg-white border-red-200 text-red-700 hover:bg-red-50' : 'bg-white border-amber-200 text-amber-700 hover:bg-amber-50' }}">
                            {{ trim(($v->prenom ?? '') . ' ' . ($v->nom ?? '')) }}
                            <span class="text-[10px]">→ Ajouter EDT</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- ── ÉTAPE 2 : Configuration ────────────────────────────────────── --}}
    <div class="bg-white border border-violet-100 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-6 pt-6 pb-2">
            <h2 class="text-sm font-extrabold uppercase tracking-wide text-gray-500 mb-1">
                Étape 2 — Configuration
            </h2>
            <div class="flex items-center gap-3 mt-2">
                <span class="inline-flex px-3 py-1 rounded-full bg-violet-100 text-violet-700 text-xs font-bold">
                    Mode : {{ $modeLabel }}
                </span>
                <div class="flex flex-wrap gap-4">
                    <a href="{{ route('emploi-du-temps.parametres.edit', ['annee_scolaire_id' => $anneeActive?->id]) }}"
                       class="text-xs text-violet-600 hover:underline font-semibold">
                        Modifier les paramètres IA →
                    </a>
                    <a href="{{ route('emploi-du-temps.parametres.plages', ['annee_scolaire_id' => $anneeActive?->id]) }}"
                       class="text-xs text-violet-600 hover:underline font-semibold">
                        Plages matin/après-midi →
                    </a>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('emploi-du-temps.ia.generate') }}" id="form-generate" class="px-6 pb-6 pt-4 space-y-5">
            @csrf
            <input type="hidden" name="annee_scolaire_id" value="{{ $anneeActive?->id }}">
            <input type="hidden" name="apply_immediately" value="1">

            {{-- Portée --}}
            <div>
                <label class="block text-xs font-bold uppercase text-gray-600 mb-2">Portée de la génération</label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="cursor-pointer">
                        <input type="radio" name="portee" value="globale" class="sr-only peer"
                               @checked($oldPortee === 'globale')>
                        <div class="rounded-xl border-2 border-gray-200 peer-checked:border-violet-500 peer-checked:bg-violet-50 px-4 py-3 text-sm font-semibold text-gray-700 peer-checked:text-violet-800 transition">
                            <p class="font-bold">Toutes les classes</p>
                            <p class="text-xs text-gray-400 peer-checked:text-violet-600 mt-0.5">Génération complète — {{ $nbClasses }} classe(s)</p>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="portee" value="classes_selectionnees" class="sr-only peer"
                               @checked($oldPortee === 'classes_selectionnees')>
                        <div class="rounded-xl border-2 border-gray-200 peer-checked:border-violet-500 peer-checked:bg-violet-50 px-4 py-3 text-sm font-semibold text-gray-700 peer-checked:text-violet-800 transition">
                            <p class="font-bold">Certaines classes</p>
                            <p class="text-xs text-gray-400 mt-0.5">Sélection manuelle</p>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Classes ciblées (visible si portée = classes_selectionnees) --}}
            <div id="scope-classes-block" class="{{ $oldPortee === 'classes_selectionnees' ? '' : 'hidden' }}">
                <label class="block text-xs font-bold uppercase text-gray-600 mb-2">Sélectionner les classes</label>
                <div class="rounded-xl border border-violet-100 overflow-hidden divide-y divide-gray-100">
                    @foreach($classes as $classe)
                        <label class="flex items-center gap-3 px-4 py-2.5 hover:bg-violet-50 cursor-pointer">
                            <input type="checkbox" name="scope_classes[]" value="{{ $classe->id }}"
                                   class="rounded accent-violet-600"
                                   @checked(in_array((int) $classe->id, $selectedClasses, true))>
                            <span class="text-sm font-semibold text-gray-800">{{ $classe->nom }}</span>
                            @if($classe->niveau)
                                <span class="ml-auto text-xs text-gray-400">{{ $classe->niveau->libelle }}</span>
                            @endif
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Force si vacataires manquants --}}
            @if($vacatairesMissing->isNotEmpty())
                <input type="hidden" name="force_generate_without_vacataires" value="0">
                <label class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 cursor-pointer">
                    <input type="checkbox" name="force_generate_without_vacataires" value="1"
                           class="mt-0.5 accent-amber-600" @checked($oldForce)>
                    <span class="text-sm text-amber-800">
                        <span class="font-bold">Forcer la génération</span> même si certains vacataires n'ont pas d'horaire validé
                    </span>
                </label>
            @else
                <input type="hidden" name="force_generate_without_vacataires" value="0">
            @endif
        </form>
    </div>

    {{-- ── ÉTAPE 3 : Lancer ──────────────────────────────────────────── --}}
    <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
        <h2 class="text-sm font-extrabold uppercase tracking-wide text-gray-500 mb-4">
            Étape 3 — Lancer
        </h2>

        <div class="flex flex-col sm:flex-row gap-3">
            {{-- Bouton principal : Générer avec l'IA --}}
            <button type="submit"
                    form="form-generate"
                    name="action_mode"
                    value="apply"
                    @disabled(!$prereqOk)
                    class="flex-1 flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl font-extrabold text-sm transition
                    {{ $prereqOk ? 'bg-violet-600 hover:bg-violet-700 text-white shadow-sm' : 'bg-gray-100 text-gray-400 cursor-not-allowed' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Générer l'emploi du temps
            </button>

            {{-- Grille manuelle --}}
            <a href="{{ $grilleEntry['route'] ?? route('emploi-du-temps.grille', ['annee_scolaire_id' => $anneeActive?->id]) }}"
               class="flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl border border-gray-200 text-gray-700 font-bold text-sm hover:bg-gray-50 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 17v-2m3 2v-4m3 4v-6M7 7h10M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z"/>
                </svg>
                Ajuster sur la grille
            </a>
        </div>

        @if(!$prereqOk)
            <p class="mt-3 text-xs text-red-600 text-center">
                La génération est désactivée — configurez d'abord les classes et créneaux.
            </p>
        @endif

        {{-- Dernier run IA --}}
        @if($lastIaRun)
            <div class="mt-5 rounded-xl border border-gray-100 bg-gray-50 px-4 py-3 flex items-center justify-between gap-4">
                <div class="text-sm">
                    <span class="font-bold text-gray-700">Dernier run IA</span>
                    <span class="ml-2 text-gray-500">#{{ $lastIaRun['id'] }}</span>
                    <span class="ml-3 inline-flex px-2 py-0.5 rounded-full text-xs font-bold
                        {{ $lastIaRun['status'] === 'completed' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-200 text-gray-600' }}">
                        {{ $lastIaRun['status'] }}
                    </span>
                    @if(!empty($lastIaRun['assignments_count']))
                        <span class="ml-2 text-gray-500">· {{ $lastIaRun['assignments_count'] }} affectations</span>
                    @endif
                </div>
                <a href="{{ route('emploi-du-temps.ia.report', $lastIaRun['id']) }}"
                   class="text-xs font-bold text-violet-600 hover:underline whitespace-nowrap">
                    Voir le rapport →
                </a>
            </div>
        @endif
    </div>

    {{-- ── Emplois du temps générés (liens rapides) ──────────────────── --}}
    @if(($statsIa['classes'] ?? 0) > 0)
        <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
            <h2 class="text-sm font-extrabold uppercase tracking-wide text-gray-500 mb-4">
                Consulter les emplois du temps
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <a href="{{ route('emploi-du-temps.index') }}"
                   class="flex items-center gap-3 rounded-xl border border-brand-100 bg-brand-50 px-4 py-3 hover:bg-brand-100 transition">
                    <svg class="w-5 h-5 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M19 21H5a2 2 0 01-2-2V7a2 2 0 012-2h11l5 5v9a2 2 0 01-2 2z M9 21V12h6v9"/>
                    </svg>
                    <div>
                        <p class="text-sm font-bold text-brand-800">Par classe</p>
                        <p class="text-xs text-brand-600">{{ $nbClasses }} classe(s)</p>
                    </div>
                </a>

                <a href="{{ route('emploi-du-temps.grille.pdf.professeurs', ['annee_scolaire_id' => $anneeActive?->id]) }}"
                   class="flex items-center gap-3 rounded-xl border border-indigo-100 bg-indigo-50 px-4 py-3 hover:bg-indigo-100 transition">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <div>
                        <p class="text-sm font-bold text-indigo-800">Par professeur</p>
                        <p class="text-xs text-indigo-600">Vue individuelle</p>
                    </div>
                </a>
            </div>
        </div>
    @endif

    {{-- ── Vacataires — section repliée ─────────────────────────────── --}}
    @if($vacataires->isNotEmpty())
        <details class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
            <summary class="flex items-center justify-between px-6 py-4 cursor-pointer select-none hover:bg-gray-50">
                <div class="flex items-center gap-3">
                    <span class="text-sm font-bold text-gray-700">Gestion des vacataires</span>
                    @if($vacatairesMissing->isNotEmpty())
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-700">
                            {{ $vacatairesMissing->count() }} en attente
                        </span>
                    @else
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">
                            Tous validés
                        </span>
                    @endif
                </div>
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </summary>

            <div class="px-6 pb-6 pt-2 border-t border-gray-100 divide-y divide-gray-100">
                @foreach($vacataires as $vacataire)
                    @php
                        $isOk = $validatedVacataireIds->contains((int) $vacataire->id);
                    @endphp
                    <div class="flex items-center justify-between py-3 gap-4">
                        <div class="flex items-center gap-3">
                            <span class="text-base {{ $isOk ? 'text-emerald-500' : 'text-amber-400' }}">
                                {{ $isOk ? '✓' : '!' }}
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">
                                    {{ trim(($vacataire->prenom ?? '') . ' ' . ($vacataire->nom ?? '')) }}
                                </p>
                                <p class="text-xs {{ $isOk ? 'text-emerald-600' : 'text-amber-600' }}">
                                    {{ $isOk ? 'Horaire validé' : 'Aucun horaire' }}
                                </p>
                            </div>
                        </div>
                        <a href="{{ route('emploi-du-temps.horaires-externes.index', $vacataire) }}"
                           class="text-xs font-bold px-3 py-1.5 rounded-lg
                           {{ $isOk ? 'border border-gray-200 text-gray-600 hover:bg-gray-50' : 'bg-amber-100 text-amber-700 hover:bg-amber-200' }}">
                            {{ $isOk ? 'Voir' : 'Ajouter EDT' }}
                        </a>
                    </div>
                @endforeach
            </div>
        </details>
    @endif

</div>

<script>
    // Affiche/cache les classes ciblées selon la portée choisie
    document.querySelectorAll('[name="portee"]').forEach(radio => {
        radio.addEventListener('change', () => {
            const block = document.getElementById('scope-classes-block');
            block.classList.toggle('hidden', radio.value !== 'classes_selectionnees');
        });
    });
</script>
@endsection
