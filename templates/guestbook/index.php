<?php use App\Core\View; ?>
<?php use App\Config\Config; ?>

<?php
    $flashAutoHide = Config::envBool('FLASH_AUTOHIDE', true);
    $flashAutoHideDelay = Config::envInt('FLASH_AUTOHIDE_DELAY_MS', 3000, 0);
    $titre = Config::get('APP_NAME', 'Livre d\'Or');
    $messageIntro = Config::get('APP_INTRO', '');
    $flashType = $flash['type'] ?? '';
    $hasDangerFlash = !empty($flash) && $flashType === 'danger';
    $canAutoHide = $flashAutoHide && in_array($flashType, ['success', 'info', 'warning'], true);
?>

<section class="hero-card shadow-soft mb-4 mb-lg-5">
    <div class="row g-4 align-items-center">
        <div class="col-lg-7">
            <h1 class="hero-title text-center"><?= View::e($titre) ?></h1>
            <div class="d-flex flex-wrap gap-2 justify-content-center mt-3">
                <button type="button" class="btn btn-bengalis" data-bs-toggle="modal" data-bs-target="#signModal">
                    <i class="bi bi-pencil-square me-2"></i>Signer le livre d'or
                </button>               
            </div>
        </div>
        <div class="col-lg-5">
            <div class="royal-card h-100">                
                <div class="royal-card-body">
                    <p class="hero-text mt-4">
                        <?= View::e($messageIntro) ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if (!empty($flash) && !$hasDangerFlash): ?>
    <div
        class="alert text-center alert-<?= View::e($flashType) ?> shadow-sm app-flash mb-4"
        role="alert"
        data-flash-message="1"
        data-autohide="<?= $canAutoHide ? 'true' : 'false' ?>"
        data-delay="<?= $flashAutoHideDelay ?>"
    >
        <?= View::e($flash['message']) ?>
    </div>
<?php endif; ?>

<div class="card app-card shadow-soft border-0" id="messages">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-4">
            <div>
                <h2 class="h4 app-section-title mb-1">Messages publiés</h2>
                <p class="app-muted mb-0"><?= (int) $totalEntries ?> message(s) visible(s)</p>
            </div>
            <button type="button" class="btn btn-bengalis btn-sm" data-bs-toggle="modal" data-bs-target="#signModal">
                <i class="bi bi-pencil-square me-2"></i>Signer
            </button>
        </div>

        <?php if (empty($entries)): ?>
            <div class="empty-state text-center py-5">
                <div class="empty-state-icon mb-3"><i class="bi bi-chat-heart"></i></div>
                <p class="mb-0">Aucun message publié pour le moment.</p>
            </div>
        <?php else: ?>
            <div class="guestbook-list d-flex flex-column gap-3">
                <?php foreach ($entries as $entry): ?>
                    <article class="guestbook-entry <?= (int) ($entry['is_featured'] ?? 0) === 1 ? 'is-featured' : '' ?>">
                        <div class="d-flex justify-content-between gap-3 flex-wrap mb-2">
                            <div>
                                <h3 class="h6 mb-1"><?= View::e((string) $entry['author_name']) ?></h3>
                                <div class="small app-muted">
                                    <?= View::e((string) ($entry['city'] ?? '')) ?>
                                </div>
                            </div>
                            <div class="text-end small app-muted">
                                <?= View::e((string) $entry['created_at']) ?>
                                <?php if ((int) ($entry['is_featured'] ?? 0) === 1): ?>
                                    <div><span class="badge badge-featured">Mis en avant</span></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <p class="mb-0 guestbook-message"><?= nl2br(View::e((string) $entry['message'])) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (($totalPages ?? 1) > 1): ?>
            <nav class="mt-4" aria-label="Pagination des messages">
                <ul class="pagination app-pagination mb-0">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i === (int) $page ? 'active' : '' ?>">
                            <a class="page-link" href="?action=guestbook&page=<?= $i ?>#messages"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<!-- Modale Signature -->
<div class="modal fade" id="signModal" tabindex="-1" aria-labelledby="signModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content app-card border-0 shadow-soft">
            <div class="modal-header border-0 pb-0">
                <h2 class="modal-title h4 app-section-title mb-0" id="signModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>Signer le livre d'or
                </h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body pt-3">

                <?php if ($hasDangerFlash): ?>
                    <div
                        class="alert alert-danger shadow-sm app-flash"
                        role="alert"
                        data-flash-message="1"
                        data-autohide="false"
                    >
                        <?= View::e($flash['message']) ?>
                    </div>
                <?php endif; ?>

                <p class="app-muted mb-1">Votre message sera validé avant publication.</p>
                <p class="small app-muted mb-4">Protection anti-spam : champ piège, délai minimum, limitation d'envois et captcha optionnel.</p>

                <form method="post" action="?action=submit_entry">
                    <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">

                    <div class="app-honeypot" aria-hidden="true">
                        <label for="website" class="form-label">Ne pas remplir</label>
                        <input type="text" name="website" id="website" class="form-control" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="mb-3">
                        <label for="author_name" class="form-label app-label">Nom</label>
                        <input type="text" class="form-control app-input" id="author_name" name="author_name" required maxlength="120">
                    </div>

                    <div class="mb-3">
                        <label for="author_email" class="form-label app-label">Email</label>
                        <input type="email" class="form-control app-input" id="author_email" name="author_email" required maxlength="190">
                    </div>

                    <div class="mb-3">
                        <label for="city" class="form-label app-label">Ville <span class="app-muted">(optionnel)</span></label>
                        <input type="text" class="form-control app-input" id="city" name="city" maxlength="120">
                    </div>

                    <div class="mb-3">
                        <label for="message" class="form-label app-label">Message</label>
                        <textarea class="form-control app-input app-textarea" id="message" name="message" rows="6" required maxlength="3000"></textarea>
                    </div>

                    <?php if (!empty($turnstileEnabled) && !empty($turnstileSiteKey)): ?>
                        <div class="mb-4">
                            <div class="turnstile-wrap">
                                <div class="cf-turnstile" data-sitekey="<?= View::e((string) $turnstileSiteKey) ?>"></div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-bengalis w-100 fw-semibold">
                        <i class="bi bi-send me-2"></i>Envoyer le message
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if ($hasDangerFlash): ?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var signModal = new bootstrap.Modal(document.getElementById('signModal'));
        signModal.show();
    });
</script>
<?php endif; ?>
