<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Bulletin — {{ $eleve->nom }} {{ $eleve->prenom }}</title>
<style>
    @page { margin: 8mm 10mm; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 8.5pt; color: #000; line-height: 1.25; }
</style>
</head>
<body>
@include('admin.rh.bulletins.pdf-body')
</body>
</html>
