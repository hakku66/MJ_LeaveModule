<?php

declare(strict_types=1);

namespace MJ\LeaveModule\Personnel;

final class Area
{
    /** @param string[] $allowedDeviceSerials */
    public function __construct(
        public readonly string $code,
        public readonly string $name,
        public readonly array $allowedDeviceSerials,
    ) {
    }
}
