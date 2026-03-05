<?php

declare(strict_types=1);

namespace MJ\LeaveModule\Device;

final class DeviceConnectionSettings
{
    public function __construct(
        public readonly string $ipAddress,
        public readonly string $serialNumber,
        public readonly int $heartbeatSeconds = 10,
        public readonly string $syncMode = 'REAL_TIME',
    ) {
    }
}
