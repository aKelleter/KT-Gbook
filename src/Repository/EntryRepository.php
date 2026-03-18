<?php
declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use PDO;

final class EntryRepository
{
    public function countPublicApproved(): int
    {
        $stmt = Database::connection()->query("SELECT COUNT(*) FROM guestbook_entries WHERE status = 'approved'");
        return (int) $stmt->fetchColumn();
    }

    public function findPublicApprovedPaginated(int $limit, int $offset): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT id, author_name, city, message, created_at, is_featured
             FROM guestbook_entries
             WHERE status = 'approved'
             ORDER BY is_featured DESC, created_at DESC
             LIMIT :limit OFFSET :offset"
        );

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function countAdmin(?string $search = null, ?string $status = null): int
    {
        $pdo = Database::connection();
        $conditions = [];
        $params = [];

        if ($search !== null && $search !== '') {
            $conditions[] = '(author_name LIKE :search OR city LIKE :search OR message LIKE :search OR author_email LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        if ($status !== null && $status !== '') {
            $conditions[] = 'status = :status';
            $params['status'] = $status;
        }

        $sql = 'SELECT COUNT(*) FROM guestbook_entries';
        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function findAdminPaginated(int $limit, int $offset, ?string $search = null, ?string $status = null): array
    {
        $pdo = Database::connection();
        $conditions = [];
        $params = [];

        if ($search !== null && $search !== '') {
            $conditions[] = '(e.author_name LIKE :search OR e.city LIKE :search OR e.message LIKE :search OR e.author_email LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        if ($status !== null && $status !== '') {
            $conditions[] = 'e.status = :status';
            $params['status'] = $status;
        }

        $sql = "SELECT e.*, u.email AS approved_by_email
                FROM guestbook_entries e
                LEFT JOIN users u ON u.id = e.approved_by";

        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY e.created_at DESC LIMIT :limit OFFSET :offset';

        $stmt = $pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function countsByStatus(): array
    {
        $rows = Database::connection()->query(
            "SELECT status, COUNT(*) AS total FROM guestbook_entries GROUP BY status"
        )->fetchAll();

        $counts = [
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0,
        ];

        foreach ($rows as $row) {
            $status = (string) ($row['status'] ?? 'pending');
            $counts[$status] = (int) ($row['total'] ?? 0);
        }

        return $counts;
    }

    public function create(array $data): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO guestbook_entries (
                author_name,
                author_email,
                city,
                message,
                status,
                is_featured,
                ip_hash,
                created_at
            ) VALUES (
                :author_name,
                :author_email,
                :city,
                :message,
                :status,
                :is_featured,
                :ip_hash,
                :created_at
            )'
        );

        $stmt->execute($data);
    }

    public function countRecentByIpHash(string $ipHash, int $minutes): int
    {
        $since = date('Y-m-d H:i:s', time() - ($minutes * 60));

        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM guestbook_entries WHERE ip_hash = :ip_hash AND created_at >= :since'
        );

        $stmt->execute([
            'ip_hash' => $ipHash,
            'since' => $since,
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function findById(int $id): array|false
    {
        $stmt = Database::connection()->prepare('SELECT * FROM guestbook_entries WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function updateStatus(int $id, string $status, ?int $approvedBy = null): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE guestbook_entries
             SET status = :status,
                 approved_by = :approved_by,
                 approved_at = :approved_at
             WHERE id = :id'
        );

        $stmt->execute([
            'status' => $status,
            'approved_by' => $approvedBy,
            'approved_at' => in_array($status, ['approved', 'rejected'], true) ? date('Y-m-d H:i:s') : null,
            'id' => $id,
        ]);
    }

    public function toggleFeatured(int $id): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE guestbook_entries SET is_featured = CASE WHEN is_featured = 1 THEN 0 ELSE 1 END WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
    }

    public function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM guestbook_entries WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
