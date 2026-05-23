<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Grille de notes — {{ $classe->nom ?? '' }}</title>
    <style>
        @page { margin: 12mm; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #1e293b; margin: 0; }
        .header { text-align: center; margin-bottom: 14px; padding-bottom: 8px; border-bottom: 2px solid #6366f1; }
        .header h1 { font-size: 14pt; margin: 0; color: #6366f1; }
        .header .sub { font-size: 9pt; color: #64748b; margin-top: 4px; }
        .meta { display: table; width: 100%; margin-bottom: 10px; font-size: 9pt; }
        .meta-cell { display: table-cell; padding: 4px 8px; }
        .meta-label { font-weight: 700; color: #64748b; }
        table.grid { width: 100%; border-collapse: collapse; }
        table.grid th, table.grid td {
            border: 1px solid #cbd5e1;
            padding: 4px 6px;
            text-align: center;
            font-size: 8.5pt;
            vertical-align: middle;
        }
        table.grid thead th {
            background: #eef2ff;
            color: #1e293b;
            font-weight: 700;
        }
        .col-rank { width: 5%; }
        .col-eleve { width: 25%; text-align: left !important; }
        .col-eval { width: auto; }
        .col-moy { width: 8%; background: #fef3c7; font-weight: 700; }
        .col-moy-h { background: #fcd34d; }
        .eleve-name { font-weight: 600; font-size: 9pt; }
        .eleve-dsps { font-size: 7pt; color: #64748b; font-family: monospace; }
        .ab { color: #dc2626; font-weight: 700; font-size: 8pt; }
        .empty { color: #94a3b8; }
        .footer { margin-top: 14px; font-size: 8pt; color: #64748b; }
        .signature { margin-top: 24px; text-align: right; font-size: 9pt; }
    </style>
</head>
<body>
    <div class="header">
        <h1>GRILLE DE NOTES</h1>
        <div class="sub">{{ $etab->nom ?? '' }} · {{ $annee->libelle ?? '' }}</div>
    </div>

    <div class="meta">
        <div class="meta-cell"><span class="meta-label">Classe :</span> {{ $classe->nom }}</div>
        <div class="meta-cell"><span class="meta-label">Matière :</span> {{ $matiere->nom }} ({{ $matiere->code }})</div>
        <div class="meta-cell"><span class="meta-label">Trimestre :</span> {{ $trimestre->libelle ?? 'T'.$trimestre->numero }}</div>
        <div class="meta-cell"><span class="meta-label">Professeur :</span> {{ $enseignant->prenom }} {{ strtoupper($enseignant->nom) }}</div>
    </div>

    <table class="grid">
        <thead>
            <tr>
                <th class="col-rank">N°</th>
                <th class="col-eleve">Élève (Nom — DSPS)</th>
                @foreach ($evaluations as $ev)
                    <th class="col-eval">
                        {{ $ev->titre }}<br>
                        <span style="font-weight:400;font-size:7.5pt;">
                            /{{ (float) $ev->note_sur }}{{ $ev->coefficient != 1 ? ' × '.$ev->coefficient : '' }}
                        </span>
                    </th>
                @endforeach
                <th class="col-moy col-moy-h">Moy.<br><span style="font-weight:400;font-size:7.5pt;">/20</span></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $idx => $row)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td style="text-align:left;">
                        <span class="eleve-name">{{ $row['eleve']['prenom'] }} {{ strtoupper($row['eleve']['nom']) }}</span>
                        @if (!empty($row['eleve']['matricule_desps']))
                            <br><span class="eleve-dsps">DSPS : {{ $row['eleve']['matricule_desps'] }}</span>
                        @endif
                    </td>
                    @foreach ($evaluations as $ev)
                        @php
                            $cell = $row['cells'][$ev->id] ?? null;
                        @endphp
                        <td>
                            @if ($cell && $cell['absent'])
                                <span class="ab">ABS</span>
                            @elseif ($cell && $cell['note'] !== null)
                                {{ rtrim(rtrim(number_format($cell['note'], 2, '.', ''), '0'), '.') }}
                            @else
                                <span class="empty">—</span>
                            @endif
                        </td>
                    @endforeach
                    <td class="col-moy">
                        @if ($row['moyenne'] !== null)
                            {{ number_format($row['moyenne'], 2, '.', '') }}
                        @else
                            <span class="empty">—</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Document généré le {{ now()->locale('fr')->translatedFormat('d F Y à H:i') }} —
        {{ count($rows) }} élève(s) · {{ count($evaluations) }} évaluation(s)
    </div>

    <div class="signature">
        Signature du professeur :
        <span style="display:inline-block; border-bottom: 1px solid #1e293b; min-width: 200px;">&nbsp;</span>
    </div>
</body>
</html>
