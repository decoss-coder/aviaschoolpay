@extends('layouts.app')
@section('title', 'Résultat — ' . $simulation->nom)
@section('page-title', $simulation->nom)
@section('page-subtitle', 'Détails du scénario simulé')

@section('content')
@php
    $money = fn($v) => number_format((float) $v, 0, ',', ' ');
    $rentable = $simulation->impact_marge > 0;
    $typesLabels = [
        'augmentation_effectif' => '👥 Augmentation effectif',
        'reduction_effectif'    => '👥 Réduction effectif',
        'augmentation_tarif'    => '⬆ Augmentation tarif',
        'reduction_tarif'       => '⬇ Réduction tarif',
        'recrutement'           => '🧑‍🏫 Recrutement',
        'reduction_personnel'   => '🚪 Réduction personnel',
        'reduction_couts'       => '✂ Réduction coûts',
        'ajout_service'         => '➕ Ajout service',
        'investissement'        => '🏗 Investissement',
        'scenario_libre'        => '✏ Scénario libre',
    ];
@endphp

<div class="space-y-6">
    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
    @endif

    <div class="flex items-center justify-between">
        <a href="{{ route('simulations.index') }}" class="text-sm font-semibold text-gray-500 hover:text-cyan-600">← Retour aux simulations</a>
        <form method="POST" action="{{ route('simulations.destroy', $simulation->id) }}" onsubmit="return confirm('Supprimer cette simulation ?')">
            @csrf @method('DELETE')
            <button class="text-xs font-bold text-red-600 hover:text-red-800">🗑 Supprimer</button>
        </form>
    </div>

    {{-- Hero --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
        <div class="h-1.5 {{ $rentable ? 'bg-gradient-to-r from-emerald-400 to-emerald-600' : 'bg-gradient-to-r from-red-400 to-red-600' }}"></div>
        <div class="p-6 lg:p-8">
            <div class="flex items-start justify-between flex-wrap gap-4">
                <div>
                    <p class="text-xs font-bold uppercase text-gray-500">{{ $typesLabels[$simulation->type] ?? $simulation->type }} · Horizon {{ str_replace('_', ' ', $simulation->horizon) }}</p>
                    <h2 class="font-display text-3xl font-extrabold text-gray-900 mt-1">{{ $simulation->nom }}</h2>
                    @if($simulation->description)
                        <p class="text-sm text-gray-600 mt-2 max-w-2xl">{{ $simulation->description }}</p>
                    @endif
                </div>
                <span class="inline-flex px-4 py-2 rounded-xl text-sm font-extrabold {{ $rentable ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                    {{ $rentable ? '✓ Scénario rentable' : '⚠ Scénario non rentable' }}
                </span>
            </div>

            {{-- KPIs résultats --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-6 pt-6 border-t border-gray-100">
                <div class="text-center">
                    <p class="text-xs font-bold uppercase text-blue-600 tracking-wider">Impact revenus</p>
                    <p class="text-2xl font-extrabold text-blue-700 mt-1">{{ $simulation->impact_revenus >= 0 ? '+' : '' }}{{ $money($simulation->impact_revenus) }}</p>
                    <p class="text-[10px] font-bold text-gray-400">FCFA</p>
                </div>
                <div class="text-center">
                    <p class="text-xs font-bold uppercase text-rose-600 tracking-wider">Impact dépenses</p>
                    <p class="text-2xl font-extrabold text-rose-700 mt-1">{{ $simulation->impact_depenses >= 0 ? '+' : '' }}{{ $money($simulation->impact_depenses) }}</p>
                    <p class="text-[10px] font-bold text-gray-400">FCFA</p>
                </div>
                <div class="text-center">
                    <p class="text-xs font-bold uppercase {{ $rentable ? 'text-brand-600' : 'text-red-600' }} tracking-wider">Impact marge</p>
                    <p class="text-2xl font-extrabold {{ $rentable ? 'text-brand-700' : 'text-red-700' }} mt-1">{{ $money($simulation->impact_marge) }}</p>
                    <p class="text-[10px] font-bold text-gray-400">FCFA</p>
                </div>
                <div class="text-center">
                    <p class="text-xs font-bold uppercase text-gray-400 tracking-wider">ROI</p>
                    <p class="text-2xl font-extrabold text-gray-900 mt-1">{{ $simulation->roi_pourcent !== null ? $simulation->roi_pourcent.'%' : '—' }}</p>
                    @if($simulation->delai_rentabilite_mois)
                        <p class="text-[10px] font-bold text-gray-400">Rentable en {{ $simulation->delai_rentabilite_mois }} mois</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Paramètres + Données de base --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-card p-5">
            <h3 class="font-extrabold text-gray-900 mb-3">Paramètres saisis</h3>
            @if(empty($simulation->parametres))
                <p class="text-sm text-gray-400 italic">Aucun paramètre.</p>
            @else
                <dl class="space-y-2 text-sm">
                    @foreach($simulation->parametres as $k => $v)
                        <div class="flex justify-between py-2 border-b border-gray-100 last:border-0">
                            <dt class="text-gray-500 font-semibold">{{ ucfirst(str_replace('_', ' ', $k)) }}</dt>
                            <dd class="font-extrabold text-gray-900">{{ is_numeric($v) ? $money($v) : $v }}</dd>
                        </div>
                    @endforeach
                </dl>
            @endif
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-card p-5">
            <h3 class="font-extrabold text-gray-900 mb-3">Données de base utilisées</h3>
            @php $r = $simulation->resultats ?? []; @endphp
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <dt class="text-gray-500 font-semibold">Revenus actuels (année)</dt>
                    <dd class="font-extrabold text-blue-700">{{ $money($r['revenus_actuels'] ?? 0) }} F</dd>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <dt class="text-gray-500 font-semibold">Dépenses actuelles (exercice)</dt>
                    <dd class="font-extrabold text-rose-700">{{ $money($r['depenses_actuelles'] ?? 0) }} F</dd>
                </div>
                <div class="flex justify-between py-2">
                    <dt class="text-gray-500 font-semibold">Élèves inscrits</dt>
                    <dd class="font-extrabold text-gray-900">{{ $r['nb_eleves'] ?? 0 }}</dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- Meta --}}
    <div class="bg-gray-50 rounded-2xl p-4 text-xs text-gray-600 flex flex-wrap gap-4">
        <span>Créé par <b>{{ $simulation->creePar?->name ?? '—' }}</b></span>
        <span>·</span>
        <span>Le {{ $simulation->created_at?->format('d/m/Y H:i') }}</span>
        <span>·</span>
        <span>Statut : <b>{{ ucfirst($simulation->statut) }}</b></span>
        @if($simulation->favori)<span>·</span><span class="text-amber-600">⭐ Favori</span>@endif
    </div>
</div>
@endsection
