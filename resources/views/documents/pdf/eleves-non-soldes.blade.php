@extends('documents.pdf._layout')

@php $money = fn($v) => number_format((float) $v, 0, ',', ' '); @endphp

@section('titre', 'ÉLÈVES NON SOLDÉS')
@section('periode', ($annee->libelle ?? '—') . ' · ' . $filtre)

@section('content')
<div style="margin-bottom:10px;">
    <span class="kpi kpi-warn"><div class="lbl">Élèves concernés</div><div class="val">{{ $nb_eleves }}</div></span>
    <span class="kpi kpi-info"><div class="lbl">Dû total</div><div class="val">{{ $money($total_du) }} F</div></span>
    <span class="kpi kpi-success"><div class="lbl">Déjà payé</div><div class="val">{{ $money($total_paye) }} F</div></span>
    <span class="kpi kpi-danger"><div class="lbl">Reste à payer</div><div class="val">{{ $money($total_reste) }} F</div></span>
</div>

@if($inscriptions->isEmpty())
    <p class="text-positive" style="text-align:center;padding:30px;">🎉 Tous les élèves sont à jour de leurs paiements !</p>
@else
<table class="data">
    <thead>
        <tr>
            <th style="width:4%;" class="center">#</th>
            <th style="width:12%;">Matricule</th>
            <th style="width:24%;">Élève</th>
            <th style="width:10%;">Classe</th>
            <th style="width:16%;">Contact parent</th>
            <th style="width:11%;" class="right">Dû</th>
            <th style="width:11%;" class="right">Payé</th>
            <th style="width:9%;" class="right">Reste</th>
            <th style="width:6%;" class="center">%</th>
        </tr>
    </thead>
    <tbody>
        @foreach($inscriptions as $i => $insc)
            @php $tx = $insc->taux_calc; $cls = $tx >= 70 ? 'badge-warn' : ($tx > 0 ? 'badge-danger' : 'badge-danger'); @endphp
            <tr>
                <td class="center">{{ $i+1 }}</td>
                <td class="mono">{{ $insc->eleve?->matricule_interne ?? '—' }}</td>
                <td><b>{{ $insc->eleve?->prenom }} {{ strtoupper($insc->eleve?->nom ?? '') }}</b></td>
                <td>{{ $insc->classe?->nom ?? '—' }}</td>
                <td class="mono">{{ $insc->eleve?->contact_urgence_tel ?? '—' }}</td>
                <td class="right">{{ $money($insc->montant_net) }}</td>
                <td class="right text-positive">{{ $money($insc->paye_calc) }}</td>
                <td class="right text-negative">{{ $money($insc->reste_calc) }}</td>
                <td class="center"><span class="badge {{ $cls }}">{{ $tx }}%</span></td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="5" class="right">TOTAUX</td>
            <td class="right">{{ $money($total_du) }} F</td>
            <td class="right">{{ $money($total_paye) }} F</td>
            <td class="right">{{ $money($total_reste) }} F</td>
            <td class="center">—</td>
        </tr>
    </tfoot>
</table>

<div style="margin-top:15px;padding:10px;background:#fef3c7;border:1px solid #f59e0b;border-radius:4px;font-size:9px;color:#92400e;">
    <b>📞 Action recommandée :</b> Contactez les parents concernés via le centre de communication ou par SMS pour relance.
</div>
@endif

@endsection
