<?php
/**
 * Export complet de la base aviaschoolpay → fichier SQL
 * Usage : php dump_db.php
 *
 * Génère storage/app/aviaschoolpay_dump_YYYYMMDD_HHMMSS.sql
 * Structure (CREATE TABLE) + Données (INSERT) compatibles MySQL 5.7+
 */

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$dbName = DB::connection()->getDatabaseName();
$timestamp = date('Ymd_His');
$out = storage_path("app/aviaschoolpay_dump_{$timestamp}.sql");

$fh = fopen($out, 'w');
fwrite($fh, "-- ======================================================\n");
fwrite($fh, "-- AviaSchoolPay — Dump complet de la base de données\n");
fwrite($fh, "-- Base       : {$dbName}\n");
fwrite($fh, "-- Généré le  : " . date('Y-m-d H:i:s') . "\n");
fwrite($fh, "-- ======================================================\n\n");

fwrite($fh, "SET FOREIGN_KEY_CHECKS=0;\n");
fwrite($fh, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
fwrite($fh, "SET time_zone = \"+00:00\";\n\n");

// Liste des tables
$tables = [];
foreach (DB::select('SHOW TABLES') as $row) {
    foreach ((array) $row as $t) {
        $tables[] = $t;
    }
}

$totalTables = count($tables);
$totalRows = 0;
echo "→ {$totalTables} tables à exporter...\n\n";

foreach ($tables as $i => $table) {
    $idx = $i + 1;
    echo "[{$idx}/{$totalTables}] {$table}...";

    // ── CREATE TABLE ──
    fwrite($fh, "-- ─────────── Table : `{$table}` ───────────\n");
    fwrite($fh, "DROP TABLE IF EXISTS `{$table}`;\n");
    $create = DB::select("SHOW CREATE TABLE `{$table}`")[0];
    $createSql = (array) $create;
    $createSql = $createSql['Create Table'] ?? $createSql['Create View'] ?? null;
    if ($createSql) {
        fwrite($fh, $createSql . ";\n\n");
    }

    // ── DATA ──
    $rows = DB::table($table)->get();
    $count = $rows->count();
    $totalRows += $count;

    if ($count === 0) {
        echo " (vide)\n";
        fwrite($fh, "\n");
        continue;
    }

    fwrite($fh, "INSERT INTO `{$table}` VALUES\n");
    $values = [];
    foreach ($rows as $row) {
        $row = (array) $row;
        $parts = [];
        foreach ($row as $v) {
            if (is_null($v)) {
                $parts[] = 'NULL';
            } elseif (is_bool($v)) {
                $parts[] = $v ? '1' : '0';
            } elseif (is_numeric($v) && ! is_string($v)) {
                $parts[] = $v;
            } else {
                $escaped = str_replace(
                    ["\\", "'", "\n", "\r", "\0"],
                    ["\\\\", "\\'", "\\n", "\\r", "\\0"],
                    (string) $v
                );
                $parts[] = "'{$escaped}'";
            }
        }
        $values[] = '(' . implode(', ', $parts) . ')';
    }

    // Chunks de 200 lignes par INSERT pour fichiers manipulables
    $chunks = array_chunk($values, 200);
    foreach ($chunks as $idx2 => $chunk) {
        fwrite($fh, implode(",\n", $chunk));
        fwrite($fh, $idx2 === count($chunks) - 1 ? ";\n\n" : ";\nINSERT INTO `{$table}` VALUES\n");
    }

    echo " {$count} lignes\n";
}

fwrite($fh, "\nSET FOREIGN_KEY_CHECKS=1;\n");
fclose($fh);

$size = filesize($out);
$sizeKb = round($size / 1024, 1);
$sizeMb = round($size / 1024 / 1024, 2);

echo "\n═══════════════════════════════════════════════════════\n";
echo "✓ Export terminé :\n";
echo "  Fichier : {$out}\n";
echo "  Tables  : {$totalTables}\n";
echo "  Lignes  : {$totalRows}\n";
echo "  Taille  : {$sizeKb} Ko ({$sizeMb} Mo)\n";
echo "═══════════════════════════════════════════════════════\n";
