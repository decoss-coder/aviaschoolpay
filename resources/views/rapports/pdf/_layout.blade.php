<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>@yield('titre')</title>
    <style>
        @page { margin: 25px 25px 35px; }
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #1f2937; font-size: 10px; line-height: 1.4; }
        h1 { font-size: 18px; margin: 0; color: #0f172a; }
        h2 { font-size: 13px; margin: 14px 0 6px; color: #0f172a; padding-bottom: 4px; border-bottom: 2px solid #0a7b3f; }
        h3 { font-size: 11px; margin: 10px 0 4px; color: #374151; text-transform: uppercase; letter-spacing: 0.5px; }

        .header { border-bottom: 2px solid #0a7b3f; padding-bottom: 10px; margin-bottom: 16px; }
        .header table { width: 100%; }
        .header .etab-nom { font-size: 14px; font-weight: bold; color: #0a7b3f; }
        .header .sub { font-size: 9px; color: #64748b; }
        .header .titre-rapport { font-size: 16px; font-weight: bold; color: #0f172a; text-align: right; }
        .header .periode { font-size: 9px; color: #64748b; text-align: right; }

        .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 8px; font-weight: bold; }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-warn { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-info { background: #dbeafe; color: #1e40af; }

        table.data { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.data th, table.data td { padding: 5px 6px; border-bottom: 1px solid #e5e7eb; text-align: left; font-size: 10px; }
        table.data th { background: #f1f5f9; font-weight: bold; color: #475569; font-size: 9px; text-transform: uppercase; }
        table.data tr:nth-child(even) td { background: #fafafa; }
        table.data .right { text-align: right; }
        table.data .center { text-align: center; }
        table.data .mono { font-family: DejaVu Sans Mono, monospace; font-size: 9px; }
        table.data tfoot td { font-weight: bold; background: #e2e8f0; color: #0f172a; border-top: 2px solid #0a7b3f; }

        .kpi-grid { width: 100%; margin: 8px 0 14px; }
        .kpi { display: inline-block; vertical-align: top; width: 23%; margin-right: 1%; padding: 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; }
        .kpi .lbl { font-size: 8px; font-weight: bold; color: #64748b; text-transform: uppercase; }
        .kpi .val { font-size: 14px; font-weight: bold; color: #0f172a; margin-top: 3px; }
        .kpi .sub { font-size: 8px; color: #94a3b8; margin-top: 1px; }
        .kpi-success { border-left: 3px solid #10b981; }
        .kpi-danger { border-left: 3px solid #ef4444; }
        .kpi-info { border-left: 3px solid #3b82f6; }
        .kpi-warn { border-left: 3px solid #f59e0b; }

        .result-box { padding: 12px 16px; border-radius: 8px; margin: 14px 0; text-align: center; }
        .result-box.positive { background: #ecfdf5; border: 2px solid #10b981; }
        .result-box.negative { background: #fef2f2; border: 2px solid #ef4444; }
        .result-box .lbl { font-size: 9px; font-weight: bold; text-transform: uppercase; }
        .result-box .val { font-size: 20px; font-weight: bold; margin-top: 4px; }
        .result-box.positive .lbl, .result-box.positive .val { color: #065f46; }
        .result-box.negative .lbl, .result-box.negative .val { color: #991b1b; }

        .footer { position: fixed; bottom: -25px; left: 0; right: 0; font-size: 8px; color: #94a3b8; text-align: center; border-top: 1px solid #e5e7eb; padding-top: 6px; }
        .footer .page::after { content: counter(page); }
        .text-positive { color: #047857; font-weight: bold; }
        .text-negative { color: #b91c1c; font-weight: bold; }
        .text-muted { color: #94a3b8; }
        .small { font-size: 9px; }
    </style>
</head>
<body>

<div class="header">
    <table>
        <tr>
            <td style="width:60%;">
                <div class="etab-nom">{{ $etablissement?->nom ?? 'Établissement' }}</div>
                @if($etablissement?->adresse)
                    <div class="sub">{{ $etablissement->adresse }}</div>
                @endif
                @if($etablissement?->telephone)
                    <div class="sub">Tél : {{ $etablissement->telephone }}{{ $etablissement->email ? ' · '.$etablissement->email : '' }}</div>
                @endif
            </td>
            <td style="width:40%;">
                <div class="titre-rapport">@yield('titre')</div>
                <div class="periode">@yield('periode')</div>
                <div class="sub" style="text-align:right; margin-top:4px;">Généré le {{ now()->format('d/m/Y à H:i') }}</div>
            </td>
        </tr>
    </table>
</div>

@yield('content')

<div class="footer">
    {{ $etablissement?->nom ?? 'AviaSchoolPay' }} · Document généré automatiquement · Page <span class="page"></span>
</div>

</body>
</html>
