@extends('layouts.app')
@section('title', 'Score financier')
@section('page-title', 'Score de santé financière')

@section('content')
@php
    $score = $dernierScore;
    $val = $score?->score_global ?? 0;
    $color = $val >= 70 ? 'emerald' : ($val >= 40 ? 'amber' : 'red');
    $sousScores = [
        'tresorerie' => 'Trésorerie',
        'recouvrement' => 'Recouvrement',
        'rentabilite' => 'Rentabilité',
        'budget' => 'Respect du budget',
        'masse_salariale' => 'Masse salariale',
        'endettement' => 'Endettement',
    ];
@endphp

<div class="space-y-6">
    @include('cockpit._nav')

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
    @endif

    <div class="flex justify-end">
        <form method="POST" action="{{ route('cockpit.score.recalculer') }}">
            @csrf
            <button class="px-4 py-2.5 text-sm font-bold rounded-xl bg-gradient-to-r from-violet-500 to-purple-700 text-white shadow-card-violet hover:shadow-lg flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Recalculer maintenant
            </button>
        </form>
    </div>

    {{-- Score actuel grand --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-card p-6 text-center">
            <p class="text-xs font-bold uppercase text-gray-400 tracking-wider mb-2">Score actuel</p>
            <div class="relative w-44 h-44 mx-auto">
                <svg viewBox="0 0 36 36" class="w-full h-full">
                    <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                          fill="none" stroke="#e5e7eb" stroke-width="3"></path>
                    <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                          fill="none"
                          stroke="{{ $color === 'emerald' ? '#10b981' : ($color === 'amber' ? '#f59e0b' : '#ef4444') }}"
                          stroke-width="3"
                          stroke-dasharray="{{ $val }}, 100"
                          stroke-linecap="round"></path>
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                    <p class="text-5xl font-extrabold text-{{ $color }}-700">{{ (int) $val }}</p>
                    <p class="text-xs font-bold text-gray-500">/ 100</p>
                </div>
            </div>
            <p class="mt-4 inline-flex px-4 py-1.5 rounded-full text-sm font-extrabold
                {{ $score?->indicateur === 'vert' ? 'bg-emerald-100 text-emerald-700' : ($score?->indicateur === 'orange' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700') }}">
                @if($score?->indicateur === 'vert') ✓ Santé saine @elseif($score?->indicateur === 'orange') ⚠ Attention requise @else ⛔ Situation critique @endif
            </p>
            <p class="text-xs text-gray-500 mt-3">{{ $score ? 'Calculé le '.$score->date_calcul?->format('d/m/Y') : 'Pas encore calculé. Cliquez Recalculer.' }}</p>
        </div>

        {{-- Sous-scores --}}
        <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-card p-5">
            <h3 class="font-extrabold text-gray-900 mb-4">Sous-scores</h3>
            @if(! $score)
                <p class="text-sm text-gray-500 italic">Aucun calcul disponible.</p>
            @else
                <div class="space-y-3">
                    @foreach($sousScores as $key => $label)
                        @php $v = (float) ($score->{"score_$key"} ?? 0); $c = $v >= 70 ? 'emerald' : ($v >= 40 ? 'amber' : 'red'); @endphp
                        <div>
                            <div class="flex items-center justify-between text-sm mb-1">
                                <span class="font-semibold text-gray-700">{{ $label }}</span>
                                <span class="font-extrabold text-{{ $c }}-700">{{ (int) $v }}/100</span>
                            </div>
                            <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full"
                                     style="width:{{ min(100, max(0, $v)) }}%; background: {{ $c === 'emerald' ? '#10b981' : ($c === 'amber' ? '#f59e0b' : '#ef4444') }}"></div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="grid grid-cols-2 gap-3 mt-5 pt-5 border-t border-gray-100">
                    <div class="p-3 bg-gray-50 rounded-xl">
                        <p class="text-[10px] font-bold uppercase text-gray-400">Fonds de roulement</p>
                        <p class="text-lg font-extrabold text-gray-900">{{ $score->fonds_roulement_mois }} <span class="text-xs font-bold text-gray-500">mois</span></p>
                    </div>
                    <div class="p-3 bg-gray-50 rounded-xl">
                        <p class="text-[10px] font-bold uppercase text-gray-400">Ratio MS/CA</p>
                        <p class="text-lg font-extrabold text-gray-900">{{ $score->ratio_ms_revenus ?? '—' }}%</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Historique --}}
    @if($historique->isNotEmpty())
        <div class="bg-white rounded-2xl border border-gray-100 shadow-card p-5">
            <h3 class="font-extrabold text-gray-900 mb-4">Évolution du score</h3>
            <div class="h-48"><canvas id="histoChart"></canvas></div>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('histoChart');
            const d = @json($historique->map(fn($s) => ['date' => $s->date_calcul->format('d/m'), 'val' => (float)$s->score_global])->values());
            new Chart(ctx, {
                type: 'line',
                data: { labels: d.map(x => x.date), datasets: [{ label: 'Score', data: d.map(x => x.val), borderColor: '#8b5cf6', backgroundColor: 'rgba(139,92,246,0.1)', fill: true, tension: 0.3, borderWidth: 2 }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { min: 0, max: 100 } } }
            });
        });
        </script>
    @endif
</div>
@endsection
