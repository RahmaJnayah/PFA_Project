<?php
/**
 * YOPY Admin Panel — Front Controller
 * Entry point for all admin requests.
 * Routes requests to the appropriate controller action.
 */

define('ROOT_PATH', __DIR__);
define('APP_NAME',  'YOPY Admin');

// ── Autoload ──────────────────────────────────────────────────────────────────
spl_autoload_register(function (string $class): void {
    $map = [
        'Database'          => ROOT_PATH . '/config/Database.php',
        'AdminController'   => ROOT_PATH . '/controllers/AdminController.php',
        'UserModel'         => ROOT_PATH . '/models/UserModel.php',
        'ChildModel'        => ROOT_PATH . '/models/ChildModel.php',
        'CharacterModel'    => ROOT_PATH . '/models/CharacterModel.php',
    ];
    if (isset($map[$class])) {
        require_once $map[$class];
    }
});

// ── Session ───────────────────────────────────────────────────────────────────
session_start();

// ── Route parsing ─────────────────────────────────────────────────────────────
$action = $_GET['action'] ?? 'dashboard';
$method = $_SERVER['REQUEST_METHOD'];

// ── Auth guard (all routes require admin session) ─────────────────────────────
$publicActions = ['login', 'doLogin'];
if (!isset($_SESSION['admin_logged_in']) && !in_array($action, $publicActions, true)) {
    header('Location: index.php?action=login');
    exit;
}

// ── Dispatch ──────────────────────────────────────────────────────────────────
$controller = new AdminController();

$routes = [
    // Auth
    'login'              => 'showLogin',
    'doLogin'            => 'processLogin',
    'logout'             => 'logout',

    // Dashboard
    'dashboard'          => 'dashboard',

    // Users (parent accounts)
    'users'              => 'listUsers',
    'users.create'       => 'createUser',
    'users.store'        => 'storeUser',
    'users.edit'         => 'editUser',
    'users.update'       => 'updateUser',
    'users.delete'       => 'deleteUser',
    'users.toggleStatus' => 'toggleUserStatus',

    // Children
    'children'           => 'listChildren',
    'children.create'    => 'createChild',
    'children.store'     => 'storeChild',
    'children.edit'      => 'editChild',
    'children.update'    => 'updateChild',
    'children.delete'    => 'deleteChild',

    // Characters
    'characters'         => 'listCharacters',
    'characters.create'  => 'createCharacter',
    'characters.store'   => 'storeCharacter',
    'characters.edit'    => 'editCharacter',
    'characters.update'  => 'updateCharacter',
    'characters.delete'  => 'deleteCharacter',
    'characters.toggle'  => 'toggleCharacter',
];

$handler = $routes[$action] ?? 'notFound';

if (method_exists($controller, $handler)) {
    $controller->$handler();
} else {
    $controller->notFound();
}
