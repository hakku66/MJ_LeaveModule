<?php

declare(strict_types=1);

namespace MJ\LeaveModule\Device;

interface HttpTransportInterface
{
    /** @return array<string, mixed> */
    public function postJson(string $url, array $payload, int $timeoutSeconds = 5): array;

    /** @return array<string, mixed> */
    public function getJson(string $url, int $timeoutSeconds = 5): array;
}
