{{-- resources/views/emploi-du-temps/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Emploi du temps')
@section('page-title', 'Emploi du temps')
@section('page-subtitle', 'Pilotage, synthèse et accès rapide à la grille d’ajustement')

@section('content')

@php
    $collection = $emplois->getCollection();

    $hasDetailFilters =
        request()->filled('classe_id') ||
        request()->filled('enseignant_id') ||
        request()->filled('salle_id') ||
        request()->filled('jour');

    $groupedByClasse = $collection->groupBy(function ($emploi) {
        return $emploi->classe?->nom ?? 'Sans classe';
    });

    $jourOrder = collect($jours)->values()->flip()->toArray();

    $groupedByJour = $collection
        ->sortBy(function ($emploi) use ($jourOrder) {
            $jourIndex = $jourOrder[$emploi->jour] ?? 999;
            $creneauOrdre = $emploi->creneau->ordre ?? 999;
            return sprintf('%03d-%03d-%09d', $jourIndex, $creneauOrdre, $emploi->id);
        })
        ->groupBy('jour');

    $resultCount = $collection->count();
@endphp

<div class="space-y-6">

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800 shadow-sm">
            {{ session('success') }}
        </div>
    @endif

    @if(($stats['total'] ?? 0) === 0)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900 shadow-sm">
            <p class="font-bold">Aucun créneau en base pour l'année affichée</p>
            <p class="mt-1">
                Lors de l'archivage, les emplois du temps ont été supprimés avec les classes.
                @if($anneePourRecup && $edtDansArchiveCourante > 0)
                    Le fichier d'archive chiffré de <strong>{{ $anneePourRecup->libelle }}</strong> contient encore
                    <strong>{{ $edtDansArchiveCourante }}</strong> créneau(x).
                @elseif($anneePourRecup && $edtDansArchiveCourante === 0)
                    L'archive disponible ne contient pas d'emploi du temps (sauvegarde antérieure à la correction) : ressaisie ou génération IA nécessaire.
                @else
                    Vérifiez une autre année dans le filtre ci-dessous
                    @foreach($annees as $annee)
                        @if(($edtCountsParAnnee[$annee->id] ?? 0) > 0)
                            — <strong>{{ $annee->libelle }}</strong> : {{ $edtCountsParAnnee[$annee->id] }} créneau(x)
                        @endif
                    @endforeach
                @endif
            </p>

            @if($anneePourRecup && $edtDansArchiveCourante > 0)
                <div class="mt-3 flex flex-col sm:flex-row sm:items-end gap-3 pt-3 border-t border-amber-200"
                     x-data="{ besoinCle: !{{ $anneePourRecup->restoration_key_vault ? 'true' : 'false' }} }">
                    <form method="POST" action="{{ route('admin.annees.reimporter-edt', $anneePourRecup) }}"
                          class="flex flex-col sm:flex-row sm:items-end gap-2 flex-1"
                          onsubmit="return confirm('Déchiffrer et réimporter {{ $edtDansArchiveCourante }} créneaux pour {{ $anneePourRecup->libelle }} ?');">
                        @csrf
                        <div x-show="besoinCle" x-cloak class="flex-1">
                            <label class="block text-xs font-bold uppercase text-amber-800 mb-1">🔑 Clé de chiffrement</label>
                            <input type="text" name="cle_restauration" maxlength="50"
                                   class="w-full rounded-lg border-2 border-amber-300 font-mono uppercase text-sm tracking-wider text-center focus:border-amber-500 focus:ring-amber-200"
                                   placeholder="XXXX-XXXX-XXXX-XXXX" />
                        </div>
                        <button type="submit"
                                class="px-5 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-sm font-bold shadow-md flex items-center gap-2 whitespace-nowrap">
                            🔓 Récupérer l'emploi du temps depuis l'archive
                        </button>
                    </form>
                    @if(! $anneePourRecup->restoration_key_vault)
                        <p class="text-xs text-amber-800 italic">La clé n'est pas en coffre — saisissez-la pour déchiffrer.</p>
                    @else
                        <p class="text-xs text-amber-700 italic">✓ Clé disponible dans le coffre Avia — déchiffrement automatique.</p>
                    @endif
                </div>
            @endif
        </div>
    @endif

    {{-- ── Stats ─────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white border border-brand-100 rounded-2xl p-4 shadow-card-brand">
            <p class="text-xs font-bold uppercase text-gray-500">Total créneaux</p>
            <p class="mt-2 text-3xl font-extrabold text-gray-900">{{ $stats['total'] }}</p>
        </div>

        <div class="bg-white border border-emerald-100 rounded-2xl p-4 shadow-sm">
            <p class="text-xs font-bold uppercase text-emerald-600">Actifs</p>
            <p class="mt-2 text-3xl font-extrabold text-emerald-700">{{ $stats['actifs'] }}</p>
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl p-4 shadow-sm">
            <p class="text-xs font-bold uppercase text-gray-600">Inactifs</p>
            <p class="mt-2 text-3xl font-extrabold text-gray-700">{{ $stats['inactifs'] }}</p>
        </div>

        <div class="bg-white border border-brand-100 rounded-2xl p-4 shadow-sm">
            <p class="text-xs font-bold uppercase text-brand-600">Résultats affichés</p>
            <p class="mt-2 text-3xl font-extrabold text-brand-700">{{ $resultCount }}</p>
        </div>
        
        
    </div>

    {{-- ── Filtres + actions ─────────────────────────────────────── --}}
    <div class="bg-white border border-brand-100 rounded-2xl p-5 shadow-card-brand">
        <div class="flex flex-col xl:flex-row xl:items-end justify-between gap-4">

            <form method="GET" class="flex flex-wrap gap-2">
                <select name="annee_scolaire_id"
                        class="px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm">
                    <option value="">Toutes les années</option>
                    @foreach($annees as $annee)
                        <option value="{{ $annee->id }}"
                                @selected((string) request('annee_scolaire_id', $anneeIdDefaut ?? '') === (string) $annee->id)>
                            {{ $annee->libelle }}@if(isset($edtCountsParAnnee[$annee->id])) ({{ $edtCountsParAnnee[$annee->id] }} créneaux)@endif
                        </option>
                    @endforeach
                </select>

                <select name="classe_id"
                        class="px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm">
                    <option value="">Toutes les classes</option>
                    @foreach($classes as $classe)
                        <option value="{{ $classe->id }}"
                                @selected(request('classe_id') == $classe->id)>
                            {{ $classe->nom }}
                        </option>
                    @endforeach
                </select>

                <select name="enseignant_id"
                        class="px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm">
                    <option value="">Tous les enseignants</option>
                    @foreach($enseignants as $enseignant)
                        <option value="{{ $enseignant->id }}"
                                @selected(request('enseignant_id') == $enseignant->id)>
                            {{ $enseignant->nom_complet }}
                        </option>
                    @endforeach
                </select>

                <select name="salle_id"
                        class="px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm">
                    <option value="">Toutes les salles</option>
                    @foreach($salles as $salle)
                        <option value="{{ $salle->id }}"
                                @selected(request('salle_id') == $salle->id)>
                            {{ $salle->nom }}
                        </option>
                    @endforeach
                </select>

                <select name="jour"
                        class="px-3 py-2.5 bg-white border border-brand-100 rounded-xl text-sm">
                    <option value="">Tous les jours</option>
                    @foreach($jours as $jour)
                        <option value="{{ $jour }}" @selected(request('jour') === $jour)>
                            {{ ucfirst($jour) }}
                        </option>
                    @endforeach
                </select>

                <button class="px-4 py-2.5 bg-white border border-brand-100 rounded-xl text-sm font-bold shadow-sm hover:bg-brand-50 transition">
                    Filtrer
                </button>

                <a href="{{ route('emploi-du-temps.index') }}"
                   class="px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-bold text-gray-600 hover:bg-gray-50 transition">
                    Réinitialiser
                </a>
            </form>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('emploi-du-temps.conflits') }}"
                   class="px-4 py-2.5 bg-white border border-amber-200 text-amber-700 rounded-xl text-sm font-bold shadow-sm hover:bg-amber-50 transition">
                    ⚠ Voir les conflits
                </a>

                <a href="{{ route('emploi-du-temps.create') }}"
                   class="px-4 py-2.5 bg-gradient-to-r from-brand-500 via-brand-600 to-brand-700 text-white rounded-xl text-sm font-bold shadow-brand-glow transition">
                    Génération IA
                </a>

                <a href="{{ route('emploi-du-temps.grille', request()->query()) }}"
                   class="px-4 py-2.5 bg-white border border-brand-200 text-brand-700 rounded-xl text-sm font-bold shadow-sm hover:bg-brand-50 transition inline-flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 10h18M3 14h18M10 3v18M14 3v18"/>
                    </svg>
                    Ouvrir la grille
                </a>
            </div>
        </div>
    </div>

    {{-- ── Mode synthèse par défaut ───────────────────────────────── --}}
    @if(!$hasDetailFilters)

        <div class="bg-white border border-brand-100 rounded-2xl p-5 shadow-card-brand">
            <div class="flex items-center justify-between gap-3 mb-4">
                <div>
                    <h3 class="text-base font-extrabold text-gray-900">Vue synthèse par classe</h3>
                    <p class="text-sm text-gray-500">
                        L’index reste lisible. Utilise la grille pour les ajustements détaillés.
                    </p>
                </div>
                <span class="inline-flex items-center px-3 py-1 rounded-full bg-brand-50 text-brand-700 text-xs font-bold">
                    Synthèse
                </span>
            </div>

            @if($groupedByClasse->isEmpty())
                <div class="rounded-2xl border border-dashed border-gray-200 p-10 text-center text-sm text-gray-400">
                    Aucun créneau trouvé.
                </div>
            @else
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                    @foreach($groupedByClasse as $classeNom => $items)
                        @php
                            $classeId = $items->first()?->classe_id;
                            $actifs = $items->where('actif', true)->count();
                            $ia = $items->where('source', 'ia')->count();
                            $ajustes = $items->where('source', 'ajustement')->count();
                            $verrouilles = $items->where('locked_by_user', true)->count();
                            $enseignantsCount = $items->pluck('enseignant_id')->filter()->unique()->count();
                            $matieresCount = $items->pluck('matiere_id')->filter()->unique()->count();
                        @endphp

                        <div class="rounded-2xl border border-brand-100 bg-white p-4 shadow-sm hover:shadow-md transition">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h4 class="text-lg font-extrabold text-gray-900">{{ $classeNom }}</h4>
                                    <p class="text-sm text-gray-500">
                                        {{ $items->count() }} créneau(x) sur cette page
                                    </p>
                                </div>

                                <a href="{{ route('emploi-du-temps.grille', array_filter([
                                    'annee_scolaire_id' => request('annee_scolaire_id'),
                                    'vue' => 'classe',
                                    'classe_id' => $classeId,
                                ])) }}"
                                   class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-brand-50 text-brand-700 text-xs font-bold border border-brand-100 hover:bg-brand-100 transition">
                                    Ouvrir la grille
                                </a>
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-4">
                                <div class="rounded-xl bg-gray-50 px-3 py-3">
                                    <p class="text-[10px] uppercase font-extrabold text-gray-500">Actifs</p>
                                    <p class="mt-1 text-lg font-extrabold text-gray-900">{{ $actifs }}</p>
                                </div>

                                <div class="rounded-xl bg-brand-50 px-3 py-3">
                                    <p class="text-[10px] uppercase font-extrabold text-brand-600">IA</p>
                                    <p class="mt-1 text-lg font-extrabold text-brand-700">{{ $ia }}</p>
                                </div>

                                <div class="rounded-xl bg-violet-50 px-3 py-3">
                                    <p class="text-[10px] uppercase font-extrabold text-violet-600">Ajustés</p>
                                    <p class="mt-1 text-lg font-extrabold text-violet-700">{{ $ajustes }}</p>
                                </div>

                                <div class="rounded-xl bg-amber-50 px-3 py-3">
                                    <p class="text-[10px] uppercase font-extrabold text-amber-600">Verrouillés</p>
                                    <p class="mt-1 text-lg font-extrabold text-amber-700">{{ $verrouilles }}</p>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2 mt-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-gray-100 text-gray-700 text-xs font-bold">
                                    {{ $matieresCount }} matière(s)
                                </span>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-gray-100 text-gray-700 text-xs font-bold">
                                    {{ $enseignantsCount }} enseignant(s)
                                </span>
                            </div>

                            <div class="mt-4">
                                <p class="text-[10px] uppercase font-extrabold text-gray-500 mb-2">Aperçu matières</p>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($items->pluck('matiere.nom')->filter()->unique()->take(6) as $matiereNom)
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-white border border-brand-100 text-brand-700 text-xs font-bold">
                                            {{ $matiereNom }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="px-1 pt-5">
                    {{ $emplois->links() }}
                </div>
            @endif
        </div>

    @else

        {{-- ── Mode détail compact filtré ─────────────────────────── --}}
        <div class="bg-white border border-brand-100 rounded-2xl p-5 shadow-card-brand">
            <div class="flex items-center justify-between gap-3 mb-4">
                <div>
                    <h3 class="text-base font-extrabold text-gray-900">Vue détaillée filtrée</h3>
                    <p class="text-sm text-gray-500">
                        Affichage compact par jour. La grille reste l’espace de modification principal.
                    </p>
                </div>

                <span class="inline-flex items-center px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-bold">
                    Détail
                </span>
            </div>

            @if($collection->isEmpty())
                <div class="rounded-2xl border border-dashed border-gray-200 p-10 text-center text-sm text-gray-400">
                    Aucun créneau trouvé pour ces filtres.
                </div>
            @else
                <div class="space-y-5">
                    @foreach($jours as $jour)
                        @php
                            $items = $groupedByJour->get($jour, collect());
                        @endphp

                        @continue($items->isEmpty())

                        <div class="rounded-2xl border border-brand-100 overflow-hidden">
                            <div class="px-4 py-3 bg-brand-50/60 border-b border-brand-100 flex items-center justify-between">
                                <h4 class="text-sm font-extrabold uppercase tracking-wide text-brand-700">
                                    {{ ucfirst($jour) }}
                                </h4>
                                <span class="text-xs font-bold text-gray-500">
                                    {{ $items->count() }} créneau(x)
                                </span>
                            </div>

                            <div class="divide-y divide-brand-50">
                                @foreach($items as $emploi)
                                    @php
                                        $source = $emploi->source ?? 'manuel';
                                        $isIa = $source === 'ia';
                                        $isAdjusted = $source === 'ajustement';
                                        $isLocked = (bool) ($emploi->locked_by_user ?? false);

                                        $creneauLabel = $emploi->creneau->libelle
                                            ?? (($emploi->creneau->heure_debut ?? null)
                                                ? \Carbon\Carbon::parse($emploi->creneau->heure_debut)->format('H:i') . ' – ' .
                                                  \Carbon\Carbon::parse($emploi->creneau->heure_fin)->format('H:i')
                                                : '—');
                                    @endphp

                                    <div class="px-4 py-4 flex flex-col xl:flex-row xl:items-center justify-between gap-4 hover:bg-brand-50/20 transition">
                                        <div class="flex-1 min-w-0">
                                            <div class="flex flex-wrap items-center gap-2 mb-2">
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-gray-100 text-gray-700 text-xs font-bold">
                                                    {{ $creneauLabel }}
                                                </span>

                                                @if($emploi->actif)
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold">
                                                        Actif
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-gray-100 text-gray-600 text-xs font-bold">
                                                        Inactif
                                                    </span>
                                                @endif

                                                @if($isIa)
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-brand-600 text-white text-xs font-bold">
                                                        IA
                                                    </span>
                                                @elseif($isAdjusted)
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-violet-600 text-white text-xs font-bold">
                                                        Ajusté
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-gray-700 text-white text-xs font-bold">
                                                        Manuel
                                                    </span>
                                                @endif

                                                @if($isLocked)
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-amber-500 text-white text-xs font-bold">
                                                        🔒 Verrouillé
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
                                                <div>
                                                    <p class="text-[10px] uppercase font-extrabold text-gray-500">Classe</p>
                                                    <p class="text-sm font-bold text-gray-900">{{ $emploi->classe->nom ?? '—' }}</p>
                                                </div>

                                                <div>
                                                    <p class="text-[10px] uppercase font-extrabold text-gray-500">Matière</p>
                                                    <p class="text-sm text-gray-700">{{ $emploi->matiere->nom ?? '—' }}</p>
                                                </div>

                                                <div>
                                                    <p class="text-[10px] uppercase font-extrabold text-gray-500">Enseignant</p>
                                                    <p class="text-sm text-gray-700">
                                                        {{ optional($emploi->enseignant)->nom_complet ?? optional($emploi->enseignant)->nom ?? 'Non affecté' }}
                                                    </p>
                                                </div>

                                                <div>
                                                    <p class="text-[10px] uppercase font-extrabold text-gray-500">Salle</p>
                                                    <p class="text-sm text-gray-700">{{ $emploi->salle->nom ?? '—' }}</p>
                                                </div>
                                            </div>

                                            @if(!empty($emploi->ia_score))
                                                <p class="mt-2 text-xs text-gray-400">
                                                    Score IA : {{ number_format((float) $emploi->ia_score, 0) }}%
                                                </p>
                                            @endif
                                        </div>

                                        <div class="flex flex-wrap gap-2 xl:justify-end">
                                            @if($emploi->enseignant)
                                                <a href="{{ route('emploi-du-temps.professeur', $emploi->enseignant) }}"
                                                   class="px-3 py-2 bg-indigo-50 border border-indigo-200 rounded-lg text-xs font-bold text-indigo-700 hover:bg-indigo-100 transition">
                                                    Fiche professeur
                                                </a>
                                            @endif

                                            <a href="{{ route('emploi-du-temps.grille', array_filter([
                                                'annee_scolaire_id' => request('annee_scolaire_id'),
                                                'vue' => 'classe',
                                                'classe_id' => $emploi->classe_id,
                                            ])) }}"
                                               class="px-3 py-2 bg-white border border-brand-100 rounded-lg text-xs font-bold text-brand-700 hover:bg-brand-50 transition">
                                                Ouvrir dans la grille
                                            </a>

                                            <form method="POST" action="{{ route('emploi-du-temps.toggle', $emploi) }}">
                                                @csrf
                                                <button class="px-3 py-2 bg-white border border-blue-100 rounded-lg text-xs font-bold text-blue-700 hover:bg-blue-50 transition">
                                                    {{ $emploi->actif ? 'Désactiver' : 'Activer' }}
                                                </button>
                                            </form>

                                            <form method="POST"
                                                  action="{{ route('emploi-du-temps.destroy', $emploi) }}"
                                                  onsubmit="return confirm('Supprimer ce créneau ?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="px-3 py-2 bg-white border border-red-100 rounded-lg text-xs font-bold text-red-700 hover:bg-red-50 transition">
                                                    Supprimer
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="px-1 pt-5">
                    {{ $emplois->links() }}
                </div>
            @endif
        </div>
    @endif
</div>
@endsection