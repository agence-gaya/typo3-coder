<?php

declare(strict_types=1);

namespace GAYA\Typo3Coder\Configuration;

use Closure;
use UnexpectedValueException;

final class Overrides
{
    public static function apply(string $tool, object $configuration, ProjectContext $context): object
    {
        $file = $context->overrideFile($tool);
        // When coder is the root package, its shared configuration is not an override.
        if (!is_file($file) || realpath($file) === realpath(__DIR__ . '/../../build/' . $tool . '.php')) {
            return $configuration;
        }
        // Preserve the variable scope of the historical project.php files.
        $config = $rectorConfigBuilder = $fractorConfigBuilder = $configuration;
        $result = require $file;
        if ($result instanceof Closure) {
            $result = $result($configuration, $context);
            if ($result === null) {
                return $configuration;
            }
        } elseif ($result === 1 || $result === null) {
            $result = match ($tool) {
                'rector' => $rectorConfigBuilder,
                'fractor' => $fractorConfigBuilder,
                default => $config,
            };
        }
        if (!$result instanceof ($configuration::class)) {
            throw new UnexpectedValueException('Invalid configuration returned by ' . $file);
        }
        return $result;
    }
}
