@extends('layouts.app')
@section('title', 'Cockpit dirigeant')
@section('page-title', 'Cockpit dirigeant')

@section('content')
@php
    $money = fn($v) => number_format((float) $v, 0, ',', ' ');
    $resultatMois = $revenusMois - $depensesMois;
    $scoreVal = $score?->score_global ?? 0;
    $scoreColor = $scoreVal >= 70 ? 'emerald' : ($scoreVal >= 40 ? 'amber' : 'red');
    $indicateur = $score?->indicateur ?? 'gris';
@endphp

<div class="space-y-6">
    @include('cockpit._nav')

    {{-- Score gauge + KPIs principaux --}}
    <div class="grid grid-cols-1 xl:grid-cols-4 gap-5">
        {{-- Score gauge --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-card p-6 text-center">
            <p class="text-xs font-bold uppercase text-gray-400 tracking-wider mb-3">Score de santé</p>
            <div class="relative w-32 h-32 mx-auto">
                <svg viewBox="0 0 36 36" class="w-full h-full">
                    <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                          fill="none" stroke="#e5e7eb" stroke-width="3"></path>
                    <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                          fill="none"
                          stroke="{{ $scoreColor === 'emerald' ? '#10b981' : ($scoreColor === 'amber' ? '#f59e0b' : '#ef4444') }}"
                          stroke-width="3"
                          stroke-dasharray="{{ $scoreVal }}, 100"
                          stroke-linecap="round"></path>
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                    <p class="text-3xl font-extrabold text-{{ $scoreColor }}-700">{{ (int) $scoreVal }}</p>
                    <p class="text-[10px] font-bold text-gray-500">/ 100</p>
                </div>
            </div>
            <p class="mt-3 inline-flex px-3 py-1 rounded-full text-xs font-extrabold
                {{ $indicateur === 'vert' ? 'bg-emerald-100 text-emerald-700' : ($indicateur === 'orange' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}">
                @if($indicateur === 'vert') ✓ Saine @elseif($indicateur === 'orange') ⚠ Attention @else ⛔ Critique @endif
            </p>
            <p class="text-xs text-gray-500 mt-2">{{ $score ? 'Calculé le '.$score->date_calcul?->format('d/m/Y') : 'Pas encore calculé' }}</p>
        </div>

        {{-- Trésorerie totale --}}
        <div class="bg-gradient-to-br from-emerald-500 to-teal-700 rounded-2xl p-5 shadow-card-brand text-white">
            <p class="text-xs font-bold uppercase text-emerald-100 tracking-wider mb-3">Trésorerie totale</p>
            <p class="text-3xl font-extrabold">{{ $money($tresoTotale) }} <span class="text-sm font-bold opacity-80">F</span></p>
            <div class="mt-3 pt-3 border-t border-white/20 grid grid-cols-3 gap-2 text-xs">
                <div>
                    <p class="opacity-70">Caisse</p>
                    <p class="font-extrabold">{{ $money($soldeCaisse) }}</p>
                </div>
                <div>
                    <p class="opacity-70">Banque</p>
                    <p class="font-extrabold">{{ $money($soldeBanque) }}</p>
                </div>
                <div>
                    <p class="opacity-70">MM</p>
                    <p class="font-extrabold">{{ $money($soldeMM) }}</p>
                </div>
            </div>
        </div>

        {{-- Résultat mois --}}
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-card">
            <p class="text-xs font-bold uppercase text-gray-400 tracking-wider mb-3">{{ now()->locale('fr')->isoFormat('MMMM YYYY') }}</p>
            <div class="space-y-2">
                <div class="flex justify-between items-baseline">
                    <span class="text-xs text-blue-600 font-bold">Revenus</span>
                    <span class="font-extrabold text-blue-700">{{ $money($revenusMois) }} F</span>
                </div>
                <div class="flex justify-between items-baseline">
                    <span class="text-xs text-rose-600 font-bold">Dépenses</span>
                    <span class="font-extrabold text-rose-700">{{ $money($depensesMois) }} F</span>
                </div>
                <div class="flex justify-between items-baseline pt-2 border-t border-gray-100">
                    <span class="text-xs text-gray-600 font-bold">Résultat</span>
                    <span class="text-lg font-extrabold {{ $resultatMois >= 0 ? 'text-brand-700' : 'text-red-700' }}">{{ $money($resultatMois) }} F</span>
                </div>
            </div>
        </div>

        {{-- Alertes + impayés --}}
        <div class="space-y-3">
            <div class="bg-white rounded-2xl border border-red-100 p-4 shadow-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-bold uppercase text-red-600 tracking-wider">Alertes critiques</p>
                        <p class="text-2xl font-extrabold text-red-700">{{ $alertesCritiques }}</p>
                    </div>
                    <a href="{{ route('cockpit.alertes') }}" class="text-xs font-bold text-red-600 hover:text-red-800">→</a>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-amber-100 p-4 shadow-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-bold uppercase text-amber-600 tracking-wider">Impayés</p>
                        <p class="text-2xl font-extrabold text-amber-700">{{ $impayes }}</p>
                    </div>
                    <span class="text-xs font-bold text-amber-600">élèves</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Graphique évolution trésorerie + Rentabilité --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
        <div class="xl:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-card p-5">
            <h3 class="font-extrabold text-gray-900 mb-1">Évolution trésorerie 30 jours</h3>
            <p class="text-xs text-gray-500 mb-4">Entrées vs sorties quotidiennes</p>
            <div class="h-64"><canvas id="evoChart"></canvas></div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-card p-5">
            <h3 class="font-extrabold text-gray-900 mb-4">Rentabilité année</h3>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600">Revenus</span>
                    <span class="font-bold text-blue-700">{{ $money($synthRenta['revenus']) }} F</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Dépenses</span>
                    <span class="font-bold text-rose-700">{{ $money($synthRenta['depenses']) }} F</span>
                </div>
                <div class="flex justify-between pt-2 border-t border-gray-100">
                    <span class="text-gray-600 font-bold">Marge</span>
                    <span class="font-extrabold {{ $synthRenta['rentable'] ? 'text-brand-700' : 'text-red-700' }}">{{ $money($synthRenta['marge']) }} F</span>
                </div>
                <div class="bg-gray-50 rounded-xl p-3 mt-3">
                    <div class="flex items-center justify-between text-xs mb-1">
                        <span class="font-bold text-gray-600">Taux de marge</span>
                        <span class="font-extrabold {{ $synthRenta['rentable'] ? 'text-brand-700' : 'text-red-700' }}">{{ $synthRenta['taux_marge'] }}%</span>
                    </div>
                    <div class="h-1.5 bg-white rounded-full">
                        <div class="h-full {{ $synthRenta['rentable'] ? 'bg-brand-500' : 'bg-red-500' }} rounded-full" style="width:{{ min(100, abs($synthRenta['taux_marge'])) }}%"></div>
                    </div>
                </div>
                <div class="bg-amber-50 border border-amber-100 rounded-xl p-3 mt-2">
                    <p class="text-[10px] font-bold uppercase text-amber-700">Masse salariale / CA</p>
                    <p class="text-lg font-extrabold {{ $synthRenta['ms_saine'] ? 'text-emerald-700' : 'text-amber-700' }}">{{ $synthRenta['ratio_ms_revenus'] }}% {{ $synthRenta['ms_saine'] ? '✓' : '⚠' }}</p>
                </div>
                <a href="{{ route('rentabilite.index') }}" class="block text-center text-xs font-bold text-amber-600 hover:text-amber-800 mt-2">Détails rentabilité →</a>
            </div>
        </div>
    </div>

    {{-- Alertes récentes --}}
    @if($alertesRecentes->isNotEmpty())
        <div class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-extrabold text-gray-900">⚠ Alertes en attente de traitement</h3>
                <a href="{{ route('cockpit.alertes') }}" class="text-xs font-bold text-violet-600 hover:text-violet-800">Voir tout →</a>
            </div>
            <div class="divide-y divide-gray-100">
                @foreach($alertesRecentes as $a)
                    @php
                        $gColor = ['critique' => 'red', 'warning' => 'amber', 'info' => 'blue'][$a->gravite] ?? 'gray';
                    @endphp
                    <div class="px-5 py-4 flex items-start gap-4 hover:bg-gray-50">
                        <span class="flex-shrink-0 w-9 h-9 rounded-full bg-{{ $gColor }}-100 flex items-center justify-center">
                            @if($a->gravite === 'critique') ⛔ @elseif($a->gravite === 'warning') ⚠ @else ℹ @endif
                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-gray-900 text-sm">{{ $a->titre }}</p>
                            <p class="text-xs text-gray-600 mt-0.5">{{ $a->message }}</p>
                            @if($a->montant_concerne)
                                <p class="text-xs font-bold text-{{ $gColor }}-700 mt-1">Montant : {{ $money($a->montant_concerne) }} F</p>
                            @endif
                        </div>
                        <span class="text-[10px] font-bold uppercase text-{{ $gColor }}-700 bg-{{ $gColor }}-50 px-2 py-1 rounded-lg">{{ $a->gravite }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('evoChart');
    if (!ctx) return;
    const d = @json($evolution);
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: d.map(x => x.date),
            datasets: [
                { label: 'Entrées', data: d.map(x => x.entrees), borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.1)', fill: true, tension: 0.3, borderWidth: 2 },
                { label: 'Sorties', data: d.map(x => x.sorties), borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,0.1)', fill: true, tension: 0.3, borderWidth: 2 },
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'top', labels: { font: { size: 11 } } } },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 9 }, maxRotation: 0 } },
                y: { grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { font: { size: 9 }, callback: v => (v/1000) + 'k' } }
            }
        }
    });
});
</script>
@endsection
