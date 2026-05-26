<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Bulletin — {{ $eleve->nom }} {{ $eleve->prenom }}</title>
<style>
    @page { margin: 12mm 12mm; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 7.2pt; color: #000; line-height: 1.08; }
</style>
</head>
<body>
@include('admin.rh.bulletins.pdf-body')
</body>
</html>
