<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fournitures — {{ $liste->classe?->nom }}</title>
    <style>
        @page { margin: 20px 25px 30px; }
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #1f2937; font-size: 11px; line-height: 1.5; }

        .header { border-bottom: 2px solid #0a7b3f; padding-bottom: 10px; margin-bottom: 14px; }
        .header table { width: 100%; }
        .etab-nom { font-size: 14px; font-weight: bold; color: #0a7b3f; }
        .sub { font-size: 9px; color: #64748b; }
        .titre-doc { font-size: 17px; font-weight: bold; color: #0f172a; text-align: right; text-transform: uppercase; }

        h1.cls {
            text-align: center;
            font-size: 22px;
            color: #0a7b3f;
            margin: 14px 0 6px;
            padding: 8px;
            border-top: 2px solid #0a7b3f;
            border-bottom: 2px solid #0a7b3f;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .annee { text-align: center; font-size: 10px; color: #94a3b8; font-style: italic; margin-bottom: 14px; }

        h2 {
            font-size: 11px; color: #fff; background: #0a7b3f;
            padding: 5px 10px; margin: 12px 0 4px;
            border-radius: 4px;
            text-transform: uppercase; letter-spacing: 1px;
        }

        table.items { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        table.items th { background: #f1f5f9; padding: 5px 8px; font-size: 9px; text-transform: uppercase; color: #475569; text-align: left; }
        table.items td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; font-size: 10px; vertical-align: top; }
        table.items tr:nth-child(even) td { background: #fafafa; }

        .checkbox { display: inline-block; width: 11px; height: 11px; border: 1.5px solid #475569; border-radius: 2px; vertical-align: middle; margin-right: 4px; }

        .badge-oblig { display: inline-block; background: #fee2e2; color: #991b1b; padding: 1px 5px; border-radius: 8px; font-size: 7px; font-weight: bold; text-transform: uppercase; }
        .badge-facult { display: inline-block; background: #f1f5f9; color: #64748b; padding: 1px 5px; border-radius: 8px; font-size: 7px; font-weight: bold; text-transform: uppercase; }

        .notes-bloc {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 5px;
            padding: 10px 14px;
            margin: 10px 0;
            font-size: 10px;
            color: #92400e;
        }

        .footer { position: fixed; bottom: -20px; left: 0; right: 0; font-size: 8px; color: #94a3b8; text-align: center; border-top: 1px solid #e5e7eb; padding-top: 5px; }
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
                <div class="titre-doc">Fournitures scolaires</div>
                <div class="sub" style="text-align:right;">Édité le {{ now()->format('d/m/Y') }}</div>
            </td>
        </tr>
    </table>
</div>

<h1 class="cls">{{ $liste->classe?->nom }}</h1>
<div class="annee">Année scolaire {{ $liste->anneeScolaire?->libelle }}</div>

@if($liste->notes)
<div class="notes-bloc">
    <b>📌 Notes :</b><br>
    {{ $liste->notes }}
</div>
@endif

@if($liste->items->isEmpty())
    <p style="text-align:center;color:#94a3b8;padding:30px;">Aucune fourniture définie.</p>
@else

@php $parCat = $liste->items->groupBy(fn($i) => $i->categorie ?: 'Autres'); @endphp

@foreach($parCat as $cat => $items)
<h2>{{ $cat }} ({{ $items->count() }})</h2>
<table class="items">
    <thead>
        <tr>
            <th style="width:5%;" class="center">✓</th>
            <th style="width:7%;" class="center">Qté</th>
            <th>Fourniture</th>
            <th style="width:14%;">Unité</th>
            <th style="width:16%;">Marque suggérée</th>
            <th style="width:10%;" class="center">Statut</th>
        </tr>
    </thead>
    <tbody>
        @foreach($items as $item)
            <tr>
                <td style="text-align:center;"><span class="checkbox"></span></td>
                <td style="text-align:center;font-weight:bold;color:#0a7b3f;font-size:12px;">{{ $item->quantite }}</td>
                <td>
                    <b>{{ $item->libelle }}</b>
                    @if($item->observations)<br><span style="font-size:8px;color:#64748b;font-style:italic;">{{ $item->observations }}</span>@endif
                </td>
                <td style="font-size:9px;">{{ $item->unite ?: 'pièce' }}</td>
                <td style="font-size:9px;color:#475569;">{{ $item->marque_suggeree ?: '—' }}</td>
                <td style="text-align:center;">
                    @if($item->obligatoire)
                        <span class="badge-oblig">Obligatoire</span>
                    @else
                        <span class="badge-facult">Facultatif</span>
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
@endforeach

@endif

<div style="margin-top:14px;padding:8px 12px;background:#dbeafe;border:1px solid #3b82f6;border-radius:4px;font-size:9px;color:#1e3a8a;">
    💡 <b>Conseil pour les parents :</b> Cochez les fournitures au fur et à mesure de vos achats.
    Privilégiez les fournitures durables et de qualité moyenne — elles dureront toute l'année.
</div>

<div style="margin-top:30px;text-align:right;">
    <div style="font-size:9px;color:#475569;">Fait à {{ $etab?->ville ?? '—' }}, le {{ now()->format('d/m/Y') }}</div>
    <div style="margin-top:25px;border-top:1px dashed #94a3b8;padding-top:3px;width:200px;margin-left:auto;text-align:center;font-size:8px;color:#64748b;">
        <b>Le Directeur</b>
    </div>
</div>

<div class="footer">
    Liste de fournitures · {{ $etab?->nom ?? '' }} · {{ $liste->anneeScolaire?->libelle ?? '' }} · AviaSchoolPay
</div>

</body>
</html>
