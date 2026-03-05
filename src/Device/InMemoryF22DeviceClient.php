<?php

declare(strict_types=1);

namespace MJ\LeaveModule\Device;

use MJ\LeaveModule\Reporting\AttendanceRecord;

final class InMemoryF22DeviceClient implements F22DeviceClientInterface
{
    /** @var array<string, array<int, string>> */
    private array $syncedEmployees = [];

    /** @param array<string, AttendanceRecord[]> $logsByDevice */
    public function __construct(private array $logsByDevice = [])
    {
    }

    public function syncEmployees(string $deviceSerial, array $employeeNamesById): void
    {
        $this->syncedEmployees[$deviceSerial] = $employeeNamesById;
    }

    public function pullPunchLogs(string $deviceSerial): array
    {
        return $this->logsByDevice[$deviceSerial] ?? [];
    }

    /** @return array<int, string> */
    public function syncedEmployees(string $deviceSerial): array
    {
        return $this->syncedEmployees[$deviceSerial] ?? [];
    }
}
