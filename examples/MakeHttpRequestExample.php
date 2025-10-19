<?php

/**
 * Example usage of the MakeHttpRequest action
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Xavante\Actions\MakeHttpRequest;

// Example 1: Simple GET request
echo "=== Example 1: Simple GET Request ===\n";
$httpAction = new MakeHttpRequest();
$httpAction->configure([
    'url' => 'https://jsonplaceholder.typicode.com/posts/1',
    'method' => 'GET'
]);

try {
    $httpAction->execute();
    
    if ($httpAction->wasSuccessful()) {
        echo "Status: " . $httpAction->getStatusCode() . "\n";
        echo "Response: " . $httpAction->getResponseBody() . "\n";
    } else {
        echo "Request failed: " . $httpAction->getLastError() . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Example 2: POST request with JSON data
echo "=== Example 2: POST Request with JSON ===\n";
$httpAction = new MakeHttpRequest();
$httpAction->configure([
    'url' => 'https://jsonplaceholder.typicode.com/posts',
    'method' => 'POST',
    'json' => [
        'title' => 'Test Post',
        'body' => 'This is a test post created by Xavante workflow',
        'userId' => 1
    ],
    'headers' => [
        'Content-Type' => 'application/json'
    ]
]);

try {
    $httpAction->execute();
    
    if ($httpAction->wasSuccessful()) {
        echo "Status: " . $httpAction->getStatusCode() . "\n";
        echo "Created post: " . json_encode($httpAction->getResponseJson(), JSON_PRETTY_PRINT) . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Example 3: Dry run mode
echo "=== Example 3: Dry Run Mode ===\n";
$httpAction = new MakeHttpRequest();
$httpAction->configure([
    'url' => 'https://api.example.com/sensitive-operation',
    'method' => 'DELETE',
    'dry_run' => true
]);

$httpAction->execute();

if ($httpAction->isDryRun()) {
    echo "Dry run completed successfully\n";
    echo "Status: " . $httpAction->getStatusCode() . "\n";
    echo "Response: " . $httpAction->getResponseBody() . "\n";
}

echo "\n";

// Example 4: Request with authentication and custom headers
echo "=== Example 4: Request with Authentication ===\n";
$httpAction = new MakeHttpRequest();
$httpAction->configure([
    'url' => 'https://jsonplaceholder.typicode.com/posts',
    'method' => 'GET',
    'headers' => [
        'Authorization' => 'Bearer your-api-token',
        'User-Agent' => 'Xavante Workflow Engine/1.0',
        'X-Custom-Header' => 'workflow-processing'
    ],
    'timeout' => 30
]);

try {
    $httpAction->execute();
    
    if ($httpAction->wasSuccessful()) {
        echo "Status: " . $httpAction->getStatusCode() . "\n";
        echo "Response headers: " . json_encode($httpAction->getResponseHeaders(), JSON_PRETTY_PRINT) . "\n";
        echo "Found " . count($httpAction->getResponseJson()) . " posts\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Example 5: Runtime configuration override
echo "=== Example 5: Runtime Configuration Override ===\n";
$httpAction = new MakeHttpRequest();

// Initial configuration
$httpAction->configure([
    'url' => 'https://jsonplaceholder.typicode.com/posts/1',
    'method' => 'GET'
]);

// Override configuration at runtime
try {
    $httpAction->execute([
        'url' => 'https://jsonplaceholder.typicode.com/users/1', // Override URL
        'query' => ['fields' => 'name,email'] // Add query parameters
    ]);
    
    if ($httpAction->wasSuccessful()) {
        echo "Status: " . $httpAction->getStatusCode() . "\n";
        echo "User data: " . json_encode($httpAction->getResponseJson(), JSON_PRETTY_PRINT) . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}