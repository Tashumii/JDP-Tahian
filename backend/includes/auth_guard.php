<?php
// backend/includes/auth_guard.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/response.php';

if (!isset($_SESSION['admin_id'])) {
    json_error('Unauthorized. Please log in.', 401);
}

// Optional: simple CSRF check for POST requests if implemented
// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     $headers = getallheaders();
//     if (!isset($headers['X-CSRF-Token']) || $headers['X-CSRF-Token'] !== $_SESSION['csrf_token']) {
//         json_error('Invalid CSRF token.', 403);
//     }
// }

