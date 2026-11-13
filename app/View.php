<?php
namespace App;

use Mustache_Engine;
use Mustache_Loader_FilesystemLoader;

final class View
{
    /** Mustache escapes {{var}} with htmlspecialchars; we never use {{{triple}}} for user text. */
    public static function render(string $tpl, array $data = []): string
    {
        static $m = null;
        $m ??= new Mustache_Engine([
            'loader' => new Mustache_Loader_FilesystemLoader(__DIR__ . '/Views', ['extension' => '.mustache']),
            'partials_loader' => new Mustache_Loader_FilesystemLoader(__DIR__ . '/Views', ['extension' => '.mustache']),
        ]);
        $data += [
            'user' => Auth::id() ? ['username' => $_SESSION['username']] : null,
            'csrf' => Csrf::token(),
        ];
        return $m->render($tpl, $data);
    }

    public static function redirect(string $to): never
    {
        header('Location: ' . $to, true, 303);
        exit;
    }
}
