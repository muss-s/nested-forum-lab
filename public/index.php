<?php
declare(strict_types=1);
// php -S with a router script: let real static files (app.js) be served directly
if (PHP_SAPI === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) && parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) !== '/index.php') { return false; }
require __DIR__ . '/../vendor/autoload.php';

use App\{Auth, View};

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => $https, 'path' => '/']);
session_start();

$router = new Bramus\Router\Router();
$router->set404(function () {
    http_response_code(404);
    echo View::render('error', ['title' => 'Not found', 'message' => 'Page not found.']);
});
require __DIR__ . '/../config/routes.php';
$router->run();
