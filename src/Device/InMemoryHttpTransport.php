<?php

declare(strict_types=1);

namespace MJ\LeaveModule\Device;

final class InMemoryHttpTransport implements HttpTransportInterface
{
    /** @var array<string, array<string, mixed>> */
    private array $getResponses;

    /** @var array<string, array<string, mixed>> */
    private array $postResponses;

    /** @var array<int, array{method:string,url:string,payload:array<string,mixed>}> */
    private array $requests = [];

    /**
     * @param array<string, array<string, mixed>> $getResponses
     * @param array<string, array<string, mixed>> $postResponses
     */
    public function __construct(array $getResponses = [], array $postResponses = [])
    {
        $this->getResponses = $getResponses;
        $this->postResponses = $postResponses;
    }

    public function postJson(string $url, array $payload, int $timeoutSeconds = 5): array
    {
        $this->requests[] = ['method' => 'POST', 'url' => $url, 'payload' => $payload];
        return $this->postResponses[$url] ?? ['ok' => true];
    }

    public function getJson(string $url, int $timeoutSeconds = 5): array
    {
        $this->requests[] = ['method' => 'GET', 'url' => $url, 'payload' => []];
        return $this->getResponses[$url] ?? ['ok' => true];
    }

    /** @return array<int, array{method:string,url:string,payload:array<string,mixed>}> */
    public function requests(): array
    {
        return $this->requests;
    }
}
