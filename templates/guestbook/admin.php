<?php use App\Core\View; ?>

<?php if (!empty($flash)): ?>
    <div class="alert alert-<?= View::e($flash['type']) ?> shadow-sm mb-4">
        <?= View::e($flash['message']) ?>
    </div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card stat-card shadow-soft border-0">
            <div class="card-body">
                <div class="stat-label">En attente</div>
                <div class="stat-value"><?= (int) ($counts['pending'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card shadow-soft border-0">
            <div class="card-body">
                <div class="stat-label">Approuvés</div>
                <div class="stat-value"><?= (int) ($counts['approved'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card shadow-soft border-0">
            <div class="card-body">
                <div class="stat-label">Refusés</div>
                <div class="stat-value"><?= (int) ($counts['rejected'] ?? 0) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card app-card shadow-soft border-0">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div>
                <h1 class="h4 app-section-title mb-1">Administration du livre d'or</h1>
                <p class="app-muted mb-0"><?= (int) $totalEntries ?> résultat(s)</p>
            </div>
        </div>

        <form method="get" class="row g-3 mb-4">
            <input type="hidden" name="action" value="admin">
            <div class="col-lg-6">
                <input type="text" class="form-control app-input" name="search" placeholder="Rechercher un nom, une ville ou un message" value="<?= View::e((string) $search) ?>">
            </div>
            <div class="col-lg-3">
                <select class="form-select app-input" name="status">
                    <option value="">Tous les statuts</option>
                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>En attente</option>
                    <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Approuvés</option>
                    <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Refusés</option>
                </select>
            </div>
            <div class="col-lg-3 d-grid">
                <button type="submit" class="btn btn-bengalis">Filtrer</button>
            </div>
        </form>

        <?php if (empty($entries)): ?>
            <div class="empty-state text-center py-5">
                <div class="empty-state-icon mb-3"><i class="bi bi-inbox"></i></div>
                <p class="mb-0">Aucun message trouvé.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table app-table align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Auteur</th>
                        <th>Message</th>
                        <th>Statut</th>
                        <th>Date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($entries as $entry): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= View::e((string) $entry['author_name']) ?></div>
                                <div class="small app-muted"><?= View::e((string) ($entry['city'] ?? '')) ?></div>
                                <?php if (!empty($entry['author_email'])): ?>
                                    <div class="small app-muted"><?= View::e((string) $entry['author_email']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="entry-excerpt" title="<?= View::e((string) $entry['message']) ?>">
                                    <?= View::e(View::excerpt((string) $entry['message'], 180)) ?>
                                </div>
                                <?php if ((int) ($entry['is_featured'] ?? 0) === 1): ?>
                                    <span class="badge badge-featured mt-2">Mis en avant</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= View::statusBadgeClass((string) $entry['status']) ?>">
                                    <?= View::e(View::statusLabel((string) $entry['status'])) ?>
                                </span>
                            </td>
                            <td class="small"><?= View::e((string) $entry['created_at']) ?></td>
                            <td>
                                <div class="d-flex flex-column flex-lg-row justify-content-end gap-2">
                                    <form method="post" action="?action=approve_entry">
                                        <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
                                        <input type="hidden" name="id" value="<?= (int) $entry['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-success">Approuver</button>
                                    </form>

                                    <form method="post" action="?action=reject_entry">
                                        <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
                                        <input type="hidden" name="id" value="<?= (int) $entry['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">Refuser</button>
                                    </form>

                                    <form method="post" action="?action=feature_entry">
                                        <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
                                        <input type="hidden" name="id" value="<?= (int) $entry['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-warning">Mettre en avant</button>
                                    </form>

                                    <form method="post" action="?action=delete_entry" class="js-confirm-delete">
                                        <input type="hidden" name="_csrf" value="<?= View::e($csrf) ?>">
                                        <input type="hidden" name="id" value="<?= (int) $entry['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if (($totalPages ?? 1) > 1): ?>
            <nav class="mt-4" aria-label="Pagination admin">
                <ul class="pagination app-pagination mb-0">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i === (int) $page ? 'active' : '' ?>">
                            <a class="page-link" href="?action=admin&page=<?= $i ?>&search=<?= urlencode((string) $search) ?>&status=<?= urlencode((string) $status) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>
