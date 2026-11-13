<?php
namespace App\Models;

use App\Database;

final class User
{
    public static function findByUsername(string $u): ?array
    {
        $s = Database::pdo()->prepare('SELECT id, username, password_hash FROM users WHERE username = ?');
        $s->execute([$u]);
        return $s->fetch() ?: null;
    }

    /** @return bool false on duplicate username/email */
    public static function create(string $username, string $email, string $hash): bool
    {
        try {
            Database::pdo()->prepare('INSERT INTO users (username, email, password_hash) VALUES (?,?,?)')
                ->execute([$username, $email, $hash]);
            return true;
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') return false; // unique violation
            throw $e;
        }
    }

    public static function gravatar(int $id): bool
    {
        $s = Database::pdo()->prepare('SELECT use_gravatar FROM users WHERE id = ?');
        $s->execute([$id]);
        return (bool)$s->fetchColumn();
    }

    public static function setGravatar(int $id, bool $on): void
    {
        Database::pdo()->prepare('UPDATE users SET use_gravatar = ? WHERE id = ?')->execute([(int)$on, $id]);
    }
}
