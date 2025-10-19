# MakeHttpRequest Action

The `MakeHttpRequest` action is a highly customizable HTTP client for the Xavante Workflow Engine, built on top of Guzzle HTTP client. It supports dry-run mode, extensive configuration options, and comprehensive error handling.

## Features

- **HTTP Methods**: Support for GET, POST, PUT, DELETE, PATCH, and other HTTP methods
- **Request Formats**: JSON, form data, raw body, and query parameters
- **Authentication**: Basic auth, bearer tokens, and custom headers
- **SSL/TLS**: Configurable SSL verification, client certificates, and custom CA bundles
- **Timeouts**: Connection and request timeouts
- **Proxy Support**: HTTP and SOCKS proxy configuration
- **Dry Run Mode**: Simulate requests without actually sending them
- **Error Handling**: Configurable HTTP error handling
- **Response Processing**: Easy access to response data, headers, and status codes

## Basic Usage

```php
use Xavante\Actions\MakeHttpRequest;

$httpAction = new MakeHttpRequest();

// Configure the request
$httpAction->configure([
    'url' => 'https://api.example.com/users',
    'method' => 'GET'
]);

// Execute the request
$httpAction->execute();

// Check results
if ($httpAction->wasSuccessful()) {
    $statusCode = $httpAction->getStatusCode();
    $responseBody = $httpAction->getResponseBody();
    $responseJson = $httpAction->getResponseJson();
    $headers = $httpAction->getResponseHeaders();
}
```

## Configuration Options

### Basic Configuration

```php
$httpAction->configure([
    'url' => 'https://api.example.com/endpoint',
    'method' => 'POST',                    // HTTP method
    'headers' => [                         // Custom headers
        'Authorization' => 'Bearer token',
        'Content-Type' => 'application/json'
    ],
    'timeout' => 30,                       // Request timeout in seconds
    'connect_timeout' => 10,               // Connection timeout in seconds
]);
```

### Request Body Options

#### JSON Data
```php
$httpAction->configure([
    'url' => 'https://api.example.com/users',
    'method' => 'POST',
    'json' => [
        'name' => 'John Doe',
        'email' => 'john@example.com'
    ]
]);
```

#### Form Parameters
```php
$httpAction->configure([
    'url' => 'https://api.example.com/contact',
    'method' => 'POST',
    'form_params' => [
        'name' => 'Jane Doe',
        'message' => 'Hello World'
    ]
]);
```

#### Raw Body
```php
$httpAction->configure([
    'url' => 'https://api.example.com/xml',
    'method' => 'POST',
    'body' => '<?xml version="1.0"?><data><item>test</item></data>',
    'headers' => ['Content-Type' => 'application/xml']
]);
```

#### Query Parameters
```php
$httpAction->configure([
    'url' => 'https://api.example.com/search',
    'method' => 'GET',
    'query' => [
        'q' => 'search term',
        'limit' => 10,
        'offset' => 0
    ]
]);
```

### Authentication

#### Basic Authentication
```php
$httpAction->configure([
    'url' => 'https://api.example.com/secure',
    'method' => 'GET',
    'auth' => ['username', 'password', 'basic']
]);
```

#### Bearer Token
```php
$httpAction->configure([
    'url' => 'https://api.example.com/secure',
    'method' => 'GET',
    'headers' => [
        'Authorization' => 'Bearer your-token-here'
    ]
]);
```

### SSL/TLS Configuration

#### Disable SSL Verification (for development)
```php
$httpAction->configure([
    'url' => 'https://self-signed.example.com/api',
    'method' => 'GET',
    'verify' => false
]);
```

#### Custom CA Bundle
```php
$httpAction->configure([
    'url' => 'https://api.example.com',
    'method' => 'GET',
    'verify' => '/path/to/ca-bundle.crt'
]);
```

#### Client Certificate
```php
$httpAction->configure([
    'url' => 'https://api.example.com',
    'method' => 'GET',
    'cert' => ['/path/to/client.pem', 'password'],
    'ssl_key' => ['/path/to/private.key', 'password']
]);
```

### Proxy Configuration

```php
$httpAction->configure([
    'url' => 'https://api.example.com',
    'method' => 'GET',
    'proxy' => 'tcp://localhost:8080'
]);
```

