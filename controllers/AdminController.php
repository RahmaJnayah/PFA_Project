<?php
// controllers/AdminController.php

namespace Controllers;

use Models\UserModel;
use Models\GameModel;
use Models\DocumentModel;
use Models\NewsSourceModel;

class AdminController
{
    public function __construct()
    {
        // NO session_start() here → already started in admin.php
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            // header('Location: /login.php');   // commented for test mode
            // exit;
        }
    }

    public function index()
    {
        $stats = [
            'active_users'     => UserModel::countActive(),
            'total_sessions'   => GameModel::countSessionsLast30Days(),
            'total_ir_queries' => DocumentModel::countIRQueriesLast30Days(),
            'news_cache_age'   => NewsSourceModel::getCacheAge()
        ];

        $recent_logs = UserModel::getRecentAdminLogs(10);

        require_once __DIR__ . '/../views/admin/layout.php';
        require_once __DIR__ . '/../views/admin/dashboard.php';
    }

    public function users()
    {
        $users = UserModel::getAllWithFilter($_GET['filter'] ?? '');
        require_once __DIR__ . '/../views/admin/layout.php';
        require_once __DIR__ . '/../views/admin/users.php';
    }
}