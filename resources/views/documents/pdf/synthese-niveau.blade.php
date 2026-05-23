@extends('documents.pdf._layout')

@php
    $fmt = fn($v) => $v !== null ? number_format((float) $v, 2, ',', ' ') : '—';
    $totalEffectif = $synthese->sum('effectif');
    $moyennesValides = $synthese->where('avec_moyenne', '>', 0);
    $moyenneNiveau = $moyennesValides->count() > 0
        ? round($moyennesValides->avg('moyenne'), 2)
        : null;
@endphp

@section('titre', 'SYNTHÈSE NIVEAU')
@section('periode', $niveau->libelle . ' · ' . $trimestre->libelle)

@section('content')

<div style="margin-bottom:10px;">
    <span class="kpi kpi-info"><div class="lbl">Classes du niveau</div><div class="val">{{ $synthese->count() }}</div></span>
    <span class="kpi kpi-success"><div class="lbl">Effectif total</div><div class="val">{{ $totalEffectif }}</div></span>
    <span class="kpi kpi-warn"><div class="lbl">Moyenne niveau</div><div class="val">{{ $fmt($moyenneNiveau) }}</div></span>
    <span class="kpi"><div class="lbl">Trimestre</div><div class="val" style="font-size:11px;">{{ $trimestre->libelle }}</div></span>
</div>

<h2>Comparatif par classe — {{ $niveau->libelle }}</h2>
@if($synthese->isEmpty())
    <p class="text-muted">Aucune classe pour ce niveau cette année.</p>
@else
<table class="data">
    <thead>
        <tr>
            <th style="width:5%;">#</th>
            <th style="width:25%;">Classe</th>
            <th style="width:10%;" class="center">Effectif</th>
            <th style="width:10%;" class="center">Notés</th>
            <th style="width:13%;" class="right">Moyenne</th>
            <th style="width:11%;" class="right">Max</th>
            <th style="width:11%;" class="right">Min</th>
            <th style="width:15%;" class="center">Taux réussite</th>
        </tr>
    </thead>
    <tbody>
        @foreach($synthese as $i => $s)
            @php
                $tx = $s['reussite'];
                $cls = $tx >= 70 ? 'badge-success' : ($tx >= 40 ? 'badge-warn' : 'badge-danger');
                $moyColor = $s['moyenne'] === null ? '#94a3b8' : ($s['moyenne'] >= 12 ? '#047857' : ($s['moyenne'] >= 10 ? '#0a7b3f' : '#b91c1c'));
            @endphp
            <tr>
                <td class="center">{{ $i+1 }}</td>
                <td><b>{{ $s['classe'] }}</b></td>
                <td class="center">{{ $s['effectif'] }}</td>
                <td class="center">{{ $s['avec_moyenne'] }}</td>
                <td class="right" style="color:{{ $moyColor }};font-weight:bold;font-size:11px;">{{ $fmt($s['moyenne']) }}</td>
                <td class="right text-positive">{{ $fmt($s['max']) }}</td>
                <td class="right text-negative">{{ $fmt($s['min']) }}</td>
                <td class="center"><span class="badge {{ $cls }}">{{ $tx }}%</span></td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="2" class="right">SYNTHÈSE NIVEAU</td>
            <td class="center">{{ $totalEffectif }}</td>
            <td class="center">{{ $synthese->sum('avec_moyenne') }}</td>
            <td class="right" style="font-size:11px;">{{ $fmt($moyenneNiveau) }}</td>
            <td class="right">{{ $fmt($synthese->max('max')) }}</td>
            <td class="right">{{ $fmt($synthese->min('min')) }}</td>
            <td class="center">—</td>
        </tr>
    </tfoot>
</table>

<div style="margin-top:15px;padding:10px;background:#eff6ff;border:1px solid #3b82f6;border-radius:4px;font-size:9px;color:#1e3a8a;">
    <b>📊 Lecture du tableau :</b>
    Ce tableau permet de comparer la performance des différentes classes d'un même niveau pour le trimestre.
    Les écarts importants entre classes peuvent indiquer un besoin de revue pédagogique ou de différenciation.
</div>
@endif

@endsection
