<?php

namespace Xavante\Actions;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

class MakeHttpRequest extends ActionBase 
{
    private Client $client;
    private array $config = [];
    private array $requestOptions = [];
    private ?ResponseInterface $lastResponse = null;
    private ?string $lastError = null;
    private bool $dryRun = false;

    public function __construct(?Client $client = null)
    {
        $this->client = $client ?? new Client();
    }

    /**
     * Configure the HTTP request with various options
     * 
     * @param mixed ...$args Configuration parameters:
     *  - url: string - The request URL
     *  - method: string - HTTP method (GET, POST, PUT, DELETE, etc.)
     *  - headers: array - Request headers
     *  - body: string|array - Request body (string for raw, array for JSON)
     *  - query: array - Query parameters
     *  - form_params: array - Form parameters for POST requests
     *  - json: array - JSON data for requests
     *  - timeout: int - Request timeout in seconds
     *  - connect_timeout: int - Connection timeout in seconds
     *  - verify: bool|string - SSL verification (true/false or path to CA bundle)
     *  - auth: array - Authentication [username, password, type]
     *  - cookies: bool|array - Cookie handling
     *  - allow_redirects: bool|array - Redirect handling
     *  - proxy: string|array - Proxy configuration
     *  - cert: string|array - Client certificate
     *  - ssl_key: string|array - SSL key
     *  - user_agent: string - User agent string
     *  - referer: string - Referer header
     *  - http_errors: bool - Whether to throw exceptions on HTTP errors (default: false)
     *  - dry_run: bool - Whether to simulate the request without actually sending it
     */
    public function configure(mixed ...$args): void
    {
        $config = $args[0] ?? [];
        
        if (!is_array($config)) {
            throw new \InvalidArgumentException('Configuration must be an array');
        }

        // Store basic configuration
        $this->config = array_merge($this->config, $config);
        
        // Set dry run mode
        $this->dryRun = $config['dry_run'] ?? false;

        // Build request options for Guzzle
        $this->buildRequestOptions($config);
    }

    private function buildRequestOptions(array $config): void
    {
        $options = [];

        // Basic request options
        if (isset($config['headers'])) {
            $options[RequestOptions::HEADERS] = $config['headers'];
        }

        if (isset($config['query'])) {
            $options[RequestOptions::QUERY] = $config['query'];
        }

        if (isset($config['form_params'])) {
            $options[RequestOptions::FORM_PARAMS] = $config['form_params'];
        }

        if (isset($config['json'])) {
            $options[RequestOptions::JSON] = $config['json'];
        }

        if (isset($config['body'])) {
            $options[RequestOptions::BODY] = $config['body'];
        }

        // Timeout settings
        if (isset($config['timeout'])) {
            $options[RequestOptions::TIMEOUT] = $config['timeout'];
        }

        if (isset($config['connect_timeout'])) {
            $options[RequestOptions::CONNECT_TIMEOUT] = $config['connect_timeout'];
        }

        // SSL/TLS settings
        if (isset($config['verify'])) {
            $options[RequestOptions::VERIFY] = $config['verify'];
        }

        if (isset($config['cert'])) {
            $options[RequestOptions::CERT] = $config['cert'];
        }

        if (isset($config['ssl_key'])) {
            $options[RequestOptions::SSL_KEY] = $config['ssl_key'];
        }

        // Authentication
        if (isset($config['auth'])) {
            $options[RequestOptions::AUTH] = $config['auth'];
        }

        // Cookie handling
        if (isset($config['cookies'])) {
            $options[RequestOptions::COOKIES] = $config['cookies'];
        }

        // Redirect handling
        if (isset($config['allow_redirects'])) {
            $options[RequestOptions::ALLOW_REDIRECTS] = $config['allow_redirects'];
        }

        // Proxy settings
        if (isset($config['proxy'])) {
            $options[RequestOptions::PROXY] = $config['proxy'];
        }

        // HTTP errors handling - by default, don't throw exceptions on HTTP errors
        if (isset($config['http_errors'])) {
            $options[RequestOptions::HTTP_ERRORS] = $config['http_errors'];
        } else {
            $options[RequestOptions::HTTP_ERRORS] = false; // Allow 4xx and 5xx responses without throwing exceptions
        }

        // User agent
        if (isset($config['user_agent'])) {
            if (!isset($options[RequestOptions::HEADERS])) {
                $options[RequestOptions::HEADERS] = [];
            }
            $options[RequestOptions::HEADERS]['User-Agent'] = $config['user_agent'];
        }

        // Referer
        if (isset($config['referer'])) {
            if (!isset($options[RequestOptions::HEADERS])) {
                $options[RequestOptions::HEADERS] = [];
            }
            $options[RequestOptions::HEADERS]['Referer'] = $config['referer'];
        }

        $this->requestOptions = $options;
    }

