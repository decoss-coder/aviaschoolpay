@extends('rapports.pdf._layout')

@php $money = fn($v) => number_format((float) $v, 0, ',', ' '); @endphp

@section('titre', 'BILAN SCOLARITÉ')
@section('periode', 'Année scolaire : ' . ($annee?->libelle ?? '—'))

@section('content')

@if(! $annee)
    <p class="text-muted">Aucune année scolaire active.</p>
@else

<div class="kpi-grid">
    <div class="kpi kpi-info">
        <div class="lbl">Élèves inscrits</div>
        <div class="val">{{ $totaux['nb_eleves'] }}</div>
        <div class="sub">{{ $totaux['a_jour'] }} à jour · {{ $totaux['en_retard'] }} en retard</div>
    </div>
    <div class="kpi kpi-warn">
        <div class="lbl">Dû total</div>
        <div class="val">{{ $money($totaux['du_total']) }} F</div>
    </div>
    <div class="kpi kpi-success">
        <div class="lbl">Encaissé</div>
        <div class="val">{{ $money($totaux['paye']) }} F</div>
        <div class="sub">Taux : {{ $totaux['taux'] }}%</div>
    </div>
    <div class="kpi kpi-danger">
        <div class="lbl">Reste à payer</div>
        <div class="val">{{ $money($totaux['reste']) }} F</div>
    </div>
</div>

@php
    $tauxGlobal = $totaux['taux'];
    $couleur = $tauxGlobal >= 90 ? 'positive' : ($tauxGlobal >= 70 ? 'warn' : 'negative');
@endphp
<div class="result-box {{ $couleur === 'positive' ? 'positive' : ($couleur === 'negative' ? 'negative' : '') }}"
     style="{{ $couleur === 'warn' ? 'background:#fef3c7;border:2px solid #f59e0b;' : '' }}">
    <div class="lbl" style="{{ $couleur === 'warn' ? 'color:#92400e;' : '' }}">Taux de recouvrement global</div>
    <div class="val" style="{{ $couleur === 'warn' ? 'color:#92400e;' : '' }}">{{ $tauxGlobal }}%</div>
</div>

<h2>Synthèse par classe</h2>
<table class="data">
    <thead>
        <tr>
            <th>Classe</th>
            <th>Niveau</th>
            <th class="center">Élèves</th>
            <th class="right">Dû total</th>
            <th class="right">Payé</th>
            <th class="right">Reste</th>
            <th class="center">Taux</th>
            <th class="center">À jour / Retard</th>
        </tr>
    </thead>
    <tbody>
        @foreach($parClasse as $c)
            <tr>
                <td><b>{{ $c['classe'] }}</b></td>
                <td class="small">{{ $c['niveau'] }}</td>
                <td class="center">{{ $c['nb_eleves'] }}</td>
                <td class="right">{{ $money($c['du_total']) }}</td>
                <td class="right text-positive">{{ $money($c['paye']) }}</td>
                <td class="right text-negative">{{ $money($c['reste']) }}</td>
                <td class="center">
                    @php $bg = $c['taux'] >= 90 ? 'badge-success' : ($c['taux'] >= 70 ? 'badge-warn' : 'badge-danger'); @endphp
                    <span class="badge {{ $bg }}">{{ $c['taux'] }}%</span>
                </td>
                <td class="center small">
                    <span class="text-positive">{{ $c['a_jour'] }}</span> /
                    <span class="text-negative">{{ $c['en_retard'] }}</span>
                </td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="2" class="right">TOTAL</td>
            <td class="center">{{ $totaux['nb_eleves'] }}</td>
            <td class="right">{{ $money($totaux['du_total']) }}</td>
            <td class="right">{{ $money($totaux['paye']) }}</td>
            <td class="right">{{ $money($totaux['reste']) }}</td>
            <td class="center">{{ $totaux['taux'] }}%</td>
            <td class="center small">{{ $totaux['a_jour'] }} / {{ $totaux['en_retard'] }}</td>
        </tr>
    </tfoot>
</table>

@if($topDebiteurs->isNotEmpty())
<h2>Top débiteurs (à relancer)</h2>
<table class="data">
    <thead>
        <tr>
            <th class="center">#</th>
            <th>Matricule</th>
            <th>Élève</th>
            <th>Classe</th>
            <th class="right">Dû</th>
            <th class="right">Payé</th>
            <th class="right">Reste</th>
            <th class="center">Taux</th>
        </tr>
    </thead>
    <tbody>
        @foreach($topDebiteurs as $i => $d)
            <tr>
                <td class="center">{{ $i + 1 }}</td>
                <td class="mono">{{ $d->eleve?->matricule_interne ?? '—' }}</td>
                <td>{{ $d->eleve?->prenom }} {{ strtoupper($d->eleve?->nom ?? '') }}</td>
                <td class="small">{{ $d->classe?->nom ?? '—' }}</td>
                <td class="right">{{ $money($d->montant_net) }}</td>
                <td class="right text-positive">{{ $money($d->montant_paye_calc) }}</td>
                <td class="right text-negative"><b>{{ $money($d->reste_calc) }}</b></td>
                <td class="center small">{{ $d->taux_calc }}%</td>
            </tr>
        @endforeach
    </tbody>
</table>
@endif

@endif

@endsection
