@extends('layouts.app')

@section('title', 'Dashboard RH')
@section('page-title', 'Dashboard RH')
@section('page-subtitle', 'Vue d’ensemble des enseignants et du contrôle terrain')

@section('content')
@include('partials.rh-admin-nav')

<div class="space-y-6">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white border border-brand-100 rounded-2xl p-5 shadow-card-brand">
            <p class="text-[11px] uppercase tracking-wider font-bold text-gray-500">Enseignants actifs</p>
            <p class="mt-2 text-3xl font-extrabold text-gray-900">{{ $totalEnseignants }}</p>
        </div>

        <div class="bg-white border border-emerald-100 rounded-2xl p-5 shadow-sm">
            <p class="text-[11px] uppercase tracking-wider font-bold text-emerald-600">Présents aujourd’hui</p>
            <p class="mt-2 text-3xl font-extrabold text-emerald-700">{{ $presents }}</p>
        </div>

        <div class="bg-white border border-amber-100 rounded-2xl p-5 shadow-sm">
            <p class="text-[11px] uppercase tracking-wider font-bold text-amber-600">Retards</p>
            <p class="mt-2 text-3xl font-extrabold text-amber-700">{{ $retards }}</p>
        </div>

        <div class="bg-white border border-red-100 rounded-2xl p-5 shadow-sm">
            <p class="text-[11px] uppercase tracking-wider font-bold text-red-600">Absents</p>
            <p class="mt-2 text-3xl font-extrabold text-red-700">{{ $absents }}</p>
        </div>

        <div class="bg-white border border-red-100 rounded-2xl p-5 shadow-sm">
            <p class="text-[11px] uppercase tracking-wider font-bold text-red-600">Alertes non traitées</p>
            <p class="mt-2 text-3xl font-extrabold text-red-700">{{ $alertesNonTraitees }}</p>
        </div>

        <div class="bg-white border border-blue-100 rounded-2xl p-5 shadow-sm">
            <p class="text-[11px] uppercase tracking-wider font-bold text-blue-600">Congés en attente</p>
            <p class="mt-2 text-3xl font-extrabold text-blue-700">{{ $congesEnAttente }}</p>
        </div>

        <div class="bg-white border border-gold-200 rounded-2xl p-5 shadow-card-gold">
            <p class="text-[11px] uppercase tracking-wider font-bold text-gold-700">Masse salariale du mois</p>
            <p class="mt-2 text-2xl font-extrabold text-gold-700">{{ number_format($masseSalarialeMois, 0, ',', ' ') }} F</p>
        </div>

        <div class="bg-white border border-brand-100 rounded-2xl p-5 shadow-card-brand">
            <p class="text-[11px] uppercase tracking-wider font-bold text-brand-600">Score ponctualité moyen</p>
            <p class="mt-2 text-3xl font-extrabold text-brand-700">{{ number_format($scorePonctualiteMoyen, 2, ',', ' ') }}%</p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="bg-white border border-brand-100 rounded-2xl p-5 shadow-card-brand">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-display text-base font-extrabold text-gray-900">Enseignants en retard aujourd’hui</h3>
                <a href="{{ route('admin.rh.pointages.index', ['statut' => 'retard']) }}" class="text-sm font-bold text-brand-600 hover:text-brand-700">
                    Voir tout
                </a>
            </div>

            <div class="space-y-3">
                @forelse($enseignantsEnRetard as $item)
                    <div class="flex items-center justify-between rounded-xl border border-amber-100 bg-amber-50/50 px-4 py-3">
                        <div>
                            <p class="font-bold text-gray-900">{{ $item->enseignant->nom_complet ?? '—' }}</p>
                            <p class="text-xs text-gray-500">{{ $item->heure_scan }}</p>
                        </div>
                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold text-amber-700 bg-amber-100">
                            Retard
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">Aucun retard aujourd’hui.</p>
                @endforelse
            </div>
        </div>

        <div class="bg-white border border-brand-100 rounded-2xl p-5 shadow-card-brand">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-display text-base font-extrabold text-gray-900">Dernières alertes</h3>
                <a href="{{ route('admin.rh.alertes.index') }}" class="text-sm font-bold text-brand-600 hover:text-brand-700">
                    Voir tout
                </a>
            </div>

            <div class="space-y-3">
                @forelse($dernieresAlertes as $alerte)
                    <div class="rounded-xl border {{ $alerte->traitee ? 'border-gray-200 bg-gray-50/50' : 'border-red-200 bg-red-50/60' }} px-4 py-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-bold text-gray-900">{{ $alerte->enseignant->nom_complet ?? '—' }}</p>
                                <p class="text-sm text-gray-600 mt-1">{{ $alerte->message }}</p>
                            </div>
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold {{ $alerte->traitee ? 'text-emerald-700 bg-emerald-100' : 'text-red-700 bg-red-100' }}">
                                {{ $alerte->traitee ? 'Traitée' : 'À traiter' }}
                            </span>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">Aucune alerte récente.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection