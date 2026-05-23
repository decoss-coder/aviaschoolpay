@extends('layouts.app')

@section('title', 'Tableau de bord')
@section('page-title', 'Tableau de bord')
@section('page-subtitle')
{{ $etab->nom ?? 'AviaSchoolPay' }} — {{ $annee->libelle ?? '2025-2026' }}
@endsection

@section('content')
<div x-data="dashboardApp()" x-init="initCharts()">

    {{-- ════════════════════════════════════════════════════ --}}
    {{-- LIGNE 1 : STATS CARDS --}}
    {{-- ════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-5 mb-6">

        {{-- Card Élèves (brand) --}}
        <div class="group relative overflow-hidden bg-gradient-to-br from-white via-white to-brand-50/60 rounded-2xl p-5 border border-brand-100/60 shadow-card-brand hover:shadow-card-hover hover:-translate-y-1 transition-all duration-300">
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-gradient-to-br from-brand-300/40 to-brand-500/10 rounded-full blur-2xl group-hover:scale-110 transition-transform duration-500"></div>
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-brand-400 via-brand-500 to-brand-600"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-brand-400 via-brand-500 to-brand-700 rounded-xl flex items-center justify-center shadow-brand-glow ring-1 ring-brand-300/40">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </div>
                    <span class="text-[10px] font-bold text-brand-700 bg-brand-100 border border-brand-200/60 px-2 py-0.5 rounded-full">{{ $stats['total_classes'] }} classes</span>
                </div>
                <p class="font-display text-3xl lg:text-4xl font-extrabold text-gray-900 tracking-tight">{{ number_format($stats['eleves_inscrits']) }}</p>
                <p class="text-xs text-gray-500 font-medium mt-1">
                    Élèves inscrits
                    <span class="text-gray-300 mx-1">•</span>
                    <span class="text-blue-600 font-bold">{{ $stats['eleves_m'] }}G</span>
                    <span class="text-pink-500 font-bold">{{ $stats['eleves_f'] }}F</span>
                </p>
            </div>
        </div>

        {{-- Card Enseignants (bleu) --}}
        <div class="group relative overflow-hidden bg-gradient-to-br from-white via-white to-blue-50/60 rounded-2xl p-5 border border-blue-100/60 shadow-card-blue hover:shadow-card-hover hover:-translate-y-1 transition-all duration-300">
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-gradient-to-br from-blue-300/40 to-blue-500/10 rounded-full blur-2xl group-hover:scale-110 transition-transform duration-500"></div>
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-400 via-blue-500 to-blue-600"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-400 via-blue-500 to-blue-700 rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/30 ring-1 ring-blue-300/40">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    @if($stats['enseignants_absents'] > 0)
                        <span class="text-[10px] font-bold text-red-700 bg-red-100 border border-red-200/60 px-2 py-0.5 rounded-full">{{ $stats['enseignants_absents'] }} absent(s)</span>
                    @else
                        <span class="text-[10px] font-bold text-brand-700 bg-brand-100 border border-brand-200/60 px-2 py-0.5 rounded-full">Tous là</span>
                    @endif
                </div>
                <div class="flex items-baseline gap-1">
                    <p class="font-display text-3xl lg:text-4xl font-extrabold text-gray-900 tracking-tight">{{ $stats['enseignants_presents'] }}</p>
                    <p class="text-lg text-gray-400 font-medium">/{{ $stats['enseignants_total'] }}</p>
                </div>
                <p class="text-xs text-gray-500 font-medium mt-1">Enseignants présents</p>
                <div class="w-full bg-blue-100/50 rounded-full h-1.5 mt-3 overflow-hidden">
                    <div class="h-1.5 rounded-full bg-gradient-to-r from-blue-400 to-blue-600 transition-all shadow-sm" style="width: {{ $stats['taux_presence'] }}%"></div>
                </div>
            </div>
        </div>

        {{-- Card Recouvrement (gold) --}}
        <div class="group relative overflow-hidden bg-gradient-to-br from-white via-white to-gold-50/60 rounded-2xl p-5 border border-gold-200/50 shadow-card-gold hover:shadow-card-hover hover:-translate-y-1 transition-all duration-300">
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-gradient-to-br from-gold-300/40 to-gold-500/10 rounded-full blur-2xl group-hover:scale-110 transition-transform duration-500"></div>
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-gold-300 via-gold-400 to-gold-500"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-gold-300 via-gold-400 to-gold-500 rounded-xl flex items-center justify-center shadow-gold-glow ring-1 ring-gold-200/60">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span class="text-[10px] font-bold {{ $stats['taux_recouvrement'] >= 70 ? 'text-brand-700 bg-brand-100 border-brand-200/60' : 'text-gold-700 bg-gold-100 border-gold-200/60' }} border px-2 py-0.5 rounded-full">
                        {{ number_format($stats['total_paye_fcfa'], 0, ',', '.') }} F
                    </span>
                </div>
                <div class="flex items-baseline gap-1">
                    <p class="font-display text-3xl lg:text-4xl font-extrabold text-gray-900 tracking-tight">{{ $stats['taux_recouvrement'] }}</p>
                    <p class="text-lg text-gray-400 font-medium">%</p>
                </div>
                <p class="text-xs text-gray-500 font-medium mt-1">Recouvrement scolarité</p>
                <div class="w-full bg-gold-100/50 rounded-full h-1.5 mt-3 overflow-hidden">
                    <div class="h-1.5 rounded-full transition-all shadow-sm {{ $stats['taux_recouvrement'] >= 80 ? 'bg-gradient-to-r from-brand-400 to-brand-600' : ($stats['taux_recouvrement'] >= 50 ? 'bg-gradient-to-r from-gold-300 to-gold-500' : 'bg-gradient-to-r from-red-400 to-red-600') }}" style="width: {{ min($stats['taux_recouvrement'], 100) }}%"></div>
                </div>
            </div>
        </div>

        {{-- Card Moyenne (violet) --}}
        <div class="group relative overflow-hidden bg-gradient-to-br from-white via-white to-violet-50/60 rounded-2xl p-5 border border-violet-100/60 shadow-card-violet hover:shadow-card-hover hover:-translate-y-1 transition-all duration-300">
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-gradient-to-br from-violet-300/40 to-purple-500/10 rounded-full blur-2xl group-hover:scale-110 transition-transform duration-500"></div>
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-violet-400 via-purple-500 to-purple-600"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-violet-400 via-purple-500 to-purple-700 rounded-xl flex items-center justify-center shadow-lg shadow-violet-500/30 ring-1 ring-violet-300/40">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </div>
                    @if($stats['eleves_en_difficulte'] > 0)
                        <span class="text-[10px] font-bold text-red-700 bg-red-100 border border-red-200/60 px-2 py-0.5 rounded-full">{{ $stats['eleves_en_difficulte'] }} en difficulté</span>
                    @else
                        <span class="text-[10px] font-bold text-violet-700 bg-violet-100 border border-violet-200/60 px-2 py-0.5 rounded-full">{{ $trimestre->libelle ?? 'T2' }}</span>
                    @endif
                </div>
                <div class="flex items-baseline gap-1">
                    <p class="font-display text-3xl lg:text-4xl font-extrabold text-gray-900 tracking-tight">{{ $stats['moyenne_generale'] ?? '—' }}</p>
                    <p class="text-lg text-gray-400 font-medium">/20</p>
                </div>
                <p class="text-xs text-gray-500 font-medium mt-1">Moyenne générale</p>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════ --}}
    {{-- LIGNE 2 : GRAPHIQUES + POINTAGE --}}
    {{-- ════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6">

        {{-- Revenus mensuels (5 cols) --}}
        <div class="lg:col-span-5 relative overflow-hidden bg-gradient-to-br from-white via-white to-brand-50/40 rounded-2xl border border-brand-100/60 shadow-card-brand p-5">
            <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-brand-100/30 rounded-full blur-3xl"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-5">
                    <div>
                        <h3 class="font-display text-base font-extrabold text-gray-900 tracking-tight">Revenus mensuels</h3>
                        <p class="text-xs text-gray-500 mt-0.5">6 derniers mois — en FCFA</p>
                    </div>
                    <div class="w-9 h-9 bg-gradient-to-br from-brand-400 to-brand-600 rounded-lg flex items-center justify-center shadow-brand-glow">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    </div>
                </div>
                <div style="height: 220px">
                    <canvas id="chartRevenus"></canvas>
                </div>
            </div>
        </div>

        {{-- Pointage du jour (4 cols) --}}
        <div class="lg:col-span-4 relative overflow-hidden bg-gradient-to-br from-white via-white to-brand-50/30 rounded-2xl border border-brand-100/60 shadow-card-brand">
            <div class="flex items-center justify-between px-5 py-4 border-b border-brand-100/40 bg-gradient-to-r from-brand-50/50 to-transparent">
                <div class="flex items-center gap-2">
                    <div class="relative">
                        <div class="w-2.5 h-2.5 bg-brand-500 rounded-full"></div>
                        <div class="absolute inset-0 w-2.5 h-2.5 bg-brand-400 rounded-full animate-ping"></div>
                    </div>
                    <h3 class="font-display text-sm font-extrabold text-gray-900">Pointage du jour</h3>
                </div>
                <a href="{{ route('pointage.index') }}" class="text-[11px] font-bold text-brand-600 hover:text-brand-700 flex items-center gap-1">Détails <span>→</span></a>
            </div>

            <div class="grid grid-cols-3 gap-px bg-brand-100/30">
                <div class="bg-white/80 p-3 text-center">
                    <p class="font-display text-xl font-extrabold text-brand-600">{{ $stats['enseignants_presents'] - $stats['enseignants_retards'] }}</p>
                    <p class="text-[10px] text-gray-500 font-semibold mt-0.5">À l'heure</p>
                </div>
                <div class="bg-white/80 p-3 text-center">
                    <p class="font-display text-xl font-extrabold text-gold-600">{{ $stats['enseignants_retards'] }}</p>
                    <p class="text-[10px] text-gray-500 font-semibold mt-0.5">Retards</p>
                </div>
                <div class="bg-white/80 p-3 text-center">
                    <p class="font-display text-xl font-extrabold text-red-500">{{ $stats['enseignants_absents'] }}</p>
                    <p class="text-[10px] text-gray-500 font-semibold mt-0.5">Absents</p>
                </div>
            </div>

            <div class="max-h-[280px] overflow-y-auto divide-y divide-brand-50/60">
                @forelse($pointages_jour as $p)
                <div class="flex items-center gap-3 px-5 py-2.5 hover:bg-brand-50/40 transition-colors">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-[11px] font-bold flex-shrink-0 shadow-sm ring-2 ring-white
                        {{ $p->statut === 'present' ? 'bg-gradient-to-br from-brand-400 to-brand-600 text-white' : ($p->statut === 'retard' ? 'bg-gradient-to-br from-gold-300 to-gold-500 text-white' : 'bg-gradient-to-br from-red-400 to-red-600 text-white') }}">
                        {{ mb_strtoupper(mb_substr($p->enseignant->prenom, 0, 1)) }}{{ mb_strtoupper(mb_substr($p->enseignant->nom, 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-[13px] font-semibold text-gray-900 truncate">{{ $p->enseignant->prenom }} {{ $p->enseignant->nom }}</p>
                        <p class="text-[11px] text-gray-400">{{ $p->salle->nom ?? '' }}</p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        @if($p->statut === 'present')
                            <span class="inline-flex items-center gap-1 text-[11px] font-bold text-brand-700 bg-brand-100 border border-brand-200/60 px-2 py-0.5 rounded-full">✓ {{ Carbon\Carbon::parse($p->heure_scan)->format('H:i') }}</span>
                        @elseif($p->statut === 'retard')
                            <span class="inline-flex items-center gap-1 text-[11px] font-bold text-gold-700 bg-gold-100 border border-gold-200/60 px-2 py-0.5 rounded-full">⏱ {{ Carbon\Carbon::parse($p->heure_scan)->format('H:i') }}</span>
                        @else
                            <span class="inline-flex items-center gap-1 text-[11px] font-bold text-red-700 bg-red-100 border border-red-200/60 px-2 py-0.5 rounded-full">⚠ Hors zone</span>
                        @endif
                    </div>
                </div>
                @empty
                <div class="px-5 py-10 text-center">
                    <div class="w-14 h-14 bg-gradient-to-br from-brand-100 to-brand-50 rounded-full flex items-center justify-center mx-auto mb-3 shadow-card-brand">
                        <svg class="w-6 h-6 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <p class="text-xs text-gray-500 font-medium">Aucun pointage aujourd'hui</p>
                </div>
                @endforelse

                @if($absents->count() > 0)
                <div class="px-5 py-3 bg-gradient-to-r from-red-50 to-red-50/50 border-t border-red-100">
                    <p class="text-[10px] font-bold text-red-600 uppercase tracking-wider mb-2">Non pointés</p>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($absents->take(6) as $a)
                        <span class="text-[10px] font-bold text-red-700 bg-white border border-red-200 px-2 py-0.5 rounded-full shadow-sm">{{ $a->prenom }} {{ mb_strtoupper(mb_substr($a->nom, 0, 1)) }}.</span>
                        @endforeach
                        @if($absents->count() > 6)
                        <span class="text-[10px] font-bold text-red-600">+{{ $absents->count() - 6 }}</span>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Modes de paiement (3 cols) --}}
        <div class="lg:col-span-3 relative overflow-hidden bg-gradient-to-br from-white via-white to-gold-50/40 rounded-2xl border border-gold-200/50 shadow-card-gold p-5">
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-gold-200/30 rounded-full blur-2xl"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="font-display text-sm font-extrabold text-gray-900">Modes de paiement</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Répartition PayDunya</p>
                    </div>
                    <div class="w-8 h-8 bg-gradient-to-br from-gold-300 to-gold-500 rounded-lg flex items-center justify-center shadow-gold-glow">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                    </div>
                </div>
                <div style="height: 180px" class="flex items-center justify-center">
                    <canvas id="chartModes"></canvas>
                </div>
                <div class="mt-4 space-y-2">
                    @foreach($graphiques['paiements_par_mode'] as $pm)
                    @php
                        $colors = [
                            'orange_money' => ['bg-orange-500', 'Orange Money'],
                            'mtn_money' => ['bg-yellow-400', 'MTN Money'],
                            'wave' => ['bg-blue-500', 'Wave'],
                            'moov_money' => ['bg-cyan-500', 'Moov'],
                            'especes' => ['bg-gray-400', 'Espèces'],
                            'carte_bancaire' => ['bg-purple-500', 'Carte']
                        ];
                        $c = $colors[$pm->mode] ?? ['bg-gray-400', $pm->mode];
                    @endphp
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-2.5 h-2.5 rounded-full {{ $c[0] }} shadow-sm"></div>
                            <span class="text-[11px] text-gray-600 font-medium">{{ $c[1] }}</span>
                        </div>
                        <span class="text-[11px] font-bold text-gray-900">{{ number_format($pm->total, 0, ',', '.') }} F</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════ --}}
    {{-- LIGNE 3 : PAIEMENTS + TOP ÉLÈVES + SIGFNE --}}
    {{-- ════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6">

        {{-- Derniers paiements --}}
        <div class="lg:col-span-4 relative overflow-hidden bg-gradient-to-br from-white via-white to-gold-50/30 rounded-2xl border border-gold-100/60 shadow-card-gold">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gold-100/60 bg-gradient-to-r from-gold-50/50 to-transparent">
                <h3 class="font-display text-sm font-extrabold text-gray-900">Derniers paiements</h3>
                <a href="{{ route('paiements.index') }}" class="text-[11px] font-bold text-gold-600 hover:text-gold-700 flex items-center gap-1">Voir tout <span>→</span></a>
            </div>
            <div class="divide-y divide-gold-50">
                @forelse($derniersPaiements as $p)
                @php $modeIcons = ['orange_money' => 'OM', 'mtn_money' => 'MTN', 'wave' => 'W', 'especes' => 'ESP', 'carte_bancaire' => 'CB', 'moov_money' => 'MV']; @endphp
                <div class="flex items-center gap-3 px-5 py-3 hover:bg-gold-50/40 transition-colors">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center text-[10px] font-bold flex-shrink-0 shadow-sm
                        {{ $p->mode === 'orange_money' ? 'bg-gradient-to-br from-orange-100 to-orange-200 text-orange-700' : ($p->mode === 'mtn_money' ? 'bg-gradient-to-br from-yellow-100 to-yellow-200 text-yellow-700' : ($p->mode === 'wave' ? 'bg-gradient-to-br from-blue-100 to-blue-200 text-blue-700' : 'bg-gradient-to-br from-gray-100 to-gray-200 text-gray-600')) }}">
                        {{ $modeIcons[$p->mode] ?? '?' }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-[13px] font-semibold text-gray-900 truncate">{{ $p->eleve->prenom ?? '' }} {{ $p->eleve->nom ?? '' }}</p>
                        <p class="text-[11px] text-gray-400">{{ $p->date_paiement->format('d/m/Y') }} • {{ $p->reference }}</p>
                    </div>
                    <p class="text-[13px] font-extrabold text-brand-700 flex-shrink-0">{{ number_format($p->montant, 0, ',', '.') }}&nbsp;F</p>
                </div>
                @empty
                <div class="px-5 py-10 text-center">
                    <p class="text-xs text-gray-400">Aucun paiement</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Top 5 élèves --}}
        <div class="lg:col-span-4 relative overflow-hidden bg-gradient-to-br from-white via-white to-violet-50/30 rounded-2xl border border-violet-100/60 shadow-card-violet">
            <div class="flex items-center justify-between px-5 py-4 border-b border-violet-100/60 bg-gradient-to-r from-violet-50/50 to-transparent">
                <h3 class="font-display text-sm font-extrabold text-gray-900">Top 5 élèves</h3>
                <span class="text-[10px] font-bold text-violet-700 bg-violet-100 border border-violet-200/60 px-2 py-0.5 rounded-full">{{ $trimestre->libelle ?? 'Trimestre en cours' }}</span>
            </div>
            <div class="divide-y divide-violet-50">
                @forelse($topEleves as $i => $moy)
                <div class="flex items-center gap-3 px-5 py-3 hover:bg-violet-50/40 transition-colors">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-extrabold flex-shrink-0 shadow-sm ring-2 ring-white
                        {{ $i === 0 ? 'bg-gradient-to-br from-gold-300 to-gold-500 text-white' : ($i === 1 ? 'bg-gradient-to-br from-gray-300 to-gray-400 text-white' : ($i === 2 ? 'bg-gradient-to-br from-orange-300 to-orange-500 text-white' : 'bg-gradient-to-br from-gray-100 to-gray-200 text-gray-500')) }}">
                        {{ $i + 1 }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-[13px] font-semibold text-gray-900 truncate">{{ $moy->eleve->prenom ?? '' }} {{ $moy->eleve->nom ?? '' }}</p>
                        <p class="text-[11px] text-gray-400">{{ $moy->classe->nom ?? '' }}</p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="font-display text-sm font-extrabold {{ $moy->moyenne_generale >= 14 ? 'text-brand-600' : ($moy->moyenne_generale >= 10 ? 'text-blue-600' : 'text-red-600') }}">
                            {{ number_format($moy->moyenne_generale, 2) }}
                        </p>
                        <p class="text-[10px] text-gray-400">/20</p>
                    </div>
                </div>
                @empty
                <div class="px-5 py-10 text-center">
                    <p class="text-xs text-gray-400">Aucune moyenne disponible</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Conformité SIGFNE --}}
        <div class="lg:col-span-4 relative overflow-hidden bg-gradient-to-br from-white via-white to-brand-50/30 rounded-2xl border border-brand-100/60 shadow-card-brand">
            <div class="flex items-center justify-between px-5 py-4 border-b border-brand-100/60 bg-gradient-to-r from-brand-50/50 to-transparent">
                <h3 class="font-display text-sm font-extrabold text-gray-900">SIGFNE / DESPS</h3>
                <a href="{{ route('sigfne.index') }}" class="text-[11px] font-bold text-brand-600 hover:text-brand-700 flex items-center gap-1">Gérer <span>→</span></a>
            </div>
            <div class="p-5 space-y-3">
                @forelse($trimestres as $t)
                <div class="relative overflow-hidden flex items-center justify-between p-3 rounded-xl shadow-sm
                    {{ $t->moyennes_remontees
                        ? 'bg-gradient-to-r from-brand-50 to-brand-100/40 border border-brand-200/60'
                        : ($t->en_cours
                            ? 'bg-gradient-to-r from-gold-50 to-gold-100/40 border border-gold-200/60'
                            : 'bg-gradient-to-r from-gray-50 to-gray-100/40 border border-gray-200/60') }}">
                    <div>
                        <p class="text-[13px] font-bold {{ $t->moyennes_remontees ? 'text-brand-800' : ($t->en_cours ? 'text-gold-700' : 'text-gray-600') }}">{{ $t->libelle }}</p>
                        @if($t->moyennes_remontees)
                            <p class="text-[11px] text-brand-600 font-medium mt-0.5">Moyennes remontées</p>
                        @elseif($t->en_cours && $t->date_remontee_desps)
                            @php $jours = now()->diffInDays($t->date_remontee_desps, false); @endphp
                            <p class="text-[11px] {{ $jours < 7 ? 'text-red-600 font-bold' : 'text-gold-600 font-medium' }} mt-0.5">
                                {{ $jours > 0 ? 'J-'.$jours.' avant clôture' : 'Délai dépassé' }}
                            </p>
                        @else
                            <p class="text-[11px] text-gray-400 font-medium mt-0.5">À venir</p>
                        @endif
                    </div>
                    @if($t->moyennes_remontees)
                        <span class="text-xs font-bold text-white bg-gradient-to-br from-brand-400 to-brand-600 w-7 h-7 rounded-full flex items-center justify-center shadow-brand-glow">✓</span>
                    @elseif($t->en_cours)
                        <span class="text-[9px] font-extrabold text-white bg-gradient-to-br from-gold-400 to-gold-500 px-2 py-1 rounded-full shadow-gold-glow uppercase tracking-wider">En cours</span>
                    @else
                        <span class="text-[10px] font-bold text-gray-400 bg-white border border-gray-200 px-2 py-1 rounded-full">—</span>
                    @endif
                </div>
                @empty
                <div class="text-center py-6">
                    <p class="text-xs text-gray-400">Configurez vos trimestres</p>
                </div>
                @endforelse
            </div>

            <div class="px-5 py-3 bg-gradient-to-r from-brand-50/60 to-gold-50/40 border-t border-brand-100/60">
                <div class="flex items-center justify-between">
                    <span class="text-[11px] text-gray-500 font-semibold">Code DESPS</span>
                    <span class="text-[12px] font-mono font-extrabold text-brand-700">{{ $etab->code_desps ?? '000000' }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════ --}}
    {{-- LIGNE 4 : ALERTES IA + ACTIONS RAPIDES --}}
    {{-- ════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        {{-- Alertes IA --}}
        <div class="lg:col-span-8 relative overflow-hidden bg-gradient-to-br from-white via-white to-violet-50/30 rounded-2xl border border-violet-100/60 shadow-card-violet p-5">
            <div class="absolute -top-10 -right-10 w-48 h-48 bg-violet-200/25 rounded-full blur-3xl"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 bg-gradient-to-br from-violet-400 via-purple-500 to-purple-700 rounded-xl flex items-center justify-center shadow-lg shadow-violet-500/30 ring-1 ring-violet-300/40">
                            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                        </div>
                        <div>
                            <h3 class="font-display text-sm font-extrabold text-gray-900">Intelligence Artificielle</h3>
                            <p class="text-[11px] text-gray-500">Recommandations en temps réel</p>
                        </div>
                    </div>
                    @if($stats['alertes_non_lues'] > 0)
                    <span class="text-[10px] font-bold text-white bg-gradient-to-br from-red-500 to-red-600 px-2 py-1 rounded-full shadow-sm">{{ $stats['alertes_non_lues'] }} alertes</span>
                    @endif
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @if($stats['eleves_en_difficulte'] > 0)
                    <div class="flex items-start gap-3 p-3 bg-gradient-to-br from-red-50 to-red-100/40 border border-red-200/60 rounded-xl shadow-sm">
                        <div class="w-8 h-8 bg-gradient-to-br from-red-400 to-red-600 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5 shadow-sm shadow-red-500/30">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <div>
                            <p class="text-[13px] font-bold text-red-800">{{ $stats['eleves_en_difficulte'] }} élève(s) en difficulté</p>
                            <p class="text-[11px] text-red-600 mt-0.5">Moyenne &lt; 10/20. Convoquer les parents.</p>
                        </div>
                    </div>
                    @endif

                    @if($stats['taux_recouvrement'] < 70)
                    <div class="flex items-start gap-3 p-3 bg-gradient-to-br from-gold-50 to-gold-100/40 border border-gold-200/60 rounded-xl shadow-sm">
                        <div class="w-8 h-8 bg-gradient-to-br from-gold-300 to-gold-500 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5 shadow-gold-glow">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1"/></svg>
                        </div>
                        <div>
                            <p class="text-[13px] font-bold text-gold-700">Recouvrement à {{ $stats['taux_recouvrement'] }}%</p>
                            <p class="text-[11px] text-gold-600 mt-0.5">Envoyez les relances SMS via PayDunya.</p>
                        </div>
                    </div>
                    @endif

                    @if($stats['enseignants_absents'] > 0)
                    <div class="flex items-start gap-3 p-3 bg-gradient-to-br from-blue-50 to-blue-100/40 border border-blue-200/60 rounded-xl shadow-sm">
                        <div class="w-8 h-8 bg-gradient-to-br from-blue-400 to-blue-600 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5 shadow-sm shadow-blue-500/30">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-[13px] font-bold text-blue-800">{{ $stats['enseignants_absents'] }} enseignant(s) non pointé(s)</p>
                            <p class="text-[11px] text-blue-600 mt-0.5">Vérifiez et organisez les remplacements.</p>
                        </div>
                    </div>
                    @endif

                    <div class="flex items-start gap-3 p-3 bg-gradient-to-br from-brand-50 to-brand-100/40 border border-brand-200/60 rounded-xl shadow-sm">
                        <div class="w-8 h-8 bg-gradient-to-br from-brand-400 to-brand-600 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5 shadow-brand-glow">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-[13px] font-bold text-brand-800">Score santé établissement</p>
                            <p class="text-[11px] text-brand-600 mt-0.5">Consultez l'IA pour un diagnostic complet.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions rapides --}}
        <div class="lg:col-span-4 relative overflow-hidden bg-gradient-to-br from-white via-white to-brand-50/30 rounded-2xl border border-brand-100/60 shadow-card-brand p-5">
            <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-gold-100/30 rounded-full blur-2xl"></div>
            <div class="relative">
                <h3 class="font-display text-sm font-extrabold text-gray-900 mb-4 flex items-center gap-2">
                    <span class="w-1 h-4 bg-gradient-to-b from-gold-400 to-gold-600 rounded-full"></span>
                    Actions rapides
                </h3>
                <div class="space-y-2">
                    <a href="{{ route('eleves.create') }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-gradient-to-r hover:from-brand-50 hover:to-brand-50/50 transition-all group border border-transparent hover:border-brand-100">
                        <div class="w-9 h-9 bg-gradient-to-br from-brand-400 to-brand-600 rounded-lg flex items-center justify-center shadow-brand-glow group-hover:scale-110 transition-transform">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                        </div>
                        <div>
                            <p class="text-[13px] font-bold text-gray-900">Inscrire un élève</p>
                            <p class="text-[11px] text-gray-500">Nouvelle inscription</p>
                        </div>
                    </a>
                    <a href="{{ route('paiements.create') }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-gradient-to-r hover:from-gold-50 hover:to-gold-50/50 transition-all group border border-transparent hover:border-gold-200">
                        <div class="w-9 h-9 bg-gradient-to-br from-gold-300 to-gold-500 rounded-lg flex items-center justify-center shadow-gold-glow group-hover:scale-110 transition-transform">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                        </div>
                        <div>
                            <p class="text-[13px] font-bold text-gray-900">Enregistrer un paiement</p>
                            <p class="text-[11px] text-gray-500">PayDunya / Espèces</p>
                        </div>
                    </a>
                    <a href="{{ route('pointage.index') }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-gradient-to-r hover:from-blue-50 hover:to-blue-50/50 transition-all group border border-transparent hover:border-blue-100">
                        <div class="w-9 h-9 bg-gradient-to-br from-blue-400 to-blue-600 rounded-lg flex items-center justify-center shadow-sm shadow-blue-500/30 group-hover:scale-110 transition-transform">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                        </div>
                        <div>
                            <p class="text-[13px] font-bold text-gray-900">Gérer les QR Codes</p>
                            <p class="text-[11px] text-gray-500">Pointage enseignants</p>
                        </div>
                    </a>
                    <a href="{{ route('sigfne.index') }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-gradient-to-r hover:from-violet-50 hover:to-violet-50/50 transition-all group border border-transparent hover:border-violet-100">
                        <div class="w-9 h-9 bg-gradient-to-br from-violet-400 to-purple-600 rounded-lg flex items-center justify-center shadow-sm shadow-violet-500/30 group-hover:scale-110 transition-transform">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <div>
                            <p class="text-[13px] font-bold text-gray-900">Remontée SIGFNE</p>
                            <p class="text-[11px] text-gray-500">Conformité DESPS</p>
                        </div>
                    </a>
                    <a href="{{ route('notes.index') }}" class="flex items-center gap-3 p-3 rounded-xl hover:bg-gradient-to-r hover:from-purple-50 hover:to-purple-50/50 transition-all group border border-transparent hover:border-purple-100">
                        <div class="w-9 h-9 bg-gradient-to-br from-purple-400 to-purple-600 rounded-lg flex items-center justify-center shadow-sm shadow-purple-500/30 group-hover:scale-110 transition-transform">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        </div>
                        <div>
                            <p class="text-[13px] font-bold text-gray-900">Saisie des notes</p>
                            <p class="text-[11px] text-gray-500">Notes et bulletins</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function dashboardApp() {
    return {
        initCharts() {
            this.renderRevenusChart();
            this.renderModesChart();
        },
        renderRevenusChart() {
            var ctx = document.getElementById('chartRevenus');
            if (!ctx) return;
            var moisData = {!! json_encode($graphiques['paiements_mensuels']->pluck('mois')->map(function($m){ $parts = explode('-',$m); $moisNoms = ['','Jan','Fév','Mar','Avr','Mai','Juin','Jul','Aoû','Sep','Oct','Nov','Déc']; return ($moisNoms[(int)$parts[1]] ?? $parts[1]) . ' ' . substr($parts[0],2); })) !!};
            var totaux = {!! json_encode($graphiques['paiements_mensuels']->pluck('total')) !!};

            // Gradient fill
            var gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 220);
            gradient.addColorStop(0, 'rgba(10, 123, 63, 0.35)');
            gradient.addColorStop(1, 'rgba(10, 123, 63, 0.02)');

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: moisData,
                    datasets: [{
                        data: totaux,
                        backgroundColor: gradient,
                        borderColor: 'rgb(10, 123, 63)',
                        borderWidth: 2,
                        borderRadius: 8,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false }, ticks: { font: { size: 11, weight: '600' }, color: '#6b7280' } },
                        y: { grid: { color: 'rgba(10,123,63,0.06)' }, ticks: { font: { size: 10 }, color: '#9ca3af', callback: function(v) { return (v/1000000).toFixed(1) + 'M'; } } }
                    }
                }
            });
        },
        renderModesChart() {
            var ctx = document.getElementById('chartModes');
            if (!ctx) return;
            var modes = {!! json_encode($graphiques['paiements_par_mode']->pluck('mode')) !!};
            var totaux = {!! json_encode($graphiques['paiements_par_mode']->pluck('total')) !!};
            var colors = { orange_money: '#f97316', mtn_money: '#eab308', wave: '#3b82f6', moov_money: '#06b6d4', especes: '#9ca3af', carte_bancaire: '#8b5cf6' };
            var bgColors = modes.map(function(m) { return colors[m] || '#6b7280'; });
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: modes.map(function(m) { return m.replace('_', ' '); }),
                    datasets: [{ data: totaux, backgroundColor: bgColors, borderWidth: 0, spacing: 3, hoverOffset: 8 }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false, cutout: '72%',
                    plugins: { legend: { display: false } }
                }
            });
        }
    }
}
</script>
@endpush