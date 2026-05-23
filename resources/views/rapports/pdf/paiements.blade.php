@extends('rapports.pdf._layout')

@php
    $money = fn($v) => number_format((float) $v, 0, ',', ' ');
    $modeLabels = [
        'especes' => 'Espèces', 'cheque' => 'Chèque', 'virement' => 'Virement',
        'wave' => 'Wave', 'orange_money' => 'Orange Money', 'mtn_money' => 'MTN Money',
        'moov_money' => 'Moov Money', 'carte_bancaire' => 'Carte bancaire',
    ];
@endphp

@section('titre', 'ÉTAT DES PAIEMENTS')
@section('periode', 'Du ' . \Carbon\Carbon::parse($debut)->format('d/m/Y') . ' au ' . \Carbon\Carbon::parse($fin)->format('d/m/Y') . ($classe ? ' · ' . $classe->nom : ''))

@section('content')

<div class="kpi-grid">
    <div class="kpi kpi-info">
        <div class="lbl">Total encaissé</div>
        <div class="val">{{ $money($total) }} F</div>
        <div class="sub">{{ $paiements->count() }} paiement(s)</div>
    </div>
    <div class="kpi kpi-success">
        <div class="lbl">Inscriptions</div>
        <div class="val">{{ $money($totalInsc) }} F</div>
    </div>
    <div class="kpi kpi-warn">
        <div class="lbl">Scolarité</div>
        <div class="val">{{ $money($totalScol) }} F</div>
    </div>
    <div class="kpi">
        <div class="lbl">Période</div>
        <div class="val small">{{ \Carbon\Carbon::parse($debut)->diffInDays(\Carbon\Carbon::parse($fin)) + 1 }} jours</div>
    </div>
</div>

@if($parMode->isNotEmpty())
<h2>Ventilation par mode de paiement</h2>
<table class="data">
    <thead>
        <tr>
            <th>Mode</th>
            <th class="center">Nombre</th>
            <th class="right">Montant</th>
            <th class="right">% total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($parMode as $m)
            <tr>
                <td>{{ $modeLabels[$m['mode']] ?? ucfirst($m['mode']) }}</td>
                <td class="center">{{ $m['nombre'] }}</td>
                <td class="right">{{ $money($m['montant']) }} F</td>
                <td class="right">{{ $total > 0 ? round(($m['montant'] / $total) * 100, 1) : 0 }}%</td>
            </tr>
        @endforeach
    </tbody>
</table>
@endif

@if($parClasse->isNotEmpty() && ! $classe)
<h2>Ventilation par classe</h2>
<table class="data">
    <thead>
        <tr>
            <th>Classe</th>
            <th class="center">Paiements</th>
            <th class="right">Montant</th>
        </tr>
    </thead>
    <tbody>
        @foreach($parClasse as $c)
            <tr>
                <td>{{ $c['classe'] }}</td>
                <td class="center">{{ $c['nombre'] }}</td>
                <td class="right">{{ $money($c['montant']) }} F</td>
            </tr>
        @endforeach
    </tbody>
</table>
@endif

<h2>Détail des paiements</h2>
@if($paiements->isEmpty())
    <p class="text-muted">Aucun paiement sur cette période.</p>
@else
<table class="data">
    <thead>
        <tr>
            <th>Date</th>
            <th>N° reçu</th>
            <th>Élève</th>
            <th>Classe</th>
            <th>Mode</th>
            <th class="right">Inscription</th>
            <th class="right">Scolarité</th>
            <th class="right">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($paiements as $p)
            <tr>
                <td class="mono">{{ $p->date_paiement?->format('d/m/Y') }}</td>
                <td class="mono">{{ $p->numero_recu ?: $p->reference }}</td>
                <td>{{ $p->eleve?->prenom }} {{ strtoupper($p->eleve?->nom ?? '') }}</td>
                <td class="small">{{ $p->inscription?->classe?->nom ?? '—' }}</td>
                <td class="small">{{ $modeLabels[$p->mode] ?? $p->mode }}</td>
                <td class="right">{{ $p->montant_inscription ? $money($p->montant_inscription) : '—' }}</td>
                <td class="right">{{ $p->montant_scolarite ? $money($p->montant_scolarite) : '—' }}</td>
                <td class="right text-positive">{{ $money($p->montant) }} F</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="5" class="right">TOTAL GÉNÉRAL</td>
            <td class="right">{{ $money($totalInsc) }} F</td>
            <td class="right">{{ $money($totalScol) }} F</td>
            <td class="right">{{ $money($total) }} F</td>
        </tr>
    </tfoot>
</table>
@endif

@endsection
