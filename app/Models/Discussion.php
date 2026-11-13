<?php
namespace App\Models;

use App\Database;

final class Discussion
{
    public static function all(): array
    {
        return Database::pdo()->query('SELECT id, title FROM discussions ORDER BY id')->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $s = Database::pdo()->prepare(
            'SELECT d.id, d.title, d.body, d.created_at, u.username AS author
             FROM discussions d JOIN users u ON u.id = d.user_id WHERE d.id = ?');
        $s->execute([$id]);
        return $s->fetch() ?: null;
    }
}
