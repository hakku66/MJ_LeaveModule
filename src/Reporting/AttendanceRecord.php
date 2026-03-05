<?php

declare(strict_types=1);

namespace MJ\LeaveModule\Reporting;

use DateTimeImmutable;

final class AttendanceRecord
{
    public function __construct(
        public readonly int $employeeId,
        public readonly string $team,
        public readonly DateTimeImmutable $date,
        public readonly float $workedHours,
        public readonly bool $present,
    ) {
    }
}
