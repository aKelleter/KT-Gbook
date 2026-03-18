<?php
declare(strict_types=1);

namespace App\Controller;

use App\Config\Config;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Flash;
use App\Core\Response;
use App\Core\Turnstile;
use App\Core\View;
use App\Repository\EntryRepository;

final class GuestbookController
{
    public function publicIndex(): void
    {
        $repo = new EntryRepository();
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(1, min(50, (int) Config::get('ENTRIES_PER_PAGE', 8)));

        $totalEntries = $repo->countPublicApproved();
        $totalPages = max(1, (int) ceil($totalEntries / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $_SESSION['guestbook_form_started_at'] = time();

        $offset = ($page - 1) * $perPage;
        $entries = $repo->findPublicApprovedPaginated($perPage, $offset);

        View::render('guestbook/index', [
            'csrf' => Csrf::token(),
            'flash' => Flash::get(),
            'entries' => $entries,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalEntries' => $totalEntries,
            'turnstileEnabled' => Turnstile::isEnabled(),
            'turnstileSiteKey' => Turnstile::siteKey(),
        ]);
    }

    public function submit(): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            Flash::set('danger', 'Jeton CSRF invalide.');
            Response::redirect('?action=guestbook#form');
        }

        if (trim((string) ($_POST['website'] ?? '')) !== '') {
            Flash::set('danger', 'Soumission refusée.');
            Response::redirect('?action=guestbook#form');
        }

        $startedAt = (int) ($_SESSION['guestbook_form_started_at'] ?? 0);
        $minSeconds = max(1, (int) Config::get('GUESTBOOK_MIN_SECONDS', 4));
        if ($startedAt > 0 && (time() - $startedAt) < $minSeconds) {
            Flash::set('danger', 'Soumission trop rapide. Merci de relire votre message puis de réessayer.');
            Response::redirect('?action=guestbook#form');
        }

        $ipHash = hash('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        $repo = new EntryRepository();
        $floodWindowMinutes = max(1, (int) Config::get('GUESTBOOK_RATE_LIMIT_MINUTES', 15));
        $floodMaxSubmissions = max(1, (int) Config::get('GUESTBOOK_RATE_LIMIT_MAX_SUBMISSIONS', 3));
        $recentSubmissions = $repo->countRecentByIpHash($ipHash, $floodWindowMinutes);

        if ($recentSubmissions >= $floodMaxSubmissions) {
            Flash::set('danger', 'Trop de messages envoyés récemment depuis cette connexion. Merci de réessayer un peu plus tard.');
            Response::redirect('?action=guestbook#form');
        }

        if (Turnstile::isEnabled()) {
            $turnstileResult = Turnstile::verify(
                $_POST['cf-turnstile-response'] ?? null,
                $_SERVER['REMOTE_ADDR'] ?? null
            );

            if (!$turnstileResult['success']) {
                Flash::set('danger', $turnstileResult['message'] ?? 'La vérification anti-spam a échoué.');
                Response::redirect('?action=guestbook#form');
            }
        }

        $authorName = trim((string) ($_POST['author_name'] ?? ''));
        $authorEmail = trim((string) ($_POST['author_email'] ?? ''));
        $city = trim((string) ($_POST['city'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));

        if ($authorName === '' || mb_strlen($authorName) < 2) {
            Flash::set('danger', 'Le nom est obligatoire.');
            Response::redirect('?action=guestbook#form');
        }

        if ($authorEmail === '') {
            Flash::set('danger', 'L’adresse email est obligatoire.');
            Response::redirect('?action=guestbook#form');
        }

        if ($message === '' || mb_strlen($message) < 10) {
            Flash::set('danger', 'Le message est trop court.');
            Response::redirect('?action=guestbook#form');
        }

        if ($authorEmail !== '' && filter_var($authorEmail, FILTER_VALIDATE_EMAIL) === false) {
            Flash::set('danger', 'L’adresse email n’est pas valide.');
            Response::redirect('?action=guestbook#form');
        }

        $repo->create([
            'author_name' => mb_substr($authorName, 0, 120),
            'author_email' => $authorEmail !== '' ? mb_substr($authorEmail, 0, 190) : null,
            'city' => $city !== '' ? mb_substr($city, 0, 120) : null,
            'message' => mb_substr($message, 0, 3000),
            'status' => 'pending',
            'is_featured' => 0,
            'ip_hash' => $ipHash,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        unset($_SESSION['guestbook_form_started_at']);

        Flash::set('success', 'Merci pour votre message. Il sera publié après validation.');
        Response::redirect('?action=guestbook#form');
    }

    public function adminDashboard(): void
    {
        $repo = new EntryRepository();
        $search = trim((string) ($_GET['search'] ?? ''));
        $status = trim((string) ($_GET['status'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = max(1, min(100, (int) Config::get('ADMIN_ENTRIES_PER_PAGE', 12)));

        $totalEntries = $repo->countAdmin($search, $status !== '' ? $status : null);
        $totalPages = max(1, (int) ceil($totalEntries / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $offset = ($page - 1) * $perPage;
        $entries = $repo->findAdminPaginated($perPage, $offset, $search, $status !== '' ? $status : null);

        View::render('guestbook/admin', [
            'csrf' => Csrf::token(),
            'flash' => Flash::get(),
            'entries' => $entries,
            'counts' => $repo->countsByStatus(),
            'search' => $search,
            'status' => $status,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalEntries' => $totalEntries,
            'user' => Auth::user(),
        ]);
    }

    public function editForm(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $repo = new EntryRepository();
        $entry = $repo->findById($id);

        if (!$entry) {
            Flash::set('danger', 'Message introuvable.');
            Response::redirect('?action=admin');
        }

        View::render('guestbook/edit', [
            'csrf'  => Csrf::token(),
            'flash' => Flash::get(),
            'entry' => $entry,
        ]);
    }

    public function editSubmit(): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            Flash::set('danger', 'Jeton CSRF invalide.');
            Response::redirect('?action=admin');
        }

        $id = (int) ($_POST['id'] ?? 0);
        $repo = new EntryRepository();
        $entry = $repo->findById($id);

        if (!$entry) {
            Flash::set('danger', 'Message introuvable.');
            Response::redirect('?action=admin');
        }

        $authorName  = trim((string) ($_POST['author_name'] ?? ''));
        $authorEmail = trim((string) ($_POST['author_email'] ?? ''));
        $city        = trim((string) ($_POST['city'] ?? ''));
        $message     = trim((string) ($_POST['message'] ?? ''));

        if ($authorName === '' || mb_strlen($authorName) < 2) {
            Flash::set('danger', 'Le nom est obligatoire (min. 2 caractères).');
            Response::redirect('?action=edit_entry&id=' . $id);
        }

        if ($message === '' || mb_strlen($message) < 10) {
            Flash::set('danger', 'Le message est trop court (min. 10 caractères).');
            Response::redirect('?action=edit_entry&id=' . $id);
        }

        if ($authorEmail !== '' && filter_var($authorEmail, FILTER_VALIDATE_EMAIL) === false) {
            Flash::set('danger', 'L\'adresse email n\'est pas valide.');
            Response::redirect('?action=edit_entry&id=' . $id);
        }

        $repo->update($id, [
            'author_name'  => mb_substr($authorName, 0, 120),
            'author_email' => $authorEmail !== '' ? mb_substr($authorEmail, 0, 190) : null,
            'city'         => $city !== '' ? mb_substr($city, 0, 120) : null,
            'message'      => mb_substr($message, 0, 3000),
        ]);

        Flash::set('success', 'Message modifié avec succès.');
        Response::redirect('?action=admin');
    }

    public function approve(): void
    {
        $this->updateStatus('approved', 'Message approuvé.');
    }

    public function reject(): void
    {
        $this->updateStatus('rejected', 'Message refusé.');
    }

    private function updateStatus(string $status, string $flashMessage): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            Flash::set('danger', 'Jeton CSRF invalide.');
            Response::redirect('?action=admin');
        }

        $id = (int) ($_POST['id'] ?? 0);
        $repo = new EntryRepository();
        $entry = $repo->findById($id);

        if (!$entry) {
            Flash::set('danger', 'Message introuvable.');
            Response::redirect('?action=admin');
        }

        $repo->updateStatus($id, $status, Auth::id());
        Flash::set('success', $flashMessage);
        Response::redirect('?action=admin');
    }

    public function toggleFeatured(): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            Flash::set('danger', 'Jeton CSRF invalide.');
            Response::redirect('?action=admin');
        }

        $id = (int) ($_POST['id'] ?? 0);
        $repo = new EntryRepository();

        if (!$repo->findById($id)) {
            Flash::set('danger', 'Message introuvable.');
            Response::redirect('?action=admin');
        }

        $repo->toggleFeatured($id);
        Flash::set('success', 'Mise en avant mise à jour.');
        Response::redirect('?action=admin');
    }

    public function delete(): void
    {
        if (!Csrf::validate($_POST['_csrf'] ?? null)) {
            Flash::set('danger', 'Jeton CSRF invalide.');
            Response::redirect('?action=admin');
        }

        $id = (int) ($_POST['id'] ?? 0);
        $repo = new EntryRepository();

        if (!$repo->findById($id)) {
            Flash::set('danger', 'Message introuvable.');
            Response::redirect('?action=admin');
        }

        $repo->delete($id);
        Flash::set('success', 'Message supprimé.');
        Response::redirect('?action=admin');
    }
}
