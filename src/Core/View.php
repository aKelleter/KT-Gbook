<?php
declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $template, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $viewPath = BASE_PATH . '/templates/' . $template . '.php';
        require BASE_PATH . '/templates/layout.php';
    }

    public static function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function asset(string $path): string
    {
        return 'public/assets/' . ltrim($path, '/');
    }

    public static function excerpt(?string $value, int $maxLength = 120): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        if (mb_strlen($value) <= $maxLength) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, $maxLength - 1)) . '…';
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'approved' => 'Approuvé',
            'rejected' => 'Refusé',
            default => 'En attente',
        };
    }

    public static function statusBadgeClass(string $status): string
    {
        return match ($status) {
            'approved' => 'badge-status-approved',
            'rejected' => 'badge-status-rejected',
            default    => 'badge-status-pending',
        };
    }
}
