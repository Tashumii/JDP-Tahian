<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/validate.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

$input = sanitize_input($_POST);
require_fields($input, ['username', 'password']);

$stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
$stmt->execute([$input['username']]);
$user = $stmt->fetch();

if ($user && password_verify($input['password'], $user['password_hash'])) {
    session_regenerate_id(true); // Prevent session fixation
    $_SESSION['admin_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    
    json_success(['csrf_token' => $_SESSION['csrf_token']], 'Logged in successfully');
} else {
    json_error('Invalid username or password', 401);
}

