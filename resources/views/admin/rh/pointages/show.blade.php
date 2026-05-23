@extends('layouts.app')

@section('title', 'Détail pointage')
@section('page-title', 'Détail du pointage')
@section('page-subtitle', $pointage->enseignant->nom_complet ?? 'Pointage')

@section('content')
@include('partials.rh-admin-nav')

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-1 bg-white border border-brand-100 rounded-2xl p-5 shadow-card-brand">
        <h3 class="font-display text-base font-extrabold text-gray-900 mb-4">Enseignant</h3>
        <p class="font-bold text-gray-900">{{ $pointage->enseignant->nom_complet ?? '—' }}</p>
        <p class="text-sm text-gray-500 mt-1">{{ $pointage->enseignant->telephone ?? '—' }}</p>
        <div class="mt-4 space-y-2 text-sm">
            <div><strong>Date :</strong> {{ $pointage->date?->format('d/m/Y') }}</div>
            <div><strong>Heure :</strong> {{ $pointage->heure_scan }}</div>
            <div><strong>Type :</strong> {{ $pointage->type_scan_libelle }}</div>
            <div><strong>Méthode :</strong> {{ $pointage->methode_libelle }}</div>
            <div><strong>Statut :</strong> {{ $pointage->statut_libelle }}</div>
            <div><strong>Salle :</strong> {{ $pointage->salle->nom ?? '—' }}</div>
            <div><strong>GPS valide :</strong> {{ $pointage->gps_valide ? 'Oui' : 'Non' }}</div>
            <div><strong>Token valide :</strong> {{ $pointage->token_valide ? 'Oui' : 'Non' }}</div>
            <div><strong>EDT conforme :</strong> {{ $pointage->conforme_emploi_temps ? 'Oui' : 'Non' }}</div>
            <div><strong>Spoofing :</strong> {{ $pointage->spoofing_detecte ? 'Oui' : 'Non' }}</div>
        </div>
    </div>

    <div class="lg:col-span-2 space-y-6">
        @include('pointage.partials.cahier-texte-detail', [
            'pointage' => $pointage,
            'cahierRoute' => 'admin.rh.pointages.cahier-texte',
        ])

        <div class="bg-white border border-brand-100 rounded-2xl p-5 shadow-card-brand">
            <h3 class="font-display text-base font-extrabold text-gray-900 mb-4">Observations</h3>
            <p class="text-sm text-gray-700">{{ $pointage->observations ?: 'Aucune observation.' }}</p>
        </div>

        <div class="bg-white border border-brand-100 rounded-2xl p-5 shadow-card-brand">
            <h3 class="font-display text-base font-extrabold text-gray-900 mb-4">Alertes liées</h3>
            <div class="space-y-3">
                @forelse($pointage->alertes as $alerte)
                    <div class="rounded-xl border {{ $alerte->traitee ? 'border-gray-200 bg-gray-50/50' : 'border-red-200 bg-red-50/60' }} p-4">
                        <p class="font-bold text-gray-900">{{ $alerte->message }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ $alerte->gravite_libelle }} · {{ $alerte->date?->format('d/m/Y') }}</p>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">Aucune alerte liée à ce pointage.</p>
                @endforelse
            </div>
        </div>

        @if($pointage->selfie_path)
            <div class="bg-white border border-brand-100 rounded-2xl p-5 shadow-card-brand">
                <h3 class="font-display text-base font-extrabold text-gray-900 mb-4">Selfie de contrôle</h3>
                <img src="{{ route('admin.rh.pointages.selfie', $pointage) }}" alt="Selfie"
                     class="max-w-full rounded-2xl border border-brand-100 shadow-sm">
            </div>
        @endif
    </div>
</div>
@endsection