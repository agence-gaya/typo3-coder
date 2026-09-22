<?php

declare(strict_types=1);

// Run with: php Tests/command-smoke.php /path/to/composer /path/to/consumer/vendor
$composer = $argv[1] ?? '/usr/local/bin/composer';
$vendor = realpath($argv[2] ?? dirname(__DIR__, 2));
require $vendor . '/autoload.php';
$root = sys_get_temp_dir() . '/coder smoke ' . bin2hex(random_bytes(5));
mkdir($root);
$manifest = [
    'name' => 'gaya/coder-smoke',
    'type' => 'typo3-cms-extension',
    'require-dev' => ['gaya/typo3-coder' => '*'],
    'config' => ['vendor-dir' => $vendor, 'bin-dir' => $vendor . '/bin', 'allow-plugins' => ['gaya/typo3-coder' => true, 'a9f/fractor-extension-installer' => true, 'typo3/cms-composer-installers' => true, 'typo3/class-alias-loader' => true, 'cweagans/composer-patches' => true, 'helhum/dotenv-connector' => true]],
];
$write = static function () use ($root, &$manifest): void {
    file_put_contents($root . '/composer.json', json_encode($manifest, JSON_THROW_ON_ERROR));
};
$run = static function (string $suite, int $expected) use ($root, $composer): void {
    $process = new Symfony\Component\Process\Process([PHP_BINARY, $composer, '--working-dir=' . $root, 'coder:tests:' . $suite, '--continuous-integration']);
    $process->setTimeout(60);
    $status = $process->run();
    if ($status !== $expected) {
        throw new RuntimeException('Expected ' . $expected . ', got ' . $status . "\n" . $process->getOutput() . $process->getErrorOutput());
    }
};
try {
    $write();
    $run('unit', 0);
    $run('functional', 0);
    mkdir($root . '/Tests/Unit', 0o777, true);
    $before = glob(sys_get_temp_dir() . '/typo3-coder-phpunit-*');
    $run('unit', 0);
    mkdir($root . '/Tests/Functional', 0o777, true);
    $run('functional', 0);
    file_put_contents($root . '/Tests/Unit/FailureTest.php', '<?php final class FailureTest extends \\PHPUnit\\Framework\\TestCase { public function testFailure(): void { self::assertTrue(false); } }');
    $run('unit', 1);
    $after = glob(sys_get_temp_dir() . '/typo3-coder-phpunit-*');
    if (array_diff($after, $before) !== []) {
        throw new RuntimeException('Temporary PHPUnit configuration leaked.');
    }
    $manifest['extra']['gaya/typo3-coder']['tests']['unit'] = ['does-not-exist'];
    $write();
    $run('unit', 1);
    echo "Command integration: 6 scenarios passed (absent suites, empty suite, failing test, invalid path); temporary files cleaned.\n";
} finally {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($root);
}
