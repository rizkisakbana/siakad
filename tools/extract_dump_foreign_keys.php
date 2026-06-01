<?php

if ($argc < 3) {
    fwrite(STDERR, "Usage: php tools/extract_dump_foreign_keys.php <input.sql> <output.sql>\n");
    exit(1);
}

$input = $argv[1];
$output = $argv[2];

$in = fopen($input, 'rb');
if (!$in) {
    fwrite(STDERR, "Cannot open input: {$input}\n");
    exit(1);
}

$out = fopen($output, 'wb');
if (!$out) {
    fclose($in);
    fwrite(STDERR, "Cannot open output: {$output}\n");
    exit(1);
}

fwrite($out, "SET FOREIGN_KEY_CHECKS=0;\n\n");

$currentTable = null;
while (($line = fgets($in)) !== false) {
    if (preg_match('/^CREATE TABLE `([^`]+)`/i', $line, $matches)) {
        $currentTable = $matches[1];
        continue;
    }

    if ($currentTable && preg_match('/^\s*CONSTRAINT\s+`([^`]+)`\s+FOREIGN KEY\s+(.+?)(,)?\s*$/i', trim($line), $matches)) {
        $constraint = $matches[1];
        $definition = rtrim($matches[2], ',');
        fwrite($out, "ALTER TABLE `{$currentTable}` ADD CONSTRAINT `{$constraint}` FOREIGN KEY {$definition};\n");
        continue;
    }

    if ($currentTable && preg_match('/^\)\s+ENGINE=/i', $line)) {
        $currentTable = null;
    }
}

fwrite($out, "\nSET FOREIGN_KEY_CHECKS=1;\n");

fclose($in);
fclose($out);

echo "Foreign key restore SQL written to {$output}\n";
