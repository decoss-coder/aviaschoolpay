@extends('layouts.app')

@section('title', 'Rapport ponctualité')
@section('page-title', 'Rapport de ponctualité')
@section('page-subtitle')
    Analyse sur {{ $periode }} jours · {{ $debut->isoFormat('D MMM') }} — {{ $fin->isoFormat('D MMM YYYY') }}
@endsection

@section('content')
<div class="space-y-6">

    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-brand-900 to-brand-800 text-white shadow-xl">
        <div class="relative px-6 py-6 sm:px-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <a href="{{ route('pointage.index') }}" class="inline-flex items-center gap-1.5 text-sm text-brand-100/90 hover:text-white mb-2 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    Retour supervision
                </a>
                <h2 class="font-display text-2xl font-extrabold tracking-tight">Rapport de ponctualité</h2>
                <p class="text-brand-100/90 text-sm mt-1">Scores enseignants, répartition par jour et tendances (cahier des charges module pointage)</p>
                @if(isset($annee) && $annee)
                    <p class="text-brand-100/80 text-xs mt-1">📅 Année active : <span class="font-bold text-white">{{ $annee->libelle }}</span> · Seuls les enseignants affectés cette année sont classés.</p>
                @elseif(isset($annee))
                    <p class="text-amber-200 text-xs mt-1">⚠️ Aucune année scolaire active — le classement est vide.</p>
                @endif
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach([7 => '7 jours', 30 => '30 jours', 90 => '90 jours'] as $jours => $libelle)
                    <a href="{{ route('pointage.rapport', ['periode' => $jours]) }}"
                       class="px-4 py-2 rounded-xl text-sm font-bold transition-colors {{ $periode === $jours ? 'bg-white text-brand-800 shadow-lg' : 'bg-white/10 border border-white/25 hover:bg-white/15' }}">
                        {{ $libelle }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-6 gap-3">
        @include('pointage.partials.kpi-card', ['label' => 'Scans', 'value' => $stats['total'], 'accent' => 'brand'])
        @include('pointage.partials.kpi-card', ['label' => 'Arrivées', 'value' => $stats['arrivees'], 'accent' => 'blue'])
        @include('pointage.partials.kpi-card', ['label' => 'Présents', 'value' => $stats['presents'], 'accent' => 'emerald'])
        @include('pointage.partials.kpi-card', ['label' => 'Retards', 'value' => $stats['retards'], 'accent' => 'amber'])
        @include('pointage.partials.kpi-card', ['label' => 'Anomalies', 'value' => $stats['anomalies'], 'accent' => 'red'])
        @include('pointage.partials.kpi-card', ['label' => 'Score moyen', 'value' => $moyenneScore !== null ? round($moyenneScore).'%' : '—', 'hint' => 'Établissement', 'accent' => 'violet'])
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-card p-5">
            <h3 class="font-display font-bold text-gray-900 mb-4">Activité par jour de la semaine</h3>
            <div class="h-64"><canvas id="chartJourSemaine"></canvas></div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-card p-5">
            <h3 class="font-display font-bold text-gray-900 mb-4">Répartition des statuts</h3>
            <div class="h-64 flex items-center justify-center"><canvas id="chartStatuts" class="max-h-64"></canvas></div>
        </div>
    </div>

    @if(count($chartEvolution['labels']) > 0)
    <div class="bg-white rounded-2xl border border-gray-100 shadow-card p-5">
        <h3 class="font-display font-bold text-gray-900 mb-4">Évolution quotidienne des scans</h3>
        <div class="h-56"><canvas id="chartEvolution"></canvas></div>
    </div>
    @endif

    <div class="bg-white rounded-2xl border border-gray-100 shadow-card overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h3 class="font-display font-bold text-gray-900">Classement ponctualité enseignants</h3>
                <p class="text-xs text-gray-500 mt-0.5">Score 0–100 : présence à l'arrivée, −5 pt/retard, −15 pt/hors zone</p>
            </div>
            @if(Route::has('alertes-pointage.index'))
                <a href="{{ route('alertes-pointage.index') }}" class="text-sm font-bold text-brand-600 hover:text-brand-700">
                    {{ $stats['alertes'] }} alerte(s) sur la période →
                </a>
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left text-[11px] font-bold uppercase tracking-wider text-gray-500">
                    <tr>
                        <th class="px-5 py-3">#</th>
                        <th class="px-5 py-3">Enseignant</th>
                        <th class="px-5 py-3 text-center">Arrivées</th>
                        <th class="px-5 py-3 text-center">Présents</th>
                        <th class="px-5 py-3 text-center">Retards</th>
                        <th class="px-5 py-3 text-center">Hors zone</th>
                        <th class="px-5 py-3 text-center">Score</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($classement as $i => $row)
                        @php $ens = $row['enseignant']; @endphp
                        <tr class="hover:bg-brand-50/30 transition-colors">
                            <td class="px-5 py-3 text-gray-400 font-bold">{{ $i + 1 }}</td>
                            <td class="px-5 py-3">
                                <p class="font-bold text-gray-900">{{ $ens->nom }} {{ $ens->prenom }}</p>
                                <p class="text-xs text-gray-500">{{ $ens->specialite ?? '—' }}</p>
                            </td>
                            <td class="px-5 py-3 text-center font-semibold">{{ $row['total'] }}</td>
                            <td class="px-5 py-3 text-center text-emerald-700 font-semibold">{{ $row['presents'] }}</td>
                            <td class="px-5 py-3 text-center text-amber-700 font-semibold">{{ $row['retards'] }}</td>
                            <td class="px-5 py-3 text-center text-red-700 font-semibold">{{ $row['hors_zone'] }}</td>
                            <td class="px-5 py-3 text-center">
                                @if($row['score'] !== null)
                                    <span class="inline-flex min-w-[3rem] justify-center px-2.5 py-1 rounded-lg text-xs font-extrabold
                                        {{ $row['score'] >= 80 ? 'bg-emerald-100 text-emerald-800' : ($row['score'] >= 60 ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800') }}">
                                        {{ $row['score'] }}%
                                    </span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-gray-500">Aucun pointage sur cette période.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const brand = '#0A7B3F';
    const brandLight = 'rgba(10, 123, 63, 0.15)';

    new Chart(document.getElementById('chartJourSemaine'), {
        type: 'bar',
        data: {
            labels: @json($chartJourSemaine['labels']),
            datasets: [{
                label: 'Scans',
                data: @json($chartJourSemaine['data']),
                backgroundColor: brandLight,
                borderColor: brand,
                borderWidth: 2,
                borderRadius: 8,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } },
                x: { grid: { display: false } }
            }
        }
    });

    @if(count($chartStatuts['labels']) > 0)
    new Chart(document.getElementById('chartStatuts'), {
        type: 'doughnut',
        data: {
            labels: @json($chartStatuts['labels']),
            datasets: [{
                data: @json($chartStatuts['data']),
                backgroundColor: ['#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#6b7280'],
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });
    @endif

    @if(count($chartEvolution['labels']) > 0)
    const ctxEv = document.getElementById('chartEvolution');
    if (ctxEv) {
        new Chart(ctxEv, {
            type: 'line',
            data: {
                labels: @json($chartEvolution['labels']),
                datasets: [{
                    label: 'Scans / jour',
                    data: @json($chartEvolution['data']),
                    borderColor: brand,
                    backgroundColor: brandLight,
                    fill: true,
                    tension: 0.35,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }
    @endif
});
</script>
@endpush
