<?php
/**
 * YOPY — Admin Controller
 * Handles all admin panel actions: auth, dashboard, users, children, characters.
 */

class AdminController
{
    private UserModel      $userModel;
    private ChildModel     $childModel;
    private CharacterModel $characterModel;

    public function __construct()
    {
        $this->userModel      = new UserModel();
        $this->childModel     = new ChildModel();
        $this->characterModel = new CharacterModel();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function render(string $view, array $data = []): void
    {
        extract($data);
        $pageTitle = $data['pageTitle'] ?? APP_NAME;
        require ROOT_PATH . '/views/layout/header.php';
        require ROOT_PATH . '/views/layout/sidebar.php';
        require ROOT_PATH . "/views/admin/{$view}.php";
        require ROOT_PATH . '/views/layout/footer.php';
    }

    private function redirect(string $action, array $flash = []): void
    {
        if ($flash) {
            $_SESSION['flash'] = $flash;
        }
        header("Location: index.php?action={$action}");
        exit;
    }

    private function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    private function verifyCsrf(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(403);
            die('Invalid CSRF token.');
        }
    }

    private function sanitize(string $value): string
    {
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }

    // ── Auth ──────────────────────────────────────────────────────────────────

    public function showLogin(): void
    {
        if (isset($_SESSION['admin_logged_in'])) {
            $this->redirect('dashboard');
        }
        $csrf = $this->csrfToken();
        $flash = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        require ROOT_PATH . '/views/admin/login.php';
    }

    public function processLogin(): void
    {
        $this->verifyCsrf();

        $email    = $this->sanitize($_POST['email']    ?? '');
        $password = $_POST['password'] ?? '';

        // Demo / fallback credentials (override with DB in production)
        $demoEmail = getenv('ADMIN_EMAIL') ?: 'admin@yopy.app';
        $demoHash  = getenv('ADMIN_HASH')  ?: password_hash('yopy2025!', PASSWORD_BCRYPT);

        $valid = ($email === $demoEmail && password_verify($password, $demoHash));

        // Also try database admin user
        if (!$valid) {
            $admin = $this->userModel->findAdmin($email);
            $valid = $admin && password_verify($password, $admin['password_hash']);
        }

        if ($valid) {
            session_regenerate_id(true);
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_email']     = $email;
            $this->redirect('dashboard');
        }

        $this->redirect('login', ['type' => 'error', 'msg' => 'Invalid credentials. Please try again.']);
    }

    public function logout(): void
    {
        session_destroy();
        header('Location: index.php?action=login');
        exit;
    }

    // ── Dashboard ─────────────────────────────────────────────────────────────

    public function dashboard(): void
    {
        $stats = [
            'total_users'       => $this->userModel->count(),
            'active_users'      => $this->userModel->countActive(),
            'premium_users'     => $this->userModel->countByPlan('premium'),
            'total_children'    => $this->childModel->count(),
            'total_characters'  => $this->characterModel->count(),
            'active_characters' => $this->characterModel->countActive(),
        ];

        $recentUsers    = $this->userModel->findAll(5);
        $recentChildren = $this->childModel->findAll(5);
        $characters     = $this->characterModel->findAll();

        $this->render('dashboard', [
            'pageTitle'      => 'Dashboard — ' . APP_NAME,
            'stats'          => $stats,
            'recentUsers'    => $recentUsers,
            'recentChildren' => $recentChildren,
            'characters'     => $characters,
        ]);
    }

    // ── Users ─────────────────────────────────────────────────────────────────

    public function listUsers(): void
    {
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = 20;
        $offset = ($page - 1) * $limit;

        $users = $this->userModel->findAll($limit, $offset);
        $total = $this->userModel->count();
        $pages = (int) ceil($total / $limit);

        $this->render('users', [
            'pageTitle' => 'Parent Accounts — ' . APP_NAME,
            'users'     => $users,
            'total'     => $total,
            'page'      => $page,
            'pages'     => $pages,
            'csrf'      => $this->csrfToken(),
            'flash'     => $this->popFlash(),
        ]);
    }

    public function createUser(): void
    {
        $this->render('user_form', [
            'pageTitle' => 'New User — ' . APP_NAME,
            'user'      => [],
            'isEdit'    => false,
            'csrf'      => $this->csrfToken(),
        ]);
    }

    public function storeUser(): void
    {
        $this->verifyCsrf();
        $data = $this->collectUserPost();

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
        $id   = (int)($_GET['id'] ?? 0);
        $user = $this->userModel->findById($id);
        if (!$user) { $this->notFound(); return; }

        $this->render('user_form', [
            'pageTitle' => 'Edit User — ' . APP_NAME,
            'user'      => $user,
            'isEdit'    => true,
            'csrf'      => $this->csrfToken(),
        ]);
    }

    public function updateUser(): void
    {
        $this->verifyCsrf();
        $id   = (int)($_POST['id'] ?? 0);
        $data = $this->collectUserPost();
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
        $this->userModel->delete($id);
        $this->redirect('users', ['type' => 'success', 'msg' => 'User deleted.']);
    }

    // ── Children ──────────────────────────────────────────────────────────────