    /**
     * Execute the HTTP request
     * 
     * @param mixed ...$args Additional runtime parameters that can override configuration
     */
    public function execute(mixed ...$args): void
    {
        $runtimeConfig = $args[0] ?? [];
        
        // Merge runtime configuration with stored configuration
        if (is_array($runtimeConfig)) {
            $this->configure(array_merge($this->config, $runtimeConfig));
        }

        $url = $this->config['url'] ?? null;
        $method = $this->config['method'] ?? 'GET';

        if (!$url) {
            throw new \InvalidArgumentException('URL is required for HTTP request');
        }

        // Clear previous state
        $this->lastResponse = null;
        $this->lastError = null;

        if ($this->dryRun) {
            // Simulate the request without actually sending it
            $this->simulateRequest($method, $url);
            return;
        }

        try {
            $this->lastResponse = $this->client->request($method, $url, $this->requestOptions);
        } catch (GuzzleException $e) {
            $this->lastError = $e->getMessage();
            throw new \RuntimeException("HTTP request failed: " . $e->getMessage(), 0, $e);
        }
    }

    private function simulateRequest(string $method, string $url): void
    {
        // Create a mock response for dry run mode
        $this->lastResponse = new class implements ResponseInterface {
            public function getStatusCode(): int { return 200; }
            public function withStatus($code, $reasonPhrase = ''): ResponseInterface { return $this; }
            public function getReasonPhrase(): string { return 'OK'; }
            public function getProtocolVersion(): string { return '1.1'; }
            public function withProtocolVersion($version): ResponseInterface { return $this; }
            public function getHeaders(): array { return ['Content-Type' => ['application/json']]; }
            public function hasHeader($name): bool { return $name === 'Content-Type'; }
            public function getHeader($name): array { return $name === 'Content-Type' ? ['application/json'] : []; }
            public function getHeaderLine($name): string { return $name === 'Content-Type' ? 'application/json' : ''; }
            public function withHeader($name, $value): ResponseInterface { return $this; }
            public function withAddedHeader($name, $value): ResponseInterface { return $this; }
            public function withoutHeader($name): ResponseInterface { return $this; }
            public function getBody(): \Psr\Http\Message\StreamInterface { 
                return new class implements \Psr\Http\Message\StreamInterface {
                    public function __toString(): string { return '{"status": "dry_run_simulation"}'; }
                    public function close(): void {}
                    public function detach() { return null; }
                    public function getSize(): ?int { return 31; }
                    public function tell(): int { return 0; }
                    public function eof(): bool { return false; }
                    public function isSeekable(): bool { return false; }
                    public function seek($offset, $whence = SEEK_SET): void {}
                    public function rewind(): void {}
                    public function isWritable(): bool { return false; }
                    public function write($string): int { return 0; }
                    public function isReadable(): bool { return true; }
                    public function read($length): string { return $this->__toString(); }
                    public function getContents(): string { return $this->__toString(); }
                    public function getMetadata($key = null) { return null; }
                };
            }
            public function withBody(\Psr\Http\Message\StreamInterface $body): ResponseInterface { return $this; }
        };
    }

    /**
     * Get the last HTTP response
     */
    public function getLastResponse(): ?ResponseInterface
    {
        return $this->lastResponse;
    }

    /**
     * Get the last error message
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Check if the last request was successful
     */
    public function wasSuccessful(): bool
    {
        return $this->lastResponse !== null && $this->lastError === null;
    }

    /**
     * Get response status code
     */
    public function getStatusCode(): ?int
    {
        return $this->lastResponse ? $this->lastResponse->getStatusCode() : null;
    }

    /**
     * Get response body as string
     */
    public function getResponseBody(): ?string
    {
        return $this->lastResponse ? (string) $this->lastResponse->getBody() : null;
    }

    /**
     * Get response body as JSON array
     */
    public function getResponseJson(): ?array
    {
        $body = $this->getResponseBody();
        return $body ? json_decode($body, true) : null;
    }

    /**
     * Get response headers
     */
    public function getResponseHeaders(): array
    {
        return $this->lastResponse ? $this->lastResponse->getHeaders() : [];
    }

    /**
     * Check if dry run mode is enabled
     */
    public function isDryRun(): bool
    {
        return $this->dryRun;
    }
}