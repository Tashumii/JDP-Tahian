<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/response.php';

session_unset();
session_destroy();
json_success(null, 'Logged out successfully');

