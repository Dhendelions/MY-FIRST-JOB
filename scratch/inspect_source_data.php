<?php
$sourcePdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=dadhen_mandiri', 'root', 'edurank123', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$tables = $sourcePdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $table) {
    $count = $sourcePdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
    if ($count > 0) {
        echo "Table: $table ($count rows)\n";
        $rows = $sourcePdo->query("SELECT * FROM `$table` LIMIT 5")->fetchAll();
        foreach ($rows as $r) {
            print_r($r);
        }
        echo "----------------------------------------\n";
    }
}

