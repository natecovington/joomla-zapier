<?php

declare(strict_types=1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET,POST');
header('Access-Control-Allow-Headers: X-Site-Url, X-Joomla-Token, Origin, X-Requested-With, Content-Type, Accept, Cache-Control');
header('Access-Control-Max-Age: 60');
header('Cache-Control: private, max-age=120');

try {
    // Preconditions such as site_url and token
    require_once __DIR__ . '/preconditions.php';


// Prevent function name collision. Load this only when the verifications are mode
    if (!function_exists('performCurlRequest')) {
// Function to perform a cURL request
        function performCurlRequest(string $url, string $method = 'GET', $data = null, ?string $token = null)
        {
            // Do not use require_once here it will not work after first require
            $config = require __DIR__ . '/config.php';

            // Initialize cURL session
            $ch = curl_init();

            // First set mostly optimal config
            curl_setopt_array($ch, ($config['curl'] ?? []));

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            if (hash_equals('POST', $method)) {
                curl_setopt($ch, CURLOPT_POST, true);
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
            }
            if ($token) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Accept: application/vnd.api+json',
                    sprintf('X-Joomla-Token: %s', $token),
                ]);
            }

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (curl_errno($ch)) {
                echo json_encode(['error' => curl_error($ch)]);
                http_response_code(500); // Internal Server Error
                exit();
            }
            curl_close($ch);

            return [$response, $httpCode];
        }
    }

    $siteUrl = rtrim(htmlspecialchars($_GET['site_url']), '/');

// Check if request body is present
    $requestBody = file_get_contents('php://input');
    if (empty($requestBody)) {
        http_response_code(400); // Bad request
        echo json_encode(['message' => 'Request body is empty.']);
        exit();
    }

// Decode the JSON data from the request body
    $data = json_decode($requestBody, true);

// Verify if required fields are present
    $requiredFields = ['title', 'body', 'category', 'published'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field])) {
            http_response_code(400); // Bad request
            echo json_encode(['message' => "Field '$field' is missing."]);
            exit();
        }
    }

// Extract field values
    $title     = $data['title'];
    $body      = $data['body'];
    $category  = $data['category'];
    $published = filter_var($data['published'], FILTER_VALIDATE_BOOLEAN); // Convert to boolean
    $categoryId = 2; // Uncategorized by default

// Get List of Categories
    $categoryEndpoint = sprintf('%s/api/index.php/v1/content/categories', $siteUrl);
    [$categoryResponse, $categoryHttpCode] = performCurlRequest($categoryEndpoint, 'GET', null, $token);

    $responseData = json_decode($categoryResponse, true);
    $output       = [];

    if ($categoryHttpCode >= 200 && $categoryHttpCode < 300) {
        // Check if data is present
        $foundCategory = null;
        if (isset($responseData['data']) && is_array($responseData['data']) && count($responseData['data']) > 0) {
            foreach ($responseData['data'] as $categoryItem) {
                if ($categoryItem['attributes']['title'] == $category) {
                    $foundCategory = $categoryItem;
                    break;
                }
            }
        }

        if ($foundCategory) {
            $categoryId    = $foundCategory['id'];
            $categoryTitle = $foundCategory['attributes']['title'];
            $output        = [
                'message'        => 'Category found.',
                'category_id'    => $categoryId,
                'category_title' => $categoryTitle,
            ];
        } else {
            // Create new category
            $newCategoryData = [
                'access'    => 1,
                'alias'     => strtolower(str_replace(' ', '-', $category)),
                'extension' => 'com_content',
                'language'  => '*',
                'note'      => '',
                'parent_id' => 1, // Adjust this ID as needed
                'published' => 1,
                'title'     => $category,
            ];

            $createCategoryEndpoint = sprintf('%s/api/index.php/v1/content/categories', $siteUrl);
            [$createCategoryResponse, $createCategoryHttpCode] = performCurlRequest(
                $createCategoryEndpoint,
                'POST',
                $newCategoryData,
                $token
            );

            if ($createCategoryHttpCode >= 200 && $createCategoryHttpCode < 300) {
                $newCategoryResponseData = json_decode($createCategoryResponse, true);
                if (isset($newCategoryResponseData['data'])) {
                    $categoryId    = $newCategoryResponseData['data']['id'];
                    $categoryTitle = $newCategoryResponseData['data']['attributes']['title'];
                    $output        = [
                        'message'        => 'New category created.',
                        'category_id'    => $categoryId,
                        'category_title' => $categoryTitle,
                    ];
                } else {
                    $output = ['message' => 'Failed to create category.', 'response' => $createCategoryResponse];
                    http_response_code(500);
                }
            } else {
                $output = ['message' => 'Failed to create category.', 'response' => $createCategoryResponse];
                http_response_code($createCategoryHttpCode);
            }
        }
    } else {
        $output = ['message' => 'Failed to fetch categories.', 'response' => $categoryResponse];
        http_response_code($categoryHttpCode);
    }

// Output the final JSON response
//header('Content-Type: application/json');
//echo json_encode($output);


// Prepare data for Joomla article creation
    $joomlaData = [
        'alias'     => $title, // Use title as alias
        'introtext' => $body,
        'catid'     => $categoryId,
        'language'  => '*',
        'metadesc'  => '',
        'metakey'   => '',
        'title'     => $title,
        'state'     => $published ? 1 : 0, // Convert boolean to Joomla state
    ];

// Create the article
    $createEndpoint = sprintf('%s/api/index.php/v1/content/articles', $siteUrl);
    [$response, $httpCode] = performCurlRequest($createEndpoint, 'POST', $joomlaData, $token);

// Handle response based on HTTP code
    if ($httpCode >= 200 && $httpCode < 300) {
        echo $response; // Return the actual response from the Joomla API
    } else {
        echo json_encode(['message' => 'Failed to create article.', 'response' => $response]);
        http_response_code($httpCode);
    }
} catch (Throwable $e) {
    echo json_encode(['message' => 'Unknown error']);
    http_response_code(500);
}
