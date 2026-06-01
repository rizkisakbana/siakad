<?php

require __DIR__ . '/../config/database.php';

$tablesResult = mysqli_query(
    $conn,
    "SELECT TABLE_NAME FROM information_schema.tables WHERE table_schema = DATABASE()"
);

$knownTables = [];
while ($row = mysqli_fetch_assoc($tablesResult)) {
    $knownTables[strtolower($row['TABLE_NAME'])] = true;
}

$root = realpath(__DIR__ . '/..');
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

$patterns = [
    '/\bFROM\s+`?([a-zA-Z_][a-zA-Z0-9_]*)`?/i',
    '/\bJOIN\s+`?([a-zA-Z_][a-zA-Z0-9_]*)`?/i',
    '/\bUPDATE\s+`?([a-zA-Z_][a-zA-Z0-9_]*)`?/i',
    '/\bINSERT\s+INTO\s+`?([a-zA-Z_][a-zA-Z0-9_]*)`?/i',
    '/\bDELETE\s+FROM\s+`?([a-zA-Z_][a-zA-Z0-9_]*)`?/i',
];

$ignore = [
    'information_schema' => true,
    'mysql' => true,
    'performance_schema' => true,
];

$missing = [];

foreach ($iterator as $file) {
    if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    if (strpos($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR) !== false) {
        continue;
    }

    $content = file_get_contents($path);
    foreach ($patterns as $pattern) {
        if (!preg_match_all($pattern, $content, $matches)) {
            continue;
        }

        foreach ($matches[1] as $table) {
            $normalized = strtolower($table);
            if (isset($knownTables[$normalized]) || isset($ignore[$normalized])) {
                continue;
            }

            $relative = str_replace($root . DIRECTORY_SEPARATOR, '', $path);
            $missing[$normalized][$relative] = true;
        }
    }
}

if (!$missing) {
    echo "PHP_TABLE_REFERENCES_OK\n";
    exit;
}

ksort($missing);
foreach ($missing as $table => $files) {
    echo "{$table}: " . implode(', ', array_keys($files)) . "\n";
}
