{{-- BULLETIN DES NOTES — modèle compact type officiel --}}
@php
    $fmt = fn($v, $dec = 2) => $v !== null && $v !== '' ? number_format((float) $v, $dec, ',', ' ') : '—';
    $fmt0 = fn($v) => $v !== null && $v !== '' ? number_format((float) $v, 0, ',', ' ') : '—';
    $rang = fn($r) => $r ? $r.'e' : '—';
    $norm = function ($v) {
        $v = strtolower(trim((string) $v));
        $v = strtr($v, ['à'=>'a','á'=>'a','â'=>'a','ä'=>'a','ç'=>'c','è'=>'e','é'=>'e','ê'=>'e','ë'=>'e','î'=>'i','ï'=>'i','ô'=>'o','ö'=>'o','ù'=>'u','û'=>'u','ü'=>'u']);
        return preg_replace('/\s+/', ' ', $v) ?: '';
    };
    $isFrancais = fn($m) => str_contains($norm(($m->code ?? '').' '.($m->nom ?? '')), 'francais') || in_array($norm($m->code ?? ''), ['fr','fra'], true);
    $isPremierCycle = function () use ($classe, $norm) {
        $classe?->loadMissing('niveau');
        $n = $classe?->niveau;
        if (!$n) return false;
        $cycle = $norm($n->cycle ?? '');
        if (in_array($cycle, ['premier_cycle','premier cycle','college'], true)) return true;
        $txt = $norm(($n->code ?? '').' '.($n->libelle ?? '').' '.($classe->nom ?? ''));
        return preg_match('/(^|\s)(6|5|4|3)\s*(e|eme)?(\s|$)/', $txt) === 1;
    };
    $appreciation = function ($moy) {
        if ($moy === null || $moy === '') return 'NC';
        $moy = (float) $moy;
        if ($moy >= 16) return 'TRES BIEN';
        if ($moy >= 14) return 'BIEN';
        if ($moy >= 12) return 'ASSEZ BIEN';
        if ($moy >= 10) return 'PASSABLE';
        if ($moy >= 8) return 'INSUFFISANT';
        return 'FAIBLE';
    };

    $moyennesParId = collect($moyennes)->keyBy('matiere_id');
    $effectif = (int) ($generale->effectif_classe ?: ($classe->effectif ?? 0));
    $annuelle = \App\Models\MoyenneAnnuelle::where('eleve_id', $eleve->id)->where('annee_scolaire_id', $annee?->id)->first();

    $rootIds = \Illuminate\Support\Facades\DB::table('affectations')
        ->where('classe_id', $classe?->id)
        ->where('annee_scolaire_id', $annee?->id)
        ->where('active', true)
        ->pluck('matiere_id')
        ->unique()
        ->values();

    $matieres = \App\Models\Matiere::whereIn('id', $rootIds)
        ->whereNull('parent_matiere_id')
        ->where('active', true)
        ->with(['sousDisciplines' => fn($q) => $q->where('active', true)->orderBy('ordre')->orderBy('nom')])
        ->orderBy('ordre')->orderBy('nom')
        ->get();

    $enseignants = \Illuminate\Support\Facades\DB::table('affectations')
        ->join('enseignants', 'enseignants.id', '=', 'affectations.enseignant_id')
        ->where('affectations.classe_id', $classe?->id)
        ->where('affectations.annee_scolaire_id', $annee?->id)
        ->select('affectations.matiere_id', 'enseignants.nom', 'enseignants.prenom')
        ->get()
        ->groupBy('matiere_id')
        ->map(fn($rows) => $rows->map(fn($r) => trim(($r->prenom ? substr($r->prenom, 0, 1).'. ' : '').$r->nom))->unique()->join(', '));

    $groups = ['LETTRES' => [], 'SCIENCES' => [], 'AUTRES' => []];
    foreach ($matieres as $mat) {
        $g = $norm($mat->groupe ?? '');
        $code = strtoupper((string) $mat->code);
        if (str_contains($g, 'scient') || in_array($code, ['MATH','PC','SVT','SP','PHYS','CHIM'], true)) $groups['SCIENCES'][] = $mat;
        elseif (str_contains($g, 'litt') || in_array($code, ['FR','FRANCAIS','ANG','ANGL','HG','ESP','ALL','PHILO'], true) || $isFrancais($mat)) $groups['LETTRES'][] = $mat;
        else $groups['AUTRES'][] = $mat;
    }

    $photoSrc = null;
    if (!empty($eleve->photo_path)) {
        foreach ([public_path(ltrim($eleve->photo_path, '/')), public_path('storage/'.ltrim($eleve->photo_path, '/')), storage_path('app/public/'.ltrim($eleve->photo_path, '/'))] as $p) {
            if ($p && file_exists($p)) { $photoSrc = $p; break; }
        }
    }

    $totalCoef = 0; $totalPoints = 0;
    $decision = $annuelle?->decision_finale ?: (((float)($annuelle?->moyenne_annuelle ?? $generale->moyenne_generale) >= 10) ? 'Admis(e)' : 'Redouble en cas de non orientation');
