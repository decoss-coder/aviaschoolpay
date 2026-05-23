<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Moyennes — {{ $classe->nom ?? '' }}</title>
    <style>
        @page { margin: 14mm; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #1e293b; margin: 0; }
        .header { text-align: center; margin-bottom: 14px; padding-bottom: 8px; border-bottom: 2px solid #10b981; }
        .header h1 { font-size: 14pt; margin: 0; color: #10b981; }
        .header .sub { font-size: 9pt; color: #64748b; margin-top: 4px; }
        .meta { display: table; width: 100%; margin-bottom: 12px; font-size: 9pt; }
        .meta-cell { display: table-cell; padding: 4px 8px; }
        .meta-label { font-weight: 700; color: #64748b; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; font-size: 10pt; }
        thead th { background: #d1fae5; color: #064e3b; font-weight: 700; text-align: left; }
        .col-rank { width: 6%; text-align: center; }
        .col-eleve { width: 38%; }
        .col-dsps { width: 16%; font-family: monospace; font-size: 9pt; }
        .col-moy { width: 12%; text-align: center; font-weight: 800; font-size: 11pt; }
        .col-apprec { width: 28%; font-size: 9pt; }
        .moy-good { color: #059669; }
        .moy-ok { color: #2563eb; }
        .moy-warn { color: #d97706; }
        .moy-bad { color: #dc2626; }
        .empty { color: #94a3b8; }
        .stats {
            margin-top: 12px;
            padding: 8px;
            background: #f0fdf4;
            border-left: 3px solid #10b981;
            font-size: 9.5pt;
        }
        .footer { margin-top: 18px; font-size: 8pt; color: #64748b; }
        .signature { margin-top: 30px; text-align: right; font-size: 9pt; }
    </style>
</head>
<body>
    <div class="header">
        <h1>MOYENNES PAR MATIÈRE</h1>
        <div class="sub">{{ $etab->nom ?? '' }} · {{ $annee->libelle ?? '' }}</div>
    </div>

    <div class="meta">
        <div class="meta-cell"><span class="meta-label">Classe :</span> {{ $classe->nom }}</div>
        <div class="meta-cell"><span class="meta-label">Matière :</span> {{ $matiere->nom }} ({{ $matiere->code }})</div>
        <div class="meta-cell"><span class="meta-label">Trimestre :</span> {{ $trimestre->libelle ?? 'T'.$trimestre->numero }}</div>
        <div class="meta-cell"><span class="meta-label">Professeur :</span> {{ $enseignant->prenom }} {{ strtoupper($enseignant->nom) }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="col-rank">N°</th>
                <th class="col-eleve">Nom &amp; prénom</th>
                <th class="col-dsps">Matricule DSPS</th>
                <th class="col-moy">Moyenne /20</th>
                <th class="col-apprec">Appréciation</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $idx => $row)
                @php
                    $moy = $row['moyenne'] ?? null;
                    $cls = $moy === null ? 'empty'
                        : ($moy >= 14 ? 'moy-good'
                        : ($moy >= 10 ? 'moy-ok'
                        : ($moy >= 8 ? 'moy-warn' : 'moy-bad')));
                @endphp
                <tr>
                    <td class="col-rank">{{ $idx + 1 }}</td>
                    <td class="col-eleve"><strong>{{ $row['eleve']['prenom'] }} {{ strtoupper($row['eleve']['nom']) }}</strong></td>
                    <td class="col-dsps">{{ $row['eleve']['matricule_desps'] ?? '—' }}</td>
                    <td class="col-moy {{ $cls }}">
                        {{ $moy !== null ? number_format($moy, 2, '.', '') : '—' }}
                    </td>
                    <td class="col-apprec">{{ $row['appreciation'] ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @php
        $moyennes = collect($rows)->pluck('moyenne')->filter(fn ($m) => $m !== null);
        $moyClasse = $moyennes->isEmpty() ? null : $moyennes->avg();
        $max = $moyennes->isEmpty() ? null : $moyennes->max();
        $min = $moyennes->isEmpty() ? null : $moyennes->min();
    @endphp
    @if ($moyClasse !== null)
        <div class="stats">
            <strong>Statistiques :</strong>
            Moyenne classe : <strong>{{ number_format($moyClasse, 2, '.', '') }}</strong> /20 ·
            Max : <strong>{{ number_format($max, 2, '.', '') }}</strong> ·
            Min : <strong>{{ number_format($min, 2, '.', '') }}</strong> ·
            Saisis : {{ $moyennes->count() }}/{{ count($rows) }}
        </div>
    @endif

    <div class="footer">
        Document généré le {{ now()->locale('fr')->translatedFormat('d F Y à H:i') }} —
        {{ count($rows) }} élève(s)
    </div>

    <div class="signature">
        Signature du professeur :
        <span style="display:inline-block; border-bottom: 1px solid #1e293b; min-width: 200px;">&nbsp;</span>
    </div>
</body>
</html>
