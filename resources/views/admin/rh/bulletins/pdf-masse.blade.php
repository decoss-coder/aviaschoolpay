<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Bulletins en lot — {{ $classe->nom }}</title>
@php $perPage = (int) $disposition; @endphp
<style>
    @if($perPage === 2)
        @page { size: A4 landscape; margin: 0; }
    @else
        @page { size: A4 portrait; margin: 0; }
    @endif

    html, body { margin: 0; }
    body {
        font-family: DejaVu Sans, sans-serif;
        color: #000;
        line-height: 1.2;
        padding: @if($perPage === 1) 15mm @elseif($perPage === 2) 4mm @else 6mm @endif;
    }

    .batch-page-break {
        clear: both;
        page-break-after: always;
        height: 0;
        line-height: 0;
        font-size: 0;
    }

    @if($perPage === 2)
        .bulletin-wrap {
            float: left;
            width: 49%;
            margin: 0 2% 0 0;
            padding: 0;
            page-break-inside: avoid;
        }
        .bulletin-wrap:nth-of-type(2n) { margin-right: 0; }
        .bulletin-wrap .bulletin {
            width: 100% !important;
            box-sizing: border-box;
            font-size: 5.2pt !important;
            line-height: 1.03 !important;
        }
        .bulletin-wrap .bulletin td,
        .bulletin-wrap .bulletin th { padding: 0.7px 1.4px !important; }
        .bulletin-wrap .title { font-size: 9pt !important; }
        .bulletin-wrap .sub-title { font-size: 5.9pt !important; }
        .bulletin-wrap .ministry { font-size: 4.8pt !important; }
        .bulletin-wrap .school { font-size: 6.4pt !important; }
        .bulletin-wrap .name { font-size: 8.8pt !important; }
        .bulletin-wrap .label,
        .bulletin-wrap .value,
        .bulletin-wrap .dot { font-size: 4.9pt !important; }
        .bulletin-wrap .photo,
        .bulletin-wrap .photo-empty {
            width: 15mm !important;
            height: 18mm !important;
            line-height: 18mm !important;
        }
        .bulletin-wrap .notes thead th { font-size: 4.7pt !important; }
        .bulletin-wrap .notes td { font-size: 5.05pt !important; }
        .bulletin-wrap .subdisc { font-size: 4.9pt !important; padding-left: 4px !important; }
        .bulletin-wrap .app,
        .bulletin-wrap .prof { font-size: 4.65pt !important; }
        .bulletin-wrap .foot-title { font-size: 5.1pt !important; }
        .bulletin-wrap .big { font-size: 9.2pt !important; }
        .bulletin-wrap .rg-line { font-size: 5.2pt !important; }
        .bulletin-wrap .decision { font-size: 7.2pt !important; }
        .bulletin-wrap .small { font-size: 4.3pt !important; }
        .bulletin-wrap .divider { margin: 0.6mm 0 !important; }
        .bulletin-wrap .visa-date { font-size: 5.1pt !important; margin: 0.4mm 0 1.4mm !important; }
        .bulletin-wrap .visa-role { font-size: 5.1pt !important; }
    @else
        .bulletin-wrap {
            display: block;
            width: 100%;
            clear: both;
            float: none;
            padding-bottom: 1mm;
            page-break-inside: avoid;
        }
        .bulletin-wrap + .bulletin-wrap {
            margin-top: 3mm;
            padding-top: 3mm;
            border-top: 1.5px dashed #999;
        }

        @if($perPage === 3)
            .bulletin { font-size: 4.8pt !important; line-height: 1.05 !important; }
            .bulletin .title { font-size: 10pt !important; }
            .bulletin .name { font-size: 8.5pt !important; }
            .bulletin .big { font-size: 10pt !important; }
            .bulletin .ministry { font-size: 5pt !important; }
            .bulletin .sub-title { font-size: 6.5pt !important; }
            .bulletin .school { font-size: 7pt !important; }
            .bulletin .foot-title { font-size: 5pt !important; }
            .bulletin .notes thead th { font-size: 4.2pt !important; }
            .bulletin .notes td { font-size: 4.8pt !important; }
            .bulletin .photo, .bulletin .photo-empty { width: 14mm !important; height: 17mm !important; line-height: 17mm !important; }
            .bulletin .label, .bulletin .value, .bulletin .dot { font-size: 4.8pt !important; }
            .bulletin .app, .bulletin .prof { font-size: 4.4pt !important; }
            .bulletin .decision { font-size: 7pt !important; }
            .bulletin td, .bulletin th { padding: 0.8px 2px !important; }
        @elseif($perPage === 4)
            .bulletin { font-size: 4.2pt !important; line-height: 1 !important; }
            .bulletin .title { font-size: 8.5pt !important; }
            .bulletin .name { font-size: 7.5pt !important; }
            .bulletin .big { font-size: 9pt !important; }
            .bulletin .ministry { font-size: 4.5pt !important; }
            .bulletin .sub-title { font-size: 5.8pt !important; }
            .bulletin .school { font-size: 6pt !important; }
            .bulletin .foot-title { font-size: 4.5pt !important; }
            .bulletin .notes thead th { font-size: 3.8pt !important; }
            .bulletin .notes td { font-size: 4.2pt !important; }
            .bulletin .photo, .bulletin .photo-empty { width: 11mm !important; height: 14mm !important; line-height: 14mm !important; }
            .bulletin .label, .bulletin .value, .bulletin .dot { font-size: 4.2pt !important; }
            .bulletin .app, .bulletin .prof { font-size: 3.9pt !important; }
            .bulletin .decision { font-size: 6pt !important; }
            .bulletin td, .bulletin th { padding: 0.5px 1.5px !important; }
        @endif
    @endif
</style>
</head>
<body>
@foreach($bulletins as $i => $b)
    @php $eleve = $b['eleve']; $generale = $b['generale']; $moyennes = $b['moyennes']; @endphp
    <div class="bulletin-wrap">
        @include('admin.rh.bulletins.pdf-body')
    </div>

    @if(($i + 1) % $perPage === 0 && !$loop->last)
        <div class="batch-page-break"></div>
    @endif
@endforeach
</body>
</html>
