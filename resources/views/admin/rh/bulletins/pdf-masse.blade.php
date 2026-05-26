<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Bulletins en lot — {{ $classe->nom }}</title>
@php $perPage = (int) $disposition; @endphp
<style>
    @page { margin: 0; }
    html, body { margin: 0; }
    body { font-family: DejaVu Sans, sans-serif; color: #000; line-height: 1.2;
           padding: @if($perPage === 1) 15mm @elseif($perPage === 2) 8mm @else 6mm @endif; }
    .bulletin-wrap { padding-bottom: 1mm; }
    .bulletin-wrap + .bulletin-wrap { margin-top: 3mm; padding-top: 3mm; border-top: 1.5px dashed #999; }

    /* === Scaling selon disposition === */
    @if($perPage === 2)
        .bulletin { font-size: 6pt !important; line-height: 1.1 !important; }
        .bulletin .title { font-size: 13pt !important; }
        .bulletin .name { font-size: 10pt !important; }
        .bulletin .big { font-size: 12pt !important; }
        .bulletin .ministry { font-size: 6pt !important; }
        .bulletin .sub-title { font-size: 8pt !important; }
        .bulletin .school { font-size: 8.5pt !important; }
        .bulletin .foot-title { font-size: 6.2pt !important; }
        .bulletin .notes thead th { font-size: 5.4pt !important; }
        .bulletin .notes td { font-size: 6pt !important; }
        .bulletin .photo, .bulletin .photo-empty { width: 18mm !important; height: 22mm !important; line-height: 22mm !important; }
        .bulletin .label, .bulletin .value, .bulletin .dot { font-size: 6pt !important; }
        .bulletin .app, .bulletin .prof { font-size: 5.5pt !important; }
        .bulletin .decision { font-size: 8pt !important; }
    @elseif($perPage === 3)
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
</style>
</head>
<body>
@foreach($bulletins as $i => $b)
    @php $eleve = $b['eleve']; $generale = $b['generale']; $moyennes = $b['moyennes']; @endphp
    <div class="bulletin-wrap" @if(($i + 1) % $perPage === 0 && !$loop->last) style="page-break-after: always;" @endif>
        @include('admin.rh.bulletins.pdf-body')
    </div>
@endforeach
</body>
</html>
