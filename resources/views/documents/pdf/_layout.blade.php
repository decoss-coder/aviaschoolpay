<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>@yield('titre')</title>
    <style>
        @page { margin: 20px 20px 30px; }
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #1f2937; font-size: 10px; line-height: 1.4; }

        .header { border-bottom: 2px solid #0a7b3f; padding-bottom: 8px; margin-bottom: 12px; }
        .header table { width: 100%; }
        .etab-nom { font-size: 13px; font-weight: bold; color: #0a7b3f; }
        .sub { font-size: 9px; color: #64748b; }
        .titre-doc { font-size: 15px; font-weight: bold; color: #0f172a; text-align: right; text-transform: uppercase; }
        .periode-doc { font-size: 9px; color: #475569; text-align: right; margin-top: 3px; }

        h2 { font-size: 12px; margin: 12px 0 5px; color: #0f172a; padding-bottom: 3px; border-bottom: 2px solid #0a7b3f; }

        table.data { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        table.data th, table.data td { padding: 4px 6px; border-bottom: 1px solid #e5e7eb; text-align: left; font-size: 9px; }
        table.data th { background: #0a7b3f; color: #fff; font-weight: bold; font-size: 9px; text-transform: uppercase; }
        table.data tr:nth-child(even) td { background: #f8fafc; }
        table.data .right { text-align: right; }
        table.data .center { text-align: center; }
        table.data .mono { font-family: DejaVu Sans Mono, monospace; font-size: 8px; }
        table.data tfoot td { font-weight: bold; background: #d1fae5; color: #065f46; border-top: 2px solid #0a7b3f; }

        .kpi { display: inline-block; vertical-align: top; width: 23%; margin-right: 1%; padding: 8px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 5px; }
        .kpi .lbl { font-size: 8px; font-weight: bold; color: #64748b; text-transform: uppercase; }
        .kpi .val { font-size: 14px; font-weight: bold; color: #0f172a; margin-top: 2px; }
        .kpi-success { border-left: 3px solid #10b981; }
        .kpi-danger { border-left: 3px solid #ef4444; }
        .kpi-info { border-left: 3px solid #3b82f6; }
        .kpi-warn { border-left: 3px solid #f59e0b; }

        .badge { display: inline-block; padding: 1px 6px; border-radius: 8px; font-size: 8px; font-weight: bold; }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-warn { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-info { background: #dbeafe; color: #1e40af; }

        .text-positive { color: #047857; font-weight: bold; }
        .text-negative { color: #b91c1c; font-weight: bold; }
        .text-muted { color: #94a3b8; }

        .footer { position: fixed; bottom: -20px; left: 0; right: 0; font-size: 8px; color: #94a3b8; text-align: center; border-top: 1px solid #e5e7eb; padding-top: 5px; }
        .footer .page::after { content: counter(page); }
    </style>
</head>
<body>

<div class="header">
    <table>
        <tr>
            <td style="width:60%;">
                <div class="etab-nom">{{ $etab?->nom ?? 'Établissement' }}</div>
                @if($etab?->adresse)<div class="sub">{{ $etab->adresse }}{{ $etab->ville ? ' · '.$etab->ville : '' }}</div>@endif
                @if($etab?->telephone)<div class="sub">Tél : {{ $etab->telephone }}{{ $etab->email ? ' · '.$etab->email : '' }}</div>@endif
            </td>
            <td style="width:40%;">
                <div class="titre-doc">@yield('titre')</div>
                <div class="periode-doc">@yield('periode')</div>
                <div class="sub" style="text-align:right; margin-top:3px;">Édité le {{ now()->format('d/m/Y H:i') }}</div>
            </td>
        </tr>
    </table>
</div>

@yield('content')

<div class="footer">
    {{ $etab?->nom ?? 'AviaSchoolPay' }} · Document officiel · Page <span class="page"></span>
</div>

</body>
</html>
