<?php

declare(strict_types=1);

// Run with: php Tests/typoscript-smoke.php /path/to/composer /path/to/consumer/vendor [/path/to/linter/bin]
$composer = $argv[1];
$vendor = realpath($argv[2]);
$bin = $argv[3] ?? $vendor . '/bin';
require $vendor . '/autoload.php';
$root = sys_get_temp_dir() . '/coder typoscript ' . bin2hex(random_bytes(5));
mkdir($root);
$manifest = [
    'name' => 'gaya/coder-typoscript-smoke',
    'type' => 'typo3-cms-extension',
    'require-dev' => ['gaya/typo3-coder' => '*'],
    'config' => ['vendor-dir' => $vendor, 'bin-dir' => $bin, 'allow-plugins' => ['gaya/typo3-coder' => true, 'a9f/fractor-extension-installer' => true, 'typo3/cms-composer-installers' => true, 'typo3/class-alias-loader' => true, 'cweagans/composer-patches' => true, 'helhum/dotenv-connector' => true]],
];
$run = static function (bool $ci, int $expected) use ($root, $composer): void {
    $arguments = [PHP_BINARY, $composer, '--working-dir=' . $root, 'coder:typoscript-lint'];
    if ($ci) {
        $arguments[] = '--continuous-integration';
    }
    $process = new Symfony\Component\Process\Process($arguments, null, ['COMPOSER' => false]);
    $process->setTimeout(60);
    $status = $process->run();
    if ($status !== $expected) {
        throw new RuntimeException('Expected ' . $expected . ', got ' . $status . "\n" . $process->getOutput() . $process->getErrorOutput());
    }
};
try {
    file_put_contents($root . '/composer.json', json_encode($manifest, JSON_THROW_ON_ERROR));
    $run(true, 0);
    mkdir($root . '/Configuration/TypoScript', 0o777, true);
    mkdir($root . '/.build', 0o777, true);
    mkdir($root . '/Resources/Public', 0o777, true);
    file_put_contents($root . '/.build/bad.typoscript', 'page {');
    file_put_contents($root . '/Resources/Public/main.ts', 'const message: string = "TypeScript";');
    $file = $root . '/Configuration/TypoScript/setup.typoscript';
    file_put_contents($file, "page = PAGE\npage {\n  10 = TEXT\n}\n");
    $run(true, 0);
    file_put_contents($file, "page = PAGE\npage {\n 10 = TEXT\n}\n");
    $run(false, 0);
    $run(true, 2);
    file_put_contents($file, 'page {');
    $run(false, 2);
    echo "TypoScript command: 5 scenarios passed (empty, valid with excluded files, warning locally/in CI, syntax error).\n";
} finally {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($root);
}
