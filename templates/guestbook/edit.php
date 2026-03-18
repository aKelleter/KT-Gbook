<?php use App\Core\View; ?>

<div class="row justify-content-center">
    <div class="col-lg-8 col-xl-7">
        <div class="card app-card shadow-soft border-0">
            <div class="card-body p-4 p-md-5">

                <div class="d-flex align-items-center gap-3 mb-4">
                    <a href="?action=admin" class="btn btn-outline-bengalis btn-sm" title="Retour à l'administration">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                    <div>
                        <h1 class="h4 app-section-title mb-0">Modifier le message</h1>
                        <p class="app-muted small mb-0">
                            #<?= (int) $entry['id'] ?> &middot;
                            <?= View::e((string) $entry['created_at']) ?>
                        </p>
                    </div>
                </div>

                <?php if (!empty($flash)): ?>
                    <div class="alert alert-<?= View::e($flash['type']) ?> mb-4">
                        <?= View::e($flash['message']) ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="?action=edit_entry">
                    <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
                    <input type="hidden" name="id" value="<?= (int) $entry['id'] ?>">

                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label for="author_name" class="form-label app-label">Nom</label>
                            <input type="text"
                                   class="form-control app-input"
                                   id="author_name"
                                   name="author_name"
                                   value="<?= View::e((string) $entry['author_name']) ?>"
                                   required
                                   maxlength="120">
                        </div>
                        <div class="col-sm-6">
                            <label for="city" class="form-label app-label">Ville <span class="app-muted">(optionnel)</span></label>
                            <input type="text"
                                   class="form-control app-input"
                                   id="city"
                                   name="city"
                                   value="<?= View::e((string) ($entry['city'] ?? '')) ?>"
                                   maxlength="120">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="author_email" class="form-label app-label">Email <span class="app-muted">(optionnel)</span></label>
                        <input type="email"
                               class="form-control app-input"
                               id="author_email"
                               name="author_email"
                               value="<?= View::e((string) ($entry['author_email'] ?? '')) ?>"
                               maxlength="190">
                    </div>

                    <div class="mb-4">
                        <label for="message" class="form-label app-label">Message</label>
                        <textarea class="form-control app-input app-textarea"
                                  id="message"
                                  name="message"
                                  rows="8"
                                  required
                                  maxlength="3000"><?= View::e((string) $entry['message']) ?></textarea>
                        <div class="small app-muted mt-1 text-end">max. 3 000 caractères</div>
                    </div>

                    <div class="d-flex gap-2 justify-content-end">
                        <a href="?action=admin" class="btn btn-admin-reject">Annuler</a>
                        <button type="submit" class="btn btn-admin-approve fw-semibold">
                            <i class="bi bi-check-lg me-1"></i>Enregistrer
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>
