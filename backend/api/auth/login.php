<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/response.php';
require_once __DIR__ . '/../../includes/validate.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Method not allowed', 405);
}

$input = read_request_data();
require_fields($input, ['username', 'password']);

$username = is_string($input['username']) ? trim($input['username']) : '';
$password = is_string($input['password']) ? $input['password'] : '';
if ($username === '' || $password === '') {
    json_error('Invalid username or password', 401);
}

$stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
$stmt->execute([$username]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password_hash'])) {
    session_regenerate_id(true); // Prevent session fixation
    $_SESSION['admin_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    
    json_success(['csrf_token' => $_SESSION['csrf_token']], 'Logged in successfully');
} else {
    json_error('Invalid username or password', 401);
}

