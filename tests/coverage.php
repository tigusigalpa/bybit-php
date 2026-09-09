<?php

declare(strict_types=1);

if (!function_exists('xdebug_start_code_coverage')) {
    fwrite(STDERR, "Xdebug with coverage mode is required.\n");
    exit(2);
}

xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);

require __DIR__ . '/run.php';

$coverage = xdebug_get_code_coverage();
xdebug_stop_code_coverage();

$root = realpath(__DIR__ . '/..');
if ($root === false) {
    throw new RuntimeException('Cannot resolve project root.');
}

$root = str_replace('\\', '/', $root);
$sourcePrefix = $root . '/src/';
$files = [];

foreach ($coverage as $file => $lines) {
    $file = str_replace('\\', '/', $file);

    if (strpos($file, $sourcePrefix) !== 0) {
        continue;
    }

    $relativeFile = substr($file, strlen($root) + 1);
    $statements = [];

    foreach ($lines as $number => $hits) {
        if ($number > 0 && $hits >= 0) {
            $statements[(int) $number] = (int) $hits;
        }
    }

    if ($statements !== []) {
        ksort($statements, SORT_NUMERIC);
        $files[$relativeFile] = $statements;
    }
}

if ($files === []) {
    throw new RuntimeException('No source code was captured by Xdebug.');
}

ksort($files, SORT_STRING);
$timestamp = (string) time();
$xml = [
    '<?xml version="1.0" encoding="UTF-8"?>',
    '<coverage generated="' . $timestamp . '">',
    '  <project timestamp="' . $timestamp . '" name="bybit-php">',
];

foreach ($files as $file => $statements) {
    $xml[] = '    <file name="' . htmlspecialchars($file, ENT_XML1 | ENT_COMPAT, 'UTF-8') . '">';

    foreach ($statements as $number => $hits) {
        $xml[] = '      <line num="' . $number . '" type="stmt" count="' . $hits . '"/>';
    }

    $xml[] = '    </file>';
}

$xml[] = '  </project>';
$xml[] = '</coverage>';

file_put_contents($root . '/coverage.xml', implode(PHP_EOL, $xml) . PHP_EOL);

echo "Coverage report written to coverage.xml.\n";
