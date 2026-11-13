<?php
use App\Controllers\{DiscussionController, AuthController, CommentController};

/** @var Bramus\Router\Router $router */
$router->get('/', [DiscussionController::class, 'index']);
// Only numeric ids match; anything else (e.g. /discussions/abc) falls to the router 404.
$router->get('/discussions/(\d+)', [DiscussionController::class, 'show']);
$router->post('/discussions/(\d+)/comments', [CommentController::class, 'store']);
$router->get('/register', [AuthController::class, 'registerForm']);
$router->post('/register', [AuthController::class, 'register']);
$router->get('/login', [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->get('/settings', [AuthController::class, 'settingsForm']);
$router->post('/settings', [AuthController::class, 'settings']);
