<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=aviaschoolpay', 'root', '');
foreach ($pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_ASSOC) as $r)
    echo "{$r['Field']} | {$r['Type']} | Null:{$r['Null']} | Default:{$r['Default']}\n";
