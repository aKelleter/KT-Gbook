<?php

use App\Config\Config;
use App\Core\Auth;
use App\Core\View;

$appName = Config::get('APP_NAME', "Livre d'Or - Les Bengalis de Liège");
$appVersion = Config::get('APP_VERSION', '0.1.0');
$appUpd = Config::get('APP_UPD', '00000000-0000');
$turnstileEnabled = Config::bool('TURNSTILE_ENABLED', false);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= View::e((string) $appName) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="<?= View::asset('img/favicon.svg') ?>" type="image/svg+xml">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= View::asset('css/app.css') ?>">
    <?php if ($turnstileEnabled): ?>
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <?php endif; ?>
</head>
<body class="app-body">
<div class="app-shell">
    <nav class="navbar navbar-expand-lg app-navbar app-navbar-fixed">
        <div class="container">
            <a class="navbar-brand app-brand d-flex align-items-center gap-3" href="?action=guestbook">
                <img src="<?= View::asset('img/favicon.svg') ?>" alt="Logo" class="app-logo">
                <span>
                    <small class="d-block app-brand-kicker">Chœur Royal</small>
                    <?= View::e((string) $appName) ?>
                </span>
            </a>

            <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                <a href="?action=guestbook" class="btn btn-outline-bengalis btn-sm">Livre d'or</a>
                <?php if (Auth::check()): ?>
                    <a href="?action=admin" class="btn btn-outline-bengalis btn-sm">Administration</a>
                    <span class="small app-user-email"><?= View::e(Auth::user()['email'] ?? '') ?></span>
                    <a href="?action=logout" class="btn btn-bengalis btn-sm">Déconnexion</a>
                <?php else: ?>
                    <a href="?action=login" class="btn btn-bengalis btn-sm">Connexion admin</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="app-main py-4 py-md-5">
        <div class="container">
            <?php require $viewPath; ?>
        </div>
    </main>

    <footer class="app-footer">
        <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span><?= View::e((string) $appName) ?></span>
            <span>Version <?= View::e((string) $appVersion) ?> · <?= View::e((string) $appUpd) ?></span>
        </div>
    </footer>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script defer src="<?= View::asset('js/app.js') ?>"></script>
</body>
</html>
