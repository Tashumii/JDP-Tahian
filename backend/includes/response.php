<?php
// backend/includes/response.php

function json_success($data = null, $message = '') {
    header('Content-Type: application/json');
    $response = ['status' => 'success'];
    if ($message) $response['message'] = $message;
    if ($data !== null) $response['data'] = $data;
    echo json_encode($response);
    exit;
}

function json_error($message, $code = 400) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}

