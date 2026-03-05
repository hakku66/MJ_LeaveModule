<?php

declare(strict_types=1);

namespace MJ\LeaveModule\Attendance;

use DateTimeImmutable;

final class AttendanceCalculator
{
    public function evaluateDay(
        DateTimeImmutable $checkin,
        DateTimeImmutable $checkout,
        Timetable $timetable,
    ): array {
        $shiftDate = $this->resolveShiftDate($checkin, $timetable->dayChangeTime);

        $plannedIn = new DateTimeImmutable($shiftDate->format('Y-m-d') . ' ' . $timetable->checkinStart);
        $plannedOut = new DateTimeImmutable($shiftDate->format('Y-m-d') . ' ' . $timetable->checkoutEnd);

        if ($plannedOut <= $plannedIn) {
            $plannedOut = $plannedOut->modify('+1 day');
        }

        $lateBy = max(0, (int) floor(($checkin->getTimestamp() - $plannedIn->getTimestamp()) / 60));
        $earlyBy = max(0, (int) floor(($plannedOut->getTimestamp() - $checkout->getTimestamp()) / 60));

        $isAbsent = $timetable->clockRequired && (
            $lateBy > $timetable->absenceThresholdMinutes ||
            $earlyBy > $timetable->absenceThresholdMinutes
        );

        return [
            'shift_date' => $shiftDate->format('Y-m-d'),
            'late_minutes' => max(0, $lateBy - $timetable->allowLateInMinutes),
            'early_out_minutes' => max(0, $earlyBy - $timetable->allowEarlyOutMinutes),
            'is_absent' => $isAbsent,
        ];
    }

    private function resolveShiftDate(DateTimeImmutable $punchTime, string $dayChangeTime): DateTimeImmutable
    {
        $dayChange = new DateTimeImmutable($punchTime->format('Y-m-d') . ' ' . $dayChangeTime);
        if ($punchTime < $dayChange) {
            return $punchTime->modify('-1 day');
        }

        return $punchTime;
    }
}
