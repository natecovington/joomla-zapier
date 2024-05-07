<?php
// Check if X-Joomla-Token parameter is set in the URL
if (!isset($_GET['X-Joomla-Token'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(array("message" => "X-Joomla-Token parameter is missing."));
    exit();
}

// Check if site_url parameter is set in the URL
if (!isset($_GET['site_url'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(array("message" => "site_url parameter is missing."));
    exit();
}

// Get the token from the URL parameter
$token = $_GET['X-Joomla-Token'];

// Get the site URL from the URL parameter
$siteUrl = $_GET['site_url'];

// Define the authentication endpoint using the site URL
$authEndpoint = "$siteUrl/api/index.php/v1/content/articles";

// Initialize cURL session
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, $authEndpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    "Content-Type: application/json",
    "X-Joomla-Token: $token"
));

// Execute cURL request
$response = curl_exec($ch);

// Check for errors
if (curl_errno($ch)) {
    echo 'Error: ' . curl_error($ch);
    http_response_code(500); // Internal Server Error
    exit();
}

// Close cURL session
curl_close($ch);

// Check if the response contains the specified error message
if ($response === '{"errors":[{"title":"Forbidden"}]}') {
    http_response_code(403); // Forbidden
    echo '{ "status": "fail" }';
} else {
    http_response_code(200); // OK
    echo '{ "status": "success" }';
}
?>

