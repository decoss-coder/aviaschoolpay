@extends('documents.pdf._layout')

@php $money = fn($v) => number_format((float) $v, 0, ',', ' '); @endphp

@section('titre', 'RÉCAPITULATIF PAIE')
@section('periode', \Carbon\Carbon::parse($mois.'-01')->locale('fr')->isoFormat('MMMM YYYY') . ' · ' . ($etab?->nom ?? ''))

@section('content')

<div style="margin-bottom:10px;">
    <span class="kpi kpi-info"><div class="lbl">Fiches générées</div><div class="val">{{ $totaux['nb_fiches'] }}</div></span>
    <span class="kpi kpi-success"><div class="lbl">Heures totales</div><div class="val">{{ number_format($totaux['heures'], 1, ',', ' ') }}h</div></span>
    <span class="kpi kpi-warn"><div class="lbl">Salaire brut</div><div class="val" style="font-size:11px;">{{ $money($totaux['brut']) }} F</div></span>
    <span class="kpi kpi-danger"><div class="lbl">Cotisations + Impôts</div><div class="val" style="font-size:11px;">{{ $money($totaux['cotisations'] + $totaux['impots']) }} F</div></span>
</div>

@if($fiches->isEmpty())
    <p class="text-muted">Aucune fiche de paie générée pour ce mois. Allez sur /fiches-paie pour les générer.</p>
@else

<h2>Détail par enseignant — {{ \Carbon\Carbon::parse($mois.'-01')->locale('fr')->isoFormat('MMMM YYYY') }}</h2>
<table class="data">
    <thead>
        <tr>
            <th style="width:4%;">#</th>
            <th style="width:11%;">Réf</th>
            <th style="width:23%;">Enseignant</th>
            <th style="width:9%;" class="center">Statut paie</th>
            <th style="width:8%;" class="right">Heures</th>
            <th style="width:12%;" class="right">Brut</th>
            <th style="width:11%;" class="right">CNPS+IUTS</th>
            <th style="width:14%;" class="right">Net à payer</th>
            <th style="width:8%;" class="center">État</th>
        </tr>
    </thead>
    <tbody>
        @foreach($fiches as $i => $f)
            @php
                $sb = [
                    'brouillon' => 'badge-warn',
                    'validee'   => 'badge-info',
                    'payee'     => 'badge-success',
                    'annulee'   => 'badge-danger',
                ][$f->statut] ?? 'badge-info';
                $lblStatut = [
                    'brouillon' => 'Brouillon', 'validee' => 'Validée', 'payee' => 'Payée', 'annulee' => 'Annulée',
                ][$f->statut] ?? $f->statut;
            @endphp
            <tr>
                <td class="center">{{ $i+1 }}</td>
                <td class="mono" style="font-size:7px;">{{ $f->reference }}</td>
                <td>
                    <b>{{ $f->enseignant?->prenom }} {{ strtoupper($f->enseignant?->nom ?? '') }}</b>
                    <br><span style="font-size:7px;color:#64748b;">{{ $f->enseignant?->matricule_mena ?? '—' }} · {{ ucfirst($f->enseignant?->statut ?? '') }}</span>
                </td>
                <td class="center"><span class="badge {{ $sb }}" style="font-size:7px;">{{ $lblStatut }}</span></td>
                <td class="right" style="font-size:9px;"><b>{{ number_format($f->heures_travaillees, 1, ',', '') }}h</b></td>
                <td class="right">{{ $money($f->salaire_brut) }}</td>
                <td class="right text-negative">{{ $money($f->cotisations_sociales + $f->impots) }}</td>
                <td class="right text-positive" style="font-size:10px;"><b>{{ $money($f->salaire_net) }} F</b></td>
                <td class="center" style="font-size:7px;">
                    @if($f->statut === 'payee') ✓
                    @elseif($f->statut === 'validee') ⏳
                    @else 📝
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="4" class="right">TOTAUX MENSUELS</td>
            <td class="right">{{ number_format($totaux['heures'], 1, ',', '') }}h</td>
            <td class="right">{{ $money($totaux['brut']) }}</td>
            <td class="right">{{ $money($totaux['cotisations'] + $totaux['impots']) }}</td>
            <td class="right">{{ $money($totaux['net']) }} F</td>
            <td>—</td>
        </tr>
    </tfoot>
</table>

{{-- Détail cotisations / impôts --}}
<h2>Charges patronales et retenues</h2>
<table class="data">
    <thead>
        <tr>
            <th style="width:50%;">Rubrique</th>
            <th class="right">Montant</th>
            <th class="right">% / Brut</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Cotisations sociales (CNPS — part salariale)</td>
            <td class="right text-negative">{{ $money($totaux['cotisations']) }} F</td>
            <td class="right">{{ $totaux['brut'] > 0 ? round(($totaux['cotisations'] / $totaux['brut']) * 100, 2) : 0 }}%</td>
        </tr>
        <tr>
            <td>IUTS (Impôt sur traitements et salaires)</td>
            <td class="right text-negative">{{ $money($totaux['impots']) }} F</td>
            <td class="right">{{ $totaux['brut'] > 0 ? round(($totaux['impots'] / $totaux['brut']) * 100, 2) : 0 }}%</td>
        </tr>
        <tr>
            <td><b>Total retenues</b></td>
            <td class="right text-negative"><b>{{ $money($totaux['cotisations'] + $totaux['impots']) }} F</b></td>
            <td class="right"><b>{{ $totaux['brut'] > 0 ? round((($totaux['cotisations'] + $totaux['impots']) / $totaux['brut']) * 100, 2) : 0 }}%</b></td>
        </tr>
    </tbody>
</table>

{{-- Bloc signatures --}}
<table style="width:100%;margin-top:25px;border-collapse:collapse;">
    <tr>
        <td style="width:33%;padding:0 10px;vertical-align:top;text-align:center;">
            <div style="font-size:8px;color:#64748b;font-weight:bold;text-transform:uppercase;">Le Comptable</div>
            <div style="border-top:1px dashed #94a3b8;margin-top:40px;padding-top:4px;font-size:8px;color:#94a3b8;">Signature</div>
        </td>
        <td style="width:33%;padding:0 10px;vertical-align:top;text-align:center;">
            <div style="font-size:8px;color:#64748b;font-weight:bold;text-transform:uppercase;">Le Directeur</div>
            <div style="border-top:1px dashed #94a3b8;margin-top:40px;padding-top:4px;font-size:8px;color:#94a3b8;">Signature & cachet</div>
        </td>
        <td style="width:33%;padding:0 10px;vertical-align:top;text-align:center;">
            <div style="font-size:8px;color:#64748b;font-weight:bold;text-transform:uppercase;">Date d'arrêté</div>
            <div style="border-top:1px dashed #94a3b8;margin-top:40px;padding-top:4px;font-size:8px;color:#94a3b8;">{{ now()->format('d/m/Y') }}</div>
        </td>
    </tr>
</table>
@endif

@endsection
