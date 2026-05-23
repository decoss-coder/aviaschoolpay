@extends('layouts.app')
@section('title', 'Consultation année ' . $annee->libelle)
@section('page-title', 'Année ' . $annee->libelle)
@section('page-subtitle', 'Mode lecture seule')

@section('content')
<div class="space-y-6">
    <a href="{{ route('admin.annees.index') }}" class="text-sm font-semibold text-gray-500 hover:text-brand-600">← Retour aux années</a>

    {{-- Bandeau MODE LECTURE SEULE --}}
    <div class="rounded-2xl border-2 border-blue-300 bg-gradient-to-br from-blue-50 to-indigo-50 p-6 shadow-card-blue">
        <div class="flex items-start gap-4">
            <div class="w-14 h-14 bg-blue-600 rounded-2xl flex items-center justify-center text-3xl flex-shrink-0">📖</div>
            <div class="flex-1">
                <div class="flex items-center gap-2 flex-wrap">
                    <h2 class="font-display text-2xl font-extrabold text-blue-900">Année {{ $annee->libelle }}</h2>
                    <span class="inline-flex px-3 py-1 rounded-full bg-blue-600 text-white text-xs font-extrabold">🔒 LECTURE SEULE</span>
                    <span class="inline-flex px-3 py-1 rounded-full bg-gray-200 text-gray-700 text-xs font-bold">Archivée chiffrée</span>
                </div>
                <p class="text-sm text-blue-800 mt-2">
                    Cette année est <b>clôturée et archivée</b>. Aucune modification n'est possible.
                    Pour accéder aux données détaillées (élèves, notes, paiements…), demandez la clé de restauration auprès d'Avia Technologie.
                </p>
                <p class="text-xs text-blue-600 mt-2">
                    Période : du <b>{{ $annee->date_debut?->format('d/m/Y') }}</b> au <b>{{ $annee->date_fin?->format('d/m/Y') }}</b>
                    @if($annee->archived_at)
                        · Archivée le <b>{{ $annee->archived_at->format('d/m/Y H:i') }}</b>
                    @endif
                </p>
            </div>
        </div>
    </div>

    {{-- Résumé des données archivées --}}
    @if(! empty($counts))
        <div class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-extrabold text-gray-900">📦 Données archivées</h3>
                <span class="text-xs font-semibold text-gray-500">Synthèse du contenu</span>
            </div>
            <div class="p-5 grid grid-cols-2 lg:grid-cols-4 gap-4">
                @php
                    $icons = [
                        'classes' => '🏫', 'inscriptions' => '🎓', 'paiements' => '💳',
                        'trimestres' => '📅', 'notes' => '📝', 'moyennes' => '📊',
                    ];
                    $colors = [
                        'classes' => 'violet', 'inscriptions' => 'blue', 'paiements' => 'emerald',
                        'trimestres' => 'amber', 'notes' => 'pink', 'moyennes' => 'cyan',
                    ];
                @endphp
                @foreach($counts as $type => $nb)
                    @if($nb > 0)
                        @php $color = $colors[$type] ?? 'gray'; @endphp
                        <div class="bg-{{ $color }}-50 border border-{{ $color }}-200 rounded-2xl p-4">
                            <div class="text-3xl mb-2">{{ $icons[$type] ?? '📁' }}</div>
                            <p class="text-xs font-bold uppercase text-{{ $color }}-700 tracking-wider">{{ ucfirst($type) }}</p>
                            <p class="text-2xl font-extrabold text-{{ $color }}-900 mt-1">{{ number_format($nb, 0, ',', ' ') }}</p>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @else
        <div class="rounded-2xl border border-gray-200 bg-gray-50 p-5 text-sm text-gray-600">
            Aucune métadonnée de comptage disponible pour cette archive.
        </div>
    @endif

    {{-- Actions disponibles --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-card p-6">
        <h3 class="font-extrabold text-gray-900 mb-4">🔑 Actions disponibles</h3>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <form method="POST" action="{{ route('admin.annees.demander-restauration', $annee) }}">
                @csrf
                <div class="bg-amber-50 border-2 border-amber-200 rounded-2xl p-5 h-full hover:shadow-card-gold transition">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-2xl">💰</span>
                        <h4 class="font-bold text-amber-900">Demander la clé (500 F)</h4>
                    </div>
                    <p class="text-xs text-amber-700 mb-4">
                        Crée une demande de restauration. Vous recevrez un lien Wave Avia pour payer 500 FCFA.
                        Après paiement, Avia vous communiquera la clé pour décrypter et restaurer les données.
                    </p>
                    <button type="submit" class="w-full px-4 py-2.5 bg-amber-600 hover:bg-amber-700 text-white text-sm font-bold rounded-xl">
                        Créer la demande →
                    </button>
                </div>
            </form>

            <div class="bg-blue-50 border-2 border-blue-200 rounded-2xl p-5 h-full">
                <div class="flex items-center gap-2 mb-3">
                    <span class="text-2xl">🔓</span>
                    <h4 class="font-bold text-blue-900">J'ai déjà la clé</h4>
                </div>
                <p class="text-xs text-blue-700 mb-4">
                    Si vous possédez déjà la clé de restauration (XXXX-XXXX-XXXX-XXXX),
                    saisissez-la sur la page principale pour restaurer les données en lecture seule temporaire.
                </p>
                <a href="{{ route('admin.annees.index') }}#cle-{{ $annee->id }}" class="block w-full text-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-xl">
                    Saisir la clé →
                </a>
            </div>
        </div>
    </div>

    {{-- Pied page : sécurité --}}
    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 flex items-start gap-3">
        <span class="text-xl">🔐</span>
        <div class="text-sm text-emerald-900">
            <p class="font-bold">Sécurité de l'archive</p>
            <p class="mt-1 text-emerald-800">
                Cette archive est chiffrée en <b>AES-256-CBC</b>. Sans la clé de restauration,
                aucun accès aux données n'est possible — ni pour la direction, ni pour les administrateurs serveur.
            </p>
        </div>
    </div>
</div>
@endsection
