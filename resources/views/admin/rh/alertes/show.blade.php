@extends('layouts.app')

@section('title', 'Détail alerte')
@section('page-title', 'Détail de l’alerte')
@section('page-subtitle', $alerte->enseignant->nom_complet ?? 'Alerte')

@section('content')
@include('partials.rh-admin-nav')

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-1 bg-white border border-brand-100 rounded-2xl p-5 shadow-card-brand">
        <h3 class="font-display text-base font-extrabold text-gray-900 mb-4">Informations</h3>
        <div class="space-y-2 text-sm">
            <div><strong>Enseignant :</strong> {{ $alerte->enseignant->nom_complet ?? '—' }}</div>
            <div><strong>Date :</strong> {{ $alerte->date?->format('d/m/Y') }}</div>
            <div><strong>Type :</strong> {{ $alerte->type_alerte_libelle }}</div>
            <div><strong>Gravité :</strong> {{ $alerte->gravite_libelle }}</div>
            <div><strong>Lecture :</strong> {{ $alerte->lue ? 'Lue' : 'Non lue' }}</div>
            <div><strong>Traitement :</strong> {{ $alerte->traitee ? 'Traitée' : 'À traiter' }}</div>
        </div>
    </div>

    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white border border-brand-100 rounded-2xl p-5 shadow-card-brand">
            <h3 class="font-display text-base font-extrabold text-gray-900 mb-4">Message</h3>
            <p class="text-sm text-gray-700">{{ $alerte->message }}</p>
        </div>

        @if($alerte->pointage)
            <div class="bg-white border border-brand-100 rounded-2xl p-5 shadow-card-brand">
                <h3 class="font-display text-base font-extrabold text-gray-900 mb-4">Pointage lié</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div><strong>Heure scan :</strong> {{ $alerte->pointage->heure_scan }}</div>
                    <div><strong>Type :</strong> {{ $alerte->pointage->type_scan_libelle }}</div>
                    <div><strong>Statut :</strong> {{ $alerte->pointage->statut_libelle }}</div>
                    <div><strong>Salle :</strong> {{ $alerte->pointage->salle->nom ?? '—' }}</div>
                </div>
            </div>
        @endif

        <div class="bg-white border border-brand-100 rounded-2xl p-5 shadow-card-brand">
            <h3 class="font-display text-base font-extrabold text-gray-900 mb-4">Traitement</h3>

            @if($alerte->traitee)
                <div class="rounded-xl border border-emerald-100 bg-emerald-50/60 p-4">
                    <p class="text-sm font-bold text-emerald-700">Alerte déjà traitée</p>
                    <p class="text-xs text-gray-500 mt-1">
                        Par : {{ $alerte->traiteePar->name ?? $alerte->traiteePar->email ?? ('Utilisateur #'.$alerte->traitee_par) }}
                    </p>
                    @if($alerte->commentaire_traitement)
                        <p class="text-sm text-gray-700 mt-3">{{ $alerte->commentaire_traitement }}</p>
                    @endif
                </div>
            @else
                <form method="POST" action="{{ route('admin.rh.alertes.traiter', $alerte) }}" class="space-y-4">
                    @csrf
                    @method('PATCH')

                    <textarea name="commentaire_traitement" rows="4"
                              placeholder="Commentaire de traitement..."
                              class="w-full px-3 py-2.5 border border-brand-100 rounded-xl"></textarea>

                    <div class="flex justify-end">
                        <button class="px-5 py-2.5 bg-gradient-to-r from-brand-500 via-brand-600 to-brand-700 text-white rounded-xl text-sm font-bold shadow-brand-glow">
                            Marquer comme traitée
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection