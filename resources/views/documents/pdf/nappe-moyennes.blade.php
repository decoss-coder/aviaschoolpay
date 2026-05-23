@extends('documents.pdf._layout')

@php
    $fmt = fn($v) => $v !== null ? number_format((float) $v, 2, ',', ' ') : '—';
    $colorNote = fn($v) => $v === null
        ? '#94a3b8'
        : ($v >= 14 ? '#047857' : ($v >= 10 ? '#0a7b3f' : ($v >= 8 ? '#b45309' : '#b91c1c')));
@endphp

@section('titre', 'NAPPE DES MOYENNES')
@section('periode', $classe->nom . ' · ' . $trimestre->libelle . ' · ' . ($etab?->nom ?? ''))

@section('content')

<div style="margin-bottom:10px;">
    <span class="kpi kpi-info"><div class="lbl">Effectif</div><div class="val">{{ $stats['effectif'] }}</div></span>
    <span class="kpi kpi-success"><div class="lbl">Moyenne classe</div><div class="val">{{ $fmt($stats['moyenne_classe']) }}</div></span>
    <span class="kpi"><div class="lbl">Max / Min</div><div class="val" style="font-size:11px;">{{ $fmt($stats['note_max']) }} / {{ $fmt($stats['note_min']) }}</div></span>
    <span class="kpi kpi-warn"><div class="lbl">Taux réussite (≥10)</div><div class="val">{{ $stats['taux_reussite'] }}%</div></span>
</div>

@if($lignes->isEmpty())
    <p class="text-muted">Aucun élève dans cette classe.</p>
@elseif($matieres->isEmpty())
    <p class="text-muted">Aucune moyenne saisie pour ce trimestre.</p>
@else

<h2>{{ $classe->nom }} — {{ $classe->niveau?->libelle }} — {{ $trimestre->libelle }}</h2>
<table class="data" style="font-size:8px;">
    <thead>
        <tr>
            <th style="width:3%;" class="center">#</th>
            <th style="width:18%;">Élève</th>
            @foreach($matieres as $m)
                <th class="center" style="font-size:7px;">{{ $m->code ?: mb_substr($m->nom, 0, 4) }}</th>
            @endforeach
            <th class="center" style="background:#0a7b3f;color:#fff;width:8%;">MOY</th>
            <th class="center" style="background:#0a7b3f;color:#fff;width:5%;">RG</th>
            <th class="center" style="width:8%;">Mention</th>
        </tr>
    </thead>
    <tbody>
        @foreach($lignes as $i => $l)
            <tr>
                <td class="center">{{ $i+1 }}</td>
                <td><b style="font-size:8px;">{{ $l['eleve']->prenom }} {{ strtoupper($l['eleve']->nom) }}</b><br><span class="mono" style="font-size:7px;color:#64748b;">{{ $l['eleve']->matricule_interne }}</span></td>
                @foreach($matieres as $m)
                    @php $mm = $l['moyennes'][$m->id] ?? null; $val = $mm?->moyenne; @endphp
                    <td class="center" style="color:{{ $colorNote($val) }};font-weight:bold;">{{ $fmt($val) }}</td>
                @endforeach
                <td class="center" style="background:#d1fae5;color:{{ $colorNote($l['generale']) }};font-weight:bold;font-size:10px;">{{ $fmt($l['generale']) }}</td>
                <td class="center" style="background:#d1fae5;font-weight:bold;">{{ $l['rang'] ?? '—' }}</td>
                <td class="center" style="font-size:7px;">{{ $l['mention'] ?? '—' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

{{-- Légende matières --}}
<h2>Légende matières</h2>
<table class="data" style="font-size:8px;">
    <thead><tr><th style="width:15%;">Code</th><th>Matière complète</th><th class="center" style="width:15%;">Coefficient</th></tr></thead>
    <tbody>
        @foreach($matieres as $m)
            <tr>
                <td class="mono"><b>{{ $m->code ?: mb_substr($m->nom, 0, 4) }}</b></td>
                <td>{{ $m->nom }}</td>
                <td class="center">{{ $m->coefficient_defaut ?? '—' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<div style="margin-top:10px;padding:8px;background:#f1f5f9;border-radius:4px;font-size:8px;">
    <b>Code couleur :</b>
    <span style="color:#047857;">●</span> Très bien (≥14) ·
    <span style="color:#0a7b3f;">●</span> Moyenne (≥10) ·
    <span style="color:#b45309;">●</span> Limite (8-10) ·
    <span style="color:#b91c1c;">●</span> Insuffisant (&lt;8)
</div>
@endif

@endsection
