<?php

declare(strict_types=1);

namespace MJ\LeaveModule\Attendance;

final class Timetable
{
    public function __construct(
        public readonly string $checkinStart,
        public readonly string $checkinEnd,
        public readonly string $checkoutStart,
        public readonly string $checkoutEnd,
        public readonly bool $clockRequired,
        public readonly int $allowLateInMinutes,
        public readonly int $allowEarlyOutMinutes,
        public readonly int $absenceThresholdMinutes,
        public readonly string $dayChangeTime,
    ) {
    }
}
