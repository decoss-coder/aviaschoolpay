@extends('layouts.app')
@section('title', 'Avia Technologie — Cockpit plateforme')
@section('page-title', 'Cockpit Avia Technologie')
@section('page-subtitle', 'Pilotage 360° de toute la plateforme AviaSchoolPay')

@section('content')
@php
    $money = fn($v) => number_format((float) $v, 0, ',', ' ');
@endphp

<div class="space-y-6">

    {{-- ═══════════════ HERO BANNIÈRE AVIA ═══════════════ --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-brand-600 via-brand-700 to-brand-900 p-6 lg:p-8 shadow-brand-glow text-white">
        <div class="absolute top-0 right-0 w-64 h-64 bg-gold-300/10 rounded-full -translate-y-1/2 translate-x-1/2 blur-2xl"></div>
        <div class="absolute bottom-0 left-0 w-48 h-48 bg-emerald-300/10 rounded-full translate-y-1/2 -translate-x-1/4 blur-2xl"></div>

        <div class="relative grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                <div class="flex items-center gap-2 mb-2">
                    <span class="px-2 py-0.5 bg-gold-400 text-brand-900 rounded-full text-[10px] font-extrabold uppercase tracking-wider">Super Admin</span>
                    <span class="text-xs text-brand-100">{{ now()->locale('fr')->isoFormat('dddd D MMMM YYYY · HH:mm') }}</span>
                </div>
                <h1 class="font-display text-3xl lg:text-4xl font-extrabold leading-tight">
                    Bienvenue, {{ $globales['etablissements_actifs'] }} école{{ $globales['etablissements_actifs'] > 1 ? 's' : '' }} sur la plateforme
                </h1>
                <p class="text-brand-100 text-sm mt-2">
                    {{ number_format($globales['eleves_total'], 0, ',', ' ') }} élèves ·
                    {{ $globales['enseignants_total'] }} enseignants ·
                    {{ $globales['utilisateurs_actifs'] }} comptes actifs
                </p>

                <div class="flex flex-wrap gap-2 mt-5">
                    <a href="{{ route('admin.etablissements.create') }}" class="px-4 py-2 rounded-xl bg-white text-brand-700 text-sm font-bold hover:bg-gold-50 transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        Nouvel établissement
                    </a>
                    <a href="{{ route('admin.etablissements.index') }}" class="px-4 py-2 rounded-xl border-2 border-white/30 text-white text-sm font-bold hover:bg-white/10 transition">
                        🏫 Gérer les écoles
                    </a>
                    <a href="{{ route('admin.platform.parametres') }}" class="px-4 py-2 rounded-xl border-2 border-white/30 text-white text-sm font-bold hover:bg-white/10 transition">
                        ⚙ Paramètres plateforme
                    </a>
                </div>
            </div>

            {{-- Revenu Avia ce mois --}}
            <div class="bg-gold-400/95 backdrop-blur rounded-xl p-5 shadow-gold-glow">
                <p class="text-xs font-extrabold uppercase tracking-wider text-brand-900">💰 Revenus Avia ce mois</p>
                <p class="text-3xl font-extrabold text-brand-900 mt-2">{{ $money($globales['revenus_avia_mois']) }} <span class="text-sm">FCFA</span></p>
                <div class="mt-2 pt-2 border-t border-brand-900/20 text-xs text-brand-800">
                    <p>Cumul plateforme : <b>{{ $money($globales['revenus_avia_total']) }} F</b></p>
                    <p class="mt-1">{{ $globales['demandes_payees'] }} demande(s) payée(s) à traiter</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════ KPIs SECONDAIRES ═══════════════ --}}
    <section class="grid grid-cols-2 lg:grid-cols-5 gap-3">
        <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-card">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[10px] font-bold uppercase text-gray-400 tracking-wider">Établissements</p>
                    <p class="text-2xl font-extrabold text-gray-900 mt-1">{{ $globales['etablissements_total'] }}</p>
                </div>
                <span class="text-2xl">🏫</span>
            </div>
            <div class="mt-2 pt-2 border-t border-gray-100 flex items-center justify-between text-[10px]">
                <span class="text-emerald-700 font-bold">✓ {{ $globales['etablissements_actifs'] }}</span>
                <span class="text-red-600 font-bold">✕ {{ $globales['etablissements_bloques'] }}</span>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-card">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[10px] font-bold uppercase text-gray-400 tracking-wider">Élèves</p>
                    <p class="text-2xl font-extrabold text-brand-700 mt-1">{{ number_format($globales['eleves_total'], 0, ',', ' ') }}</p>
                </div>
                <span class="text-2xl">🎓</span>
            </div>
            <p class="text-[10px] text-gray-500 mt-2 pt-2 border-t border-gray-100">{{ $globales['total_parents'] }} parent(s) connecté(s)</p>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 p-4 shadow-card">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[10px] font-bold uppercase text-gray-400 tracking-wider">Paiements (mois)</p>
                    <p class="text-xl font-extrabold text-blue-700 mt-1">{{ $money($globales['paiements_mois']) }} <span class="text-xs">F</span></p>
                </div>
                <span class="text-2xl">💳</span>
            </div>
            <p class="text-[10px] text-gray-500 mt-2 pt-2 border-t border-gray-100">{{ $globales['paiements_jour_nombre'] }} paiement(s) aujourd'hui</p>
        </div>

        <div class="bg-white rounded-2xl border border-amber-100 p-4 shadow-card-gold">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[10px] font-bold uppercase text-amber-600 tracking-wider">Activation années</p>
                    <p class="text-2xl font-extrabold text-amber-700 mt-1">{{ $globales['taux_activation'] }}%</p>
                </div>
                <span class="text-2xl">📅</span>
            </div>
            <p class="text-[10px] text-gray-500 mt-2 pt-2 border-t border-gray-100">{{ $globales['archives_total'] }} archive(s) chiffrée(s)</p>
        </div>

        <div class="bg-white rounded-2xl border border-violet-100 p-4 shadow-card-violet">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[10px] font-bold uppercase text-violet-600 tracking-wider">Activité mobile (7j)</p>
                    <p class="text-xl font-extrabold text-violet-700 mt-1">{{ number_format($globales['notifs_semaine'], 0, ',', ' ') }}</p>
                </div>
                <span class="text-2xl">📱</span>
            </div>
            <p class="text-[10px] text-gray-500 mt-2 pt-2 border-t border-gray-100">{{ $globales['pointages_jour'] }} pointage(s) aujourd'hui</p>
        </div>
    </section>

    {{-- ═══════════════ DEMANDES RESTAURATION URGENTES ═══════════════ --}}
    @if($demandesRestauration->where('statut', 'paye')->isNotEmpty())
        <div class="rounded-2xl border-2 border-amber-300 bg-amber-50 p-5">
            <div class="flex items-start gap-3">
                <span class="text-3xl">🔥</span>
                <div class="flex-1">
                    <p class="font-extrabold text-amber-900">{{ $demandesRestauration->where('statut', 'paye')->count() }} paiement(s) reçu(s) — À traiter en priorité</p>
                    <p class="text-xs text-amber-800 mt-1">Communiquez la clé de chiffrement aux établissements ayant payé.</p>
                </div>
                <a href="{{ route('admin.platform.parametres') }}" class="px-4 py-2 bg-amber-600 text-white text-sm font-bold rounded-xl hover:bg-amber-700">Traiter →</a>
            </div>
        </div>
    @endif

    {{-- ═══════════════ GRAPHIQUE + TOP ÉTABLISSEMENTS ═══════════════ --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
        {{-- Évolution paiements 14j --}}
        <div class="xl:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-card p-5">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h3 class="font-extrabold text-gray-900">📈 Évolution paiements 14 jours</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Toutes écoles confondues</p>
                </div>
            </div>
            <div class="h-64"><canvas id="evoChart"></canvas></div>
        </div>

        {{-- Top 5 CA --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-card p-5">
            <h3 class="font-extrabold text-gray-900 mb-3">🏆 Top écoles ({{ now()->locale('fr')->isoFormat('MMMM') }})</h3>
            @if($topCA->isEmpty())
                <p class="text-sm text-gray-400 italic">Aucun paiement ce mois.</p>
            @else
                <div class="space-y-3">
                    @php $maxCA = $topCA->max('ca') ?: 1; @endphp
                    @foreach($topCA as $i => $row)
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-full bg-gradient-to-br from-gold-300 to-gold-500 text-brand-900 flex items-center justify-center text-[10px] font-extrabold">{{ $i+1 }}</span>
                                    <span class="font-bold text-gray-800 truncate">{{ $row['etablissement']?->nom ?? '—' }}</span>
                                </span>
                                <span class="font-extrabold text-brand-700">{{ $money($row['ca']) }} F</span>
                            </div>
                            <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-brand-400 to-brand-600 rounded-full" style="width:{{ round(($row['ca']/$maxCA)*100) }}%"></div>
                            </div>
                            <p class="text-[10px] text-gray-500 mt-1">{{ $row['nb_paiements'] }} paiement(s) · {{ $row['etablissement']?->ville }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- ═══════════════ DEMANDES RESTAURATION ═══════════════ --}}
    @if($demandesRestauration->isNotEmpty())
    <div class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h3 class="font-extrabold text-gray-900">🔐 Demandes de restauration d'archives</h3>
                <p class="text-xs text-gray-500 mt-0.5">{{ $globales['demandes_en_attente'] }} en attente · {{ $globales['demandes_payees'] }} payée(s) à traiter</p>
            </div>
            <a href="{{ route('admin.platform.parametres') }}" class="text-xs font-bold text-brand-600 hover:text-brand-800">Tout voir →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-5 py-3 text-left font-bold">Référence</th>
                        <th class="px-5 py-3 text-left font-bold">École</th>
                        <th class="px-5 py-3 text-left font-bold">Année</th>
                        <th class="px-5 py-3 text-left font-bold">Demandeur</th>
                        <th class="px-5 py-3 text-right font-bold">Montant</th>
                        <th class="px-5 py-3 text-center font-bold">Statut</th>
                        <th class="px-5 py-3 text-left font-bold">Demandée le</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($demandesRestauration as $d)
                        @php
                            $sb = $d->statut === 'paye'
                                ? ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-700', 'label' => '💵 Payé — à traiter']
                                : ['bg' => 'bg-amber-100', 'text' => 'text-amber-700', 'label' => '⏳ En attente'];
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-mono text-xs text-gray-600">{{ $d->reference }}</td>
                            <td class="px-5 py-3 font-bold text-gray-900">{{ $d->etablissement?->nom ?? '—' }}</td>
                            <td class="px-5 py-3 text-xs text-gray-700">{{ $d->anneeScolaire?->libelle ?? '—' }}</td>
                            <td class="px-5 py-3 text-xs text-gray-700">{{ $d->demandeur?->name ?? '—' }}</td>
                            <td class="px-5 py-3 text-right font-extrabold text-amber-700">{{ $money($d->montant_fcfa) }} F</td>
                            <td class="px-5 py-3 text-center">
                                <span class="inline-flex px-2 py-1 rounded-lg text-xs font-bold {{ $sb['bg'] }} {{ $sb['text'] }}">{{ $sb['label'] }}</span>
                            </td>
                            <td class="px-5 py-3 text-xs text-gray-500">{{ $d->created_at?->format('d/m/Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ═══════════════ LISTE COMPLÈTE ÉTABLISSEMENTS ═══════════════ --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="font-extrabold text-gray-900">🏫 Tous les établissements</h3>
                <p class="text-xs text-gray-500 mt-0.5">{{ $etablissements->count() }} école(s) inscrite(s)</p>
            </div>
            <a href="{{ route('admin.etablissements.index') }}" class="text-xs font-bold text-brand-600 hover:text-brand-800">Gérer →</a>
        </div>
        @if($etablissements->isEmpty())
            <div class="px-5 py-16 text-center">
                <p class="text-4xl mb-3">🏗</p>
                <p class="font-bold text-gray-800">Aucun établissement</p>
                <a href="{{ route('admin.etablissements.create') }}" class="inline-flex items-center mt-3 px-4 py-2 bg-brand-600 text-white text-sm font-bold rounded-xl">+ Créer le premier</a>
            </div>
        @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-5 py-3 text-left font-bold">École</th>
                        <th class="px-3 py-3 text-left font-bold">Année active</th>
                        <th class="px-3 py-3 text-center font-bold">Élèves</th>
                        <th class="px-3 py-3 text-right font-bold">Recouvrement</th>
                        <th class="px-3 py-3 text-center font-bold">Wave</th>
                        <th class="px-3 py-3 text-center font-bold">Statut</th>
                        <th class="px-3 py-3 text-left font-bold">Dernier paiement</th>
                        <th class="px-5 py-3 text-right font-bold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($etablissements as $row)
                        @php $e = $row['etablissement']; $r = $row['recouvrement']; @endphp
                        <tr class="hover:bg-brand-50/30 transition">
                            <td class="px-5 py-3">
                                <p class="font-bold text-gray-900">{{ $e->nom }}</p>
                                <p class="text-[11px] text-gray-400">DESPS {{ $e->code_desps ?? '—' }} · {{ $e->ville ?? '—' }}</p>
                            </td>
                            <td class="px-3 py-3">
                                @if($row['annee_courante'])
                                    <span class="inline-flex px-2 py-0.5 rounded-lg bg-emerald-50 text-emerald-700 text-xs font-bold">{{ $row['annee_courante']->libelle }}</span>
                                @else
                                    <span class="inline-flex px-2 py-0.5 rounded-lg bg-red-50 text-red-700 text-xs font-bold">⚠ Non configurée</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center font-bold text-gray-800">{{ number_format($row['eleves'], 0, ',', ' ') }}</td>
                            <td class="px-3 py-3 text-right">
                                @php $tx = $r['taux']; $tc = $tx >= 70 ? 'text-emerald-700' : ($tx >= 40 ? 'text-amber-700' : 'text-red-700'); @endphp
                                <span class="font-extrabold {{ $tc }}">{{ $tx }}%</span>
                                <p class="text-[10px] text-gray-400">{{ $money($r['total_paye']) }} / {{ $money($r['total_du']) }} F</p>
                            </td>
                            <td class="px-3 py-3 text-center">
                                @if($row['wave_actif'])
                                    <span class="inline-flex px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold">🌊 Actif</span>
                                @else
                                    <span class="inline-flex px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 text-[10px] font-bold">Off</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center">
                                @if($e->actif)
                                    <span class="inline-flex px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold">● En ligne</span>
                                @else
                                    <span class="inline-flex px-2 py-0.5 rounded-full bg-red-100 text-red-800 text-[10px] font-bold">● Bloqué</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-xs text-gray-500">
                                {{ $row['dernier_paiement'] ? \Carbon\Carbon::parse($row['dernier_paiement'])->diffForHumans() : '—' }}
                            </td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('admin.etablissements.show', $e) }}" class="text-xs font-bold text-brand-600 hover:underline">Détail</a>
                                @if($e->actif)
                                    <span class="text-gray-300 mx-1">|</span>
                                    <form method="POST" action="{{ route('admin.etablissements.ouvrir', $e) }}" class="inline">@csrf
                                        <button type="submit" class="text-xs font-bold text-gold-600 hover:underline">🔓 Ouvrir</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- ═══════════════ FOOTER ═══════════════ --}}
    <div class="text-center text-xs text-gray-400 pt-2">
        Avia Technologie · Plateforme AviaSchoolPay · Données rafraîchies à {{ now()->format('H:i:s') }}
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('evoChart');
    if (!ctx) return;
    const d = @json($evolution);
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: d.map(x => x.date),
            datasets: [
                {
                    label: 'Montant encaissé (F)',
                    data: d.map(x => x.montant),
                    backgroundColor: 'rgba(10, 123, 63, 0.7)',
                    borderRadius: 4,
                    yAxisID: 'y',
                },
                {
                    label: 'Nombre de paiements',
                    data: d.map(x => x.nombre),
                    type: 'line',
                    borderColor: '#E8A817',
                    backgroundColor: 'rgba(232, 168, 23, 0.1)',
                    borderWidth: 2,
                    tension: 0.3,
                    yAxisID: 'y1',
                    pointRadius: 3,
                }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'top', labels: { font: { size: 11 } } } },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                y: { type: 'linear', position: 'left', grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { font: { size: 9 }, callback: v => (v/1000) + 'k' } },
                y1: { type: 'linear', position: 'right', grid: { display: false }, ticks: { font: { size: 9 } } }
            }
        }
    });
});
</script>
@endsection
