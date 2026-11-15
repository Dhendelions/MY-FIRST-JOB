<?php

$dir = dirname(__DIR__) . '/app';
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

$rawTables = [];
foreach ($rii as $file) {
    if ($file->isDir() || $file->getExtension() !== 'php') continue;
    $content = file_get_contents($file->getPathname());

    // Match DB::table('...')
    if (preg_match_all("/(?:DB::|db\(\)->)table\(\s*['\"]([^'\"]+)['\"]\s*\)/i", $content, $m)) {
        foreach ($m[1] as $t) $rawTables[$t][] = $file->getFilename();
    }

    // Match raw SQL FROM / JOIN / INTO / UPDATE
    if (preg_match_all("/(?:FROM|JOIN|INTO|UPDATE)\s+[`]?([a-zA-Z0-9_]+)[`]?/i", $content, $m)) {
        foreach ($m[1] as $t) {
            $upper = strtoupper($t);
            if (in_array($upper, ['SELECT', 'WHERE', 'SET', 'VALUES', 'DUAL'])) continue;
            $rawTables[$t][] = $file->getFilename();
        }
    }
}

echo "=== TABLES REFERENCED IN APP/ ===\n";
foreach ($rawTables as $t => $files) {
    echo sprintf("%-30s in %s\n", $t, implode(', ', array_unique($files)));
}

