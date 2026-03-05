<?php

declare(strict_types=1);

namespace MJ\LeaveModule\Device;

use MJ\LeaveModule\Employee\EmployeeDirectory;
use MJ\LeaveModule\Employee\EmployeeProfile;
use MJ\LeaveModule\Reporting\AttendanceRecord;

final class DeviceService
{
    public function __construct(private readonly F22DeviceClientInterface $client)
    {
    }

    public function syncEmployeesToDevice(EmployeeDirectory $directory, string $deviceSerial): void
    {
        $payload = [];
        foreach ($directory->all() as $employee) {
            $payload[$employee->id] = $employee->name;
        }

        $this->client->syncEmployees($deviceSerial, $payload);
    }

    /** @return AttendanceRecord[] */
    public function fetchDevicePunches(string $deviceSerial): array
    {
        return $this->client->pullPunchLogs($deviceSerial);
    }

    public function checkHeartbeat(string $deviceSerial): bool
    {
        return $this->client->heartbeat($deviceSerial);
    }

    public function startRemoteEnrollment(string $deviceSerial, int $employeeId, string $mode): bool
    {
        return $this->client->triggerRemoteEnrollment($deviceSerial, $employeeId, $mode);
    }

    public function canUseDevice(EmployeeProfile $employee, string $deviceSerial, array $areasByCode): bool
    {
        foreach ($employee->assignedAreas as $areaCode) {
            $area = $areasByCode[$areaCode] ?? null;
            if ($area !== null && in_array($deviceSerial, $area->allowedDeviceSerials, true)) {
                return true;
            }
        }

        return false;
    }
}
