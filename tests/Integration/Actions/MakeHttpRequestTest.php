<?php

namespace Tests\Integration\Actions;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use Xavante\Actions\MakeHttpRequestAction;

class MakeHttpRequestTest extends TestCase
{
    private MakeHttpRequestAction $action;
    private MockHandler $mockHandler;
    private Client $mockClient;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a mock handler for Guzzle
        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);
        $this->mockClient = new Client(['handler' => $handlerStack]);
        
        // Create the action with the mocked client
        $this->action = new MakeHttpRequestAction($this->mockClient);
    }

    public function testBasicGetRequest()
    {
        // Mock a successful response
        $this->mockHandler->append(new Response(200, [], '{"message": "success"}'));

        $this->action->configure([
            'url' => 'https://api.example.com/users',
            'method' => 'GET'
        ]);

        $this->action->execute();

        $this->assertTrue($this->action->wasSuccessful());
        $this->assertEquals(200, $this->action->getStatusCode());
        $this->assertEquals('{"message": "success"}', $this->action->getResponseBody());
        $this->assertEquals(['message' => 'success'], $this->action->getResponseJson());
    }

    public function testPostRequestWithJsonData()
    {
        $this->mockHandler->append(new Response(201, [], '{"id": 123, "created": true}'));

        $this->action->configure([
            'url' => 'https://api.example.com/users',
            'method' => 'POST',
            'json' => [
                'name' => 'John Doe',
                'email' => 'john@example.com'
            ],
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer token123'
            ]
        ]);

        $this->action->execute();

        $this->assertTrue($this->action->wasSuccessful());
        $this->assertEquals(201, $this->action->getStatusCode());
        $this->assertEquals(['id' => 123, 'created' => true], $this->action->getResponseJson());
    }

    public function testPostRequestWithFormParams()
    {
        $this->mockHandler->append(new Response(200, [], 'Form submitted successfully'));

        $this->action->configure([
            'url' => 'https://api.example.com/contact',
            'method' => 'POST',
            'form_params' => [
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'message' => 'Hello world'
            ]
        ]);

        $this->action->execute();

        $this->assertTrue($this->action->wasSuccessful());
        $this->assertEquals('Form submitted successfully', $this->action->getResponseBody());
    }

    public function testRequestWithQueryParameters()
    {
        $this->mockHandler->append(new Response(200, [], '{"results": [], "total": 0}'));

        $this->action->configure([
            'url' => 'https://api.example.com/search',
            'method' => 'GET',
            'query' => [
                'q' => 'test query',
                'limit' => 10,
                'offset' => 0
            ]
        ]);

        $this->action->execute();

        $this->assertTrue($this->action->wasSuccessful());
        $this->assertEquals(['results' => [], 'total' => 0], $this->action->getResponseJson());
    }

    public function testRequestWithAuthentication()
    {
        $this->mockHandler->append(new Response(200, [], '{"authenticated": true}'));

        $this->action->configure([
            'url' => 'https://api.example.com/secure',
            'method' => 'GET',
            'auth' => ['username', 'password', 'basic']
        ]);

        $this->action->execute();

        $this->assertTrue($this->action->wasSuccessful());
        $this->assertEquals(['authenticated' => true], $this->action->getResponseJson());
    }

    public function testRequestWithCustomHeaders()
    {
        $this->mockHandler->append(new Response(200, [], '{"custom_header_received": true}'));

        $this->action->configure([
            'url' => 'https://api.example.com/test',
            'method' => 'GET',
            'headers' => [
                'X-Custom-Header' => 'custom-value',
                'User-Agent' => 'Custom User Agent/1.0'
            ]
        ]);

        $this->action->execute();

        $this->assertTrue($this->action->wasSuccessful());
        $this->assertEquals(['custom_header_received' => true], $this->action->getResponseJson());
    }

    public function testRequestWithTimeout()
    {
        $this->mockHandler->append(new Response(200, [], '{"timeout_test": true}'));

        $this->action->configure([
            'url' => 'https://api.example.com/slow',
            'method' => 'GET',
            'timeout' => 30,
            'connect_timeout' => 10
        ]);

        $this->action->execute();

        $this->assertTrue($this->action->wasSuccessful());
    }

    public function testRequestWithSslVerificationDisabled()
    {
        $this->mockHandler->append(new Response(200, [], '{"ssl_disabled": true}'));

        $this->action->configure([
            'url' => 'https://self-signed.example.com/api',
            'method' => 'GET',
            'verify' => false
        ]);

        $this->action->execute();

        $this->assertTrue($this->action->wasSuccessful());
    }

    public function testPutRequest()
    {
        $this->mockHandler->append(new Response(200, [], '{"updated": true}'));

        $this->action->configure([
            'url' => 'https://api.example.com/users/123',
            'method' => 'PUT',
            'json' => [
                'name' => 'Updated Name',
                'email' => 'updated@example.com'
            ]
        ]);

        $this->action->execute();

        $this->assertTrue($this->action->wasSuccessful());
        $this->assertEquals(['updated' => true], $this->action->getResponseJson());
    }

    public function testDeleteRequest()
    {
        $this->mockHandler->append(new Response(204, [], ''));

        $this->action->configure([
            'url' => 'https://api.example.com/users/123',
            'method' => 'DELETE'
        ]);

        $this->action->execute();

        $this->assertTrue($this->action->wasSuccessful());
        $this->assertEquals(204, $this->action->getStatusCode());
        $this->assertEquals('', $this->action->getResponseBody());
    }

    public function testRequestFailure()
    {
        // Mock a failed request
        $this->mockHandler->append(new RequestException(
            'Connection timeout',
            new Request('GET', 'https://api.example.com/test')
        ));

        $this->action->configure([
            'url' => 'https://api.example.com/test',
            'method' => 'GET'
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('HTTP request failed: Connection timeout');

        $this->action->execute();
    }

    public function testHttpErrorResponse()
    {
        $this->mockHandler->append(new Response(404, [], '{"error": "Not found"}'));

        $this->action->configure([
            'url' => 'https://api.example.com/nonexistent',
            'method' => 'GET'
        ]);

        $this->action->execute();

        $this->assertTrue($this->action->wasSuccessful()); // Request succeeded, but returned 404
        $this->assertEquals(404, $this->action->getStatusCode());
        $this->assertEquals(['error' => 'Not found'], $this->action->getResponseJson());
    }

    public function testRuntimeConfigurationOverride()
    {
        $this->mockHandler->append(new Response(200, [], '{"overridden": true}'));

        // Initial configuration
        $this->action->configure([
            'url' => 'https://api.example.com/initial',
            'method' => 'GET'
        ]);

        // Override at execution time
        $this->action->execute([
            'url' => 'https://api.example.com/overridden',
            'method' => 'POST',
            'json' => ['override' => true]
        ]);

        $this->assertTrue($this->action->wasSuccessful());
        $this->assertEquals(['overridden' => true], $this->action->getResponseJson());
    }

    public function testDryRunMode()
    {
        $this->action->configure([
            'url' => 'https://api.example.com/test',
            'method' => 'POST',
            'json' => ['test' => 'data'],
            'dry_run' => true
        ]);

        $this->action->execute();

        $this->assertTrue($this->action->isDryRun());
        $this->assertTrue($this->action->wasSuccessful());
        $this->assertEquals(200, $this->action->getStatusCode());
        $this->assertEquals(['status' => 'dry_run_simulation'], $this->action->getResponseJson());
    }

    public function testMissingUrlThrowsException()
    {
        $this->action->configure([
            'method' => 'GET'
            // Missing URL
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('URL is required for HTTP request');

        $this->action->execute();
    }

    public function testInvalidConfigurationThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Configuration must be an array');

        $this->action->configure('invalid config');
    }

    public function testRequestWithUserAgentAndReferer()
    {
        $this->mockHandler->append(new Response(200, [], '{"headers_received": true}'));

        $this->action->configure([
            'url' => 'https://api.example.com/test',
            'method' => 'GET',
            'user_agent' => 'Xavante Workflow Engine/1.0',
            'referer' => 'https://example.com/workflow'
        ]);

        $this->action->execute();

        $this->assertTrue($this->action->wasSuccessful());
    }

    public function testRequestWithRawBody()
    {
        $this->mockHandler->append(new Response(200, [], '{"raw_body_received": true}'));

        $xmlData = '<?xml version="1.0"?><data><item>test</item></data>';

        $this->action->configure([
            'url' => 'https://api.example.com/xml',
            'method' => 'POST',
            'body' => $xmlData,
            'headers' => [
                'Content-Type' => 'application/xml'
            ]
        ]);

        $this->action->execute();

        $this->assertTrue($this->action->wasSuccessful());
        $this->assertEquals(['raw_body_received' => true], $this->action->getResponseJson());
    }

    public function testMultipleSequentialRequests()
    {
        // First request
        $this->mockHandler->append(new Response(200, [], '{"first": true}'));
        
        $this->action->configure([
            'url' => 'https://api.example.com/first',
            'method' => 'GET'
        ]);
        $this->action->execute();
        
        $this->assertEquals(['first' => true], $this->action->getResponseJson());

        // Second request with different configuration
        $this->mockHandler->append(new Response(201, [], '{"second": true}'));
        
        $this->action->configure([
            'url' => 'https://api.example.com/second',
            'method' => 'POST',
            'json' => ['data' => 'second request']
        ]);
        $this->action->execute();
        
        $this->assertEquals(201, $this->action->getStatusCode());
        $this->assertEquals(['second' => true], $this->action->getResponseJson());
    }

    public function testResponseHeadersRetrieval()
    {
        $responseHeaders = [
            'Content-Type' => ['application/json'],
            'X-Rate-Limit' => ['100'],
            'X-Request-ID' => ['abc123']
        ];

        $this->mockHandler->append(new Response(200, $responseHeaders, '{"success": true}'));

        $this->action->configure([
            'url' => 'https://api.example.com/test',
            'method' => 'GET'
        ]);

        $this->action->execute();

        $headers = $this->action->getResponseHeaders();
        $this->assertEquals($responseHeaders, $headers);
    }

    public function testHttpErrorsConfigurationEnabled()
    {
        // Mock a 404 response
        $this->mockHandler->append(new Response(404, [], '{"error": "Not found"}'));

        $this->action->configure([
            'url' => 'https://api.example.com/nonexistent',
            'method' => 'GET',
            'http_errors' => true // Enable exception throwing on HTTP errors
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('HTTP request failed:');

        $this->action->execute();
    }

    public function testHttpErrorsConfigurationDisabled()
    {
        // Mock a 500 response
        $this->mockHandler->append(new Response(500, [], '{"error": "Internal server error"}'));

        $this->action->configure([
            'url' => 'https://api.example.com/error',
            'method' => 'GET',
            'http_errors' => false // Disable exception throwing on HTTP errors
        ]);

        $this->action->execute();

        $this->assertTrue($this->action->wasSuccessful());
        $this->assertEquals(500, $this->action->getStatusCode());
        $this->assertEquals(['error' => 'Internal server error'], $this->action->getResponseJson());
    }
}