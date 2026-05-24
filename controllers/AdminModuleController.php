<?php

namespace Controllers;

use Models\AdminModuleUserModel;
use Models\AdminModuleChildModel;
use Models\AdminModuleCharacterModel;
use Models\AdminModuleGameModel;
use Models\AdminModuleAnalysisModel;
use Models\AdminModuleDatabase;

/**
 * YOPY — Admin Module Controller
 * Handles admin panel actions: auth, dashboard, users, children, characters.
 */
class AdminModuleController
{
    private const MAX_NAME_LENGTH = 100;
    private const MAX_EMAIL_LENGTH = 254;

    private AdminModuleUserModel $userModel;
    private AdminModuleChildModel $childModel;
    private AdminModuleCharacterModel $characterModel;
    private AdminModuleGameModel $gameModel;
    private AdminModuleAnalysisModel $analysisModel;
    private string $basePath;

    public function __construct()
    {
        $this->userModel = new AdminModuleUserModel();
        $this->childModel = new AdminModuleChildModel();
        $this->characterModel = new AdminModuleCharacterModel();
        $this->gameModel = new AdminModuleGameModel();
        $this->analysisModel = new AdminModuleAnalysisModel();
        $this->basePath = defined('BASE_PATH') ? BASE_PATH : '';
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function render(string $view, array $data = []): void
    {
        extract($data);
        $pageTitle = $data['pageTitle'] ?? APP_NAME;
        $basePath = htmlspecialchars($this->basePath, ENT_QUOTES, 'UTF-8');

        require ROOT_PATH . '/views/layouts/admin_header.php';
        require ROOT_PATH . '/views/layouts/admin_sidebar.php';
        require ROOT_PATH . "/views/admin/{$view}.php";
        require ROOT_PATH . '/views/layouts/admin_footer.php';
    }

    private function redirect(string $action, array $flash = []): void
    {
        if ($flash) {
            $_SESSION['flash'] = $flash;
        }
        header('Location: ' . $this->basePath . '/admin.php?action=' . $action);
        exit;
    }

    private function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    private function verifyCsrf(string $fallbackAction = 'dashboard'): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            $this->redirect($fallbackAction, [
                'type' => 'error',
                'msg' => 'Your session expired. Please retry the action.',
            ]);
        }
    }

    private function normalizeInput(string $value): string
    {
        return trim($value);
    }

    private function sanitizeEmail(string $value): string
    {
        $email = $this->normalizeInput($value);
        if ($email === '' || strlen($email) > self::MAX_EMAIL_LENGTH) {
            return '';
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
    }

    private function sanitizeName(string $value): string
    {
        $name = $this->normalizeInput($value);
        if ($name === '' || strlen($name) > self::MAX_NAME_LENGTH) {
            return '';
        }
        return $name;
    }

    private function sanitizeHexColor(string $value, string $default = '#9B59B6'): string
    {
        $color = $this->normalizeInput($value);
        return preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? $color : $default;
    }

    private function sanitizeImagePath(string $value): string
    {
        $path = $this->normalizeInput($value);
        if ($path === '') {
            return '';
        }

        if (preg_match('#^(https?://|/|\./|\.\./)#i', $path) && preg_match('/^[a-zA-Z0-9_\-./:?&=%#+~]+$/', $path)) {
            return $path;
        }

        return '';
    }

    private function sanitizeTheme(string $value): string
    {
        $theme = $this->normalizeInput($value);
        if ($theme === '') {
            return 'theme-rose';
        }
        return preg_match('/^[a-z0-9_-]{1,40}$/', $theme) ? $theme : 'theme-rose';
    }

    private function sanitizeEmoji(string $value): string
    {
        $emoji = $this->normalizeInput($value);
        if ($emoji === '') {
            return '🦊';
        }
        return strlen($emoji) <= 16 ? $emoji : '🦊';
    }

    // ── Auth ──────────────────────────────────────────────────────────────────

    public function showLogin(): void
    {
        if (isset($_SESSION['admin_logged_in'])) {
            $this->redirect('dashboard');
        }
        $csrf = $this->csrfToken();
        $flash = $this->popFlash();
        $basePath = htmlspecialchars($this->basePath, ENT_QUOTES, 'UTF-8');
        require ROOT_PATH . '/views/admin/admin-login.php';
    }

    public function processLogin(): void
    {
        $this->verifyCsrf('login');

        $email = $this->sanitizeEmail((string) ($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            $this->redirect('login', ['type' => 'error', 'msg' => 'Enter a valid email and password.']);
        }

        // Demo / fallback credentials (override with env vars in production)
        $demoEmail = getenv('ADMIN_EMAIL') ?: 'admin@yopy.app';
        $demoHash = getenv('ADMIN_HASH') ?: password_hash('yopy2025!', PASSWORD_BCRYPT);

        $valid = ($email === $demoEmail && password_verify($password, $demoHash));

        if (!$valid) {
            $admin = $this->userModel->findAdmin($email);
            $valid = $admin && password_verify($password, $admin['password_hash']);
        }

        if ($valid) {
            session_regenerate_id(true);
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_email'] = $email;
            $this->redirect('dashboard');
        }

        $this->redirect('login', ['type' => 'error', 'msg' => 'Invalid credentials. Please try again.']);
    }

    public function logout(): void
    {
        session_destroy();
        header('Location: ' . $this->basePath . '/admin.php?action=login');
        exit;
    }

    // ── Dashboard ─────────────────────────────────────────────────────────────

    public function dashboard(): void
    {
        $stats = [
            'total_users' => $this->userModel->count(),
            'active_users' => $this->userModel->countActive(),
            'premium_users' => $this->userModel->countByPlan('premium'),
            'total_children' => $this->childModel->count(),
            'total_characters' => $this->characterModel->count(),
            'active_characters' => $this->characterModel->countActive(),
            'total_games' => $this->gameModel->count(),
            'active_games' => $this->gameModel->countActive(),
        ];

        $recentUsers = $this->userModel->findAll(5);
        $recentChildren = $this->childModel->findAll(5);
        $characters = $this->characterModel->findAll();
        $games = $this->gameModel->findAll(8);
        $recentAnalyses = $this->analysisModel->findRecent(5);
        $allChildren = $this->childModel->findAllSimple();
        $analysisHistory = $this->analysisModel->findChronological(20);

        $this->render('admin-dashboard', [
            'pageTitle' => 'Dashboard — ' . APP_NAME,
            'stats' => $stats,
            'recentUsers' => $recentUsers,
            'recentChildren' => $recentChildren,
            'characters' => $characters,
            'games' => $games,
            'recentAnalyses' => $recentAnalyses,
            'allChildren' => $allChildren,
            'analysisHistory' => $analysisHistory,
            'flash' => $this->popFlash(),
            'csrf' => $this->csrfToken(),
        ]);
    }

    public function runChildAnalysis(): void
    {
        $this->verifyCsrf();
        $childId = (int) ($_POST['child_id'] ?? 0);
        if ($childId <= 0) {
            $this->redirect('dashboard', ['type' => 'error', 'msg' => 'Please select a child.']);
        }

        require_once ROOT_PATH . '/ai/analysis/BehaviorAnalyzer.php';
        $pdo = AdminModuleDatabase::getInstance();
        $analyzer = new \BehaviorAnalyzer($pdo);

        $analysis = $analyzer->analyzeChildAllSessions($childId);
        if ($analysis === null) {
            $this->redirect('dashboard', ['type' => 'error', 'msg' => 'No sessions found for this child.']);
        }

        try {
            $stored = $analyzer->storeAnalysis($analysis);
        } catch (\Throwable $e) {
            $stored = false;
        }

        if ($stored) {
            $this->redirect('dashboard', ['type' => 'success', 'msg' => 'Child analysis stored or updated.']);
        }

        $this->redirect('dashboard', ['type' => 'error', 'msg' => 'Analysis could not be stored.']);
    }

    public function runAllChildrenAnalysis(): void
    {
        $this->verifyCsrf();

        require_once ROOT_PATH . '/ai/analysis/BehaviorAnalyzer.php';
        $pdo = AdminModuleDatabase::getInstance();
        $analyzer = new \BehaviorAnalyzer($pdo);
        $children = $this->childModel->findAllSimple();

        $stored = 0;
        $skipped = 0;

        foreach ($children as $child) {
            $analysis = $analyzer->analyzeChildAllSessions((int) $child['id']);
            if ($analysis === null) {
                $skipped++;
                continue;
            }

            try {
                if ($analyzer->storeAnalysis($analysis)) {
                    $stored++;
                } else {
                    $skipped++;
                }
            } catch (\Throwable $e) {
                $skipped++;
            }
        }

        $this->redirect('dashboard', [
            'type' => 'success',
            'msg' => "Full analysis completed. Stored: {$stored}, skipped: {$skipped}.",
        ]);
    }

    // ── Users ─────────────────────────────────────────────────────────────────

    public function listUsers(): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $users = $this->userModel->findAll($limit, $offset);
        $total = $this->userModel->count();
        $pages = (int) ceil($total / $limit);

        $this->render('manage-users', [
            'pageTitle' => 'Parent Accounts — ' . APP_NAME,
            'users' => $users,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'csrf' => $this->csrfToken(),
            'flash' => $this->popFlash(),
        ]);
    }

    public function createUser(): void
    {
        $this->render('user-form', [
            'pageTitle' => 'New User — ' . APP_NAME,
            'user' => [],
            'isEdit' => false,
            'csrf' => $this->csrfToken(),
        ]);
    }

    public function storeUser(): void
    {
        $this->verifyCsrf();
        $data = $this->collectUserPost();

        if ($data['name'] === '' || $data['email'] === '' || strlen((string) $data['password']) < 8) {
            $this->redirect('users', ['type' => 'error', 'msg' => 'Invalid user input. Name, valid email, and password (8+) are required.']);
        }

        if ($this->userModel->findByEmail($data['email'])) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Email already in use.'];
        } else {
            $this->userModel->create($data);
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'User created successfully.'];
        }
        $this->redirect('users');
    }

    public function editUser(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $user = $this->userModel->findById($id);
        if (!$user) {
            $this->notFound();
            return;
        }

        $this->render('user-form', [
            'pageTitle' => 'Edit User — ' . APP_NAME,
            'user' => $user,
            'isEdit' => true,
            'csrf' => $this->csrfToken(),
        ]);
    }

    public function updateUser(): void
    {
        $this->verifyCsrf();
        $id = (int)($_POST['id'] ?? 0);
        $data = $this->collectUserPost();

        if ($id <= 0 || $data['name'] === '' || $data['email'] === '') {
            $this->redirect('users', ['type' => 'error', 'msg' => 'Invalid user update request.']);
        }

        if ($data['password'] !== '' && strlen((string) $data['password']) < 8) {
            $this->redirect('users', ['type' => 'error', 'msg' => 'Password must be at least 8 characters.']);
        }

        $this->userModel->update($id, $data);
        $this->redirect('users', ['type' => 'success', 'msg' => 'User updated.']);
    }

    public function toggleUserStatus(): void
    {
        $this->verifyCsrf();
        $id = (int)($_POST['id'] ?? 0);
        $this->userModel->toggleStatus($id);
        $this->redirect('users', ['type' => 'success', 'msg' => 'User status changed.']);
    }

    public function deleteUser(): void
    {
        $this->verifyCsrf();
        $id = (int)($_POST['id'] ?? 0);
        $user = $this->userModel->findById($id);
        if ($user && ($user['role'] ?? '') === 'admin') {
            $this->redirect('users', ['type' => 'error', 'msg' => 'Admin accounts cannot be deleted.']);
        }
        $this->userModel->delete($id);
        $this->redirect('users', ['type' => 'success', 'msg' => 'User deleted.']);
    }

    // ── Children ──────────────────────────────────────────────────────────────

    public function listChildren(): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $children = $this->childModel->findAll($limit, $offset);
        $total = $this->childModel->count();
        $pages = (int) ceil($total / $limit);

        $this->render('manage-children', [
            'pageTitle' => 'Child Profiles — ' . APP_NAME,
            'children' => $children,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'csrf' => $this->csrfToken(),
            'flash' => $this->popFlash(),
        ]);
    }

    public function createChild(): void
    {
        $users = $this->userModel->findAll(200);
        $characters = $this->characterModel->findActive();
        $this->render('child-form', [
            'pageTitle' => 'New Child Profile — ' . APP_NAME,
            'child' => [],
            'isEdit' => false,
            'users' => $users,
            'characters' => $characters,
            'csrf' => $this->csrfToken(),
        ]);
    }

    public function storeChild(): void
    {
        $this->verifyCsrf();
        $data = $this->collectChildPost();
        if ($data['name'] === '' || $data['user_id'] <= 0) {
            $this->redirect('children', ['type' => 'error', 'msg' => 'Invalid child input.']);
        }

        $this->childModel->create($data);
        $this->redirect('children', ['type' => 'success', 'msg' => 'Child profile created.']);
    }

    public function editChild(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $child = $this->childModel->findById($id);
        if (!$child) {
            $this->notFound();
            return;
        }

        $users = $this->userModel->findAll(200);
        $characters = $this->characterModel->findActive();

        $this->render('child-form', [
            'pageTitle' => 'Edit Child — ' . APP_NAME,
            'child' => $child,
            'isEdit' => true,
            'users' => $users,
            'characters' => $characters,
            'csrf' => $this->csrfToken(),
        ]);
    }

    public function updateChild(): void
    {
        $this->verifyCsrf();
        $id = (int)($_POST['id'] ?? 0);
        $data = $this->collectChildPost();
        if ($id <= 0 || $data['name'] === '' || $data['user_id'] <= 0) {
            $this->redirect('children', ['type' => 'error', 'msg' => 'Invalid child update request.']);
        }

        $this->childModel->update($id, $data);
        $this->redirect('children', ['type' => 'success', 'msg' => 'Child profile updated.']);
    }

    public function deleteChild(): void
    {
        $this->verifyCsrf();
        $id = (int)($_POST['id'] ?? 0);
        $this->childModel->delete($id);
        $this->redirect('children', ['type' => 'success', 'msg' => 'Child profile deleted.']);
    }

    // ── Games ─────────────────────────────────────────────────────────────────

    public function listGames(): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $games = $this->gameModel->findAll($limit, $offset);
        $total = $this->gameModel->count();
        $pages = (int) ceil($total / $limit);

        $this->render('manage-games', [
            'pageTitle' => 'Game Library — ' . APP_NAME,
            'games' => $games,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'csrf' => $this->csrfToken(),
            'flash' => $this->popFlash(),
        ]);
    }

    public function editGame(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $game = $this->gameModel->findById($id);
        if (!$game) {
            $this->notFound();
            return;
        }

        $this->render('game-form', [
            'pageTitle' => 'Edit Game — ' . APP_NAME,
            'game' => $game,
            'csrf' => $this->csrfToken(),
        ]);
    }

    public function updateGame(): void
    {
        $this->verifyCsrf();
        $id = (int)($_POST['id'] ?? 0);
        $data = $this->collectGamePost();

        if ($id <= 0 || $data['name'] === '' || $data['category'] === '' || $data['description'] === '') {
            $this->redirect('games', ['type' => 'error', 'msg' => 'Invalid game update request.']);
        }

        $this->gameModel->update($id, $data);
        $this->redirect('games', ['type' => 'success', 'msg' => 'Game updated.']);
    }

    public function toggleGame(): void
    {
        $this->verifyCsrf();
        $id = (int)($_POST['id'] ?? 0);
        $this->gameModel->toggleActive($id);
        $this->redirect('games', ['type' => 'success', 'msg' => 'Game visibility updated.']);
    }

    // ── Characters ────────────────────────────────────────────────────────────

    public function listCharacters(): void
    {
        $characters = $this->characterModel->findAll();
        $this->render('manage-characters', [
            'pageTitle' => 'Characters — ' . APP_NAME,
            'characters' => $characters,
            'csrf' => $this->csrfToken(),
            'flash' => $this->popFlash(),
        ]);
    }

    public function createCharacter(): void
    {
        $this->render('character-form', [
            'pageTitle' => 'New Character — ' . APP_NAME,
            'character' => [],
            'isEdit' => false,
            'csrf' => $this->csrfToken(),
        ]);
    }

    public function storeCharacter(): void
    {
        $this->verifyCsrf();
        $data = $this->collectCharacterPost();
        if ($data['name'] === '' || $data['trait'] === '' || $data['tagline'] === '') {
            $this->redirect('characters', ['type' => 'error', 'msg' => 'Invalid character input.']);
        }

        $this->characterModel->create($data);
        $this->redirect('characters', ['type' => 'success', 'msg' => 'Character created.']);
    }

    public function editCharacter(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $character = $this->characterModel->findById($id);
        if (!$character) {
            $this->notFound();
            return;
        }

        $this->render('character-form', [
            'pageTitle' => 'Edit Character — ' . APP_NAME,
            'character' => $character,
            'isEdit' => true,
            'csrf' => $this->csrfToken(),
        ]);
    }

    public function updateCharacter(): void
    {
        $this->verifyCsrf();
        $id = (int)($_POST['id'] ?? 0);
        $data = $this->collectCharacterPost();
        if ($id <= 0 || $data['name'] === '' || $data['trait'] === '' || $data['tagline'] === '') {
            $this->redirect('characters', ['type' => 'error', 'msg' => 'Invalid character update request.']);
        }

        $this->characterModel->update($id, $data);
        $this->redirect('characters', ['type' => 'success', 'msg' => 'Character updated.']);
    }

    public function toggleCharacter(): void
    {
        $this->verifyCsrf();
        $id = (int)($_POST['id'] ?? 0);
        $this->characterModel->toggleActive($id);
        $this->redirect('characters', ['type' => 'success', 'msg' => 'Character visibility toggled.']);
    }

    public function deleteCharacter(): void
    {
        $this->verifyCsrf();
        $id = (int)($_POST['id'] ?? 0);
        $this->characterModel->delete($id);
        $this->redirect('characters', ['type' => 'success', 'msg' => 'Character deleted.']);
    }

    // ── 404 ───────────────────────────────────────────────────────────────────

    public function notFound(): void
    {
        http_response_code(404);
        $this->render('admin-404', ['pageTitle' => '404 — ' . APP_NAME]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function popFlash(): array
    {
        $flash = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);

        if (!is_array($flash) || empty($flash['msg'])) {
            return [];
        }

        $type = (string) ($flash['type'] ?? 'success');
        if (!in_array($type, ['success', 'error'], true)) {
            $type = 'success';
        }

        $msg = (string) ($flash['msg'] ?? '');
        if ($msg !== '' && strlen($msg) > 240) {
            $msg = substr($msg, 0, 240);
        }

        return ['type' => $type, 'msg' => $msg];
    }

    private function collectUserPost(): array
    {
        $name = $this->sanitizeName((string) ($_POST['name'] ?? ''));
        $email = $this->sanitizeEmail((string) ($_POST['email'] ?? ''));
        $pinRaw = trim((string) ($_POST['pin'] ?? ''));

        return [
            'name' => $name,
            'email' => $email,
            'password' => $_POST['password'] ?? '',
            'pin' => preg_match('/^\d{4}$/', $pinRaw) ? $pinRaw : '',
            'status' => in_array($_POST['status'] ?? '', ['active', 'suspended'], true) ? $_POST['status'] : 'active',
            'plan' => in_array($_POST['plan'] ?? '', ['free', 'premium'], true) ? $_POST['plan'] : 'free',
        ];
    }

    private function collectChildPost(): array
    {
        $age = $_POST['age'] ?? null;
        $age = (is_numeric($age) && (int) $age >= 1 && (int) $age <= 18) ? (int) $age : null;

        return [
            'name' => $this->sanitizeName((string) ($_POST['name'] ?? '')),
            'age' => $age,
            'user_id' => (int)($_POST['user_id'] ?? 0),
            'character_id' => !empty($_POST['character_id']) ? (int)$_POST['character_id'] : null,
            'emoji' => $this->sanitizeEmoji((string) ($_POST['emoji'] ?? '🦊')),
            'theme' => $this->sanitizeTheme((string) ($_POST['theme'] ?? 'theme-rose')),
        ];
    }

    private function collectCharacterPost(): array
    {
        return [
            'name' => $this->sanitizeName((string) ($_POST['name'] ?? '')),
            'trait' => $this->normalizeInput((string) ($_POST['trait'] ?? '')),
            'tagline' => $this->normalizeInput((string) ($_POST['tagline'] ?? '')),
            'image' => $this->sanitizeImagePath((string) ($_POST['image'] ?? '')),
            'color' => $this->sanitizeHexColor((string) ($_POST['color'] ?? '#9B59B6')),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];
    }

    private function collectGamePost(): array
    {
        $difficulty = strtolower($this->normalizeInput((string) ($_POST['difficulty'] ?? 'medium')));
        if (!in_array($difficulty, ['easy', 'medium', 'hard'], true)) {
            $difficulty = 'medium';
        }

        return [
            'name' => $this->sanitizeName((string) ($_POST['name'] ?? '')),
            'category' => $this->normalizeInput((string) ($_POST['category'] ?? '')),
            'difficulty' => $difficulty,
            'description' => $this->normalizeInput((string) ($_POST['description'] ?? '')),
        ];
    }
}
