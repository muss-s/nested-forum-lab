<?php
namespace App\Controllers;

use App\{View, Auth, Csrf};
use App\Models\User;

final class AuthController
{
    private static function fail(int $code, string $tpl, string $title, string $err, array $old = []): void
    {
        http_response_code($code);
        echo View::render($tpl, ['title' => $title, 'error' => $err, 'old' => $old]);
    }

    public static function registerForm(): void
    {
        echo View::render('register', ['title' => 'Register']);
    }

    public static function register(): void
    {
        if (!Csrf::valid($_POST['csrf'] ?? null)) { self::fail(403, 'register', 'Register', 'Invalid CSRF token.'); return; }
        $u = trim((string)($_POST['username'] ?? ''));
        $e = trim((string)($_POST['email'] ?? ''));
        $p = (string)($_POST['password'] ?? '');
        $old = ['username' => $u, 'email' => $e];
        if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $u)) { self::fail(422, 'register', 'Register', 'Username: 3-30 letters, digits or underscore.', $old); return; }
        if (!filter_var($e, FILTER_VALIDATE_EMAIL) || strlen($e) > 255) { self::fail(422, 'register', 'Register', 'Invalid email.', $old); return; }
        if (strlen($p) < 8 || strlen($p) > 200) { self::fail(422, 'register', 'Register', 'Password must be 8-200 characters.', $old); return; }
        if (!User::create($u, $e, password_hash($p, PASSWORD_DEFAULT))) { self::fail(409, 'register', 'Register', 'Username or email already in use.', $old); return; }
        View::redirect('/login');
    }

    public static function loginForm(): void
    {
        echo View::render('login', ['title' => 'Log in']);
    }

    public static function login(): void
    {
        if (!Csrf::valid($_POST['csrf'] ?? null)) { self::fail(403, 'login', 'Log in', 'Invalid CSRF token.'); return; }
        $u = trim((string)($_POST['username'] ?? ''));
        $row = User::findByUsername($u);
        $hash = $row['password_hash'] ?? null;
        // NULL hash (seed users) or wrong password both give the same generic error.
        if (!$row || $hash === null || !password_verify((string)($_POST['password'] ?? ''), $hash)) {
            self::fail(401, 'login', 'Log in', 'Invalid username or password.', ['username' => $u]);
            return;
        }
        Auth::login((int)$row['id'], $row['username']);
        View::redirect('/');
    }

    public static function logout(): void
    {
        if (!Csrf::valid($_POST['csrf'] ?? null)) { http_response_code(403); echo View::render('error', ['title' => 'Forbidden', 'message' => 'Invalid CSRF token.']); return; }
        Auth::logout();
        View::redirect('/');
    }

    public static function settingsForm(): void
    {
        if (!Auth::id()) { View::redirect('/login'); }
        echo View::render('settings', ['title' => 'Settings', 'gravatar' => User::gravatar(Auth::id())]);
    }

    public static function settings(): void
    {
        if (!Auth::id()) { http_response_code(401); echo View::render('error', ['title' => 'Error 401', 'message' => 'Log in first.']); return; }
        if (!Csrf::valid($_POST['csrf'] ?? null)) { http_response_code(403); echo View::render('error', ['title' => 'Forbidden', 'message' => 'Invalid CSRF token.']); return; }
        User::setGravatar(Auth::id(), isset($_POST['use_gravatar']));
        View::redirect('/settings');
    }
}
