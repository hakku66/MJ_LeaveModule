<?php

declare(strict_types=1);

namespace MJ\LeaveModule\Attendance\Shift;

final class ShiftDefinition
{
    public function __construct(
        public readonly string $name,
        public readonly string $startTime,
        public readonly string $endTime,
        public readonly string $cycleType, // WEEKLY|MONTHLY|TEMPORARY
    ) {
    }
}
