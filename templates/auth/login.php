<?php use App\Core\View; ?>

<div class="row justify-content-center">
    <div class="col-lg-5 col-xl-4">
        <div class="card app-card shadow-soft border-0">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="app-auth-icon mb-3"><i class="bi bi-person-lock"></i></div>
                    <h1 class="h3 app-title mb-2">Connexion administration</h1>
                    <p class="app-muted mb-0">Modération du livre d'or des Bengalis.</p>
                </div>

                <?php if (!empty($flash)): ?>
                    <div class="alert alert-<?= View::e($flash['type']) ?>">
                        <?= View::e($flash['message']) ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="?action=login_submit">
                    <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">

                    <div class="mb-3">
                        <label for="email" class="form-label app-label">Email</label>
                        <input type="email" class="form-control app-input" id="email" name="email" required>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label app-label">Mot de passe</label>
                        <input type="password" class="form-control app-input" id="password" name="password" required>
                    </div>

                    <button type="submit" class="btn btn-bengalis w-100 fw-semibold">Se connecter</button>
                </form>
            </div>
        </div>
    </div>
</div>
