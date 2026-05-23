{{-- resources/views/emploi-du-temps/pdf/classes.blade.php --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        @page {
            size: A4 portrait;
            margin: 8mm 7mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #111;
            margin: 0;
            padding: 0;
        }

        .page {
            page-break-after: always;
        }

        .page:last-child {
            page-break-after: auto;
        }

        .doc {
            width: 100%;
        }

        .top-head,
        .school-row,
        .meta-table,
        .grid,
        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }

        .top-head td,
        .school-row td,
        .meta-table td {
            vertical-align: top;
        }

        .top-left {
            font-size: 9px;
            font-weight: 700;
            line-height: 1.35;
            text-transform: uppercase;
        }

        .top-right {
            font-size: 9px;
            font-weight: 700;
            line-height: 1.35;
            text-align: right;
            text-transform: uppercase;
        }

        .motto {
            font-size: 8px;
            font-style: italic;
            font-weight: 400;
            text-transform: none;
        }

        .school-left {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .school-sub {
            font-size: 9px;
            font-weight: 400;
        }

        .school-right {
            font-size: 12px;
            font-weight: 800;
            text-align: right;
        }

        .title {
            text-align: center;
            font-size: 18px;
            font-weight: 800;
            text-transform: uppercase;
            margin: 8px 0 10px;
        }

        .meta-box {
            border: 1px solid #444;
            border-radius: 8px;
            padding: 9px 11px;
            margin-bottom: 9px;
        }

        .meta-table td {
            font-size: 10px;
            padding: 4px 5px;
        }

        .meta-label {
            color: #555;
            font-weight: 700;
            text-transform: uppercase;
            width: 125px;
            white-space: nowrap;
        }

        .meta-value {
            font-weight: 800;
            color: #111;
        }

        .grid {
            table-layout: fixed;
        }

        .grid th,
        .grid td {
            border: 1px solid #555;
            text-align: center;
            vertical-align: middle;
            padding: 4px 5px;
        }

        .grid thead th {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            background: #efefef;
        }

        .time-col {
            width: 72px;
            font-size: 9px;
            font-weight: 700;
            line-height: 1.2;
        }

        .slot-cell {
            height: 48px;
            font-size: 9px;
            line-height: 1.15;
        }

        .slot-main {
            font-size: 12px;
            font-weight: 800;
            color: #111;
            line-height: 1.1;
        }

        .slot-room {
            font-size: 8px;
            color: #555;
            text-transform: uppercase;
            line-height: 1.1;
            margin-top: 3px;
        }

        .special-row {
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 3px;
            height: 20px;
        }

        .special-row-recreation td {
            background-color: #fff3e0;
            color: #b45309;
        }

        .special-row-pause td {
            background-color: #fef9c3;
            color: #92400e;
        }

        .special-row-time {
            font-size: 7.5px;
            font-weight: 700;
            line-height: 1.2;
        }

        .section-title {
            text-align: center;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #444;
            margin: 9px 0 6px;
        }

        .footer-table {
            margin-top: 7px;
            table-layout: fixed;
        }

        .footer-table th,
        .footer-table td {
            border: 1px solid #555;
            padding: 4px 5px;
            text-align: center;
            vertical-align: middle;
            font-size: 9px;
        }

        .footer-table th {
            background: #efefef;
            font-weight: 800;
            text-transform: uppercase;
        }

        .left {
            text-align: left !important;
        }

        .muted {
            color: #666;
        }
    </style>
</head>
<body>
@foreach($documents as $doc)
    <div class="page">
        <div class="doc">

            <table class="top-head">
                <tr>
                    <td class="top-left">
                        MINISTERE DE L'EDUCATION NATIONALE, DE<br>
                        L'ALPHABETISATION ET DE L'ENSEIGNEMENT TECHNIQUE<br><br>
                        DIRECTION REGIONALE DE MANKONO
                    </td>
                    <td class="top-right">
                        REPUBLIQUE DE COTE D'IVOIRE<br>
                        <span class="motto">Union - Discipline - Travail</span>
                    </td>
                </tr>
            </table>

            <table class="school-row">
                <tr>
                    <td>
                        <div class="school-left">
                            {{ strtoupper($etablissement->nom ?? 'ETABLISSEMENT') }}
                        </div>
                        <div class="school-sub">
                            @if(!empty($etablissement->telephone))
                                - Tél. : {{ $etablissement->telephone }}
                            @endif
                        </div>
                    </td>
                    <td class="school-right">
                        Année scolaire {{ $annee->libelle ?? '' }}
                    </td>
                </tr>
            </table>

            <div class="title">Emploi du Temps Classe</div>

            <div class="meta-box">
                <table class="meta-table">
                    <tr>
                        <td class="meta-label">Classe</td>
                        <td class="meta-value">{{ $doc['classe']->nom ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Professeur principal</td>
                        <td>{{ $doc['professeur_principal'] ?: '—' }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Educateur</td>
                        <td>{{ $doc['educateur'] ?: '—' }}</td>
                    </tr>
                </table>
            </div>

            <table class="grid">
                <thead>
                    <tr>
                        <th class="time-col">Horaires</th>
                        @foreach($doc['jours'] as $jour)
                            <th>{{ strtoupper($jour) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($doc['creneaux'] as $creneau)
                        @php
                            $type = $creneau->type ?? 'cours';
                            $debut = $creneau->heure_debut ? \Carbon\Carbon::parse($creneau->heure_debut)->format('H:i') : '';
                            $fin = $creneau->heure_fin ? \Carbon\Carbon::parse($creneau->heure_fin)->format('H:i') : '';
                        @endphp

                        @if($type === 'recreation')
                            <tr class="special-row-recreation">
                                <td class="time-col special-row-time">{{ $debut }}<br>{{ $fin }}</td>
                                <td colspan="{{ count($doc['jours']) }}" class="special-row">
                                    R &nbsp; É &nbsp; C &nbsp; R &nbsp; É &nbsp; A &nbsp; T &nbsp; I &nbsp; O &nbsp; N
                                </td>
                            </tr>
                        @elseif($type === 'pause_dejeuner')
                            <tr class="special-row-pause">
                                <td class="time-col special-row-time">{{ $debut }}<br>{{ $fin }}</td>
                                <td colspan="{{ count($doc['jours']) }}" class="special-row">
                                    P &nbsp; A &nbsp; U &nbsp; S &nbsp; E &nbsp;&nbsp;&nbsp; M &nbsp; I &nbsp; - &nbsp; J &nbsp; O &nbsp; U &nbsp; R &nbsp; N &nbsp; É &nbsp; E
                                </td>
                            </tr>
                        @else
                            <tr>
                                <td class="time-col">{{ $debut }} - {{ $fin }}</td>
                                @foreach($doc['jours'] as $jour)
                                    @php $items = $doc['grid'][$jour][$creneau->id] ?? collect(); @endphp
                                    <td class="slot-cell">
                                        @if($items->count())
                                            @foreach($items as $item)
                                                <div class="slot-main">
                                                    {{ $item->matiere->code ?? '—' }}
                                                </div>
                                                <div class="slot-room">
                                                    {{ $item->salle->nom ?? '' }}
                                                </div>
                                            @endforeach
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>

            <div class="section-title">Récapitulatif</div>

            <table class="footer-table">
                <thead>
                    <tr>
                        <th style="width: 42%;">Matière</th>
                        <th>Professeur</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($doc['matiere_professeurs'] as $row)
                        <tr>
                            <td class="left">{{ $row['matiere'] }}</td>
                            <td class="left">{{ $row['enseignant'] ?: 'Non affecté' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="muted">Aucune donnée</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

        </div>
    </div>
@endforeach
</body>
</html>