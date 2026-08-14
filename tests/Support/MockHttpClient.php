<?php

declare(strict_types=1);

namespace GetCNPJ\Tests\Support;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class MockHttpClient implements ClientInterface
{
    /** @var list<string> */
    public array $requestedUrls = [];

    /** @param array<string, array{status?: int, body?: string}> $responses */
    public function __construct(private readonly array $responses)
    {
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $url = (string) $request->getUri();
        $this->requestedUrls[] = $url;
        $response = $this->responses[$url] ?? ['status' => 404, 'body' => '{"error":"not found"}'];
        return new Response($response['status'] ?? 200, ['Content-Type' => 'application/json'], $response['body'] ?? '');
    }
}
