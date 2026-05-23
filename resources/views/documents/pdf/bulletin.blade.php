@extends('documents.pdf._layout')

@php
    $fmt = fn($v) => $v !== null ? number_format((float) $v, 2, ',', ' ') : '—';
    $colorNote = fn($v) => $v === null ? '#94a3b8'
        : ($v >= 14 ? '#047857' : ($v >= 10 ? '#0a7b3f' : ($v >= 8 ? '#b45309' : '#b91c1c')));

    // ─── Calcul du radar SVG ───
    $maxMatieres = 8;
    $matieresRadar = $moyennes->take($maxMatieres);
    $nbAxes = $matieresRadar->count();
    $cx = 150; $cy = 130; $rMax = 90;
    $axes = [];
    if ($nbAxes >= 3) {
        foreach ($matieresRadar->values() as $i => $m) {
            $angle = -M_PI / 2 + (2 * M_PI * $i / $nbAxes);
            $x = $cx + $rMax * cos($angle);
            $y = $cy + $rMax * sin($angle);
            $val = $m->moyenne ? min(20, max(0, (float) $m->moyenne)) : 0;
            $r = $rMax * ($val / 20);
            $px = $cx + $r * cos($angle);
            $py = $cy + $r * sin($angle);
            $axes[] = [
                'angle'   => $angle,
                'axe_x'   => $x, 'axe_y' => $y,
                'point_x' => $px, 'point_y' => $py,
                'lbl_x'   => $cx + ($rMax + 12) * cos($angle),
                'lbl_y'   => $cy + ($rMax + 12) * sin($angle),
                'libelle' => mb_substr($m->matiere?->code ?? mb_substr($m->matiere?->nom ?? '?', 0, 4), 0, 5),
                'val'     => $m->moyenne,
            ];
        }
    }
    $pointsPolygon = '';
    foreach ($axes as $a) $pointsPolygon .= "{$a['point_x']},{$a['point_y']} ";
@endphp

@section('titre', 'BULLETIN INDIVIDUEL')
@section('periode', $trimestre->libelle . ' · ' . ($eleve->classe?->nom ?? '—'))

@section('content')

{{-- Identité élève --}}
<table style="width:100%;margin-bottom:10px;border:1px solid #e2e8f0;border-radius:5px;">
    <tr>
        <td style="padding:8px 12px;width:55%;">
            <div style="font-size:9px;color:#64748b;text-transform:uppercase;font-weight:bold;">Élève</div>
            <div style="font-size:16px;font-weight:bold;color:#0f172a;margin-top:3px;">{{ $eleve->prenom }} {{ strtoupper($eleve->nom) }}</div>
            <div style="font-size:9px;color:#64748b;margin-top:2px;">
                Matricule : <b>{{ $eleve->matricule_interne ?? '—' }}</b>
                · Sexe : <b>{{ $eleve->sexe }}</b>
                · Né(e) le : <b>{{ $eleve->date_naissance?->format('d/m/Y') ?? '—' }}</b>
            </div>
        </td>
        <td style="padding:8px 12px;width:45%;border-left:1px solid #e2e8f0;">
            <div style="font-size:9px;color:#64748b;text-transform:uppercase;font-weight:bold;">Classe</div>
            <div style="font-size:13px;font-weight:bold;color:#0a7b3f;margin-top:3px;">{{ $eleve->classe?->nom ?? '—' }}</div>
            <div style="font-size:9px;color:#64748b;">{{ $eleve->classe?->niveau?->libelle ?? '' }} · Effectif {{ $statsClasse['effectif'] }}</div>
        </td>
    </tr>
</table>

