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

$createEndpoint = $siteUrl . "api/index.php/v1/content/articles";


// Check if request body is present
$requestBody = file_get_contents('php://input');
if (empty($requestBody)) {
    http_response_code(400); // Bad request
    echo json_encode(array("message" => "Request body is empty."));
    exit();
}

// Decode the JSON data from the request body
$data = json_decode($requestBody, true);

// Verify if required fields are present
$requiredFields = ['title', 'body', 'category', 'published'];
foreach ($requiredFields as $field) {
    if (!isset($data[$field])) {
        http_response_code(400); // Bad request
        echo json_encode(array("message" => "Field '$field' is missing."));
        exit();
    }
}

// Extract field values
$title = $data['title'];
$body = $data['body'];
$category = $data['category'];
$published = filter_var($data['published'], FILTER_VALIDATE_BOOLEAN); // Convert to boolean

// Prepare the response with received field values
$response = array(
    "title" => $title,
    "body" => $body,
    "category" => $category,
    "published" => $published
);

// Optionally, you can add more data to the response if needed

// Return the response as JSON
http_response_code(200); // OK
header('Content-Type: application/json');
echo json_encode($response);

//echo json_encode($createEndpoint);
//echo json_encode($token);



// Prepare data for Joomla article creation
$joomlaData = array(
    "alias" => $title, // Use title as alias
    "articletext" => $body,
    "catid" => $category,
    "language" => "*",
    "metadesc" => "",
    "metakey" => "",
    "title" => $title,
    "state" => $published ? 1 : 0 // Convert boolean to Joomla state
);

// Initialize cURL session
$ch = curl_init();



// Set cURL options for creating Joomla article
curl_setopt($ch, CURLOPT_URL, $createEndpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($joomlaData));
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

?>

