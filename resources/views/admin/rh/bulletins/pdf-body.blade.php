{{-- ============================================================
     PARTIAL : corps du bulletin officiel ivoirien (sans <html>/<body>)
     Variables attendues : $etab, $annee, $trimestre, $classe, $eleve, $generale, $moyennes
     ============================================================ --}}
@php
    // ── Préparer les données : grouper par "groupe" (Littéraire / Scientifique / Autres) ──
    $totalGen = 0; $coefGen = 0;
    $moyennesParId = collect($moyennes)->keyBy('matiere_id');

    // Charger matières racines actives de l'etab pour ordre canonique + sous-disciplines
    $etabMatieres = \App\Models\Matiere::where('etablissement_id', $etab->id)
        ->whereNull('parent_matiere_id')
        ->where('active', true)
        ->with(['sousDisciplines' => fn($q) => $q->where('active', true)])
        ->orderBy('ordre')->orderBy('nom')
        ->get();

    $groupes = ['Littéraire' => [], 'Scientifique' => [], 'Artistique' => [], 'Sportive' => [], 'Autres' => []];
    foreach ($etabMatieres as $mat) {
        $g = $mat->groupe ?: 'Autres';
        if (!isset($groupes[$g])) {
            $groupes[$g] = [];
        }
        $groupes[$g][] = $mat;
    }
    $groupes = array_filter($groupes, fn($items) => count($items) > 0);

    $cssMoy = function($m) {
        if ($m === null) return '';
        if ($m >= 14) return 'ms-bon';
        if ($m >= 10) return 'ms-moy';
        return 'ms-faible';
    };

    $estSemestre = stripos($trimestre->libelle ?? '', 'semestre') !== false;
    $titrePeriode = $estSemestre ? 'Bulletin Semestriel de Notes' : 'Bulletin Trimestriel de Notes';
    $libelleNumero = strtoupper($trimestre->libelle ?? 'Période');
    $effectif = $generale->effectif_classe ?? 0;
@endphp

