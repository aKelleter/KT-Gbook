<?php
declare(strict_types=1);

namespace App\Core;

final class LoginRateLimiter
{
    public static function tooManyAttempts(string $ipHash, int $maxAttempts, int $minutes): bool
    {
        return self::countRecent($ipHash, $minutes) >= $maxAttempts;
    }

    public static function record(string $ipHash): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO login_attempts (ip_hash, attempted_at) VALUES (:ip_hash, :attempted_at)'
        );
        $stmt->execute([
            'ip_hash'      => $ipHash,
            'attempted_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private static function countRecent(string $ipHash, int $minutes): int
    {
        $since = date('Y-m-d H:i:s', time() - ($minutes * 60));

        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM login_attempts WHERE ip_hash = :ip_hash AND attempted_at >= :since'
        );
        $stmt->execute([
            'ip_hash' => $ipHash,
            'since'   => $since,
        ]);

        return (int) $stmt->fetchColumn();
    }
}
