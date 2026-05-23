@extends('documents.pdf._layout')

@php
    $typeIcons = [
        'rentree' => '🎒', 'vacances' => '🏖', 'examen' => '📝', 'conseil_classe' => '👥',
        'reunion_parents' => '🤝', 'fete' => '🎉', 'sortie' => '🚌', 'ferie' => '🇨🇮', 'autre' => '📌',
    ];
    $typeColors = [
        'rentree'         => 'badge-success',
        'vacances'        => 'badge-info',
        'examen'          => 'badge-warn',
        'conseil_classe'  => 'badge-info',
        'reunion_parents' => 'badge-success',
        'fete'            => 'badge-warn',
        'sortie'          => 'badge-info',
        'ferie'           => 'badge-danger',
        'autre'           => 'badge-info',
    ];
@endphp

@section('titre', 'CALENDRIER SCOLAIRE')
@section('periode', 'Année scolaire : ' . ($annee->libelle ?? '—'))

@section('content')

<div style="text-align:center;padding:14px;background:linear-gradient(to bottom right,#fef3c7,#fde68a);border-radius:8px;margin-bottom:14px;border:2px solid #f59e0b;">
    <div style="font-size:9px;color:#92400e;text-transform:uppercase;font-weight:bold;letter-spacing:1px;">📅 Calendrier officiel</div>
    <div style="font-size:18px;color:#78350f;font-weight:bold;margin-top:3px;">{{ $annee->libelle }}</div>
    <div style="font-size:9px;color:#92400e;margin-top:2px;">Du {{ $annee->date_debut?->format('d/m/Y') }} au {{ $annee->date_fin?->format('d/m/Y') }}</div>
</div>

@if($trimestres->isNotEmpty())
<h2>📚 Découpage par trimestres</h2>
<table class="data">
    <thead>
        <tr>
            <th style="width:8%;" class="center">N°</th>
            <th>Libellé</th>
            <th class="center">Début</th>
            <th class="center">Fin</th>
            <th class="center">Durée</th>
        </tr>
    </thead>
    <tbody>
        @foreach($trimestres as $t)
            <tr>
                <td class="center"><b>T{{ $t->numero }}</b></td>
                <td><b>{{ $t->libelle }}</b></td>
                <td class="center">{{ $t->date_debut?->format('d/m/Y') }}</td>
                <td class="center">{{ $t->date_fin?->format('d/m/Y') }}</td>
                <td class="center">{{ $t->date_debut && $t->date_fin ? $t->date_debut->diffInDays($t->date_fin) + 1 . ' j' : '—' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
@endif

@if($evenements->isEmpty())
    <div style="padding:25px;text-align:center;background:#f8fafc;border-radius:5px;color:#94a3b8;">
        Aucun événement enregistré pour cette année.<br>
        <span style="font-size:9px;">Ajoutez-en via : Outils → Événements scolaires.</span>
    </div>
@else

<h2>📋 Tous les événements ({{ $evenements->count() }})</h2>

@php
    use Carbon\Carbon;
    Carbon::setLocale('fr');
@endphp

@foreach($parMois as $moisKey => $items)
    @php $moisCarbon = \Carbon\Carbon::parse($moisKey.'-01'); @endphp
    <div style="background:#0a7b3f;color:#fff;padding:6px 10px;border-radius:4px;margin:8px 0 4px;font-size:10px;font-weight:bold;text-transform:uppercase;letter-spacing:1px;">
        {{ $moisCarbon->isoFormat('MMMM YYYY') }} — {{ $items->count() }} événement(s)
    </div>
    <table class="data">
        <thead>
            <tr>
                <th style="width:10%;">Date</th>
                <th style="width:8%;" class="center">Type</th>
                <th style="width:32%;">Titre</th>
                <th style="width:18%;">Lieu</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $e)
                @php $tc = $typeColors[$e->type] ?? 'badge-info'; @endphp
                <tr>
                    <td><b>{{ $e->date_debut->format('d/m') }}</b>
                        @if($e->date_fin && $e->date_fin->ne($e->date_debut))
                            <br><span style="font-size:7px;color:#64748b;">→ {{ $e->date_fin->format('d/m') }}</span>
                        @endif
                        @if(! $e->toute_journee && $e->heure_debut)
                            <br><span style="font-size:7px;color:#64748b;font-family:DejaVu Sans Mono;">{{ substr($e->heure_debut, 0, 5) }}</span>
                        @endif
                    </td>
                    <td class="center">
                        <span style="font-size:14px;">{{ $typeIcons[$e->type] ?? '📌' }}</span>
                        <br><span class="badge {{ $tc }}" style="font-size:6px;">{{ ucfirst(str_replace('_', ' ', $e->type)) }}</span>
                    </td>
                    <td><b>{{ $e->titre }}</b></td>
                    <td style="font-size:8px;color:#64748b;">{{ $e->lieu ?? '—' }}</td>
                    <td style="font-size:8px;color:#64748b;">{{ $e->description ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endforeach

@endif

<div style="margin-top:20px;text-align:right;font-size:9px;color:#64748b;">
    <div>Fait à {{ $etab?->ville ?? '—' }}, le {{ now()->format('d/m/Y') }}</div>
    <div style="margin-top:30px;border-top:1px dashed #94a3b8;padding-top:3px;width:200px;margin-left:auto;text-align:center;">
        <b>Le Directeur</b>
    </div>
</div>

@endsection
