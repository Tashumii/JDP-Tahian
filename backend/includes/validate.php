<?php
// backend/includes/validate.php

function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function require_fields($data, $fields) {
    foreach ($fields as $field) {
        if (!isset($data[$field]) || trim($data[$field]) === '') {
            json_error("Missing required field: $field", 400);
        }
    }
}

/** Read JSON and normal form submissions through the same interface. */
function read_request_data() {
    $contentType = strtolower($_SERVER['CONTENT_TYPE'] ?? '');

    if (strpos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            json_error('Request body must be valid JSON.', 400);
        }
        return $data;
    }

    if (!empty($_POST)) {
        return $_POST;
    }

    $raw = file_get_contents('php://input');
    if ($raw !== '') {
        parse_str($raw, $data);
        if (is_array($data) && $data) {
            return $data;
        }
    }

    return [];
}

function validated_string($value, $field, $minLength, $maxLength) {
    if (!is_string($value) && !is_numeric($value)) {
        json_error("Invalid $field.", 400);
    }

    $value = trim((string)$value);
    $length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    if ($length < $minLength || $length > $maxLength) {
        json_error(ucfirst($field) . " must be between $minLength and $maxLength characters.", 400);
    }

    return $value;
}

function valid_phone_number($value) {
    $normalized = preg_replace('/[\s().-]+/', '', trim((string)$value));
    return preg_match('/^(?:\+?63|0)?9\d{9}$/', $normalized) === 1;
}

