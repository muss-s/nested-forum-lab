<?php
namespace App;

final class Auth
{
    public static function id(): ?int
    {
        return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    }

    public static function login(int $id, string $username): void
    {
        session_regenerate_id(true); // prevent session fixation
        $_SESSION['user_id'] = $id;
        $_SESSION['username'] = $username;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
    }
}
