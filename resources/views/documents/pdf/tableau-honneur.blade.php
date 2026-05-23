@extends('documents.pdf._layout')

@php
    $fmt = fn($v) => $v !== null ? number_format((float) $v, 2, ',', ' ') : '—';
    $medailles = ['🥇', '🥈', '🥉', '🏅', '🏅'];
@endphp

@section('titre', 'TABLEAU D\'HONNEUR')
@section('periode', $classe->nom . ' · ' . $trimestre->libelle)

@section('content')

<div style="text-align:center;padding:18px 10px;background:linear-gradient(to bottom, #fef3c7, #fde68a);border:2px solid #f59e0b;border-radius:8px;margin-bottom:16px;">
    <div style="font-size:11px;color:#92400e;text-transform:uppercase;font-weight:bold;letter-spacing:2px;">🏆 Tableau d'honneur 🏆</div>
    <div style="font-size:18px;color:#78350f;font-weight:bold;margin-top:3px;">{{ $classe->nom }} — {{ $trimestre->libelle }}</div>
    <div style="font-size:9px;color:#92400e;margin-top:3px;">Les {{ $top->count() }} meilleurs élèves du trimestre · Félicitations !</div>
</div>

@if($top->isEmpty())
    <p class="text-muted">Aucune moyenne saisie pour ce trimestre.</p>
@else
@foreach($top as $i => $row)
    @php
        $rang = $i + 1;
        $medaille = $medailles[$i] ?? '🎖';
        $bg = $rang === 1 ? '#fef9c3' : ($rang === 2 ? '#f1f5f9' : ($rang === 3 ? '#fed7aa' : '#ffffff'));
        $borderC = $rang === 1 ? '#f59e0b' : ($rang === 2 ? '#94a3b8' : ($rang === 3 ? '#ea580c' : '#e2e8f0'));
        $fontSize = $rang <= 3 ? '14px' : '12px';
    @endphp
    <table style="width:100%;margin-bottom:10px;border-collapse:collapse;border:2px solid {{ $borderC }};border-radius:6px;background:{{ $bg }};">
        <tr>
            <td style="width:8%;text-align:center;font-size:32px;padding:8px;">{{ $medaille }}</td>
            <td style="width:8%;text-align:center;font-size:24px;font-weight:bold;color:{{ $borderC }};">{{ $rang }}<sup style="font-size:10px;">{{ $rang === 1 ? 'er' : 'e' }}</sup></td>
            <td style="padding:10px;">
                <div style="font-size:{{ $fontSize }};font-weight:bold;color:#0f172a;">{{ $row->eleve->prenom }} {{ strtoupper($row->eleve->nom) }}</div>
                <div style="font-size:9px;color:#64748b;margin-top:2px;">Matricule {{ $row->eleve->matricule_interne ?? '—' }}{{ $row->mention ? ' · '.$row->mention : '' }}</div>
            </td>
            <td style="text-align:right;padding:10px 15px;">
                <div style="font-size:8px;color:#64748b;text-transform:uppercase;font-weight:bold;">Moyenne</div>
                <div style="font-size:24px;font-weight:bold;color:#0a7b3f;">{{ $fmt($row->moyenne_generale) }}<span style="font-size:11px;color:#64748b;">/20</span></div>
            </td>
        </tr>
    </table>
@endforeach
@endif

<div style="margin-top:20px;text-align:center;padding:12px;background:#f8fafc;border-radius:5px;">
    <div style="font-size:9px;color:#64748b;font-style:italic;">
        « L'excellence n'est pas un acte mais une habitude. » — Aristote
    </div>
</div>

<div style="margin-top:30px;text-align:right;font-size:9px;color:#64748b;">
    <div>Fait à {{ $etab?->ville ?? '—' }}, le {{ now()->format('d/m/Y') }}</div>
    <div style="margin-top:25px;border-top:1px dashed #94a3b8;padding-top:3px;width:200px;margin-left:auto;text-align:center;">
        <b>Le Directeur</b>
    </div>
</div>

@endsection