{{-- Détail matières + Radar --}}
<table style="width:100%;border-collapse:collapse;margin-bottom:10px;">
    <tr>
        <td style="width:65%;vertical-align:top;padding-right:8px;">
            <h2 style="margin-top:0;">Notes par matière</h2>
            @if($moyennes->isEmpty())
                <p class="text-muted">Aucune note saisie ce trimestre.</p>
            @else
            <table class="data" style="font-size:9px;">
                <thead>
                    <tr>
                        <th style="width:45%;">Matière</th>
                        <th style="width:10%;" class="center">Coef</th>
                        <th style="width:15%;" class="center">Moyenne /20</th>
                        <th style="width:10%;" class="center">Rang</th>
                        <th style="width:20%;">Appréciation</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($moyennes as $m)
                        <tr>
                            <td><b>{{ $m->matiere?->nom ?? '—' }}</b></td>
                            <td class="center">{{ $m->matiere?->coefficient_defaut ?? '—' }}</td>
                            <td class="center" style="color:{{ $colorNote($m->moyenne) }};font-weight:bold;font-size:11px;">{{ $fmt($m->moyenne) }}</td>
                            <td class="center">{{ $m->rang_classe ?? '—' }}</td>
                            <td style="font-size:8px;">{{ $m->appreciation ?? '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </td>

        {{-- Radar SVG --}}
        <td style="width:35%;vertical-align:top;padding-left:8px;">
            <h2 style="margin-top:0;">Profil radar</h2>
            @if($nbAxes >= 3)
            <svg width="300" height="260" viewBox="0 0 300 260" xmlns="http://www.w3.org/2000/svg" style="display:block;margin:auto;">
                {{-- Cercles concentriques --}}
                @for($k = 1; $k <= 4; $k++)
                    <circle cx="{{ $cx }}" cy="{{ $cy }}" r="{{ $rMax * $k / 4 }}" fill="none" stroke="#e2e8f0" stroke-width="0.5" />
                @endfor
                {{-- Axes --}}
                @foreach($axes as $a)
                    <line x1="{{ $cx }}" y1="{{ $cy }}" x2="{{ $a['axe_x'] }}" y2="{{ $a['axe_y'] }}" stroke="#cbd5e1" stroke-width="0.5" />
                @endforeach
                {{-- Polygone scores --}}
                <polygon points="{{ trim($pointsPolygon) }}" fill="rgba(10, 123, 63, 0.25)" stroke="#0a7b3f" stroke-width="1.5" />
                {{-- Points --}}
                @foreach($axes as $a)
                    <circle cx="{{ $a['point_x'] }}" cy="{{ $a['point_y'] }}" r="2" fill="#0a7b3f" />
                @endforeach
                {{-- Labels --}}
                @foreach($axes as $a)
                    <text x="{{ $a['lbl_x'] }}" y="{{ $a['lbl_y'] }}" font-size="8" fill="#0f172a" text-anchor="middle" font-weight="bold">{{ $a['libelle'] }}</text>
                @endforeach
                {{-- Échelle --}}
                <text x="{{ $cx }}" y="{{ $cy - $rMax - 2 }}" font-size="6" fill="#94a3b8" text-anchor="middle">20</text>
                <text x="{{ $cx }}" y="{{ $cy + 3 }}" font-size="6" fill="#94a3b8" text-anchor="middle">0</text>
            </svg>
            <p style="font-size:7px;text-align:center;color:#64748b;margin-top:-10px;">Profil sur 20 par matière</p>
            @else
            <div style="background:#f1f5f9;padding:20px;border-radius:5px;text-align:center;color:#64748b;font-size:9px;">
                Radar disponible à partir de 3 matières
            </div>
            @endif
        </td>
    </tr>
</table>

{{-- Résultats généraux --}}
<h2>Résultats généraux</h2>
<table style="width:100%;border-collapse:collapse;margin-bottom:8px;">
    <tr>
        <td style="width:25%;padding:10px;background:linear-gradient(to bottom right, #ecfdf5, #d1fae5);border:2px solid #10b981;border-radius:5px;text-align:center;">
            <div style="font-size:8px;color:#065f46;text-transform:uppercase;font-weight:bold;">Moyenne générale</div>
            <div style="font-size:22px;color:{{ $colorNote($generale?->moyenne_generale) }};font-weight:bold;margin-top:3px;">{{ $fmt($generale?->moyenne_generale) }}</div>
            <div style="font-size:8px;color:#64748b;">/ 20</div>
        </td>
        <td style="width:5%;"></td>
        <td style="width:23%;padding:10px;background:#f8fafc;border:1px solid #cbd5e1;border-radius:5px;text-align:center;">
            <div style="font-size:8px;color:#64748b;text-transform:uppercase;font-weight:bold;">Rang</div>
            <div style="font-size:18px;color:#0f172a;font-weight:bold;margin-top:3px;">{{ $generale?->rang ?? '—' }}<span style="font-size:10px;color:#64748b;">/{{ $statsClasse['effectif'] }}</span></div>
        </td>
        <td style="width:5%;"></td>
        <td style="width:23%;padding:10px;background:#f8fafc;border:1px solid #cbd5e1;border-radius:5px;text-align:center;">
            <div style="font-size:8px;color:#64748b;text-transform:uppercase;font-weight:bold;">Mention</div>
            <div style="font-size:11px;color:#0f172a;font-weight:bold;margin-top:5px;">{{ $generale?->mention ?? '—' }}</div>
        </td>
        <td style="width:5%;"></td>
        <td style="width:14%;padding:8px;background:#fef3c7;border:1px solid #f59e0b;border-radius:5px;text-align:center;">
            <div style="font-size:7px;color:#92400e;text-transform:uppercase;font-weight:bold;">Moy classe</div>
            <div style="font-size:13px;color:#92400e;font-weight:bold;">{{ $fmt($statsClasse['moy_classe']) }}</div>
        </td>
    </tr>
</table>

<table class="data" style="margin-top:8px;font-size:9px;">
    <tr>
        <td style="width:50%;background:#f1f5f9;padding:6px;"><b>Note maximale classe :</b> {{ $fmt($statsClasse['max']) }}</td>
        <td style="width:50%;background:#f1f5f9;padding:6px;"><b>Note minimale classe :</b> {{ $fmt($statsClasse['min']) }}</td>
    </tr>
</table>

{{-- Signatures --}}
<table style="width:100%;margin-top:25px;">
    <tr>
        <td style="width:33%;text-align:center;font-size:8px;color:#64748b;">
            <b style="text-transform:uppercase;">Professeur principal</b>
            <div style="border-top:1px dashed #94a3b8;margin-top:35px;padding-top:3px;">Signature</div>
        </td>
        <td style="width:33%;text-align:center;font-size:8px;color:#64748b;">
            <b style="text-transform:uppercase;">Parent / Tuteur</b>
            <div style="border-top:1px dashed #94a3b8;margin-top:35px;padding-top:3px;">Signature</div>
        </td>
        <td style="width:33%;text-align:center;font-size:8px;color:#64748b;">
            <b style="text-transform:uppercase;">Le Directeur</b>
            <div style="border-top:1px dashed #94a3b8;margin-top:35px;padding-top:3px;">Signature & cachet</div>
        </td>
    </tr>
</table>

@endsection