    public function listChildren(): void
    {
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = 20;
        $offset = ($page - 1) * $limit;

        $children = $this->childModel->findAll($limit, $offset);
        $total    = $this->childModel->count();
        $pages    = (int) ceil($total / $limit);

        $this->render('children', [
            'pageTitle' => 'Child Profiles — ' . APP_NAME,
            'children'  => $children,
            'total'     => $total,
            'page'      => $page,
            'pages'     => $pages,
            'csrf'      => $this->csrfToken(),
            'flash'     => $this->popFlash(),
        ]);
    }

    public function createChild(): void
    {
        $users      = $this->userModel->findAll(200);
        $characters = $this->characterModel->findActive();
        $this->render('child_form', [
            'pageTitle'  => 'New Child Profile — ' . APP_NAME,
            'child'      => [],
            'isEdit'     => false,
            'users'      => $users,
            'characters' => $characters,
            'csrf'       => $this->csrfToken(),
        ]);
    }

    public function storeChild(): void
    {
        $this->verifyCsrf();
        $this->childModel->create($this->collectChildPost());
        $this->redirect('children', ['type' => 'success', 'msg' => 'Child profile created.']);
    }

    public function editChild(): void
    {
        $id    = (int)($_GET['id'] ?? 0);
        $child = $this->childModel->findById($id);
        if (!$child) { $this->notFound(); return; }

        $users      = $this->userModel->findAll(200);
        $characters = $this->characterModel->findActive();

        $this->render('child_form', [
            'pageTitle'  => 'Edit Child — ' . APP_NAME,
            'child'      => $child,
            'isEdit'     => true,
            'users'      => $users,
            'characters' => $characters,
            'csrf'       => $this->csrfToken(),
        ]);
    }

    public function updateChild(): void
    {
        $this->verifyCsrf();
        $id = (int)($_POST['id'] ?? 0);
        $this->childModel->update($id, $this->collectChildPost());
        $this->redirect('children', ['type' => 'success', 'msg' => 'Child profile updated.']);
    }

    public function deleteChild(): void
    {
        $this->verifyCsrf();
        $id = (int)($_POST['id'] ?? 0);
        $this->childModel->delete($id);
        $this->redirect('children', ['type' => 'success', 'msg' => 'Child profile deleted.']);
    }

    // ── Characters ────────────────────────────────────────────────────────────

    public function listCharacters(): void
    {
        $characters = $this->characterModel->findAll();
        $this->render('characters', [
            'pageTitle'  => 'Characters — ' . APP_NAME,
            'characters' => $characters,
            'csrf'       => $this->csrfToken(),
            'flash'      => $this->popFlash(),
        ]);
    }

    public function createCharacter(): void
    {
        $this->render('character_form', [
            'pageTitle' => 'New Character — ' . APP_NAME,
            'character' => [],
            'isEdit'    => false,
            'csrf'      => $this->csrfToken(),
        ]);
    }

    public function storeCharacter(): void
    {
        $this->verifyCsrf();
        $this->characterModel->create($this->collectCharacterPost());
        $this->redirect('characters', ['type' => 'success', 'msg' => 'Character created.']);
    }

    public function editCharacter(): void
    {
        $id        = (int)($_GET['id'] ?? 0);
        $character = $this->characterModel->findById($id);
        if (!$character) { $this->notFound(); return; }

        $this->render('character_form', [
            'pageTitle' => 'Edit Character — ' . APP_NAME,
            'character' => $character,
            'isEdit'    => true,
            'csrf'      => $this->csrfToken(),
        ]);
    }

    public function updateCharacter(): void
    {
        $this->verifyCsrf();
        $id = (int)($_POST['id'] ?? 0);
        $this->characterModel->update($id, $this->collectCharacterPost());
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
        $this->render('404', ['pageTitle' => '404 — ' . APP_NAME]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function popFlash(): array
    {
        $flash = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $flash;
    }

    private function collectUserPost(): array
    {
        return [
            'name'     => $this->sanitize($_POST['name']     ?? ''),
            'email'    => $this->sanitize($_POST['email']    ?? ''),
            'password' => $_POST['password'] ?? '',
            'pin'      => $_POST['pin']      ?? '',
            'status'   => in_array($_POST['status'] ?? '', ['active','suspended']) ? $_POST['status'] : 'active',
            'plan'     => in_array($_POST['plan']   ?? '', ['free','premium'])     ? $_POST['plan']   : 'free',
        ];
    }

    private function collectChildPost(): array
    {
        return [
            'user_id'      => (int)($_POST['user_id']      ?? 0),
            'name'         => $this->sanitize($_POST['name']         ?? ''),
            'emoji'        => $this->sanitize($_POST['emoji']        ?? '🦊'),
            'theme'        => $this->sanitize($_POST['theme']        ?? 'theme-rose'),
            'character_id' => ($_POST['character_id'] ?? '') !== '' ? (int)$_POST['character_id'] : null,
            'age'          => ($_POST['age']           ?? '') !== '' ? (int)$_POST['age']          : null,
        ];
    }

    private function collectCharacterPost(): array
    {
        return [
            'name'      => $this->sanitize($_POST['name']    ?? ''),
            'image'     => $this->sanitize($_POST['image']   ?? ''),
            'trait'     => $this->sanitize($_POST['trait']   ?? ''),
            'tagline'   => $this->sanitize($_POST['tagline'] ?? ''),
            'color'     => $this->sanitize($_POST['color']   ?? '#9B59B6'),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];
    }
}
