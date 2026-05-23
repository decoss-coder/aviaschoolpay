@extends('layouts.app')

@section('title', 'Pointage #' . $pointage->id)
@section('page-title', 'Détail du pointage')
@section('page-subtitle', ($pointage->enseignant->nom_complet ?? 'Enseignant') . ' · ' . ($pointage->date?->format('d/m/Y') ?? ''))

@section('content')
@php
    $isAnomalie = $pointage->statut === 'hors_zone'
        || $pointage->statut === 'fraude_detectee'
        || $pointage->spoofing_detecte
        || ! $pointage->gps_valide
        || ! $pointage->token_valide
        || ! $pointage->conforme_emploi_temps;
    $mapsUrl = ($pointage->gps_latitude && $pointage->gps_longitude)
        ? 'https://www.google.com/maps?q=' . $pointage->gps_latitude . ',' . $pointage->gps_longitude
        : null;
@endphp

<div class="max-w-7xl mx-auto space-y-6">

    <nav class="flex flex-wrap items-center gap-3 text-sm">
        <a href="{{ route('pointage.index', ['date' => $pointage->date?->format('Y-m-d')]) }}"
           class="inline-flex items-center gap-2 font-semibold text-gray-500 hover:text-brand-600 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Retour à la supervision
        </a>
        <span class="text-gray-300">/</span>
        <span class="font-bold text-gray-800">Pointage #{{ $pointage->id }}</span>
    </nav>

    @if(session('success'))
        <div class="px-4 py-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-semibold">
            {{ session('success') }}
        </div>
    @endif

    {{-- Bandeau résumé --}}
    <div class="relative overflow-hidden rounded-2xl border border-gray-200/80 bg-white shadow-sm">
        <div class="absolute top-0 left-0 right-0 h-1 {{ $isAnomalie ? 'bg-gradient-to-r from-amber-400 to-red-500' : 'bg-gradient-to-r from-brand-400 to-emerald-500' }}"></div>
        <div class="p-6 sm:p-8">
            <div class="flex flex-col lg:flex-row lg:items-start gap-6">
                <div class="flex items-start gap-4 flex-1 min-w-0">
                    @if(!empty($pointage->enseignant->photo_path))
                        <img src="{{ route('enseignants.photo', $pointage->enseignant) }}" alt=""
                             class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl object-cover ring-2 ring-gray-100 shadow-md shrink-0">
                    @else
                        <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl flex items-center justify-center text-xl font-extrabold shrink-0 bg-gradient-to-br from-brand-500 to-brand-700 text-white shadow-md">
                            {{ strtoupper(substr($pointage->enseignant->prenom ?? 'X', 0, 1)) }}{{ strtoupper(substr($pointage->enseignant->nom ?? 'X', 0, 1)) }}
                        </div>
                    @endif
                    <div class="min-w-0">
                        <h2 class="font-display text-xl sm:text-2xl font-extrabold text-gray-900 truncate">
                            {{ $pointage->enseignant->nom_complet ?? '—' }}
                        </h2>
                        <p class="text-sm text-gray-500 mt-1">{{ $pointage->enseignant->telephone ?? '—' }} · {{ $pointage->enseignant->matricule_mena ?: 'Sans matricule MENA' }}</p>
                        <div class="flex flex-wrap items-center gap-2 mt-3">
                            @include('pointage.partials.statut-badge', ['pointage' => $pointage])
                            <span class="text-xs font-bold text-gray-500 bg-gray-100 px-2.5 py-1 rounded-lg">{{ $pointage->methode_libelle }}</span>
                            <span class="text-xs font-bold text-gray-500 bg-gray-100 px-2.5 py-1 rounded-lg">{{ $pointage->type_scan_libelle }}</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 lg:w-auto shrink-0">
                    <div class="rounded-xl bg-gray-50 border border-gray-100 px-4 py-3 text-center min-w-[88px]">
                        <p class="text-[10px] font-bold uppercase text-gray-400">Date</p>
                        <p class="text-sm font-extrabold text-gray-900 mt-0.5">{{ $pointage->date?->format('d/m/Y') }}</p>
                    </div>
                    <div class="rounded-xl bg-gray-50 border border-gray-100 px-4 py-3 text-center min-w-[88px]">
                        <p class="text-[10px] font-bold uppercase text-gray-400">Heure</p>
                        <p class="text-sm font-extrabold text-gray-900 mt-0.5 tabular-nums">{{ $pointage->heure_scan ? substr((string) $pointage->heure_scan, 0, 5) : '—' }}</p>
                    </div>
                    <div class="rounded-xl bg-gray-50 border border-gray-100 px-4 py-3 text-center min-w-[88px]">
                        <p class="text-[10px] font-bold uppercase text-gray-400">Salle</p>
                        <p class="text-sm font-extrabold text-gray-900 mt-0.5 truncate max-w-[100px]">{{ $pointage->salle->nom ?? '—' }}</p>
                    </div>
                    <div class="rounded-xl bg-gray-50 border border-gray-100 px-4 py-3 text-center min-w-[88px]">
                        <p class="text-[10px] font-bold uppercase text-gray-400">Distance</p>
                        <p class="text-sm font-extrabold text-gray-900 mt-0.5 tabular-nums">{{ number_format((float) ($pointage->distance_ecole_metres ?? 0), 0, ',', ' ') }} m</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Parcours mobile --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="flex items-center gap-3 p-4 rounded-xl border border-emerald-200 bg-emerald-50/50">
            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-600 text-white text-xs font-bold">1</span>
            <div>
                <p class="text-xs font-bold text-emerald-800">Scan QR + GPS</p>
                <p class="text-[11px] text-emerald-700/80">Enregistré à {{ $pointage->heure_scan ? substr((string) $pointage->heure_scan, 0, 5) : '—' }}</p>
            </div>
        </div>
        <div class="flex items-center gap-3 p-4 rounded-xl border {{ $pointage->aCahierTexte() ? 'border-violet-200 bg-violet-50/50' : 'border-gray-200 bg-gray-50' }}">
            <span class="flex h-8 w-8 items-center justify-center rounded-full {{ $pointage->aCahierTexte() ? 'bg-violet-600' : 'bg-gray-300' }} text-white text-xs font-bold">2</span>
            <div>
                <p class="text-xs font-bold {{ $pointage->aCahierTexte() ? 'text-violet-800' : 'text-gray-500' }}">Photo cahier</p>
                <p class="text-[11px] opacity-80">{{ $pointage->aCahierTexte() ? 'Image reçue' : 'Non envoyée' }}</p>
            </div>
        </div>
        <div class="flex items-center gap-3 p-4 rounded-xl border {{ $pointage->cahier_texte_validated ? 'border-emerald-200 bg-emerald-50/50' : 'border-gray-200 bg-gray-50' }}">
            <span class="flex h-8 w-8 items-center justify-center rounded-full {{ $pointage->cahier_texte_validated ? 'bg-emerald-600' : 'bg-gray-300' }} text-white text-xs font-bold">3</span>
            <div>
                <p class="text-xs font-bold">Validation IA</p>
                <p class="text-[11px] opacity-80">{{ $pointage->cahier_texte_validated ? 'Conforme EDT' : ($pointage->aCahierTexte() ? 'À revoir' : 'En attente') }}</p>
            </div>
        </div>
    </div>

    {{-- Cahier (priorité) --}}
    @include('pointage.partials.cahier-texte-detail', ['pointage' => $pointage, 'cahierRoute' => 'pointages.cahier-texte'])

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Contrôles --}}
        <div class="space-y-6">
            <div class="bg-white border border-gray-200/80 rounded-2xl p-5 shadow-sm">
                <h3 class="text-sm font-extrabold text-gray-900 mb-4">Contrôles automatiques</h3>
                <div class="space-y-2">
                    @include('pointage.partials.check-pill', ['ok' => $pointage->gps_valide, 'label' => 'Géolocalisation'])
                    @include('pointage.partials.check-pill', ['ok' => $pointage->token_valide, 'label' => 'Jeton serveur'])
                    @include('pointage.partials.check-pill', ['ok' => $pointage->conforme_emploi_temps, 'label' => 'Emploi du temps'])
                    @include('pointage.partials.check-pill', ['ok' => ! $pointage->spoofing_detecte, 'label' => 'Pas de spoofing'])
                </div>
            </div>

            @if($mapsUrl)
                <a href="{{ $mapsUrl }}" target="_blank" rel="noopener"
                   class="flex items-center justify-center gap-2 w-full px-4 py-3 bg-white border border-gray-200 rounded-xl text-sm font-bold text-brand-700 hover:bg-brand-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Voir sur la carte
                </a>
            @endif

            @if($pointage->selfie_path)
                <div class="bg-white border border-gray-200/80 rounded-2xl p-5 shadow-sm">
                    <h3 class="text-sm font-extrabold text-gray-900 mb-3">Selfie de contrôle</h3>
                    <img src="{{ route('pointages.selfie', $pointage) }}" alt="Selfie" class="w-full rounded-xl border border-gray-100">
                </div>
            @endif
        </div>

        {{-- Technique + alertes --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white border border-gray-200/80 rounded-2xl shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/80">
                    <h3 class="text-sm font-extrabold text-gray-900">Données techniques</h3>
                </div>
                <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-[10px] font-bold uppercase text-gray-400">QR code</p>
                        <p class="font-mono text-xs font-bold text-gray-800 mt-1 break-all">{{ $pointage->qrCode->code_unique ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase text-gray-400">Précision GPS</p>
                        <p class="font-bold text-gray-900 mt-1">{{ number_format((float) ($pointage->gps_precision_metres ?? 0), 1, ',', ' ') }} m</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold uppercase text-gray-400">Latitude</p>
                        <p class="font-mono text-xs text-gray-800 mt-1">{{ $pointage->gps_latitude ?? '—' }}</p>
                    <div>
                        <p class="text-[10px] font-bold uppercase text-gray-400">Longitude</p>
                        <p class="font-mono text-xs text-gray-800 mt-1">{{ $pointage->gps_longitude ?? '—' }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <p class="text-[10px] font-bold uppercase text-gray-400">Token (usage unique)</p>
                        <p class="font-mono text-xs text-gray-700 mt-1 break-all bg-gray-50 p-3 rounded-lg border border-gray-100">{{ $pointage->token_validation ?: '—' }}</p>
                        <p class="text-[11px] text-gray-400 mt-1">Expire : {{ $pointage->token_expire_at?->format('d/m/Y H:i') ?: '—' }}</p>
                    </div>
                    @if($pointage->observations)
                        <div class="sm:col-span-2">
                            <p class="text-[10px] font-bold uppercase text-gray-400">Observations</p>
                            <p class="text-gray-700 mt-1">{{ $pointage->observations }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white border border-gray-200/80 rounded-2xl shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/80">
                    <h3 class="text-sm font-extrabold text-gray-900">Alertes liées</h3>
                    <span class="text-xs font-bold text-gray-500 bg-white px-2.5 py-1 rounded-lg border border-gray-200">{{ $pointage->alertes->count() }}</span>
                </div>
                <div class="p-5 space-y-3">
                    @forelse($pointage->alertes as $alerte)
                        <div class="rounded-xl border p-4 {{ $alerte->traitee ? 'border-gray-200 bg-gray-50/50' : 'border-red-200 bg-red-50/40' }}">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-bold text-gray-900">{{ $alerte->message }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ $alerte->type_alerte_libelle ?? $alerte->type_alerte }} · {{ $alerte->gravite_libelle ?? $alerte->gravite }}</p>
                                </div>
                                <span class="shrink-0 text-[10px] font-bold px-2 py-1 rounded-md {{ $alerte->traitee ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $alerte->traitee ? 'Traitée' : 'Ouverte' }}
                                </span>
                            </div>
                            @if(!$alerte->traitee)
                                <form method="POST" action="{{ route('pointages.alertes.traiter', $alerte) }}" class="mt-4 space-y-2">
                                    @csrf
                                    @method('PATCH')
                                    <textarea name="commentaire_traitement" rows="2" placeholder="Commentaire de traitement…"
                                              class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-100 outline-none resize-none"></textarea>
                                    <button type="submit" class="w-full sm:w-auto px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold rounded-xl transition-colors">
                                        Marquer comme traitée
                                    </button>
                                </form>
                            @elseif($alerte->commentaire_traitement)
                                <p class="mt-3 text-xs text-gray-600 bg-white/80 rounded-lg p-3 border border-gray-100">
                                    <span class="font-bold text-gray-700">Traitement :</span> {{ $alerte->commentaire_traitement }}
                                </p>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 text-center py-6">Aucune alerte pour ce pointage.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
