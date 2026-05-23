@extends('layouts.app')

@section('title', 'Finances — ' . $eleve->prenom . ' ' . $eleve->nom)
@section('page-title', 'Fiche financière')
@section('page-subtitle', $eleve->prenom . ' ' . strtoupper($eleve->nom))

@section('content')
@php
    $resume = $finances['resume'] ?? [];
    $inscriptions = $finances['inscriptions'] ?? [];
    $totalDu = (int) ($resume['montant_total_du'] ?? 0);
    $totalPaye = (int) ($resume['montant_paye'] ?? 0);
    $reste = (int) ($resume['reste_a_payer'] ?? 0);
    $pct = $totalDu > 0 ? min(100, round(($totalPaye / $totalDu) * 100, 1)) : 0;
    $isAff = $eleve->statut_eleve === 'AFF';
@endphp

<div class="max-w-5xl mx-auto space-y-5">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm">
        <a href="{{ route('finances.index') }}" class="text-brand-600 font-semibold hover:underline">Finances</a>
        <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-600">Élève</span>
        <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-900 font-bold">{{ $eleve->prenom }} {{ strtoupper($eleve->nom) }}</span>
    </nav>

    @if(session('success'))
        <div class="px-4 py-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm">
            @foreach($errors->all() as $e) <p>• {{ $e }}</p> @endforeach
        </div>
    @endif

    {{-- ──────────────  HERO ÉLÈVE  ────────────── --}}
    <div class="relative overflow-hidden bg-white rounded-3xl border border-gray-100 shadow-sm p-6">
        <div class="absolute top-0 right-0 w-64 h-64 bg-gradient-to-bl from-brand-100/40 to-transparent rounded-full blur-3xl"></div>

        <div class="relative flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-start gap-4">
                <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-xl font-extrabold {{ $isAff ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                    {{ strtoupper(substr($eleve->prenom ?? '', 0, 1) . substr($eleve->nom ?? '', 0, 1)) }}
                </div>
                <div>
                    <h1 class="text-2xl font-extrabold text-gray-900">{{ $eleve->prenom }} {{ strtoupper($eleve->nom) }}</h1>
                    <div class="flex flex-wrap items-center gap-2 mt-2">
                        <span class="text-xs font-bold bg-gray-100 text-gray-700 px-2 py-1 rounded-lg">
                            {{ $eleve->classe?->nom ?? 'Sans classe' }}
                        </span>
                        <span class="text-xs font-bold px-2 py-1 rounded-lg {{ $isAff ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                            {{ $finances['statut_eleve_libelle'] ?? $eleve->statut_eleve }}
                        </span>
                        <span class="text-xs font-mono text-gray-400">{{ $eleve->matricule_interne }}</span>
                    </div>
                </div>
            </div>

            <a href="{{ route('paiements.create', ['eleve_id' => $eleve->id]) }}" class="px-5 py-2.5 rounded-xl bg-brand-600 text-white text-sm font-bold hover:bg-brand-700 transition shadow-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Nouveau paiement
            </a>
        </div>

        @if(!empty($finances['message']))
            <div class="mt-4 px-4 py-3 rounded-xl bg-blue-50 border border-blue-100 text-blue-900 text-sm flex gap-2 items-start">
                <svg class="w-5 h-5 flex-shrink-0 text-blue-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ $finances['message'] }}</span>
            </div>
        @endif

        {{-- KPI financiers --}}
        <div class="grid grid-cols-3 gap-3 mt-6">
            <div class="bg-gray-50 rounded-xl p-4">
                <p class="text-[11px] text-gray-500 uppercase font-bold">Total dû</p>
                <p class="text-xl font-extrabold text-gray-900 mt-1">{{ number_format($totalDu, 0, ',', ' ') }}<span class="text-xs text-gray-400 ml-1">F</span></p>
            </div>
            <div class="bg-emerald-50 rounded-xl p-4">
                <p class="text-[11px] text-emerald-700 uppercase font-bold">Payé</p>
                <p class="text-xl font-extrabold text-emerald-700 mt-1">{{ number_format($totalPaye, 0, ',', ' ') }}<span class="text-xs text-emerald-400 ml-1">F</span></p>
            </div>
            <div class="bg-amber-50 rounded-xl p-4">
                <p class="text-[11px] text-amber-700 uppercase font-bold">Reste</p>
                <p class="text-xl font-extrabold text-amber-700 mt-1">{{ number_format($reste, 0, ',', ' ') }}<span class="text-xs text-amber-400 ml-1">F</span></p>
            </div>
        </div>

        {{-- Progression --}}
        <div class="mt-4">
            <div class="flex justify-between items-baseline text-xs mb-1.5">
                <span class="font-bold text-gray-600 uppercase">Avancement de la scolarité</span>
                <span class="font-extrabold text-gray-900">{{ $pct }}%</span>
            </div>
            <div class="w-full bg-gray-100 rounded-full h-2.5 overflow-hidden">
                <div class="h-2.5 rounded-full bg-gradient-to-r {{ $pct >= 100 ? 'from-emerald-400 to-emerald-600' : 'from-brand-400 to-brand-600' }} transition-all duration-700" style="width: {{ $pct }}%"></div>
            </div>
        </div>

        {{-- Bouton Wave (si applicable) --}}
        @if($waveActif && $reste > 0)
            <div class="mt-5 pt-5 border-t border-gray-100">
                @include('finances._wave-payer', [
                    'waveFormAction' => route('finances.eleve.lien-wave', $eleve),
                ])
            </div>
        @endif
    </div>

    {{-- ──────────────  INSCRIPTIONS  ────────────── --}}
    @forelse($inscriptions as $insc)
        @php
            $insReste = (int) ($insc['reste_a_payer'] ?? 0);
            $insTotal = (int) (($insc['montant_inscription'] ?? 0) + ($insc['montant_scolarite'] ?? 0));
            $insPaye = max(0, $insTotal - $insReste);
            $insPct = $insTotal > 0 ? min(100, round(($insPaye / $insTotal) * 100, 1)) : 0;
            $isPaid = $insReste <= 0;
        @endphp

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-start justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="font-bold text-gray-900">{{ $insc['annee_scolaire']['libelle'] ?? 'Année' }}</h3>
                        <span class="text-xs text-gray-300">·</span>
                        <span class="text-sm font-semibold text-gray-700">{{ $insc['classe']['nom'] ?? '' }}</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ $insc['libelle_inscription'] }}
                        @if(!empty($insc['statut_paiement']))
                            <span class="ml-2 inline-block px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $isPaid ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                {{ str_replace('_', ' ', $insc['statut_paiement']) }}
                            </span>
                        @endif
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-[10px] uppercase text-gray-400 font-bold">Reste</p>
                    <p class="text-xl font-extrabold {{ $isPaid ? 'text-emerald-600' : 'text-amber-600' }}">
                        {{ number_format($insReste, 0, ',', ' ') }}<span class="text-xs text-gray-400 ml-1">F</span>
                    </p>
                </div>
            </div>

            <div class="px-6 py-4 grid grid-cols-1 sm:grid-cols-3 gap-3 border-b border-gray-50 bg-gray-50/30">
                <div class="text-sm">
                    <p class="text-[10px] uppercase text-gray-400 font-bold">Inscription</p>
                    <p class="font-bold text-gray-900">{{ number_format($insc['montant_inscription'] ?? 0, 0, ',', ' ') }} F</p>
                </div>
                @if($finances['scolarite_applicable'] ?? false)
                    <div class="text-sm">
                        <p class="text-[10px] uppercase text-gray-400 font-bold">Scolarité</p>
                        <p class="font-bold text-gray-900">{{ number_format($insc['montant_scolarite'] ?? 0, 0, ',', ' ') }} F</p>
                    </div>
                @endif
                <div class="text-sm">
                    <p class="text-[10px] uppercase text-gray-400 font-bold">Avancement</p>
                    <div class="flex items-center gap-2 mt-1">
                        <div class="flex-1 bg-gray-200 rounded-full h-1.5 overflow-hidden">
                            <div class="h-1.5 rounded-full bg-gradient-to-r {{ $isPaid ? 'from-emerald-400 to-emerald-600' : 'from-brand-400 to-brand-600' }}" style="width: {{ $insPct }}%"></div>
                        </div>
                        <span class="text-xs font-bold text-gray-700">{{ $insPct }}%</span>
                    </div>
                </div>
            </div>

            @if(!empty($insc['paiements']))
                <div class="divide-y divide-gray-50">
                    <div class="px-6 py-2 bg-gray-50/40 text-[10px] uppercase font-bold text-gray-500 tracking-wider grid grid-cols-12 gap-2">
                        <span class="col-span-3">Date</span>
                        <span class="col-span-3">Mode</span>
                        <span class="col-span-3 text-right">Montant</span>
                        <span class="col-span-3 text-right">Statut</span>
                    </div>
                    @foreach($insc['paiements'] as $p)
                        @php
                            $pStatut = strtolower($p['statut'] ?? '');
                            $statutBadge = match($pStatut) {
                                'confirme' => 'bg-emerald-100 text-emerald-700',
                                'en_attente' => 'bg-amber-100 text-amber-700',
                                'annule' => 'bg-gray-100 text-gray-600',
                                'echoue' => 'bg-red-100 text-red-700',
                                default => 'bg-gray-100 text-gray-600',
                            };
                        @endphp
                        <div class="px-6 py-3 grid grid-cols-12 gap-2 text-sm hover:bg-gray-50/60 transition">
                            <span class="col-span-3 text-gray-600">{{ $p['date_paiement'] ?? '' }}</span>
                            <span class="col-span-3 text-gray-700">{{ str_replace('_', ' ', ucfirst($p['mode'] ?? '')) }}</span>
                            <span class="col-span-3 text-right font-bold text-gray-900">{{ number_format($p['montant'] ?? 0, 0, ',', ' ') }} F</span>
                            <span class="col-span-3 text-right">
                                <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $statutBadge }}">
                                    {{ str_replace('_', ' ', $pStatut) }}
                                </span>
                            </span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="px-6 py-6 text-center text-sm text-gray-400">Aucun paiement enregistré pour cette inscription.</div>
            @endif
        </div>
    @empty
        <div class="bg-white rounded-2xl border border-gray-100 p-10 text-center">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-gray-100 flex items-center justify-center mb-3">
                <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <p class="font-bold text-gray-900">Aucune inscription</p>
            <p class="text-sm text-gray-500 mt-1">Cet élève n'a pas encore d'inscription validée.</p>
        </div>
    @endforelse
</div>
@endsection
