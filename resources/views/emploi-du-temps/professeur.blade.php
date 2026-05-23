{{-- resources/views/emploi-du-temps/professeur.blade.php --}}
@extends('layouts.app')

@section('title', 'EDT – ' . $enseignant->nom_complet)
@section('page-title', 'Emploi du Temps Professeur')
@section('page-subtitle', $enseignant->nom_complet)

@section('content')

{{-- ── Barre d'actions ─────────────────────────────────────────────── --}}
<div class="flex items-center justify-between mb-5 print:hidden">
    <a href="{{ route('emploi-du-temps.index') }}"
       class="inline-flex items-center gap-2 text-sm text-gray-500
              hover:text-brand-700 font-medium transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Retour à la liste
    </a>

    <div class="flex items-center gap-3">
        <form method="GET" action="{{ route('emploi-du-temps.professeur', $enseignant) }}"
              class="flex items-center gap-2">
            <label class="text-xs font-bold text-gray-500 uppercase">Année</label>
            <select name="annee_scolaire_id" onchange="this.form.submit()"
                    class="text-sm px-3 py-2 border border-brand-100 rounded-xl bg-white shadow-sm">
                @foreach($annees as $annee)
                    <option value="{{ $annee->id }}"
                            @selected($annee->id == $anneeActive?->id)>
                        {{ $annee->libelle }}
                    </option>
                @endforeach
            </select>
        </form>

        <button onclick="window.print()"
                class="inline-flex items-center gap-2 bg-brand-600 hover:bg-brand-700
                       text-white text-sm font-bold px-5 py-2.5 rounded-xl shadow-sm transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0
                         002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2
                         2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            Imprimer / PDF
        </button>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     DOCUMENT OFFICIEL — fidèle au modèle papier
     ══════════════════════════════════════════════════════════════════ --}}