### Advanced Options

#### Custom User Agent and Referer
```php
$httpAction->configure([
    'url' => 'https://api.example.com',
    'method' => 'GET',
    'user_agent' => 'Xavante Workflow Engine/1.0',
    'referer' => 'https://example.com/workflow'
]);
```

#### Cookie Handling
```php
$httpAction->configure([
    'url' => 'https://api.example.com',
    'method' => 'GET',
    'cookies' => true  // Enable cookie jar
]);
```

#### Redirect Handling
```php
$httpAction->configure([
    'url' => 'https://api.example.com',
    'method' => 'GET',
    'allow_redirects' => [
        'max' => 5,              // Maximum number of redirects
        'strict' => true,        // Use strict mode
        'referer' => true,       // Add referer header on redirects
        'track_redirects' => true
    ]
]);
```

#### HTTP Error Handling
```php
// Don't throw exceptions on HTTP errors (default behavior)
$httpAction->configure([
    'url' => 'https://api.example.com/might-return-404',
    'method' => 'GET',
    'http_errors' => false
]);

// Throw exceptions on HTTP errors (4xx, 5xx)
$httpAction->configure([
    'url' => 'https://api.example.com/strict-endpoint',
    'method' => 'GET',
    'http_errors' => true
]);
```

## Dry Run Mode

Use dry run mode to simulate requests without actually sending them:

```php
$httpAction->configure([
    'url' => 'https://api.example.com/dangerous-operation',
    'method' => 'DELETE',
    'dry_run' => true
]);

$httpAction->execute();

if ($httpAction->isDryRun()) {
    echo "Dry run completed - no actual request was sent\n";
    echo "Simulated response: " . $httpAction->getResponseBody() . "\n";
}
```

## Runtime Configuration Override

You can override configuration at execution time:

```php
// Initial configuration
$httpAction->configure([
    'url' => 'https://api.example.com/users',
    'method' => 'GET'
]);

// Override at runtime
$httpAction->execute([
    'url' => 'https://api.example.com/posts',  // Override URL
    'query' => ['limit' => 5]                   // Add query parameters
]);
```

## Response Handling

### Accessing Response Data

```php
$httpAction->execute();

// Basic response info
$successful = $httpAction->wasSuccessful();
$statusCode = $httpAction->getStatusCode();
$error = $httpAction->getLastError();

// Response body
$rawBody = $httpAction->getResponseBody();
$jsonData = $httpAction->getResponseJson();

// Response headers
$headers = $httpAction->getResponseHeaders();
$contentType = $headers['Content-Type'][0] ?? 'unknown';

// Full response object (PSR-7)
$response = $httpAction->getLastResponse();
```

### Error Handling

```php
try {
    $httpAction->execute();
    
    if ($httpAction->wasSuccessful()) {
        // Process successful response
        $data = $httpAction->getResponseJson();
    } else {
        // Handle request failure
        echo "Request failed: " . $httpAction->getLastError();
    }
} catch (\RuntimeException $e) {
    // Handle exceptions (connection errors, etc.)
    echo "Error: " . $e->getMessage();
}
```

## Integration with Workflows

The `MakeHttpRequest` action is designed to integrate seamlessly with Xavante workflows:

```php
// In a workflow state
$httpAction = new MakeHttpRequest();
$httpAction->configure([
    'url' => 'https://api.external-service.com/webhook',
    'method' => 'POST',
    'json' => $workflowData,
    'headers' => [
        'Authorization' => 'Bearer ' . $apiToken,
        'X-Workflow-ID' => $workflowInstance->id
    ],
    'timeout' => 30
]);

$httpAction->execute();

if ($httpAction->wasSuccessful()) {
    // Continue workflow execution
    return $httpAction->getResponseJson();
} else {
    // Handle failure or retry
    throw new WorkflowException('External API call failed');
}
```

## Testing

The action includes comprehensive integration tests demonstrating all features. Run tests with:

```bash
./vendor/bin/phpunit tests/Integration/Actions/MakeHttpRequestTest.php
```

## Dependencies

- PHP 8.1+
- Guzzle HTTP Client 7.10+
- PSR-7 HTTP Message Interface