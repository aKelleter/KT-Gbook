<?php
declare(strict_types=1);

require dirname(__DIR__) . '/config/bootstrap.php';

use App\Config\Config;

$dbFile = Config::path((string) Config::get('DB_DATABASE', 'database/app.sqlite'));

if (!is_dir(dirname($dbFile))) {
    mkdir(dirname($dbFile), 0775, true);
}

$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec('
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT "admin",
    created_at TEXT NOT NULL
);
');

$pdo->exec('
CREATE TABLE IF NOT EXISTS login_attempts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ip_hash TEXT NOT NULL,
    attempted_at TEXT NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_login_attempts_ip ON login_attempts (ip_hash, attempted_at);
');

$pdo->exec('
CREATE TABLE IF NOT EXISTS guestbook_entries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    author_name TEXT NOT NULL,
    author_email TEXT,
    city TEXT,
    message TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT "pending",
    is_featured INTEGER NOT NULL DEFAULT 0,
    ip_hash TEXT,
    approved_by INTEGER,
    approved_at TEXT,
    created_at TEXT NOT NULL,
    FOREIGN KEY(approved_by) REFERENCES users(id)
);
');

$countUsers = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
if ($countUsers === 0) {
    $stmt = $pdo->prepare('
        INSERT INTO users (email, password_hash, role, created_at)
        VALUES (:email, :password_hash, :role, :created_at)
    ');

    $stmt->execute([
        'email' => 'admin@bengalis.local',
        'password_hash' => password_hash('admin1234', PASSWORD_DEFAULT),
        'role' => 'admin',
        'created_at' => date('Y-m-d H:i:s'),
    ]);
}

$countEntries = (int) $pdo->query('SELECT COUNT(*) FROM guestbook_entries')->fetchColumn();
if ($countEntries === 0) {
    $stmt = $pdo->prepare('
        INSERT INTO guestbook_entries (author_name, author_email, city, message, status, is_featured, ip_hash, created_at)
        VALUES (:author_name, :author_email, :city, :message, :status, :is_featured, :ip_hash, :created_at)
    ');

    $samples = [
        [
            'author_name' => 'Anne-Marie',
            'author_email' => null,
            'city' => 'Liège',
            'message' => 'Un immense merci pour l’émotion de votre dernier concert. Une très belle énergie et beaucoup de cœur.',
            'status' => 'approved',
            'is_featured' => 1,
            'ip_hash' => hash('sha256', 'sample-1'),
            'created_at' => date('Y-m-d H:i:s', strtotime('-8 days')),
        ],
        [
            'author_name' => 'Michel',
            'author_email' => null,
            'city' => 'Seraing',
            'message' => 'Très beau répertoire, belle présence scénique et ambiance chaleureuse. Bravo à toute la chorale !',
            'status' => 'approved',
            'is_featured' => 0,
            'ip_hash' => hash('sha256', 'sample-2'),
            'created_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
        ],
        [
            'author_name' => 'Visiteur du site',
            'author_email' => 'contact@example.com',
            'city' => 'Herstal',
            'message' => 'Merci pour ce beau moment. Nous reviendrons avec plaisir lors d’un prochain concert.',
            'status' => 'pending',
            'is_featured' => 0,
            'ip_hash' => hash('sha256', 'sample-3'),
            'created_at' => date('Y-m-d H:i:s'),
        ],
    ];

    foreach ($samples as $sample) {
        $stmt->execute($sample);
    }
}

echo "Base initialisée.\n";
echo "Compte admin : admin@bengalis.local / admin1234\n";
