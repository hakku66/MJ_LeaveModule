<?php

declare(strict_types=1);

namespace MJ\LeaveModule\Attendance;

use DateTimeImmutable;
use MJ\LeaveModule\Reporting\AttendanceRecord;

final class AttendanceService
{
    /** @var array<int, DateTimeImmutable> */
    private array $lastPunchByEmployee = [];

    public function __construct(private readonly AttendancePolicy $policy)
    {
    }

    /**
     * @param AttendanceRecord[] $records
     * @return AttendanceRecord[]
     */
    public function ingest(array $records): array
    {
        $accepted = [];

        foreach ($records as $record) {
            $last = $this->lastPunchByEmployee[$record->employeeId] ?? null;
            if ($this->policy->shouldAcceptPunch($last, $record->date)) {
                $accepted[] = $record;
                $this->lastPunchByEmployee[$record->employeeId] = $record->date;
            }
        }

        return $accepted;
    }
}
