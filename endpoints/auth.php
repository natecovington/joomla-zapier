<?php

declare(strict_types=1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: X-Site-Url, X-Joomla-Token, Origin, X-Requested-With, Content-Type, Accept, Cache-Control');
header('Access-Control-Max-Age: 60');
header('Cache-Control: no-store');

try {
    require_once __DIR__ . '/preconditions.php';

    // Any valid endpoint that requires authentication (i.e not guest endpoint)
    $endpoint = sprintf('%s/api/index.php/v1/content/articles', rtrim($siteUrl, '/'));

// Do not use require_once here it will not work after first require
    $config = require __DIR__ . '/config.php';

// Initialize cURL session
    $ch = curl_init();

// First set mostly optimal config
    curl_setopt_array($ch, ($config['curl'] ?? []));

// Set cURL options

    curl_setopt($ch, CURLOPT_URL, $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/vnd.api+json',
        sprintf('X-Joomla-Token: %s', $token),
    ]);

// Execute cURL request
    $response = curl_exec($ch);

// Check for errors
    if (curl_errno($ch)) {
        echo 'Error: ' . curl_error($ch);
        http_response_code(500); // Internal Server Error
        exit();
    }


// Check if the response contains the word "Forbidden"
    if (strpos($response, 'Forbidden') !== false) {
        http_response_code(403); // Forbidden
        echo '{ "status": "fail" }';
    } else {
        http_response_code(200); // OK
        echo '{ "status": "success" }';
    }
    exit(); // Not strictly needed, but it does not hurt to do it
} catch (Throwable $e) {
    http_response_code(403); // Forbidden
    echo '{ "status": "fail" }';
    exit();
}
