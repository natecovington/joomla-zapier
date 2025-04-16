<?php

declare(strict_types=1);

// Check if site_url parameter is set in the URL. hash_equals is used compare strings in constant time
if (hash_equals('', trim($_GET['site_url'] ?? ''))) {
    http_response_code(404); // Not found
    echo json_encode(['message' => 'site_url parameter is missing.']);
    exit();
}

// Get the site URL from the URL parameter
$siteUrl = $_GET['site_url'];

if (strpos($siteUrl, 'https://', 0) === false) {
    http_response_code(404); // Not found
    echo json_encode(['message' => 'site_url MUST use https.']);
    exit();
}

$token = trim($_SERVER['HTTP_X_JOOMLA_TOKEN'] ?? '');

if (hash_equals('', $token)) {
    http_response_code(403);
    echo json_encode(['message' => 'Forbidden.']);
    exit();
}