<div id="edt-doc"
     class="bg-white shadow-lg rounded-2xl overflow-hidden print:shadow-none
            print:rounded-none border border-gray-200 text-gray-900">

    {{-- ── En-tête ─────────────────────────────────────────────────── --}}
    <div class="px-6 pt-5 pb-4 border-b-2 border-gray-800">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-[11px] font-extrabold uppercase tracking-wide">
                    {{ $etablissement->nom ?? 'Établissement' }}
                </p>
                @if($etablissement->telephone ?? null)
                    <p class="text-[9px] text-gray-500 mt-0.5">
                        Tél. {{ $etablissement->telephone }}
                    </p>
                @endif
            </div>
            <div class="text-right text-[10px] text-gray-600">
                <p class="font-bold">Année scolaire {{ $anneeActive?->libelle }}</p>
                <p class="text-gray-400 mt-0.5 italic">Ordre – Discipline – Travail</p>
            </div>
        </div>
        <div class="text-center mt-3">
            <h1 class="inline-block text-sm font-extrabold uppercase tracking-[.2em]
                        border-b-2 border-gray-800 pb-1">
                Emploi du Temps Professeur
            </h1>
        </div>
    </div>

    {{-- ── Fiche identité ──────────────────────────────────────────── --}}
    <div class="px-6 py-3 border-b border-gray-300 bg-gray-50/60">
        <div class="grid grid-cols-2 gap-x-10 gap-y-1 text-[10px]">
            <div class="flex gap-1">
                <span class="font-bold uppercase text-gray-500 w-32 shrink-0">Professeur</span>
                <span class="font-extrabold text-gray-900 uppercase">{{ $enseignant->nom_complet }}</span>
            </div>
            <div class="flex gap-1">
                <span class="font-bold uppercase text-gray-500 w-24 shrink-0">Matricule</span>
                <span class="text-gray-800">{{ $enseignant->matricule_mena ?? '—' }}</span>
                <span class="ml-6 font-bold uppercase text-gray-500">Sexe :</span>
                <span class="ml-1 text-gray-800">{{ $enseignant->sexe ?? '—' }}</span>
            </div>
            <div class="flex gap-1">
                <span class="font-bold uppercase text-gray-500 w-32 shrink-0">Corps</span>
                <span class="text-gray-800">{{ $enseignant->statut_libelle ?? '—' }}</span>
            </div>
            <div class="flex gap-1">
                <span class="font-bold uppercase text-gray-500 w-24 shrink-0">Discipline</span>
                <span class="text-gray-800">
                    {{ $enseignant->matieres->pluck('nom')->join(', ') ?: '—' }}
                </span>
            </div>
            <div class="flex gap-1">
                <span class="font-bold uppercase text-gray-500 w-32 shrink-0">Prot. Princ. en</span>
                <span class="text-gray-800">
                    {{ $enseignant->classesPrincipales->first()?->nom ?? '—' }}
                </span>
            </div>
            <div class="flex gap-1">
                <span class="font-bold uppercase text-gray-500 w-24 shrink-0">Contact</span>
                <span class="text-gray-800">{{ $enseignant->telephone ?? '—' }}</span>
            </div>
        </div>
    </div>

    {{-- ══ GRILLE ═══════════════════════════════════════════════════ --}}
    <div class="overflow-x-auto">
        <table class="w-full border-collapse text-[10px]" style="min-width:700px">

            <thead>
                <tr class="bg-gray-100 border-b border-gray-400">
                    <th class="border border-gray-300 px-3 py-2 text-center font-extrabold
                               uppercase text-[9px] text-gray-600 w-[120px]">
                        Horaires
                    </th>
                    @foreach($jours as $jour)
                        <th class="border border-gray-300 px-3 py-2 text-center font-extrabold
                                   uppercase text-[9px] text-gray-700">
                            {{ ucfirst($jour) }}
                        </th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @foreach($creneaux as $creneau)
                    @php
                        // ── Utilisation du champ TYPE (enum) ──────────
                        $type          = $creneau->type ?? 'cours';
                        $isRecreation  = $type === 'recreation';
                        $isPause       = $type === 'pause_dejeuner';
                        $isCours       = $type === 'cours';

                        // Label : libelle BD ou "HH:MM – HH:MM"
                        $heureDebut = $creneau->heure_debut
                            ? \Carbon\Carbon::parse($creneau->heure_debut)->format('H:i')
                            : '';
                        $heureFin   = $creneau->heure_fin
                            ? \Carbon\Carbon::parse($creneau->heure_fin)->format('H:i')
                            : '';
                        $label = $creneau->libelle ?: "$heureDebut – $heureFin";
                    @endphp

                    @if($isRecreation)
                        {{-- ── RÉCRÉATION ── --}}
                        <tr class="bg-gray-50/80">
                            <td class="border border-gray-300 px-2 py-1 text-center
                                       text-gray-500 font-semibold text-[9px] leading-tight">
                                {{ $heureDebut }} – {{ $heureFin }}
                            </td>
                            <td colspan="{{ count($jours) }}"
                                class="border border-gray-300 py-2">
                                <p class="text-center tracking-[.6em] font-extrabold
                                          text-[8px] uppercase text-gray-400">
                                    R&nbsp;É&nbsp;C&nbsp;R&nbsp;É&nbsp;A&nbsp;T&nbsp;I&nbsp;O&nbsp;N
                                </p>
                            </td>
                        </tr>

                    @elseif($isPause)
                        {{-- ── PAUSE MI-JOURNÉE ── --}}
                        <tr class="bg-gray-50/80">
                            <td class="border border-gray-300 px-2 py-1 text-center
                                       text-gray-500 font-semibold text-[9px] leading-tight">
                                {{ $heureDebut }} – {{ $heureFin }}
                            </td>
                            <td colspan="{{ count($jours) }}"
                                class="border border-gray-300 py-2">
                                <p class="text-center tracking-[.5em] font-extrabold
                                          text-[8px] uppercase text-gray-400">
                                    P&nbsp;A&nbsp;U&nbsp;S&nbsp;E&nbsp;&nbsp;&nbsp;M&nbsp;I&nbsp;–&nbsp;J&nbsp;O&nbsp;U&nbsp;R&nbsp;N&nbsp;É&nbsp;E
                                </p>
                            </td>
                        </tr>

                    @else
                        {{-- ── CRÉNEAU COURS ── --}}
                        <tr class="hover:bg-blue-50/20 transition-colors">
                            <td class="border border-gray-300 px-2 py-1 text-center
                                       text-gray-600 font-semibold leading-tight text-[9px]">
                                {{ $heureDebut }} – {{ $heureFin }}
                            </td>

                            @foreach($jours as $jour)
                                @php $seance = $grid[$jour][$creneau->id] ?? null; @endphp
                                <td class="border border-gray-300 text-center align-middle"
                                    style="height:48px; padding:2px 4px;">
                                    @if($seance)
                                        <div class="flex flex-col items-center justify-center
                                                    gap-[2px] h-full">
                                            {{-- Discipline (code) --}}
                                            <span class="font-extrabold text-[10px] text-blue-800
                                                         leading-none">
                                                {{ $seance->matiere->code ?? $seance->matiere->nom ?? '—' }}
                                            </span>
                                            {{-- Classe --}}
                                            <span class="text-[9px] text-gray-800 leading-none">
                                                {{ $seance->classe->nom ?? '—' }}
                                            </span>
                                            {{-- Salle --}}
                                            <span class="text-[8px] text-gray-500 leading-none uppercase">
                                                {{ $seance->salle->nom ?? '—' }}
                                            </span>
                                        </div>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- ══ RÉCAPITULATIF ═══════════════════════════════════════════ --}}
    <div class="px-6 py-5 border-t-2 border-gray-800">

        <h2 class="text-[10px] font-extrabold uppercase tracking-[.3em]
                   text-center text-gray-700 mb-4">
            Récapitulatif
        </h2>

        @php
            $recapClasses = $emplois
                ->groupBy('classe_id')
                ->map(fn($seances) => [
                    'classe'    => $seances->first()->classe,
                    'matiere'   => $seances->first()->matiere,
                    'volume'    => $seances->count(),
                    'complement'=> 0,
                ])
                ->values();

            $totalSeances = $recapClasses->sum('volume');
        @endphp

        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-[9px]">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="border border-gray-300 px-3 py-1.5 text-left
                                   font-extrabold uppercase text-gray-600">Classes</th>
                        @foreach($recapClasses as $r)
                            <th class="border border-gray-300 px-3 py-1.5 text-center
                                       font-bold text-gray-800">
                                {{ $r['classe']->nom ?? '—' }}
                            </th>
                        @endforeach
                        <th class="border border-gray-300 px-3 py-1.5 text-center
                                   font-extrabold text-gray-700 bg-amber-50 text-[8px]
                                   uppercase leading-tight" rowspan="4">
                            Total Heures<br>d'Enseignement
                        </th>
                    </tr>
                    <tr>
                        <td class="border border-gray-300 px-3 py-1.5 font-bold text-gray-600">
                            Effectif
                        </td>
                        @foreach($recapClasses as $r)
                            <td class="border border-gray-300 px-3 py-1.5 text-center text-gray-700">
                                {{ $r['classe']->effectif ?? 0 }}
                            </td>
                        @endforeach
                    </tr>
                    <tr class="bg-blue-50/40">
                        <td class="border border-gray-300 px-3 py-1.5 font-bold text-gray-600">
                            Discipline
                        </td>
                        @foreach($recapClasses as $r)
                            <td class="border border-gray-300 px-3 py-1.5 text-center
                                       font-extrabold text-blue-800">
                                {{ $r['matiere']->code ?? $r['matiere']->nom ?? '—' }}
                            </td>
                        @endforeach
                    </tr>
                    <tr>
                        <td class="border border-gray-300 px-3 py-1.5 font-bold text-gray-600">
                            Volume Horaire
                        </td>
                        @foreach($recapClasses as $r)
                            <td class="border border-gray-300 px-3 py-1.5 text-center
                                       font-semibold text-gray-800">
                                {{ $r['volume'] }}H
                            </td>
                        @endforeach
                    </tr>
                    <tr>
                        <td class="border border-gray-300 px-3 py-1.5 font-bold text-gray-600">
                            Complément de service
                        </td>
                        @foreach($recapClasses as $r)
                            <td class="border border-gray-300 px-3 py-1.5 text-center text-gray-500">
                                {{ $r['complement'] > 0 ? $r['complement'].'H' : '' }}
                            </td>
                        @endforeach
                        <td class="border border-gray-300 bg-amber-50 text-center">
                            <span class="text-lg font-extrabold text-gray-900">
                                {{ $totalSeances }}H
                            </span>
                        </td>
                    </tr>
                </thead>
            </table>
        </div>

        {{-- Surveillance autres --}}
        <div class="mt-5">
            <p class="text-[9px] font-extrabold uppercase tracking-[.25em]
                       text-gray-600 text-center mb-2">
                Surveillance Autres
            </p>
            <table class="w-full border-collapse text-[9px]">
                <thead>
                    <tr class="bg-gray-100">
                        @foreach(['CN', "L'CDI", 'UP', 'LABO', 'BIBLIO', 'CABINET', 'ATT'] as $p)
                            <th class="border border-gray-300 px-3 py-1.5 text-center
                                       font-bold text-gray-600 uppercase">{{ $p }}</th>
                        @endforeach
                        <th class="border border-gray-300 px-3 py-1.5 text-center
                                   font-extrabold text-gray-700 bg-amber-50">Total D</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        @foreach(range(1, 8) as $i)
                            <td class="border border-gray-300 py-5"></td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Augmentation de service --}}
        <div class="mt-4 border border-gray-300 rounded-lg p-3 text-[9px]">
            <p class="font-extrabold uppercase text-gray-600 mb-2 tracking-wide">
                Augmentation de Service
                <span class="font-normal text-gray-400">(Classe de moins de 20 élèves)</span>
            </p>
            <div class="grid grid-cols-3 gap-4">
                @foreach(['Total A/B+D', 'Maximum de service (15,16,17,25)', 'Heures supplémentaires T.T'] as $lib)
                    <div class="flex items-end gap-1">
                        <span class="text-gray-500 shrink-0">{{ $lib }} :</span>
                        <span class="flex-1 border-b border-dotted border-gray-400 mb-0.5"></span>
                    </div>
                @endforeach
            </div>
        </div>

    </div>
</div>

<style>
    @media print {
        .print\:hidden { display: none !important; }
        nav, aside, header, footer { display: none !important; }
        #edt-doc {
            border: none !important;
            box-shadow: none !important;
            border-radius: 0 !important;
        }
        @page { size: A4 landscape; margin: 8mm 10mm; }
        body { background: #fff !important; }
    }
</style>
@endsection