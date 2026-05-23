@extends('layouts.app')
@section('title', 'IA & Analyses')
@section('page-title', 'IA & Analyses')
@section('page-subtitle', 'Diagnostic intelligent et recommandations stratégiques')

@section('content')
@php
    $money = fn($v) => number_format((float) $v, 0, ',', ' ');
    $niveauColor = [
        'positif'  => ['bg' => 'bg-emerald-50', 'border' => 'border-emerald-200', 'text' => 'text-emerald-800', 'icon' => '✓'],
        'warning'  => ['bg' => 'bg-amber-50', 'border' => 'border-amber-200', 'text' => 'text-amber-800', 'icon' => '⚠'],
        'critique' => ['bg' => 'bg-red-50', 'border' => 'border-red-200', 'text' => 'text-red-800', 'icon' => '⛔'],
        'info'     => ['bg' => 'bg-blue-50', 'border' => 'border-blue-200', 'text' => 'text-blue-800', 'icon' => 'ℹ'],
    ];
    $prioriteColor = ['haute' => 'red', 'moyenne' => 'amber', 'info' => 'blue'];
@endphp

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center gap-3">
        <div class="w-12 h-12 bg-gradient-to-br from-fuchsia-500 via-purple-600 to-indigo-700 rounded-xl flex items-center justify-center shadow-card-violet">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        </div>
        <div>
            <p class="text-xs font-bold uppercase text-gray-400 tracking-wider">IA Décisionnelle</p>
            <h2 class="font-display text-2xl font-extrabold text-gray-900">Diagnostic financier intelligent</h2>
        </div>
    </div>

    {{-- Synthèse stratégique --}}
    <div class="bg-gradient-to-br from-fuchsia-500 via-purple-600 to-indigo-700 rounded-2xl p-6 lg:p-8 shadow-card-violet text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/2"></div>
        <div class="relative z-10">
            <div class="flex items-start gap-3 mb-4">
                <span class="text-3xl">🧠</span>
                <div>
                    <p class="text-xs font-bold uppercase text-fuchsia-100 tracking-wider">Synthèse IA — {{ now()->format('d/m/Y') }}</p>
                    <h3 class="font-display text-xl font-extrabold mt-1">
                        @if($synth['rentable'] && ($fondsRoulement ?? 0) >= 2)
                            Votre établissement est en bonne santé financière.
                        @elseif($synth['rentable'])
                            Rentable mais trésorerie à surveiller.
                        @else
                            Situation financière à redresser rapidement.
                        @endif
                    </h3>
                </div>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-6">
                <div class="bg-white/10 backdrop-blur rounded-xl p-3">
                    <p class="text-[10px] font-bold uppercase text-fuchsia-100">Marge nette</p>
                    <p class="text-xl font-extrabold mt-1">{{ $money($synth['marge']) }} F</p>
                    <p class="text-[10px] text-fuchsia-100">{{ $synth['taux_marge'] }}%</p>
                </div>
                <div class="bg-white/10 backdrop-blur rounded-xl p-3">
                    <p class="text-[10px] font-bold uppercase text-fuchsia-100">Trésorerie</p>
                    <p class="text-xl font-extrabold mt-1">{{ $money($tresoTotale) }} F</p>
                    <p class="text-[10px] text-fuchsia-100">{{ $fondsRoulement ?? '—' }} mois de fonds</p>
                </div>
                <div class="bg-white/10 backdrop-blur rounded-xl p-3">
                    <p class="text-[10px] font-bold uppercase text-fuchsia-100">Score santé</p>
                    <p class="text-xl font-extrabold mt-1">{{ $score?->score_global ?? '—' }}<span class="text-xs opacity-70">/100</span></p>
                    <p class="text-[10px] text-fuchsia-100">{{ $score?->indicateur ?? 'non calculé' }}</p>
                </div>
                <div class="bg-white/10 backdrop-blur rounded-xl p-3">
                    <p class="text-[10px] font-bold uppercase text-fuchsia-100">Ratio MS/CA</p>
                    <p class="text-xl font-extrabold mt-1">{{ $synth['ratio_ms_revenus'] }}%</p>
                    <p class="text-[10px] text-fuchsia-100">{{ $synth['ms_saine'] ? '✓ sain' : '⚠ élevé' }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Diagnostics --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-card p-6">
        <div class="flex items-center gap-2 mb-4">
            <span class="text-2xl">🔍</span>
            <h3 class="font-extrabold text-gray-900">Diagnostics détectés</h3>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @foreach($diagnostics as $d)
                @php $c = $niveauColor[$d['niveau']]; @endphp
                <div class="rounded-xl border-2 {{ $c['border'] }} {{ $c['bg'] }} p-4">
                    <div class="flex items-start gap-2">
                        <span class="text-xl flex-shrink-0">{{ $c['icon'] }}</span>
                        <div>
                            <p class="font-bold {{ $c['text'] }}">{{ $d['titre'] }}</p>
                            <p class="text-xs {{ $c['text'] }} opacity-80 mt-1">{{ $d['message'] }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Recommandations IA --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-card p-6">
        <div class="flex items-center gap-2 mb-4">
            <span class="text-2xl">💡</span>
            <h3 class="font-extrabold text-gray-900">Recommandations stratégiques</h3>
        </div>
        <div class="space-y-3">
            @foreach($recommandations as $r)
                @php $pc = $prioriteColor[$r['priorite']] ?? 'gray'; @endphp
                <div class="flex items-start gap-4 p-4 rounded-xl border border-{{ $pc }}-100 bg-{{ $pc }}-50">
                    <span class="w-12 h-12 rounded-xl bg-white shadow-sm flex items-center justify-center text-2xl flex-shrink-0">{{ $r['icon'] }}</span>
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <p class="font-bold text-gray-900">{{ $r['titre'] }}</p>
                            <span class="inline-flex px-2 py-0.5 rounded-lg text-[10px] font-bold uppercase bg-{{ $pc }}-200 text-{{ $pc }}-900">{{ $r['priorite'] }}</span>
                        </div>
                        <p class="text-sm text-gray-700">{{ $r['action'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Projections + Alertes --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
        <div class="xl:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-card p-6">
            <div class="flex items-center gap-2 mb-4">
                <span class="text-2xl">📈</span>
                <div>
                    <h3 class="font-extrabold text-gray-900">Projection 6 mois</h3>
                    <p class="text-xs text-gray-500">Tendance basée sur les 12 derniers mois</p>
                </div>
            </div>
            <div class="h-64"><canvas id="projChart"></canvas></div>

            <div class="grid grid-cols-3 gap-3 mt-5 pt-5 border-t border-gray-100">
                <div>
                    <p class="text-[10px] font-bold uppercase text-blue-600">Revenus mois moy.</p>
                    <p class="text-lg font-extrabold text-blue-700">{{ $money($projections[0]['revenus'] ?? 0) }} F</p>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase text-rose-600">Dépenses mois moy.</p>
                    <p class="text-lg font-extrabold text-rose-700">{{ $money($projections[0]['depenses'] ?? 0) }} F</p>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase text-brand-600">Marge mois moy.</p>
                    <p class="text-lg font-extrabold {{ ($projections[0]['marge'] ?? 0) >= 0 ? 'text-brand-700' : 'text-red-700' }}">{{ $money($projections[0]['marge'] ?? 0) }} F</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-card p-5">
            <div class="flex items-center gap-2 mb-4">
                <span class="text-2xl">⚠</span>
                <h3 class="font-extrabold text-gray-900">Alertes actives</h3>
            </div>
            @if($alertes->isEmpty())
                <div class="text-center py-6">
                    <p class="text-3xl">🎉</p>
                    <p class="text-sm font-bold text-gray-700 mt-2">Aucune alerte</p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($alertes as $a)
                        @php $g = ['critique' => 'red', 'warning' => 'amber', 'info' => 'blue'][$a->gravite] ?? 'gray'; @endphp
                        <div class="border-l-4 border-{{ $g }}-500 bg-{{ $g }}-50 rounded-r-xl p-3">
                            <p class="font-bold text-{{ $g }}-900 text-sm">{{ $a->titre }}</p>
                            <p class="text-xs text-{{ $g }}-700 mt-0.5 line-clamp-2">{{ $a->message }}</p>
                        </div>
                    @endforeach
                </div>
                <a href="{{ route('cockpit.alertes') }}" class="block text-center text-xs font-bold text-violet-600 hover:text-violet-800 mt-3 pt-3 border-t border-gray-100">Toutes les alertes →</a>
            @endif
        </div>
    </div>

    {{-- Navigation rapide --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-card p-5">
        <h3 class="font-extrabold text-gray-900 mb-3">📋 Actions rapides</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <a href="{{ route('cockpit.index') }}" class="rounded-xl border border-gray-100 hover:border-violet-300 p-4 hover:bg-violet-50/30 transition">
                <p class="text-2xl mb-1">🎯</p>
                <p class="text-sm font-bold text-gray-800">Cockpit 360°</p>
            </a>
            <a href="{{ route('rentabilite.index') }}" class="rounded-xl border border-gray-100 hover:border-amber-300 p-4 hover:bg-amber-50/30 transition">
                <p class="text-2xl mb-1">💎</p>
                <p class="text-sm font-bold text-gray-800">Rentabilité</p>
            </a>
            <a href="{{ route('simulations.create') }}" class="rounded-xl border border-gray-100 hover:border-cyan-300 p-4 hover:bg-cyan-50/30 transition">
                <p class="text-2xl mb-1">🧮</p>
                <p class="text-sm font-bold text-gray-800">Simuler scénario</p>
            </a>
            <a href="{{ route('budgets.index') }}" class="rounded-xl border border-gray-100 hover:border-indigo-300 p-4 hover:bg-indigo-50/30 transition">
                <p class="text-2xl mb-1">📊</p>
                <p class="text-sm font-bold text-gray-800">Budgets</p>
            </a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('projChart');
    if (!ctx) return;
    const p = @json($projections);
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: p.map(x => x.mois),
            datasets: [
                { label: 'Revenus projetés', data: p.map(x => x.revenus), borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.1)', fill: false, tension: 0.3, borderWidth: 2 },
                { label: 'Dépenses projetées', data: p.map(x => x.depenses), borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,0.1)', fill: false, tension: 0.3, borderWidth: 2 },
                { label: 'Trésorerie cumulée', data: p.map(x => x.treso_projete), borderColor: '#8b5cf6', backgroundColor: 'rgba(139,92,246,0.2)', fill: true, tension: 0.3, borderWidth: 2 },
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'top', labels: { font: { size: 11 } } } },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                y: { grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { font: { size: 9 }, callback: v => (v/1000) + 'k' } }
            }
        }
    });
});
</script>

@push('styles')<style>.line-clamp-2{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}</style>@endpush
@endsection
