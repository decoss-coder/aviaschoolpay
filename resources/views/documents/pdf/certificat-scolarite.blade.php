<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Certificat de scolarité — {{ $eleve->prenom }} {{ $eleve->nom }}</title>
    <style>
        @page { margin: 30px 40px; }
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #1f2937; font-size: 11px; line-height: 1.55; }

        .official-header { text-align: center; border-bottom: 3px double #0a7b3f; padding-bottom: 12px; margin-bottom: 25px; }
        .etab-nom-big { font-size: 18px; font-weight: bold; color: #0a7b3f; letter-spacing: 1px; }
        .etab-sub { font-size: 10px; color: #475569; margin-top: 3px; }
        .desps { font-size: 9px; color: #64748b; font-style: italic; margin-top: 2px; }

        .meta { text-align: right; font-size: 10px; color: #64748b; margin-bottom: 30px; }
        .meta .numero { font-family: DejaVu Sans Mono, monospace; font-weight: bold; color: #0a7b3f; }

        h1.title {
            text-align: center;
            font-size: 26px;
            font-weight: bold;
            color: #0a7b3f;
            text-transform: uppercase;
            letter-spacing: 3px;
            margin: 0 0 8px 0;
            padding: 10px;
            border-top: 2px solid #0a7b3f;
            border-bottom: 2px solid #0a7b3f;
        }

        .ribbon { text-align: center; font-size: 10px; color: #94a3b8; margin-bottom: 30px; font-style: italic; }

        .corps { padding: 20px 30px; font-size: 13px; line-height: 2; text-align: justify; }
        .corps .important { font-weight: bold; color: #0f172a; text-decoration: underline; }
        .corps .nom-eleve { font-size: 16px; font-weight: bold; color: #0a7b3f; text-transform: uppercase; }

        .infos-eleve { background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 5px; padding: 12px 18px; margin: 20px 0; }
        .infos-eleve table { width: 100%; }
        .infos-eleve td { padding: 4px 8px; font-size: 11px; }
        .infos-eleve .lbl { color: #64748b; font-weight: bold; text-transform: uppercase; font-size: 9px; }

        .signature-bloc { margin-top: 50px; text-align: right; }
        .signature-bloc .lieu-date { font-size: 11px; color: #475569; margin-bottom: 40px; }
        .signature-bloc .fonction { font-size: 11px; font-weight: bold; color: #0f172a; }
        .signature-bloc .signature-line { width: 220px; margin-left: auto; border-top: 1px solid #94a3b8; margin-top: 50px; padding-top: 4px; text-align: center; font-size: 9px; color: #64748b; }

        .seal { position: absolute; bottom: 80px; left: 50px; width: 110px; height: 110px; border: 2px solid #0a7b3f; border-radius: 50%; text-align: center; padding-top: 30px; font-size: 9px; color: #0a7b3f; font-weight: bold; text-transform: uppercase; opacity: 0.3; }

        .footer-doc { position: fixed; bottom: 10px; left: 0; right: 0; text-align: center; font-size: 8px; color: #94a3b8; border-top: 1px solid #e5e7eb; padding-top: 5px; }
    </style>
</head>
<body>

<div class="official-header">
    <div style="font-size:8px;color:#64748b;font-style:italic;">RÉPUBLIQUE — MINISTÈRE DE L'ÉDUCATION NATIONALE</div>
    <div class="etab-nom-big">{{ $etab?->nom ?? 'Établissement' }}</div>
    <div class="etab-sub">{{ $etab?->adresse }}{{ $etab?->ville ? ', '.$etab->ville : '' }}</div>
    <div class="etab-sub">Tél : {{ $etab?->telephone }}{{ $etab?->email ? ' · '.$etab->email : '' }}</div>
    @if($etab?->code_desps)
        <div class="desps">Code DESPS : {{ $etab->code_desps }}</div>
    @endif
</div>

<div class="meta">
    N° certificat : <span class="numero">{{ $numero }}</span><br>
    Édité le {{ now()->format('d/m/Y') }}
</div>

<h1 class="title">Certificat de Scolarité</h1>
<div class="ribbon">— Année scolaire {{ $inscription->anneeScolaire?->libelle ?? '—' }} —</div>

<div class="corps">

    Je soussigné(e), Chef d'établissement de <b class="important">{{ $etab?->nom }}</b>,

    <br><br>

    Certifie que l'élève
    <br><br>
    <span class="nom-eleve">{{ $eleve->prenom }} {{ $eleve->nom }}</span>

    <div class="infos-eleve">
        <table>
            <tr>
                <td><div class="lbl">Né(e) le</div></td>
                <td><b>{{ $eleve->date_naissance?->format('d/m/Y') ?? '—' }}</b></td>
                <td><div class="lbl">À</div></td>
                <td><b>{{ $eleve->lieu_naissance ?? '—' }}</b></td>
            </tr>
            <tr>
                <td><div class="lbl">Sexe</div></td>
                <td><b>{{ $eleve->sexe === 'M' ? 'Masculin' : 'Féminin' }}</b></td>
                <td><div class="lbl">Matricule</div></td>
                <td><b style="font-family:DejaVu Sans Mono;">{{ $eleve->matricule_interne ?? '—' }}</b></td>
            </tr>
            <tr>
                <td><div class="lbl">Nationalité</div></td>
                <td><b>{{ $eleve->nationalite ?? '—' }}</b></td>
                <td><div class="lbl">Mat. DESPS</div></td>
                <td><b>{{ $eleve->matricule_desps ?? '—' }}</b></td>
            </tr>
        </table>
    </div>

    est régulièrement inscrit(e) dans notre établissement, en classe de
    <b class="important">{{ $eleve->classe?->nom ?? '—' }}</b>
    ({{ $eleve->classe?->niveau?->libelle ?? '—' }}),
    pour l'année scolaire <b class="important">{{ $inscription->anneeScolaire?->libelle ?? '—' }}</b>,
    et y suit assidûment les enseignements depuis le
    <b>{{ $inscription->date_inscription?->format('d/m/Y') ?? '—' }}</b>.

    <br><br>

    Le présent certificat est délivré à l'intéressé(e) pour servir et valoir ce que de droit.

</div>

<div class="signature-bloc">
    <div class="lieu-date">Fait à {{ $etab?->ville ?? '—' }}, le {{ now()->format('d/m/Y') }}</div>
    <div class="fonction">Le Directeur</div>
    <div class="signature-line">Signature & cachet</div>
</div>

<div class="seal">
    Cachet<br>de l'école
</div>

<div class="footer-doc">
    Document officiel généré par AviaSchoolPay · {{ $numero }} · {{ $etab?->nom ?? '' }} · Vérifiable auprès de l'établissement
</div>

</body>
</html>
