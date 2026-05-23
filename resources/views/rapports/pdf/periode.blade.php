@extends('rapports.pdf._layout')

@php
    $money = fn($v) => number_format((float) $v, 0, ',', ' ');
    $modeLabels = [
        'especes' => 'Espèces', 'cheque' => 'Chèque', 'virement' => 'Virement',
        'wave' => 'Wave', 'orange_money' => 'Orange Money', 'mtn_money' => 'MTN Money',
        'moov_money' => 'Moov Money', 'carte_bancaire' => 'Carte bancaire',
    ];
    $titreRapport = $type === 'mensuel' ? 'RAPPORT MENSUEL' : 'RAPPORT TRIMESTRIEL';
@endphp

@section('titre', $titreRapport)
@section('periode', $libelle . ' · du ' . \Carbon\Carbon::parse($debut)->format('d/m/Y') . ' au ' . \Carbon\Carbon::parse($fin)->format('d/m/Y'))

@section('content')

{{-- KPIs --}}
<div class="kpi-grid">
    <div class="kpi kpi-info">
        <div class="lbl">Revenus encaissés</div>
        <div class="val">{{ $money($totalRevenus) }} F</div>
        <div class="sub">{{ $nbPaiements }} paiement(s)</div>
    </div>
    <div class="kpi kpi-danger">
        <div class="lbl">Dépenses approuvées</div>
        <div class="val">{{ $money($totalDepenses) }} F</div>
        <div class="sub">{{ $nbDepenses }} dépense(s)</div>
    </div>
    <div class="kpi kpi-success">
        <div class="lbl">Frais inscription</div>
        <div class="val small">{{ $money($totalInscription) }} F</div>
    </div>
    <div class="kpi kpi-warn">
        <div class="lbl">Frais scolarité</div>
        <div class="val small">{{ $money($totalScolarite) }} F</div>
    </div>
</div>

{{-- Résultat --}}
<div class="result-box {{ $resultat >= 0 ? 'positive' : 'negative' }}">
    <div class="lbl">{{ $resultat >= 0 ? 'Bénéfice de la période' : 'Déficit de la période' }}</div>
    <div class="val">{{ $resultat >= 0 ? '+' : '' }}{{ $money($resultat) }} F</div>
    <div class="lbl" style="margin-top:4px;font-size:8px;font-weight:normal;">
        Marge : {{ $totalRevenus > 0 ? round(($resultat / $totalRevenus) * 100, 1) : 0 }}%
    </div>
</div>

{{-- Ventilation revenus --}}
@if($parMode->isNotEmpty())
<h2>Revenus — par mode de paiement</h2>
<table class="data">
    <thead>
        <tr>
            <th>Mode</th>
            <th class="center">Nombre</th>
            <th class="right">Montant</th>
            <th class="right">% revenus</th>
        </tr>
    </thead>
    <tbody>
        @foreach($parMode as $m)
            <tr>
                <td>{{ $modeLabels[$m['mode']] ?? ucfirst($m['mode']) }}</td>
                <td class="center">{{ $m['nombre'] }}</td>
                <td class="right text-positive">{{ $money($m['montant']) }} F</td>
                <td class="right">{{ $totalRevenus > 0 ? round(($m['montant'] / $totalRevenus) * 100, 1) : 0 }}%</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td class="right">TOTAL REVENUS</td>
            <td class="center">{{ $nbPaiements }}</td>
            <td class="right">{{ $money($totalRevenus) }} F</td>
            <td class="right">100%</td>
        </tr>
    </tfoot>
</table>
@endif

{{-- Ventilation dépenses --}}
@if($parCategorie->isNotEmpty())
<h2>Dépenses — par catégorie</h2>
<table class="data">
    <thead>
        <tr>
            <th>Catégorie</th>
            <th class="center">Nombre</th>
            <th class="right">Montant</th>
            <th class="right">% dépenses</th>
        </tr>
    </thead>
    <tbody>
        @foreach($parCategorie as $c)
            <tr>
                <td>
                    <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:{{ $c['couleur'] }};margin-right:4px;"></span>
                    {{ $c['categorie'] }}
                </td>
                <td class="center">{{ $c['nombre'] }}</td>
                <td class="right text-negative">{{ $money($c['montant']) }} F</td>
                <td class="right">{{ $totalDepenses > 0 ? round(($c['montant'] / $totalDepenses) * 100, 1) : 0 }}%</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td class="right">TOTAL DÉPENSES</td>
            <td class="center">{{ $nbDepenses }}</td>
            <td class="right">{{ $money($totalDepenses) }} F</td>
            <td class="right">100%</td>
        </tr>
    </tfoot>
</table>
@endif

{{-- Détail journalier --}}
@if($detailJournalier->isNotEmpty())
<h2>Détail journalier</h2>
<table class="data">
    <thead>
        <tr>
            <th>Date</th>
            <th class="right">Revenus</th>
            <th class="right">Dépenses</th>
            <th class="right">Solde du jour</th>
        </tr>
    </thead>
    <tbody>
        @foreach($detailJournalier as $j)
            <tr>
                <td class="mono">{{ $j['date'] }}</td>
                <td class="right">{{ $j['revenus'] ? $money($j['revenus']).' F' : '—' }}</td>
                <td class="right">{{ $j['depenses'] ? $money($j['depenses']).' F' : '—' }}</td>
                <td class="right {{ $j['solde'] >= 0 ? 'text-positive' : 'text-negative' }}">
                    {{ $j['solde'] >= 0 ? '+' : '' }}{{ $money($j['solde']) }} F
                </td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td class="right">TOTAL</td>
            <td class="right text-positive">{{ $money($totalRevenus) }} F</td>
            <td class="right text-negative">{{ $money($totalDepenses) }} F</td>
            <td class="right {{ $resultat >= 0 ? 'text-positive' : 'text-negative' }}">
                {{ $resultat >= 0 ? '+' : '' }}{{ $money($resultat) }} F
            </td>
        </tr>
    </tfoot>
</table>
@endif

@endsection
