<?php
// backend/includes/auth_guard.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/response.php';

if (!isset($_SESSION['admin_id'])) {
    json_error('Unauthorized. Please log in.', 401);
}

// Enforce CSRF protection for state-changing requests.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        json_error('Invalid CSRF token.', 403);
    }
}

