<?php
require_once __DIR__ . '/../config/constants.php';

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function formatPrice($price) {
    return '$' . number_format($price, 2);
}

function generateOrderNumber() {
    return 'ORD-' . strtoupper(uniqid()) . '-' . date('ymd');
}

function generateSlug($string) {
    $slug = strtolower(trim($string));
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    return trim($slug, '-');
}

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function logError($message) {
    error_log(date('[Y-m-d H:i:s] ') . $message . PHP_EOL);
}