@endphp

<style>
    .bulletin { width:100%; font-family: DejaVu Sans, sans-serif; color:#000; font-size:7pt; line-height:1.05; }
    .bulletin table { width:100%; border-collapse:collapse; }
    .bulletin td, .bulletin th { border:1px solid #000; padding:1px 2px; vertical-align:middle; }
    .no-border td { border:0 !important; }
    .title { text-align:center; font-family: DejaVu Serif, serif; font-size:18pt; font-weight:900; letter-spacing:.4px; }
    .sub-title { text-align:center; font-size:10.5pt; font-weight:900; margin-top:1px; }
    .ministry { text-align:center; font-size:7.2pt; font-weight:900; text-transform:uppercase; line-height:1.05; }
    .school { font-family: DejaVu Serif, serif; font-size:10.5pt; font-weight:900; text-transform:uppercase; }
    .name { font-size:13pt; font-weight:900; color:#1d4e89; text-transform:uppercase; letter-spacing:.2px; }
    .label { font-size:6.4pt; }
    .value { font-size:8pt; font-weight:900; }
    .photo { width:24mm; height:27mm; object-fit:cover; border:1px solid #999; }
    .photo-empty { width:24mm; height:27mm; border:1px solid #aaa; display:inline-block; text-align:center; line-height:27mm; color:#777; }
    .notes th { background:#f7f7f7; font-size:6.2pt; text-align:center; font-weight:900; text-transform:uppercase; }
    .notes td { font-size:6.9pt; }
    .disc { font-weight:900; }
    .subdisc { font-family: DejaVu Serif, serif; font-style:italic; padding-left:9px !important; }
    .num { text-align:center; font-weight:800; white-space:nowrap; }
    .app { font-size:6.3pt !important; font-weight:900; text-transform:uppercase; }
    .prof { font-size:6.2pt !important; font-weight:900; }
    .bilan td { font-weight:900; text-transform:uppercase; background:#f9f9f9; }
    .totaux td { font-weight:900; }
    .foot-title { text-align:center; font-weight:900; text-transform:uppercase; font-size:7pt; }
    .big { font-size:15pt; font-weight:900; text-align:center; }
    .decision { text-align:center; font-family: DejaVu Serif, serif; font-style:italic; font-weight:900; color:#6b0000; font-size:11pt; line-height:1.1; }
    .box { display:inline-block; width:8px; height:8px; border:1px solid #000; margin-right:3px; vertical-align:middle; text-align:center; line-height:7px; }
    .small { font-size:6.2pt; }
</style>

<div class="bulletin">
    <table style="border:0; margin-bottom:1px;">
        <tr class="no-border">
            <td style="width:49%; text-align:center;">
                <div class="ministry">MINISTÈRE DE L'ÉDUCATION NATIONALE ET DE L'ALPHABÉTISATION</div>
                <div class="ministry">DIRECTION RÉGIONALE {{ strtoupper($etab->drena ?: ($etab->region ?: $etab->ville ?: '')) }}</div>
            </td>
            <td style="width:51%;">
                <div class="title">BULLETIN DES NOTES</div>
                <div class="sub-title">{{ strtoupper($trimestre->libelle ?? ($trimestre->numero.'e Trimestre')) }} - {{ $annee->libelle ?? '—' }}</div>
            </td>
        </tr>
    </table>

    <table>
        <tr>
            <td style="width:66%; text-align:center;">
                <span class="label">Etab. :</span> <span class="school">{{ $etab->nom ?? '—' }}</span><br>
                <span class="label">Adres. :</span> {{ $etab->adresse ?? '—' }} &nbsp;&nbsp; <b>Tél.</b> {{ $etab->telephone ?? '—' }}
            </td>
            <td style="width:34%;">
                <b>Code :</b> {{ $etab->code_desps ?? $etab->code ?? '—' }}<br>
                <b>Statut:</b> {{ ucfirst(str_replace('_', ' ', $etab->statut_juridique ?? 'Privé')) }}<br>
                <span class="small">{{ $etab->email ?? '' }}</span>
            </td>
        </tr>
    </table>

    <table style="margin-top:2px; margin-bottom:2px;">
        <tr>
            <td style="width:50%; border-right:0;">
                <div class="name">{{ strtoupper(trim(($eleve->prenom ?? '').' '.($eleve->nom ?? ''))) }}</div>
                <span class="label">Matricule.</span> <span class="value">{{ $eleve->matricule_desps ?: $eleve->matricule_interne ?: '—' }}</span>
                &nbsp;&nbsp; <span class="label">Classe:</span> <span class="value">{{ $classe->nom ?? '—' }}</span><br>
                <span class="label">Effectif:</span> <span class="value">{{ $effectif ?: '—' }}</span>
            </td>
            <td style="width:27%; border-left:0; border-right:0;">
                <span class="label">Sexe:</span> <b>{{ strtoupper($eleve->sexe ?? '—') }}</b><br>
                <span class="label">Né(e) le:</span> <b>{{ $eleve->date_naissance?->format('d/m/Y') ?? '—' }}</b><br>
                <span class="label">À:</span> <b>{{ strtoupper($eleve->lieu_naissance ?? '—') }}</b><br>
                <span class="label">Nationalité:</span> {{ $eleve->nationalite ?? '—' }}
            </td>
            <td style="width:11%; border-left:0; border-right:0;">
                <span class="label">Redub:</span> <b>{{ $eleve->redoublant ? 'oui' : 'non' }}</b><br>
                <span class="label">Regime:</span> <b>Non bo</b><br>
                <span class="label">Interne:</span> <b>non</b><br>
                <span class="label">Affecté(e):</span> <b>{{ method_exists($eleve, 'estAffecte') && $eleve->estAffecte() ? 'oui' : 'non' }}</b>
            </td>
            <td style="width:12%; text-align:center;">
                @if($photoSrc)<img src="{{ $photoSrc }}" class="photo">@else<span class="photo-empty">PHOTO</span>@endif
            </td>
        </tr>
    </table>

    <table class="notes">
        <thead>
            <tr>
                <th style="width:26%;" rowspan="2">DISCIPLINES</th>
                <th colspan="4">{{ strtoupper($trimestre->libelle ?? ($trimestre->numero.'e TRIMESTRE')) }}</th>
                <th colspan="2">Annuel</th>
                <th style="width:12%;" rowspan="2">APPRECIATION</th>
                <th style="width:17%;" rowspan="2">NOM ET SIGNATURE DU PROF.</th>
            </tr>
            <tr>
                <th>MOY/20</th><th>Coef.</th><th>M. COEF</th><th>RANG</th><th>Moy</th><th>RANG</th>
            </tr>
        </thead>
        <tbody>
            @foreach($groups as $groupName => $groupMatieres)
                @continue(empty($groupMatieres))
                @php $gCoef = 0; $gPts = 0; @endphp
                @foreach($groupMatieres as $mat)
                    @php
                        $subs = ($isPremierCycle() && $isFrancais($mat)) ? $mat->sousDisciplines : collect();
                        $mObj = $moyennesParId->get($mat->id);
                        $moy = $mObj?->moyenne !== null ? (float) $mObj->moyenne : null;
                        if ($moy === null && $subs->isNotEmpty()) {
                            $sp = 0; $sw = 0;
                            foreach ($subs as $sd) {
                                $sdObj = $moyennesParId->get($sd->id);
                                if ($sdObj?->moyenne !== null) { $w = (float)($sd->poids_dans_parent ?: 1); $sp += (float)$sdObj->moyenne * $w; $sw += $w; }
                            }
                            if ($sw > 0) $moy = round($sp / $sw, 2);
                        }
                        $coef = (float) ($mat->coefficient_defaut ?: 1);
                        $pts = $moy !== null ? $moy * $coef : null;
                        if ($pts !== null) { $gCoef += $coef; $gPts += $pts; $totalCoef += $coef; $totalPoints += $pts; }
                        $prof = $enseignants[$mat->id] ?? '';
                    @endphp
                    <tr>
                        <td class="disc">{{ $mat->nom }}</td>
                        <td class="num">{{ $fmt($moy) }}</td>
                        <td class="num">{{ $fmt0($coef) }}</td>
                        <td class="num">{{ $fmt($pts) }}</td>
                        <td class="num">{{ $rang($mObj?->rang_classe) }}</td>
                        <td class="num">{{ $fmt($moy) }}</td>
                        <td class="num">{{ $rang($mObj?->rang_classe) }}</td>
                        <td class="app">{{ $mObj?->appreciation ?: $appreciation($moy) }}</td>
                        <td class="prof">{{ $prof }}</td>
                    </tr>
                    @foreach($subs as $sub)
                        @php $sObj = $moyennesParId->get($sub->id); $sMoy = $sObj?->moyenne !== null ? (float)$sObj->moyenne : null; @endphp
                        <tr>
                            <td class="subdisc">{{ $sub->nom }}</td>
                            <td class="num">{{ $fmt($sMoy) }}</td>
                            <td class="num">{{ $fmt0($sub->poids_dans_parent ?: 1) }}</td>
                            <td class="num">{{ $sMoy !== null ? $fmt($sMoy * (float)($sub->poids_dans_parent ?: 1)) : '—' }}</td>
                            <td class="num">{{ $rang($sObj?->rang_classe) }}</td>
                            <td class="num">{{ $fmt($sMoy) }}</td>
                            <td class="num">{{ $rang($sObj?->rang_classe) }}</td>
                            <td class="app">{{ $sObj?->appreciation ?: $appreciation($sMoy) }}</td>
                            <td></td>
                        </tr>
                    @endforeach
                @endforeach
                <tr class="bilan">
                    <td>BILAN {{ $groupName }}</td>
                    <td class="num">{{ $gCoef > 0 ? $fmt($gPts / $gCoef) : '—' }}</td>
                    <td class="num">{{ $fmt0($gCoef) }}</td>
                    <td class="num">{{ $fmt($gPts) }}</td>
                    <td></td><td class="num">{{ $gCoef > 0 ? $fmt($gPts / $gCoef) : '—' }}</td><td colspan="3"></td>
                </tr>
            @endforeach
            <tr class="totaux">
                <td>TOTAUX</td>
                <td class="num">{{ $fmt($generale->moyenne_generale) }}</td>
                <td class="num">{{ $fmt0($totalCoef ?: $generale->total_coefficients) }}</td>
                <td class="num">{{ $fmt($totalPoints ?: $generale->total_points) }}</td>
                <td class="num">{{ $rang($generale->rang) }}</td>
                <td class="num">{{ $fmt($annuelle?->moyenne_annuelle) }}</td>
                <td class="num">{{ $rang($annuelle?->rang_annuel) }}</td>
                <td colspan="2"></td>
            </tr>
        </tbody>
    </table>

    <table style="margin-top:2px;">
        <tr>
            <td style="width:34%;">
                <div class="foot-title">BILAN DU {{ $trimestre->numero ?? '' }}E TRIMESTRE</div>
                <table class="no-border"><tr><td style="width:35%;"><div class="big">{{ $fmt($generale->moyenne_generale, 1) }}</div></td><td>Rg : <b>{{ $generale->rang ?? '—' }}</b> / <b>{{ $effectif ?: '—' }}</b></td></tr></table>
                <div style="border-top:1px solid #000; margin:2px 0;"></div>
                <b>Distinctions / Sanctions</b><br>
                <span class="box">{{ in_array($generale->mention, ['felicitations','tableau_honneur'], true) ? '✓' : '' }}</span> TH + Fél. &nbsp;&nbsp;
                <span class="box">{{ $generale->mention === 'avertissement' ? '✓' : '' }}</span> Blâme Travail<br>
                <span class="box">{{ $generale->mention === 'tableau_honneur' ? '✓' : '' }}</span> TH &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <span class="box">{{ $generale->mention === 'blame' ? '✓' : '' }}</span> Blâme conduite
                <div style="border-top:1px solid #000; margin:2px 0;"></div>
                <b>{{ $generale->total_absences ?? 0 }}</b> H d'abs. dont <b>{{ $generale->absences_justifiees ?? 0 }}</b> non just.<br>
                Moy. mini : <b>{{ $fmt($generale->moyenne_dernier) }}</b> &nbsp; Moy. Classe : <b>{{ $fmt($generale->moyenne_classe) }}</b><br>
                Moy. maxi : <b>{{ $fmt($generale->moyenne_premier) }}</b>
            </td>
            <td style="width:28%;">
                <div class="foot-title">Récapitulatif</div>
                <table><tr><th>Moy.</th><th>C. M.</th><th>Rg</th></tr><tr><td class="num">{{ $fmt($generale->moyenne_generale,1) }}</td><td class="num">{{ $fmt($generale->total_points) }}</td><td class="num">{{ $rang($generale->rang) }}</td></tr><tr><td class="num">{{ $fmt($annuelle?->moyenne_annuelle,1) }}</td><td class="num">—</td><td class="num">{{ $rang($annuelle?->rang_annuel) }}</td></tr></table>
                <div class="foot-title" style="margin-top:4px;">Décision de fin d'année</div>
                <div class="decision">{{ $decision }}</div>
            </td>
            <td style="width:38%;">
                <div class="foot-title">BILAN DE FIN D'ANNÉE</div>
                <table class="no-border"><tr><td style="width:35%;"><div class="big">{{ $fmt($annuelle?->moyenne_annuelle, 2) }}</div></td><td>Rg : <b>{{ $annuelle?->rang_annuel ?? '—' }}</b> / <b>{{ $effectif ?: '—' }}</b></td></tr></table>
                <div style="border-top:1px solid #000; margin:4px 0 8px;"></div>
                <div class="foot-title">VISA DU CHEF D'ÉTABLISSEMENT</div>
                <div style="height:24px; text-align:center; font-style:italic; padding-top:5px;">Fait à {{ $etab->ville ?? '—' }}, le {{ now()->format('d/m/Y') }}</div>
                <div style="height:30px;"></div>
                <div style="text-align:center; font-weight:900;">{{ strtoupper($etab->directeur_nom ?? 'LE DIRECTEUR DES ÉTUDES') }}</div>
            </td>
        </tr>
    </table>

    <div class="small" style="margin-top:3px;">&lt;{{ strtoupper($eleve->nom_complet) }}&gt; - {{ $classe->nom ?? '—' }} - {{ $trimestre->libelle ?? 'Période' }} {{ $annee->libelle ?? '' }}</div>
</div>
