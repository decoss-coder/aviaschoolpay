<!DOCTYPE html>
<html lang="fr">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        @page {
            size: A4 portrait;
            margin: 7mm 6mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
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

        .header-table,
        .meta-table,
        .timetable,
        .recap-main,
        .service-caps {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: top;
        }

        .left-small {
            font-size: 7.8px;
            font-weight: 700;
            line-height: 1.3;
            text-transform: uppercase;
        }

        .right-small {
            font-size: 7.8px;
            text-align: right;
            font-weight: 700;
            line-height: 1.3;
            text-transform: uppercase;
        }

        .motto {
            font-size: 7px;
            font-style: italic;
            font-weight: 400;
            text-transform: none;
        }

        .school-line {
            margin-top: 8px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .school-sub {
            font-size: 7px;
        }

        .year-line {
            text-align: right;
            font-size: 10px;
            font-weight: 800;
            margin-top: 6px;
        }

        .title {
            text-align: center;
            font-size: 15px;
            font-weight: 800;
            margin: 7px 0 7px;
            text-transform: uppercase;
        }

        .meta {
            border: 1px solid #333;
            border-radius: 7px;
            padding: 7px 8px;
            margin-bottom: 7px;
        }

        .meta-table td {
            padding: 3px 4px;
            font-size: 8px;
            vertical-align: top;
        }

        .meta-label {
            width: 78px;
            font-weight: 700;
            text-transform: uppercase;
            color: #555;
            white-space: nowrap;
        }

        .meta-value {
            font-weight: 800;
            color: #111;
        }

        .timetable {
            table-layout: fixed;
            margin-bottom: 7px;
        }

        .timetable th,
        .timetable td,
        .recap-main th,
        .recap-main td,
        .service-caps th,
        .service-caps td {
            border: 1px solid #222;
            padding: 3px 4px;
            text-align: center;
            vertical-align: middle;
        }

        .timetable th {
            background: #efefef;
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .time-col {
            width: 60px;
            font-size: 8px;
            font-weight: 700;
            line-height: 1.15;
        }

        .slot-cell {
            height: 34px;
            font-size: 8px;
            line-height: 1.08;
        }

        .slot-main {
            font-size: 10px;
            font-weight: 800;
            line-height: 1.05;
        }

        .slot-sub {
            font-size: 8px;
            font-weight: 700;
            line-height: 1.05;
        }

        .slot-room {
            font-size: 7px;
            color: #555;
            line-height: 1.05;
            text-transform: uppercase;
        }

        .special-row {
            font-size: 8px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 3px;
            height: 16px;
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

        .recap-title {
            text-align: center;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            margin: 6px 0 5px;
        }

        .recap-main {
            table-layout: fixed;
        }

        .recap-main th,
        .recap-main td {
            font-size: 7.4px;
        }

        .recap-main .label-col {
            width: 132px;
            text-align: left;
            font-weight: 700;
            text-transform: uppercase;
        }

        .recap-main .class-col {
            width: 42px;
            font-weight: 700;
        }

        .recap-main .summary-col {
            width: 88px;
            text-align: left;
            font-weight: 700;
            padding-left: 6px;
        }

        .recap-main .big-value {
            font-size: 10px;
            font-weight: 800;
        }

        .caps-wrap {
            margin-top: 7px;
            display: table;
            width: 100%;
        }

        .caps-left,
        .caps-right {
            display: table-cell;
            vertical-align: bottom;
        }

        .caps-right {
            text-align: right;
            font-size: 7.5px;
            font-style: italic;
        }

        .service-caps {
            width: 74%;
        }

        .service-caps th {
            background: #efefef;
            font-size: 7.5px;
            font-weight: 800;
        }

        .service-caps td {
            font-size: 7.5px;
            font-weight: 700;
        }

        .left {
            text-align: left !important;
        }
    </style>
</head>
<body>
@foreach($documents as $doc)
    <div class="page">
        <div class="doc">

            <table class="header-table">
                <tr>
                    <td class="left-small">
                        MINISTERE DE L'EDUCATION NATIONALE, DE<br>
                        L'ALPHABETISATION ET DE L'ENSEIGNEMENT TECHNIQUE<br><br>
                        DIRECTION REGIONALE DE MANKONO
                    </td>
                    <td class="right-small">
                        REPUBLIQUE DE COTE D'IVOIRE<br>
                        <span class="motto">Union - Discipline - Travail</span>
                    </td>
                </tr>
            </table>

            <div style="display:flex; justify-content:space-between;">
                <div>
                    <div class="school-line">{{ strtoupper($etablissement->nom ?? 'ETABLISSEMENT') }}</div>
                    <div class="school-sub">- Tél. : {{ $etablissement->telephone ?? '—' }}</div>
                </div>
                <div class="year-line">Année scolaire {{ $annee->libelle ?? '' }}</div>
            </div>

            <div class="title">EMPLOI DU TEMPS PROFESSEUR</div>

            <div class="meta">
                <table class="meta-table">
                    <tr>
                        <td class="meta-label">Professeur</td>
                        <td class="meta-value">{{ strtoupper(($doc['enseignant']->nom ?? '') . ' ' . ($doc['enseignant']->prenom ?? '')) }}</td>
                        <td class="meta-label">Matricule</td>
                        <td class="meta-value">{{ $doc['matricule'] }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Corps</td>
                        <td class="meta-value">{{ strtoupper($doc['corps']) }}</td>
                        <td class="meta-label">Sexe</td>
                        <td class="meta-value">{{ $doc['enseignant']->sexe ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Discipline</td>
                        <td class="meta-value">{{ $doc['discipline'] }}</td>
                        <td class="meta-label">Contact</td>
                        <td class="meta-value">{{ $doc['enseignant']->telephone ?? '—' }}</td>
                    </tr>
                </table>
            </div>

            <table class="timetable">
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
                                    @php $item = $doc['grid'][$jour][$creneau->id] ?? null; @endphp
                                    <td class="slot-cell">
                                        @if($item)
                                            <div class="slot-main">{{ $item->matiere->code ?? '—' }}</div>
                                            <div class="slot-sub">{{ $item->classe->nom ?? '' }}</div>
                                            <div class="slot-room">{{ $item->salle->nom ?? '' }}</div>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>

            <div class="recap-title">TABLEAU RECAPITULATIF</div>

            @php
                $slots = $doc['recap']['slots'] ?? [];
                $dechargeLabels = $doc['recap']['decharges_labels'] ?? [];
                $dechargeValues = $doc['recap']['decharges_values'] ?? [];
            @endphp

            <table class="recap-main">
                <tr>
                    <th class="label-col">CLASSES</th>
                    @foreach($slots as $slot)
                        <th class="class-col">{{ $slot['classe'] }}</th>
                    @endforeach
                    <td class="summary-col" rowspan="4">
                        <span class="big-value">A = {{ $doc['recap']['total_a'] }}</span>
                    </td>
                </tr>

                <tr>
                    <td class="label-col">EFFECTIFS</td>
                    @foreach($slots as $slot)
                        <td>{{ $slot['effectif'] }}</td>
                    @endforeach
                </tr>

                <tr>
                    <td class="label-col">DISCIPLINES</td>
                    @foreach($slots as $slot)
                        <td style="font-weight:800;">{{ $slot['discipline'] }}</td>
                    @endforeach
                </tr>

                <tr>
                    <td class="label-col">NBRE D'HEURES D'ENSEIGNEMENT (A)</td>
                    @foreach($slots as $slot)
                        <td style="font-weight:800;">{{ $slot['heures_a_label'] }}</td>
                    @endforeach
                </tr>

                <tr>
                    <td class="label-col">COMPLEMENT DE SERVICE (B)</td>
                    <td colspan="{{ count($slots) }}"></td>
                    <td class="summary-col">
                        <span class="big-value">B = {{ $doc['recap']['total_b'] }}</span>
                    </td>
                </tr>

                <tr>
                    <td class="label-col" rowspan="2" style="text-align:center;">DECHARGES (C)</td>
                    @foreach($dechargeLabels as $label)
                        <td>{{ $label }}</td>
                    @endforeach
                    @for($i = count($dechargeLabels); $i < count($slots); $i++)
                        <td></td>
                    @endfor
                    <td class="summary-col" rowspan="2">
                        <span class="big-value">C = {{ $doc['recap']['total_c'] }}</span>
                    </td>
                </tr>

                <tr>
                    @foreach($dechargeValues as $value)
                        <td>{{ $value }}</td>
                    @endforeach
                    @for($i = count($dechargeValues); $i < count($slots); $i++)
                        <td></td>
                    @endfor
                </tr>

                <tr>
                    <td class="label-col">AUGMENTATION DE SERVICE (moins de 20 élèves) (D)</td>
                    <td colspan="{{ count($slots) }}"></td>
                    <td class="summary-col">
                        <span class="big-value">D = {{ $doc['recap']['total_d'] }}</span>
                    </td>
                </tr>

                <tr>
                    <td colspan="{{ count($slots) + 1 }}" class="left">TOTAL : T = A + B + C - D</td>
                    <td class="summary-col"><span class="big-value">{{ $doc['recap']['service_total'] }}</span></td>
                </tr>

                <tr>
                    <td colspan="{{ count($slots) + 1 }}" class="left">Maximum de service : (15h - 18h - 21h - 25)</td>
                    <td class="summary-col"><span class="big-value">Max. = {{ $doc['recap']['max_service'] }}</span></td>
                </tr>

                <tr>
                    <td colspan="{{ count($slots) + 1 }}" class="left">Heures supplémentaires : =T - Max</td>
                    <td class="summary-col"><span class="big-value">{{ $doc['recap']['heures_sup'] }}</span></td>
                </tr>
            </table>

            <div class="caps-wrap">
                <div class="caps-left">
                    <table class="service-caps">
                        <tr>
                            <th style="width:32%;">Vacataires</th>
                            <th>Prof.Agr</th>
                            <th>PL</th>
                            <th>PC</th>
                            <th style="width:18%;">Permanents</th>
                        </tr>
                        <tr>
                            <td style="font-weight:800;">*max de service</td>
                            <td>{{ $doc['recap']['vacataires']['Prof.Agr'] }}</td>
                            <td>{{ $doc['recap']['vacataires']['PL'] }}</td>
                            <td>{{ $doc['recap']['vacataires']['PC'] }}</td>
                            <td>{{ $doc['recap']['permanents'] }}</td>
                        </tr>
                    </table>
                </div>

                <div class="caps-right">
                    Fait à {{ $doc['recap']['signature_place'] }}, le {{ $doc['recap']['signature_date'] }}<br>
                    Cachet, signature, Nom et Prénom(s) du DE
                </div>
            </div>
        </div>
    </div>
@endforeach
</body>
</html>