<?php

declare(strict_types=1);

namespace MJ\LeaveModule\Device;

use MJ\LeaveModule\Attendance\AttendanceService;
use MJ\LeaveModule\Reporting\AttendanceRecord;

final class DeviceLogSyncService
{
    public function __construct(
        private readonly DeviceService $deviceService,
        private readonly AttendanceService $attendanceService,
    ) {
    }

    /** @return AttendanceRecord[] */
    public function pullAndIngest(string $deviceSerial): array
    {
        $raw = $this->deviceService->fetchDevicePunches($deviceSerial);
        return $this->attendanceService->ingest($raw);
    }
}
