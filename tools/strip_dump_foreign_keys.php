<?php

if ($argc < 3) {
    fwrite(STDERR, "Usage: php tools/strip_dump_foreign_keys.php <input.sql> <output.sql>\n");
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

$insideCreate = false;
$buffer = [];

while (($line = fgets($in)) !== false) {
    if (!$insideCreate && preg_match('/^CREATE TABLE `/i', $line)) {
        $insideCreate = true;
        $buffer = [$line];
        continue;
    }

    if ($insideCreate) {
        $buffer[] = $line;

        if (preg_match('/^\)\s+ENGINE=/i', $line)) {
            $filtered = array_values(array_filter($buffer, static function ($candidate) {
                return !preg_match('/^\s*CONSTRAINT\s+`/i', $candidate);
            }));

            for ($i = count($filtered) - 2; $i >= 0; $i--) {
                if (trim($filtered[$i]) === '') {
                    continue;
                }

                $filtered[$i] = preg_replace('/,\s*(\r?\n)$/', '$1', $filtered[$i]);
                break;
            }

            foreach ($filtered as $filteredLine) {
                fwrite($out, $filteredLine);
            }

            $insideCreate = false;
            $buffer = [];
        }

        continue;
    }

    fwrite($out, $line);
}

foreach ($buffer as $remainingLine) {
    fwrite($out, $remainingLine);
}

fclose($in);
fclose($out);

echo "Foreign key constraints stripped into {$output}\n";
