@extends('layouts.app')
@section('title', 'Paiements — ' . $eleve->prenom . ' ' . $eleve->nom)

@section('content')
@php
    $resume = $finances['resume'] ?? [];
    $inscriptions = $finances['inscriptions'] ?? [];
@endphp
<div class="max-w-4xl mx-auto px-4 py-8 space-y-6">

    <div class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('mon-espace-parent.dashboard') }}" class="hover:text-emerald-600 font-medium">Espace parent</a>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="font-semibold text-gray-900">{{ $eleve->prenom }} {{ strtoupper($eleve->nom) }} — Paiements</span>
    </div>

    @include('finances._wave-payer', [
        'waveFormAction' => route('mon-espace-parent.paiements.wave', $eleve),
    ])

    <div class="bg-emerald-50 border border-emerald-100 rounded-xl px-4 py-3 text-sm text-emerald-800">
        <span class="font-semibold">{{ $finances['statut_eleve_libelle'] ?? $eleve->statut_eleve }}</span>
        @if(!empty($finances['message']))
            · {{ $finances['message'] }}
        @endif
        @if($eleve->etablissement)
            · {{ $eleve->etablissement->nom }}
        @endif
    </div>

    @forelse($inscriptions as $insc)
        @php
            $paye = (int) ($insc['montant_paye'] ?? 0);
            $reste = (int) ($insc['reste_a_payer'] ?? 0);
            $total = (int) ($insc['montant_total_du'] ?? 0);
            $taux = (float) ($insc['taux_paiement'] ?? 0);
        @endphp
        <div class="bg-white rounded-2xl shadow-card border border-gray-100 overflow-hidden">

            <div class="px-6 py-5 border-b border-gray-100">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <p class="font-semibold text-gray-900">
                            {{ $insc['annee_scolaire']['libelle'] ?? 'Année en cours' }}
                            <span class="ml-2 text-xs px-2 py-0.5 rounded-full font-medium
                                {{ $reste == 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                {{ $insc['statut_paiement'] ?? ($reste == 0 ? 'Soldé' : 'En cours') }}
                            </span>
                        </p>
                        <p class="text-sm text-gray-500 mt-0.5">{{ $insc['classe']['nom'] ?? $eleve->classe?->nom }}</p>
                        @if($finances['scolarite_applicable'] ?? false)
                            <p class="text-xs text-gray-400 mt-1">Inscription {{ number_format($insc['montant_inscription'] ?? 0, 0, ',', ' ') }} + Scolarité {{ number_format($insc['montant_scolarite'] ?? 0, 0, ',', ' ') }} FCFA</p>
                        @else
                            <p class="text-xs text-gray-400 mt-1">{{ $insc['libelle_inscription'] ?? 'Frais d\'inscription' }} : {{ number_format($insc['montant_inscription'] ?? 0, 0, ',', ' ') }} FCFA</p>
                        @endif
                    </div>
                    <div class="text-right">
                        <p class="text-2xl font-extrabold {{ $reste > 0 ? 'text-amber-600' : 'text-emerald-600' }}">
                            {{ number_format($reste, 0, ',', ' ') }} FCFA
                        </p>
                        <p class="text-xs text-gray-500">reste sur {{ number_format($total, 0, ',', ' ') }} FCFA</p>
                    </div>
                </div>

                <div class="mt-4">
                    <div class="flex justify-between text-xs text-gray-500 mb-1">
                        <span>Payé : {{ number_format($paye, 0, ',', ' ') }} FCFA</span>
                        <span>{{ $taux }}%</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2">
                        <div class="h-2 rounded-full {{ $taux >= 100 ? 'bg-emerald-500' : 'bg-amber-500' }}"
                             style="width: {{ min($taux, 100) }}%"></div>
                    </div>
                </div>
            </div>

            @if(!empty($insc['paiements']))
            <div class="px-6 py-4">
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Historique des paiements</h3>
                <div class="space-y-2">
                    @foreach($insc['paiements'] as $p)
                    <div class="flex items-center justify-between py-2.5 border-b border-gray-50 last:border-0">
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ number_format($p['montant'], 0, ',', ' ') }} FCFA</p>
                            <p class="text-xs text-gray-400">{{ str_replace('_', ' ', $p['mode'] ?? '') }} · {{ $p['date_paiement'] ?? '—' }}</p>
                        </div>
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ ($p['statut'] ?? '') === 'confirme' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ ($p['statut'] ?? '') === 'confirme' ? 'Confirmé' : ucfirst($p['statut'] ?? '') }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
            @else
                <div class="px-6 py-6 text-center text-sm text-gray-400">Aucun paiement enregistré.</div>
            @endif
        </div>
    @empty
        <div class="bg-white rounded-2xl shadow-card border border-gray-100 p-8 text-center text-gray-400">
            Aucune inscription validée pour {{ $eleve->prenom }}.
        </div>
    @endforelse

    <div class="flex gap-3">
        <a href="{{ route('mon-espace-parent.dashboard') }}"
           class="flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-200 text-sm text-gray-600 hover:border-gray-300">
            Retour
        </a>
    </div>

</div>
@endsection
