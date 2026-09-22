<?php

declare(strict_types=1);

foreach ([__DIR__ . '/../vendor/autoload.php', dirname(__DIR__, 3) . '/autoload.php'] as $autoload) {
    if (is_file($autoload)) {
        require_once $autoload;
        break;
    }
}
spl_autoload_register(static function (string $class): void {
    $prefix = 'GAYA\\Typo3Coder\\';
    if (str_starts_with($class, $prefix)) {
        $path = __DIR__ . '/../src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($path)) {
            require $path;
        }
    }
});
