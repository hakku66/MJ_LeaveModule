<?php

declare(strict_types=1);

namespace MJ\LeaveModule\Device;

use MJ\LeaveModule\Employee\EmployeeDirectory;
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
}
