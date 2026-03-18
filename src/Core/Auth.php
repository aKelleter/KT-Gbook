<?php
declare(strict_types=1);

namespace App\Core;

use App\Repository\UserRepository;

final class Auth
{
    public static function attempt(string $email, string $password): bool
    {
        $repo = new UserRepository();
        $user = $repo->findByEmail($email);

        if (!$user) {
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'email' => (string) $user['email'],
            'role' => (string) $user['role'],
        ];

        return true;
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']);
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function id(): ?int
    {
        return $_SESSION['user']['id'] ?? null;
    }

    public static function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        session_destroy();
    }
}
