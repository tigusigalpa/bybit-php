<?php

declare(strict_types=1);

if (PHP_VERSION_ID < 80300) {
    fwrite(STDERR, "Pest unit tests require PHP 8.3 or later. Run composer test for PHP 7.4-8.2 compatibility checks.\n");
    exit(2);
}

$testDirectory = __DIR__;
$binary = $testDirectory . '/vendor/bin/pest';
$projectRoot = realpath($testDirectory . '/../..');

if (!is_file($binary)) {
    fwrite(STDERR, "Pest dependencies are not installed. Run: composer install --working-dir=tests/pest\n");
    exit(2);
}

if ($projectRoot === false || !chdir($projectRoot)) {
    fwrite(STDERR, "Cannot change to the project root.\n");
    exit(2);
}

$arguments = array_merge([
    PHP_BINARY,
    $binary,
    '--test-directory=..',
    '--configuration=' . $testDirectory . '/phpunit.xml',
    '--colors=always',
], array_slice($argv, 1));

$command = implode(' ', array_map('escapeshellarg', $arguments));
passthru($command, $exitCode);

exit($exitCode);
