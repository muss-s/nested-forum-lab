<?php
namespace App;

final class Csrf
{
    public static function token(): string
    {
        return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
    }

    public static function valid(?string $t): bool
    {
        return is_string($t) && isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $t);
    }
}
