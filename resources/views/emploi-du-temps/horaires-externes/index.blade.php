@extends('layouts.app')

@section('title', 'EDT autres écoles — ' . $enseignant->nom_complet)

@section('content')
<div class="max-w-5xl mx-auto px-4 py-8">

    {{-- En-tête --}}
    <div class="flex items-start justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                Emplois du temps — autres établissements
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                {{ $enseignant->nom_complet }}
                @if($enseignant->specialite)
                    &mdash; <span class="text-gray-600">{{ $enseignant->specialite }}</span>
                @endif
                @if($anneeActive)
                    &mdash; <span class="text-blue-600 font-medium">{{ $anneeActive->libelle }}</span>
                @endif
            </p>
        </div>
        <a href="{{ url()->previous() }}"
           class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Retour
        </a>
    </div>

    {{-- Bannière info --}}
    <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-4 mb-6 flex gap-3">
        <svg class="w-5 h-5 text-indigo-500 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div class="text-sm text-indigo-800">
            <p class="font-semibold mb-0.5">Comment ça marche ?</p>
            <ol class="list-decimal list-inside space-y-0.5 text-indigo-700">
                <li>Uploadez la photo ou le PDF de l'emploi du temps du prof dans son autre école</li>
                <li>Lancez l'analyse IA — elle extrait automatiquement les créneaux occupés</li>
                <li>Vérifiez et validez les créneaux extraits</li>
                <li>L'IA n'attribuera plus jamais ce prof aux mêmes créneaux</li>
            </ol>
        </div>
    </div>

    {{-- Messages flash --}}
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 mb-5 text-sm flex items-center gap-2">
            <svg class="w-4 h-4 text-green-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 rounded-xl px-4 py-3 mb-5 text-sm">
            {{ session('error') }}
        </div>
    @endif

    {{-- ══════════════════════════════════════════════
         BLOC 1 : UPLOAD
    ══════════════════════════════════════════════ --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm mb-6">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="text-base font-semibold text-gray-700 flex items-center gap-2">
                <span class="flex items-center justify-center w-6 h-6 bg-indigo-100 text-indigo-700 rounded-full text-xs font-bold">1</span>
                Uploader l'emploi du temps
            </h2>
        </div>
        <div class="p-5">
            <form method="POST"
                  action="{{ route('emploi-du-temps.horaires-externes.upload', $enseignant) }}"
                  enctype="multipart/form-data">
                @csrf

                @if($anneeActive)
                    <input type="hidden" name="annee_scolaire_id" value="{{ $anneeActive->id }}">
                @endif

                <div class="flex flex-col sm:flex-row gap-4 items-end">

                    {{-- Zone de drop --}}
                    <div class="flex-1">
                        <label class="block text-xs font-medium text-gray-600 mb-1">
                            Photo ou PDF de l'EDT <span class="text-red-500">*</span>
                        </label>
                        <div class="relative border-2 border-dashed border-gray-300 rounded-xl px-4 py-6 text-center hover:border-indigo-400 transition cursor-pointer"
                             onclick="document.getElementById('fichier-upload').click()">
                            <svg class="mx-auto w-8 h-8 text-gray-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <p class="text-sm text-gray-500" id="upload-label">
                                Cliquez pour choisir un fichier
                            </p>
                            <p class="text-xs text-gray-400 mt-1">JPG, PNG, WEBP, PDF · Max 10 Mo</p>
                            <input type="file" id="fichier-upload" name="fichier"
                                   accept=".jpg,.jpeg,.png,.webp,.pdf"
                                   class="absolute inset-0 opacity-0 cursor-pointer"
                                   onchange="document.getElementById('upload-label').textContent = this.files[0]?.name ?? 'Choisir un fichier'">
                        </div>
                        @error('fichier')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Type de source --}}
                    <div class="sm:w-40">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Type</label>
                        <select name="source_type"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="photo">Photo</option>
                            <option value="scan">Scan</option>
                            <option value="image">Image</option>
                            <option value="pdf">PDF</option>
                        </select>
                    </div>

                    <button type="submit"
                            class="sm:w-auto w-full bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition shrink-0">
                        Uploader
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════
         BLOC 2 : IMPORTS EN COURS
    ══════════════════════════════════════════════ --}}
    @if($imports->isNotEmpty())
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm mb-6">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="text-base font-semibold text-gray-700 flex items-center gap-2">
                <span class="flex items-center justify-center w-6 h-6 bg-indigo-100 text-indigo-700 rounded-full text-xs font-bold">2</span>
                Analyser & valider les imports
            </h2>
        </div>

        <div class="divide-y divide-gray-100">
            @foreach($imports as $import)
            <div class="px-5 py-4">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-3 min-w-0">
                        {{-- Icône fichier --}}
                        <div class="shrink-0 w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                            @if($import->source_type === 'pdf')
                                <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                            @else
                                <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-800 truncate">
                                {{ $import->original_filename ?? 'Fichier sans nom' }}
                            </p>
                            <div class="flex items-center gap-3 mt-0.5 flex-wrap">
                                {{-- Statut --}}
                                @php
                                    $statutColors = [
                                        'uploade' => 'bg-gray-100 text-gray-600',
                                        'analyse' => 'bg-yellow-100 text-yellow-700',
                                        'valide'  => 'bg-green-100 text-green-700',
                                        'erreur'  => 'bg-red-100 text-red-700',
                                    ];
                                    $statutLabels = [
                                        'uploade' => 'Uploadé',
                                        'analyse' => 'Analysé',
                                        'valide'  => 'Validé',
                                        'erreur'  => 'Erreur',
                                    ];
                                @endphp
                                <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $statutColors[$import->statut] ?? 'bg-gray-100 text-gray-600' }}">
                                    {{ $statutLabels[$import->statut] ?? $import->statut }}
                                </span>
                                @if($import->confidence_score > 0)
                                    @php
                                        $confColor = $import->confidence_score >= 80 ? 'text-green-600' : ($import->confidence_score >= 50 ? 'text-yellow-600' : 'text-red-500');
                                    @endphp
                                    <span class="text-xs {{ $confColor }}">
                                        Confiance : {{ $import->confidence_score }}%
                                    </span>
                                @endif
                                @if($import->etablissement_detecte)
                                    <span class="text-xs text-indigo-600 font-medium">
                                        {{ $import->etablissement_detecte }}
                                    </span>
                                @endif
                                @if($import->professeur_detecte)
                                    <span class="text-xs text-gray-500">
                                        {{ $import->professeur_detecte }}
                                    </span>
                                @endif
                            </div>
                            @if($import->notes_ocr)
                                <p class="text-xs text-gray-400 mt-1 italic">{{ Str::limit($import->notes_ocr, 100) }}</p>
                            @endif
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-2 shrink-0">
                        @if($import->statut === 'uploade' || $import->statut === 'erreur')
                            <form method="POST"
                                  action="{{ route('emploi-du-temps.horaires-externes.analyser', [$enseignant, $import]) }}">
                                @csrf
                                <button type="submit"
                                        class="inline-flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                    </svg>
                                    Analyser avec l'IA
                                </button>
                            </form>
                        @endif

                        <form method="POST"
                              action="{{ route('emploi-du-temps.horaires-externes.destroy-import', [$enseignant, $import]) }}"
                              onsubmit="return confirm('Supprimer cet import et ses créneaux ?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-400 hover:text-red-600 transition" title="Supprimer">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>

                {{-- ── Tableau de validation des créneaux extraits ── --}}
                @if($import->statut === 'analyse' && count($import->slots_ocr) > 0)
                <div class="mt-4 border border-yellow-200 rounded-xl bg-yellow-50 p-4">
                    <p class="text-sm font-semibold text-yellow-800 mb-3">
                        {{ count($import->slots_ocr) }} créneau(x) extrait(s) — vérifiez et validez :
                    </p>
                    <form method="POST"
                          action="{{ route('emploi-du-temps.horaires-externes.valider', [$enseignant, $import]) }}">
                        @csrf

                        {{-- Nom de l'établissement (éditable) --}}
                        <div class="mb-3">
                            <label class="block text-xs font-medium text-gray-600 mb-1">
                                Nom de l'établissement externe
                            </label>
                            <input type="text"
                                   name="etablissement"
                                   value="{{ old('etablissement', $import->etablissement_detecte ?? '') }}"
                                   placeholder="Nom de l'autre école"
                                   class="w-full sm:w-80 border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-500">
                        </div>

                        {{-- Tableau des créneaux --}}
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm border-collapse">
                                <thead>
                                    <tr class="bg-yellow-100 text-yellow-800 text-xs">
                                        <th class="px-3 py-2 text-left font-semibold">#</th>
                                        <th class="px-3 py-2 text-left font-semibold">Jour</th>
                                        <th class="px-3 py-2 text-left font-semibold">Début</th>
                                        <th class="px-3 py-2 text-left font-semibold">Fin</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-500">Classe (info)</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-500">Matière (info)</th>
                                        <th class="px-3 py-2 text-left font-semibold">Note</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-yellow-100">
                                    @foreach($import->slots_ocr as $i => $slot)
                                    <tr class="bg-white hover:bg-yellow-50">
                                        <td class="px-3 py-2 text-gray-400 text-xs">{{ $i + 1 }}</td>
                                        <td class="px-3 py-2">
                                            <select name="slots[{{ $i }}][jour]"
                                                    class="border border-gray-200 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-yellow-500">
                                                @foreach(['lundi','mardi','mercredi','jeudi','vendredi','samedi'] as $j)
                                                    <option value="{{ $j }}" {{ $slot['jour'] === $j ? 'selected' : '' }}>
                                                        {{ ucfirst($j) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="time"
                                                   name="slots[{{ $i }}][heure_debut]"
                                                   value="{{ $slot['heure_debut'] }}"
                                                   class="border border-gray-200 rounded px-2 py-1 text-xs w-24 focus:ring-1 focus:ring-yellow-500">
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="time"
                                                   name="slots[{{ $i }}][heure_fin]"
                                                   value="{{ $slot['heure_fin'] }}"
                                                   class="border border-gray-200 rounded px-2 py-1 text-xs w-24 focus:ring-1 focus:ring-yellow-500">
                                        </td>
                                        <td class="px-3 py-2 text-gray-400 text-xs">
                                            {{ $slot['classe'] ?? '—' }}
                                        </td>
                                        <td class="px-3 py-2 text-gray-400 text-xs">
                                            {{ $slot['matiere'] ?? '—' }}
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="text"
                                                   name="slots[{{ $i }}][commentaire]"
                                                   value="{{ $slot['commentaire'] ?? ($slot['salle'] ? 'Salle '.$slot['salle'] : '') }}"
                                                   placeholder="Optionnel"
                                                   class="border border-gray-200 rounded px-2 py-1 text-xs w-32 focus:ring-1 focus:ring-yellow-500">
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3 flex gap-3">
                            <button type="submit"
                                    class="bg-green-600 hover:bg-green-700 text-white text-sm font-semibold px-5 py-2 rounded-lg transition">
                                Valider et activer ces créneaux
                            </button>
                            <span class="text-xs text-gray-400 self-center">
                                Les créneaux validés bloqueront l'IA lors de la génération.
                            </span>
                        </div>
                    </form>
                </div>
                @endif

                {{-- Import déjà validé : résumé --}}
                @if($import->estValide())
                    <div class="mt-3 flex items-center gap-2 text-sm text-green-700">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        {{ $import->horairesExternes()->count() }} créneau(x) actif(s) depuis cet import
                        <span class="text-gray-400">— validé {{ $import->validated_at?->diffForHumans() }}</span>
                    </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ══════════════════════════════════════════════
         BLOC 3 : CRÉNEAUX ACTIFS + VUE SEMAINE
    ══════════════════════════════════════════════ --}}
    @if($slots->isNotEmpty())
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-base font-semibold text-gray-700 flex items-center gap-2">
                <span class="flex items-center justify-center w-6 h-6 bg-green-100 text-green-700 rounded-full text-xs font-bold">3</span>
                Créneaux actifs (bloqués pour l'IA)
            </h2>
            <span class="text-xs text-gray-400">
                {{ $slots->flatten()->count() }} créneau(x) au total
            </span>
        </div>

        {{-- Vue semaine visuelle --}}
        <div class="px-5 pt-4 pb-2">
            <div class="grid grid-cols-6 gap-1.5 text-xs">
                @foreach($jours as $jour)
                    <div>
                        <p class="font-semibold text-gray-500 text-center mb-1.5 uppercase text-[10px] tracking-wide">
                            {{ substr($jour, 0, 3) }}
                        </p>
                        @if($slots->has($jour))
                            @foreach($slots[$jour] as $slot)
                                <div class="{{ $slot->valide ? 'bg-red-50 border-red-300 text-red-700' : 'bg-gray-50 border-gray-200 text-gray-400' }} border rounded-lg px-1.5 py-1 mb-1 text-center leading-tight">
                                    <span class="font-semibold">{{ substr($slot->heure_debut, 0, 5) }}</span><br>
                                    <span>{{ substr($slot->heure_fin, 0, 5) }}</span>
                                    @if($slot->commentaire)
                                        <br><span class="text-[9px] opacity-70 truncate block">{{ Str::limit($slot->commentaire, 12) }}</span>
                                    @endif
                                </div>
                            @endforeach
                        @else
                            <div class="text-gray-300 text-center py-2">—</div>
                        @endif
                    </div>
                @endforeach
            </div>
            <div class="flex items-center gap-4 mt-2 text-xs text-gray-400">
                <span class="flex items-center gap-1">
                    <span class="w-3 h-3 bg-red-50 border border-red-300 rounded inline-block"></span>
                    Bloqué (actif)
                </span>
                <span class="flex items-center gap-1">
                    <span class="w-3 h-3 bg-gray-50 border border-gray-200 rounded inline-block"></span>
                    Désactivé (ignoré)
                </span>
            </div>
        </div>

        {{-- Liste détaillée --}}
        <div class="border-t border-gray-100 divide-y divide-gray-50">
            @foreach($jours as $jour)
                @if($slots->has($jour))
                    @foreach($slots[$jour] as $slot)
                        <div class="px-5 py-2.5 flex items-center justify-between gap-3 text-sm hover:bg-gray-50">
                            <div class="flex items-center gap-3">
                                <span class="w-2 h-2 rounded-full shrink-0 {{ $slot->valide ? 'bg-green-500' : 'bg-gray-300' }}"></span>
                                <span class="font-medium text-gray-600 w-20">{{ ucfirst($jour) }}</span>
                                <span class="text-gray-800">{{ $slot->heure_debut }} – {{ $slot->heure_fin }}</span>
                                <span class="text-indigo-600 text-xs font-medium">{{ $slot->etablissement_externe }}</span>
                                @if($slot->commentaire)
                                    <span class="text-gray-400 text-xs">{{ $slot->commentaire }}</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2">
                                {{-- Toggle --}}
                                <form method="POST"
                                      action="{{ route('emploi-du-temps.horaires-externes.toggle', [$enseignant, $slot]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="text-xs px-2 py-0.5 rounded border {{ $slot->valide ? 'border-green-300 text-green-700 bg-green-50' : 'border-gray-300 text-gray-500' }} hover:opacity-75 transition">
                                        {{ $slot->valide ? 'Actif' : 'Inactif' }}
                                    </button>
                                </form>

                                {{-- Supprimer --}}
                                <form method="POST"
                                      action="{{ route('emploi-du-temps.horaires-externes.destroy', [$enseignant, $slot]) }}"
                                      onsubmit="return confirm('Supprimer ce créneau ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-400 hover:text-red-600 transition">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                @endif
            @endforeach
        </div>
    </div>
    @endif

    @if($imports->isEmpty() && $slots->isEmpty())
        <div class="text-center py-12 text-gray-400">
            <svg class="w-12 h-12 mx-auto mb-3 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <p class="text-sm">Aucun emploi du temps externe pour ce professeur.</p>
            <p class="text-xs mt-1">Uploadez le document ci-dessus pour commencer.</p>
        </div>
    @endif

</div>
@endsection
