<?php
namespace App\Models;

use App\Database;

final class Comment
{
    public static function forDiscussion(int $did, int $limit): array
    {
        $s = Database::pdo()->prepare(
            'SELECT c.id, c.parent_id, c.body, c.created_at, u.username AS author, u.email, u.use_gravatar
             FROM comments c JOIN users u ON u.id = c.user_id
             WHERE c.discussion_id = ? ORDER BY c.id LIMIT ' . (int)$limit);
        $s->execute([$did]);
        return $s->fetchAll();
    }

    public static function find(int $id, int $did): ?array
    {
        $s = Database::pdo()->prepare('SELECT id, parent_id FROM comments WHERE id = ? AND discussion_id = ?');
        $s->execute([$id, $did]);
        return $s->fetch() ?: null;
    }

    /** Depth of an existing comment (top-level = 0), walking up with a step limit. */
    public static function depthOf(int $id, int $did, int $max): int
    {
        $d = 0;
        for ($cur = self::find($id, $did); $cur && $cur['parent_id'] !== null; $d++) {
            if ($d > $max) break;
            $cur = self::find((int)$cur['parent_id'], $did);
        }
        return $d;
    }

    public static function create(int $did, ?int $parent, int $userId, string $body): int
    {
        $pdo = Database::pdo();
        $pdo->prepare('INSERT INTO comments (discussion_id, parent_id, user_id, body) VALUES (?,?,?,?)')
            ->execute([$did, $parent, $userId, $body]);
        return (int)$pdo->lastInsertId();
    }
}
