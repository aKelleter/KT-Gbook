<?php
declare(strict_types=1);

namespace App\Config;

final class Config
{
    public static function get(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? $default;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = $_ENV[$key] ?? null;

        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public static function path(string $relativePath): string
    {
        return BASE_PATH . '/' . ltrim($relativePath, '/');
    }

    public static function envBool(string $key, bool $default = false): bool
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;

        if ($value === null) {
            return $default;
        }

        $value = strtolower(trim((string) $value));

        return in_array($value, ['1', 'true', 'on', 'yes'], true);
    }

    public static function envInt(string $key, int $default = 0, ?int $min = null): int
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;

        if ($value === null || trim((string) $value) === '') {
            return $default;
        }

        $intValue = (int) $value;

        if ($min !== null && $intValue < $min) {
            return $min;
        }

        return $intValue;
    }
}
