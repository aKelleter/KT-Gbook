<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
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

        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if (!Auth::attempt($email, $password)) {
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
