<?php
declare(strict_types=1);

namespace App\Controller;

use App\Config\Config;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\LoginRateLimiter;
use App\Core\Response;
use App\Core\View;

final class AuthController
{
    public function login(): void
    {
        View::render('auth/login', [
            'csrf' => Csrf::token(),
            'flash' => Flash::get(),
        ]);
    }

    public function loginSubmit(): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            Flash::set('danger', 'Jeton CSRF invalide.');
            Response::redirect('?action=login');
        }

        $ipHash     = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? '');
        $maxAttempts = Config::envInt('LOGIN_RATE_LIMIT_MAX_ATTEMPTS', 5, 1);
        $minutes    = Config::envInt('LOGIN_RATE_LIMIT_MINUTES', 15, 1);

        if (LoginRateLimiter::tooManyAttempts($ipHash, $maxAttempts, $minutes)) {
            Flash::set('danger', "Trop de tentatives de connexion. Réessayez dans {$minutes} minutes.");
            Response::redirect('?action=login');
        }

        $email    = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if (!Auth::attempt($email, $password)) {
            LoginRateLimiter::record($ipHash);
            Flash::set('danger', 'Identifiants invalides.');
            Response::redirect('?action=login');
        }

        Flash::set('success', 'Connexion réussie.');
        Response::redirect('?action=admin');
    }

    public function logout(): void
    {
        Auth::logout();
        Flash::set('success', 'Déconnexion effectuée.');
        Response::redirect('?action=guestbook');
    }
}
