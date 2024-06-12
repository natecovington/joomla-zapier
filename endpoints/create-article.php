<?php

// Function to perform a cURL request
function performCurlRequest($url, $method = 'GET', $data = null, $token = null) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    if ($token) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "X-Joomla-Token: $token"
        ));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if (curl_errno($ch)) {
        echo json_encode(array('error' => curl_error($ch)));
        http_response_code(500); // Internal Server Error
        exit();
    }
    curl_close($ch);

    return array($response, $httpCode);
}

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

// Get the token and site URL from the URL parameter
$token = $_GET['X-Joomla-Token'];
$siteUrl = rtrim($_GET['site_url'], '/') . '/';

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

// Get List of Categories
$categoryEndpoint = $siteUrl . "api/index.php/v1/content/categories";
list($categoryResponse, $categoryHttpCode) = performCurlRequest($categoryEndpoint, 'GET', null, $token);

if ($categoryHttpCode >= 200 && $categoryHttpCode < 300) {
    //echo $categoryResponse; // Return the list of categories
} else {
    echo json_encode(array("message" => "Failed to fetch categories.", "response" => $categoryResponse));
    http_response_code($categoryHttpCode);
    exit();
}

// Decode the JSON response
$responseData = json_decode($categoryResponse, true);

// Check if data is present
if (isset($responseData['data']) && is_array($responseData['data']) && count($responseData['data']) > 0) {
    $foundCategory = null;
    foreach ($responseData['data'] as $categoryItem) {
        if ($categoryItem['attributes']['title'] == $category) {
            $foundCategory = $categoryItem;
            break;
        }
    }

    if ($foundCategory) {
        $categoryId = $foundCategory['id'];
        $categoryTitle = $foundCategory['attributes']['title'];
        // disabling this because we have it working now
        //echo "Category ID: $categoryId, Title: $categoryTitle" . PHP_EOL;
    } else {
        echo "Category '$category' not found.";
    }
} else {
    echo "No categories found.";
}



// Prepare data for Joomla article creation
$joomlaData = array(
    "alias" => $title, // Use title as alias
    "articletext" => $body,
    "catid" => $categoryId,
    "language" => "*",
    "metadesc" => "",
    "metakey" => "",
    "title" => $title,
    "state" => $published ? 1 : 0 // Convert boolean to Joomla state
);

// Create the article
$createEndpoint = $siteUrl . "api/index.php/v1/content/articles";
list($response, $httpCode) = performCurlRequest($createEndpoint, 'POST', $joomlaData, $token);

// Handle response based on HTTP code
if ($httpCode >= 200 && $httpCode < 300) {
    echo $response; // Return the actual response from the Joomla API
} else {
    echo json_encode(array("message" => "Failed to create article.", "response" => $response));
    http_response_code($httpCode);
}

?>

