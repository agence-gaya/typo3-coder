<?php

declare(strict_types=1);

// Run with: php Tests/yaml-smoke.php /path/to/composer /path/to/consumer/vendor
$composer = $argv[1];
$vendor = realpath($argv[2]);
$bin = $vendor . '/bin';
require $vendor . '/autoload.php';
$root = sys_get_temp_dir() . '/coder yaml ' . bin2hex(random_bytes(5));
mkdir($root);
$manifest = [
    'name' => 'gaya/coder-yaml-smoke',
    'type' => 'typo3-cms-extension',
    'require-dev' => ['gaya/typo3-coder' => '*'],
    'config' => ['vendor-dir' => $vendor, 'bin-dir' => $bin, 'allow-plugins' => ['gaya/typo3-coder' => true, 'a9f/fractor-extension-installer' => true, 'typo3/cms-composer-installers' => true, 'typo3/class-alias-loader' => true, 'cweagans/composer-patches' => true, 'helhum/dotenv-connector' => true]],
];
$run = static function (bool $ci, int $expected) use ($root, $composer): void {
    $arguments = [PHP_BINARY, $composer, '--working-dir=' . $root, 'coder:yaml-lint'];
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
    mkdir($root . '/Configuration', 0o777, true);
    mkdir($root . '/.build', 0o777, true);
    mkdir($root . '/build', 0o777, true);
    file_put_contents($root . '/.build/bad.yaml', 'broken: [');
    file_put_contents($root . '/build/bad.yml', 'broken: [');
    $file = $root . '/Configuration/services.yaml';
    file_put_contents($file, "services: {}\n");
    $other = $root . '/Configuration/settings.yml';
    file_put_contents($other, "enabled: true\n");
    $run(true, 0);
    file_put_contents($other, 'broken: [');
    $run(false, 1);
    $run(true, 1);
    file_put_contents($other, "enabled: true\n");
    file_put_contents($file, 'broken: [');
    $run(true, 1);
    echo "YAML command: 5 scenarios passed (empty, valid with excluded files, invalid .yml locally/in CI, invalid .yaml).\n";
} finally {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($root);
}