<style>
    .b-entete { width: 100%; border-collapse: collapse; margin-bottom: 2mm; }
    .b-entete td { vertical-align: top; border: 1px solid #000; padding: 2mm; font-size: 8.5pt; }
    .b-entete .left  { width: 55%; text-align: center; }
    .b-entete .right { width: 45%; text-align: center; }
    .b-pays  { font-weight: bold; font-size: 9pt; text-transform: uppercase; }
    .b-devise { font-size: 7.5pt; font-style: italic; margin-top: 0.5mm; letter-spacing: 1px; }
    .b-ministere { font-size: 8pt; margin-top: 1mm; }
    .b-direction { font-size: 8pt; font-weight: bold; text-transform: uppercase; margin-top: 0.5mm; }
    .b-titre-bulletin { font-weight: bold; font-size: 11pt; }
    .b-sous-titre { font-weight: bold; font-size: 9pt; margin-top: 1mm; text-transform: uppercase; }

    .b-infos { width: 100%; border-collapse: collapse; margin-bottom: 2mm; }
    .b-infos td { padding: 1mm 2mm; border: 1px solid #000; font-size: 8.5pt; vertical-align: top; }
    .b-etab-nom { font-weight: bold; font-size: 11pt; text-transform: uppercase; }
    .b-etab-sub { font-size: 8pt; margin-top: 0.5mm; }
    .b-ligne { margin-bottom: 0.5mm; }
    .b-dot { border-bottom: 1px dotted #000; display: inline-block; min-width: 30mm; padding: 0 1mm; font-weight: bold; }

    .b-meta { width: 100%; border-collapse: collapse; margin-bottom: 2mm; }
    .b-meta td { border: 1px solid #000; padding: 1.5mm 2mm; font-size: 8.5pt; }
    .b-meta .red-bloc { text-align: center; }
    .b-case { display: inline-block; width: 5mm; height: 5mm; border: 1px solid #000; vertical-align: middle; text-align: center; font-weight: bold; line-height: 5mm; }

    table.b-disciplines { width: 100%; border-collapse: collapse; }
    table.b-disciplines th { background: #e5e7eb; border: 1px solid #000; padding: 1.5mm 1mm; font-size: 8pt; text-align: center; font-weight: bold; text-transform: uppercase; }
    table.b-disciplines td { border: 1px solid #000; padding: 1mm 1.5mm; font-size: 8.5pt; vertical-align: middle; }
    table.b-disciplines td.b-num { text-align: center; font-weight: bold; }
    table.b-disciplines td.b-mat { font-weight: bold; }
    table.b-disciplines td.b-sub { padding-left: 7mm; font-style: italic; font-size: 8pt; }
    table.b-disciplines td.b-app { font-size: 7.5pt; font-style: italic; }
    table.b-disciplines tr.b-bilan td { background: #f3f4f6; font-weight: bold; font-size: 8pt; text-transform: uppercase; }
    table.b-disciplines tr.b-total td { background: #1e3a8a; color: #fff; font-weight: bold; font-size: 9pt; }

    .ms-cell { font-weight: bold; }
    .ms-bon { color: #047857; }
    .ms-moy { color: #b45309; }
    .ms-faible { color: #b91c1c; }

    .b-footer { width: 100%; border-collapse: collapse; margin-top: 2mm; }
    .b-footer td { border: 1px solid #000; padding: 2mm; font-size: 8pt; vertical-align: top; }
    .b-lbl-foot { font-weight: bold; text-transform: uppercase; font-size: 7.5pt; }
    .b-ligne-sig { border-bottom: 1px dotted #555; display: block; min-height: 6mm; margin-top: 1mm; }
    .b-ville-date { text-align: right; font-size: 8.5pt; margin-top: 2mm; font-style: italic; }
</style>

<table class="b-entete">
    <tr>
        <td class="left">
            <div class="b-pays">République de Côte d'Ivoire</div>
            <div class="b-devise">Union – Discipline – Travail</div>
            <div class="b-ministere">Ministère de l'Éducation Nationale et de l'Alphabétisation</div>
            @if(!empty($etab->drena))
                <div class="b-direction">Direction Régionale {{ $etab->drena }}</div>
            @elseif(!empty($etab->region))
                <div class="b-direction">Direction Régionale de {{ $etab->region }}</div>
            @endif
            @if(!empty($etab->ddena))
                <div style="font-size:8pt; font-weight:bold; margin-top:0.5mm;">DDENA de {{ $etab->ddena }}</div>
            @endif
        </td>
        <td class="right">
            <div style="font-size: 8pt;">Année Scolaire : <b>{{ $annee->libelle ?? '—' }}</b></div>
            <div class="b-titre-bulletin" style="margin-top:1mm;">{{ $titrePeriode }}</div>
            <div class="b-sous-titre">{{ $libelleNumero }}</div>
        </td>
    </tr>
</table>

<table class="b-infos">
    <tr>
        <td style="width:50%;">
            <div class="b-etab-nom">{{ $etab->nom ?? '—' }}</div>
            <div class="b-etab-sub">
                @if($etab->adresse) B.P. : {{ $etab->adresse }}<br>@endif
                @if($etab->telephone) Cel : {{ $etab->telephone }} @endif
                @if($etab->code_desps) · DESPS : {{ $etab->code_desps }}@endif
            </div>
        </td>
        <td style="width:50%;">
            <div class="b-ligne"><b>NOM :</b> <span class="b-dot">{{ strtoupper($eleve->nom) }}</span></div>
            <div class="b-ligne"><b>Prénoms :</b> <span class="b-dot">{{ $eleve->prenom }}</span></div>
            <div class="b-ligne"><b>Date de naissance :</b> <span class="b-dot">{{ $eleve->date_naissance?->format('d/m/Y') ?? '—' }}@if($eleve->lieu_naissance) à {{ $eleve->lieu_naissance }}@endif</span></div>
            <div class="b-ligne"><b>Établis. d'origine :</b> <span class="b-dot">{{ $eleve->ecole_precedente ?: $etab->nom }}</span></div>
            <div class="b-ligne"><b>Matricule :</b> <span class="b-dot">{{ $eleve->matricule_desps ?: $eleve->matricule_interne }}</span></div>
        </td>
    </tr>
</table>

<table class="b-meta">
    <tr>
        <td style="width: 30%;"><b>CLASSE :</b> <b>{{ $classe->nom ?? '—' }}</b></td>
        <td style="width: 30%;"><b>EFFECTIF :</b> <b>{{ $effectif }}</b></td>
        <td class="red-bloc" style="width: 40%;">
            <b>REDOUBLANT :</b>
            <span class="b-case">{{ $eleve->redoublant ? '✓' : '' }}</span> OUI
            <span class="b-case" style="margin-left:3mm;">{{ ! $eleve->redoublant ? '✓' : '' }}</span> NON
        </td>
    </tr>
</table>

<table class="b-disciplines">
    <thead>
        <tr>
            <th style="width: 33%; text-align: left;">DISCIPLINES</th>
            <th style="width: 9%;">Moy. /20</th>
            <th style="width: 7%;">Coef.</th>
            <th style="width: 10%;">Moy. × Coef</th>
            <th style="width: 8%;">Rang</th>
            <th style="width: 33%;">Nom, Appréciations & Signatures<br>des Professeurs</th>
        </tr>
    </thead>
    <tbody>
    @foreach($groupes as $nomGroupe => $matieresGroupe)
        @php
            $bilanGroupePts = 0; $bilanGroupeCoefs = 0;
        @endphp

        @foreach($matieresGroupe as $mat)
            @php
                $mObj = $moyennesParId->get($mat->id);
                $moy = $mObj?->moyenne !== null ? (float) $mObj->moyenne : null;
                $coef = (float) ($mat->coefficient_defaut ?? 1);
                $pts = $moy !== null ? $moy * $coef : null;
                if ($pts !== null) {
                    $bilanGroupePts += $pts;
                    $bilanGroupeCoefs += $coef;
                    $totalGen += $pts;
                    $coefGen += $coef;
                }
                $cls = $cssMoy($moy);
            @endphp
            <tr>
                <td class="b-mat">{{ $mat->nom }}</td>
                <td class="b-num ms-cell {{ $cls }}">{{ $moy !== null ? number_format($moy, 2, ',', ' ') : '—' }}</td>
                <td class="b-num">{{ rtrim(rtrim(number_format($coef, 1, ',', ''), '0'), ',') }}</td>
                <td class="b-num ms-cell">{{ $pts !== null ? number_format($pts, 2, ',', ' ') : '—' }}</td>
                <td class="b-num">—</td>
                <td class="b-app">{{ $mObj?->appreciation ?? '' }}</td>
            </tr>
            @foreach($mat->sousDisciplines as $sub)
                @php $sObj = $moyennesParId->get($sub->id); $sMoy = $sObj?->moyenne; @endphp
                <tr>
                    <td class="b-sub">└ {{ $sub->code }} — {{ $sub->nom }}</td>
                    <td class="b-num">{{ $sMoy !== null ? number_format($sMoy, 2, ',', ' ') : '—' }}</td>
                    <td class="b-num" style="font-weight:normal; font-style:italic;">×{{ rtrim(rtrim(number_format($sub->poids_dans_parent, 1, ',', ''), '0'), ',') }}</td>
                    <td class="b-num">—</td>
                    <td class="b-num">—</td>
                    <td class="b-app">{{ $sObj?->appreciation ?? '' }}</td>
                </tr>
            @endforeach
        @endforeach

        @if($bilanGroupeCoefs > 0)
        <tr class="b-bilan">
            <td>BILAN {{ strtoupper(substr($nomGroupe, 0, 3)) }} /20</td>
            <td class="b-num">{{ number_format($bilanGroupePts / max($bilanGroupeCoefs,1), 2, ',', ' ') }}</td>
            <td class="b-num">{{ rtrim(rtrim(number_format($bilanGroupeCoefs, 1, ',', ''), '0'), ',') }}</td>
            <td class="b-num">{{ number_format($bilanGroupePts, 2, ',', ' ') }}</td>
            <td colspan="2"></td>
        </tr>
        @endif
    @endforeach

    <tr class="b-total">
        <td>TOTAL</td>
        <td class="b-num">—</td>
        <td class="b-num">{{ rtrim(rtrim(number_format($coefGen, 1, ',', ''), '0'), ',') }}</td>
        <td class="b-num">{{ number_format($totalGen, 2, ',', ' ') }}</td>
        <td colspan="2" class="b-num" style="text-align:left; padding-left:3mm;">
            Moy. {{ $estSemestre ? 'sem.' : 'trim.' }} : <b style="font-size:11pt;">{{ $generale->moyenne_generale !== null ? number_format($generale->moyenne_generale, 2, ',', ' ') : '—' }}/20</b>
            @if($generale->rang) · Rang : <b>{{ $generale->rang }}<sup>e</sup>/{{ $effectif }}</b>@endif
        </td>
    </tr>
    </tbody>
</table>

@php
    $mentionLabels = [
        'felicitations'    => 'Félicitations',
        'tableau_honneur'  => 'Tableau d\'honneur',
        'encouragements'   => 'Encouragements',
        'avertissement'    => 'Avertissement de travail',
        'blame'            => 'Blâme',
        'aucune'           => '—',
    ];
@endphp
<table class="b-footer">
    <tr>
        <td style="width: 33%;">
            <div class="b-lbl-foot">Sanctions ou Distinction</div>
            <div style="margin-top:1.5mm; font-weight:bold; font-size:9pt;">{{ $mentionLabels[$generale->mention] ?? '—' }}</div>
        </td>
        <td style="width: 33%;">
            <div class="b-lbl-foot">Absences</div>
            <div style="margin-top:1.5mm; font-size:8.5pt;">
                <b>{{ $generale->total_absences ?? 0 }}</b> abs. (dont {{ $generale->absences_justifiees ?? 0 }} just.)<br>
                <b>{{ $generale->total_retards ?? 0 }}</b> retard(s)
            </div>
        </td>
        <td style="width: 34%;">
            <div class="b-lbl-foot">Statistiques classe</div>
            <div style="margin-top:1.5mm; font-size:8pt;">
                Moy. classe : <b>{{ $generale->moyenne_classe !== null ? number_format($generale->moyenne_classe, 2, ',', ' ') : '—' }}</b><br>
                1<sup>er</sup> : <b>{{ $generale->moyenne_premier !== null ? number_format($generale->moyenne_premier, 2, ',', ' ') : '—' }}</b>
                · Dernier : <b>{{ $generale->moyenne_dernier !== null ? number_format($generale->moyenne_dernier, 2, ',', ' ') : '—' }}</b>
            </div>
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <div class="b-lbl-foot">Appréciations du Professeur Principal</div>
            <div class="b-ligne-sig"></div>
            <div class="b-ligne-sig"></div>
            <div style="margin-top:2mm; text-align:right; font-size:7.5pt; font-style:italic;">Signature :</div>
            <div style="height: 10mm;"></div>
        </td>
        <td>
            <div class="b-lbl-foot">Appréciations du Conseil de Classe</div>
            <div class="b-ligne-sig"></div>
            <div class="b-ligne-sig"></div>
            <div class="b-ligne-sig"></div>
        </td>
    </tr>
    <tr>
        <td colspan="3">
            <table style="width:100%; border:0;">
                <tr>
                    <td style="border:0; width:50%; padding:0;">
                        <div class="b-ville-date">{{ $etab->ville ?? '—' }}, le {{ now()->format('d/m/Y') }}</div>
                    </td>
                    <td style="border:0; width:50%; padding:0; text-align:right;">
                        <div class="b-lbl-foot">Visa du Chef d'Établissement</div>
                        <div style="height: 14mm;"></div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
