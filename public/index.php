<?php
declare(strict_types=1);

require dirname(__DIR__) . '/config/bootstrap.php';

use App\Controller\AuthController;
use App\Controller\GuestbookController;
use App\Core\Router;

$router = new Router();

$router->get('guestbook', [GuestbookController::class, 'publicIndex'], true);
$router->post('submit_entry', [GuestbookController::class, 'submit'], true);

$router->get('login', [AuthController::class, 'login'], true);
$router->post('login_submit', [AuthController::class, 'loginSubmit'], true);
$router->get('logout', [AuthController::class, 'logout']);

$router->get('admin', [GuestbookController::class, 'adminDashboard']);
$router->post('approve_entry', [GuestbookController::class, 'approve']);
$router->post('reject_entry', [GuestbookController::class, 'reject']);
$router->post('feature_entry', [GuestbookController::class, 'toggleFeatured']);
$router->post('delete_entry', [GuestbookController::class, 'delete']);

$action = $_GET['action'] ?? 'guestbook';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$router->dispatch($method, $action);
