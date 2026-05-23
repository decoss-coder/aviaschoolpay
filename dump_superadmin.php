<?php
/**
 * Génère les INSERT SQL pour le siège Avia + super_admins (idempotent)
 */
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$out = __DIR__.'/public/downloads/super_admin.sql';
@mkdir(dirname($out), 0777, true);
$fh = fopen($out, 'w');

fwrite($fh, "-- ════════════════════════════════════════════════════════════════\n");
fwrite($fh, "-- AviaSchoolPay — Insertion super_admin + établissement siège\n");
fwrite($fh, "-- Généré le : ".date('Y-m-d H:i:s')."\n");
fwrite($fh, "-- IDEMPOTENT : peut être exécuté plusieurs fois sans erreur\n");
fwrite($fh, "-- ════════════════════════════════════════════════════════════════\n\n");
fwrite($fh, "SET FOREIGN_KEY_CHECKS=0;\n\n");

function escapeValue($v): string {
    if (is_null($v)) return 'NULL';
    if (is_bool($v)) return $v ? '1' : '0';
    if (is_numeric($v) && !is_string($v)) return (string) $v;
    $escaped = str_replace(["\\", "'", "\n", "\r", "\0"], ["\\\\", "\\'", "\\n", "\\r", "\\0"], (string) $v);
    return "'{$escaped}'";
}

/**
 * Génère un INSERT ... ON DUPLICATE KEY UPDATE pour idempotence.
 * @param array $excludeFromUpdate colonnes à NE PAS écraser (ex: id, primary keys).
 */
function upsertRow($fh, string $table, array $row, array $excludeFromUpdate = ['id']): void {
    $cols = array_map(fn($c) => "`{$c}`", array_keys($row));
    $vals = array_map(fn($v) => escapeValue($v), array_values($row));

    $updates = [];
    foreach (array_keys($row) as $col) {
        if (in_array($col, $excludeFromUpdate, true)) continue;
        $updates[] = "`{$col}` = VALUES(`{$col}`)";
    }

    fwrite($fh, "INSERT INTO `{$table}` (" . implode(', ', $cols) . ") VALUES\n");
    fwrite($fh, "  (" . implode(', ', $vals) . ")\n");
    if (!empty($updates)) {
        fwrite($fh, "ON DUPLICATE KEY UPDATE\n  " . implode(",\n  ", $updates) . ";\n\n");
    } else {
        fwrite($fh, ";\n\n");
    }
}

// 1) Établissement siège
fwrite($fh, "-- ─── 1) Établissement siège Avia ───\n");
foreach (DB::table('etablissements')->where('code_desps', 'AVIA-SIEGE')->get() as $e) {
    upsertRow($fh, 'etablissements', (array) $e, ['id']);
}

// 2) Tous les super_admin
fwrite($fh, "-- ─── 2) Comptes super_admin ───\n");
foreach (DB::table('users')->where('role', 'super_admin')->get() as $u) {
    upsertRow($fh, 'users', (array) $u, ['id']);
}

// 3) PlatformSettings (clé primaire = `cle` → exclure de l'update bien sûr)
fwrite($fh, "-- ─── 3) PlatformSettings (config Wave Avia) ───\n");
foreach (DB::table('platform_settings')->get() as $p) {
    upsertRow($fh, 'platform_settings', (array) $p, ['cle', 'created_at']);
}

fwrite($fh, "\nSET FOREIGN_KEY_CHECKS=1;\n");
fclose($fh);

echo "✓ Fichier généré : public/downloads/super_admin.sql\n";
echo "  Taille : " . round(filesize($out) / 1024, 2) . " Ko\n";
echo "\n=== Contenu ===\n\n";
echo file_get_contents($out);
