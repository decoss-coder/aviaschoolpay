<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bulletins {{ $classe->nom }} — {{ $trimestre->libelle }}</title>
    <style>
        @page { margin: 18px 20px 25px; }
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #1f2937; font-size: 10px; line-height: 1.35; }

        .bulletin-page { page-break-after: always; padding: 0; }
        .bulletin-page:last-child { page-break-after: auto; }

        .b-header { border-bottom: 2px solid #0a7b3f; padding-bottom: 6px; margin-bottom: 8px; }
        .b-header table { width: 100%; }
        .etab-nom { font-size: 12px; font-weight: bold; color: #0a7b3f; }
        .titre-doc { font-size: 13px; font-weight: bold; color: #0f172a; text-align: right; text-transform: uppercase; }
        .sub { font-size: 8px; color: #64748b; }

        .identity { background: #f8fafc; border: 1px solid #e2e8f0; padding: 6px 10px; margin: 6px 0; border-radius: 4px; }
        .identity table { width: 100%; }
        .identity .lbl { color: #64748b; font-size: 7px; text-transform: uppercase; font-weight: bold; }
        .identity .val { color: #0f172a; font-weight: bold; font-size: 9px; }
        .nom-eleve { color: #0a7b3f; font-size: 13px; font-weight: bold; text-transform: uppercase; }

        table.notes { width: 100%; border-collapse: collapse; margin: 6px 0; font-size: 8px; }
        table.notes th { background: #0a7b3f; color: #fff; padding: 3px 4px; text-transform: uppercase; font-size: 7px; }
        table.notes td { padding: 3px 4px; border-bottom: 1px solid #e5e7eb; }
        table.notes tr:nth-child(even) td { background: #fafafa; }
        .center { text-align: center; }
        .right { text-align: right; }

        .resultats { width: 100%; border-collapse: collapse; margin: 8px 0; }
        .resultats td { padding: 6px; text-align: center; vertical-align: top; }
        .res-moy { background: linear-gradient(to bottom, #ecfdf5, #d1fae5); border: 2px solid #10b981; border-radius: 4px; }
        .res-rang { background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 4px; }
        .res-lbl { font-size: 7px; color: #064e3b; text-transform: uppercase; font-weight: bold; }
        .res-val { font-size: 18px; font-weight: bold; color: #047857; }

        .signatures { width: 100%; margin-top: 12px; }
        .signatures td { text-align: center; font-size: 8px; color: #64748b; padding: 0 6px; }
        .signatures .line { border-top: 1px dashed #94a3b8; margin-top: 25px; padding-top: 2px; font-size: 7px; }

        .text-positive { color: #047857; font-weight: bold; }
        .text-negative { color: #b91c1c; font-weight: bold; }
        .text-muted { color: #94a3b8; }
    </style>
</head>
<body>

@php
    $fmt = fn($v) => $v !== null ? number_format((float) $v, 2, ',', ' ') : '—';
    $colorNote = fn($v) => $v === null ? '#94a3b8'
        : ($v >= 14 ? '#047857' : ($v >= 10 ? '#0a7b3f' : ($v >= 8 ? '#b45309' : '#b91c1c')));
@endphp

@foreach($bulletins as $b)
<div class="bulletin-page">

    <div class="b-header">
        <table>
            <tr>
                <td style="width:60%;">
                    <div class="etab-nom">{{ $etab?->nom ?? 'Établissement' }}</div>
                    <div class="sub">{{ $etab?->adresse }}{{ $etab?->ville ? ' · '.$etab->ville : '' }} · Tél : {{ $etab?->telephone }}</div>
                </td>
                <td style="width:40%;">
                    <div class="titre-doc">Bulletin de notes</div>
                    <div class="sub" style="text-align:right;">{{ $b['trimestre']->libelle }} · {{ $b['eleve']->classe?->nom }}</div>
                    <div class="sub" style="text-align:right;">Édité le {{ now()->format('d/m/Y') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="identity">
        <table>
            <tr>
                <td style="width:50%;">
                    <div class="lbl">Élève</div>
                    <div class="nom-eleve">{{ $b['eleve']->prenom }} {{ $b['eleve']->nom }}</div>
                    <div class="sub">Mat. {{ $b['eleve']->matricule_interne ?? '—' }} · {{ $b['eleve']->sexe }} · Né(e) {{ $b['eleve']->date_naissance?->format('d/m/Y') ?? '—' }}</div>
                </td>
                <td style="width:25%;">
                    <div class="lbl">Classe</div>
                    <div class="val">{{ $b['eleve']->classe?->nom ?? '—' }}</div>
                    <div class="sub">{{ $b['eleve']->classe?->niveau?->libelle ?? '' }} · Effectif {{ $b['statsClasse']['effectif'] }}</div>
                </td>
                <td style="width:25%;">
                    <div class="lbl">Trimestre</div>
                    <div class="val">{{ $b['trimestre']->libelle }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="notes">
        <thead>
            <tr>
                <th style="width:42%;">Matière</th>
                <th style="width:8%;" class="center">Coef</th>
                <th style="width:12%;" class="center">Moyenne</th>
                <th style="width:8%;" class="center">Rang</th>
                <th style="width:10%;" class="center">Min</th>
                <th style="width:10%;" class="center">Max</th>
                <th style="width:10%;" class="center">Moy classe</th>
            </tr>
        </thead>
        <tbody>
            @forelse($b['moyennes'] as $m)
                <tr>
                    <td><b>{{ $m->matiere?->nom ?? '—' }}</b></td>
                    <td class="center">{{ $m->matiere?->coefficient_defaut ?? '—' }}</td>
                    <td class="center" style="color:{{ $colorNote($m->moyenne) }};font-weight:bold;font-size:10px;">{{ $fmt($m->moyenne) }}</td>
                    <td class="center">{{ $m->rang_classe ?? '—' }}</td>
                    <td class="center text-negative" style="font-size:7px;">{{ $fmt($m->note_min_classe) }}</td>
                    <td class="center text-positive" style="font-size:7px;">{{ $fmt($m->note_max_classe) }}</td>
                    <td class="center" style="font-size:7px;">{{ $fmt($m->moyenne_classe) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="center text-muted">Aucune note saisie</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="resultats">
        <tr>
            <td style="width:40%;" class="res-moy">
                <div class="res-lbl">Moyenne générale</div>
                <div class="res-val" style="color:{{ $colorNote($b['generale']?->moyenne_generale) }};">{{ $fmt($b['generale']?->moyenne_generale) }}<span style="font-size:9px;color:#64748b;">/20</span></div>
            </td>
            <td style="width:5%;"></td>
            <td style="width:25%;" class="res-rang">
                <div class="res-lbl" style="color:#475569;">Rang</div>
                <div style="font-size:14px;font-weight:bold;">{{ $b['generale']?->rang ?? '—' }}<span style="font-size:8px;color:#64748b;">/{{ $b['statsClasse']['effectif'] }}</span></div>
            </td>
            <td style="width:5%;"></td>
            <td style="width:25%;" class="res-rang">
                <div class="res-lbl" style="color:#475569;">Mention</div>
                <div style="font-size:11px;font-weight:bold;color:#0f172a;">{{ $b['generale']?->mention ?? '—' }}</div>
            </td>
        </tr>
    </table>

    <table class="notes">
        <tr>
            <td style="width:50%;background:#f1f5f9;padding:4px;font-size:8px;"><b>Moy. classe :</b> {{ $fmt($b['statsClasse']['moy_classe']) }}</td>
            <td style="width:25%;background:#f1f5f9;padding:4px;font-size:8px;"><b>Max :</b> {{ $fmt($b['statsClasse']['max']) }}</td>
            <td style="width:25%;background:#f1f5f9;padding:4px;font-size:8px;"><b>Min :</b> {{ $fmt($b['statsClasse']['min']) }}</td>
        </tr>
    </table>

    <table class="signatures">
        <tr>
            <td style="width:33%;"><b>PROF. PRINCIPAL</b><div class="line">Signature</div></td>
            <td style="width:33%;"><b>PARENT/TUTEUR</b><div class="line">Signature</div></td>
            <td style="width:33%;"><b>LE DIRECTEUR</b><div class="line">Signature & cachet</div></td>
        </tr>
    </table>
</div>
@endforeach

</body>
</html>
