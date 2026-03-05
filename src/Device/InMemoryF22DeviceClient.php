<?php

declare(strict_types=1);

namespace MJ\LeaveModule\Device;

use MJ\LeaveModule\Reporting\AttendanceRecord;

final class InMemoryF22DeviceClient implements F22DeviceClientInterface
{
    /** @var array<string, array<int, string>> */
    private array $syncedEmployees = [];

    /** @var array<string, bool> */
    private array $heartbeatState = [];

    /** @var array<string, array<int, string>> */
    private array $enrollmentCalls = [];

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

    public function heartbeat(string $deviceSerial): bool
    {
        return $this->heartbeatState[$deviceSerial] ?? true;
    }

    public function triggerRemoteEnrollment(string $deviceSerial, int $employeeId, string $mode): bool
    {
        $this->enrollmentCalls[$deviceSerial][$employeeId] = $mode;
        return true;
    }

    /** @return array<int, string> */
    public function syncedEmployees(string $deviceSerial): array
    {
        return $this->syncedEmployees[$deviceSerial] ?? [];
    }

    /** @return array<int, string> */
    public function enrollmentCalls(string $deviceSerial): array
    {
        return $this->enrollmentCalls[$deviceSerial] ?? [];
    }
}
