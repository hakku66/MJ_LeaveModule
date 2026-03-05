<?php

declare(strict_types=1);

namespace MJ\LeaveModule\Device;

use MJ\LeaveModule\Reporting\AttendanceRecord;

interface F22DeviceClientInterface
{
    /**
     * @param array<int, string> $employeeNamesById
     */
    public function syncEmployees(string $deviceSerial, array $employeeNamesById): void;

    /** @return AttendanceRecord[] */
    public function pullPunchLogs(string $deviceSerial): array;
}
