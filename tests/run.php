<?php

declare(strict_types=1);

$failed = false;

foreach (glob(__DIR__ . '/*.php') as $file) {
    if (in_array(basename($file), ['run.php', 'TestRunner.php'], true)) {
        continue;
    }

    echo PHP_EOL . '=== ' . basename($file) . ' ===' . PHP_EOL;
    passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($file), $exitCode);
    $failed = $failed || $exitCode !== 0;
}

echo PHP_EOL . ($failed ? 'SOME TESTS FAILED' : 'ALL TESTS PASSED') . PHP_EOL;
exit($failed ? 1 : 0);
