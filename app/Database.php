<?php
namespace App;

use PDO;

final class Database
{
    private static ?PDO $pdo = null;

    public static function config(): array
    {
        return require __DIR__ . '/../config/config.php';
    }

    public static function pdo(): PDO
    {
        if (!self::$pdo) {
            $c = self::config()['db'];
            self::$pdo = new PDO($c['dsn'], $c['user'], $c['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false, // real prepared statements
            ]);
        }
        return self::$pdo;
    }
}
