<?php

declare(strict_types=1);

namespace MJ\LeaveModule\Attendance\Shift;

use DateTimeImmutable;

final class ShiftScheduler
{
    /** @var array<int, array<string, ShiftDefinition>> */
    private array $employeeShifts = [];

    public function assignRecurring(int $employeeId, string $dayKey, ShiftDefinition $shift): void
    {
        $this->employeeShifts[$employeeId][$dayKey] = $shift;
    }

    public function assignTemporary(int $employeeId, DateTimeImmutable $date, ShiftDefinition $shift): void
    {
        $this->employeeShifts[$employeeId][$date->format('Y-m-d')] = $shift;
    }

    public function getShift(int $employeeId, DateTimeImmutable $date): ?ShiftDefinition
    {
        $exact = $date->format('Y-m-d');
        if (isset($this->employeeShifts[$employeeId][$exact])) {
            return $this->employeeShifts[$employeeId][$exact];
        }

        $weekly = 'WEEKDAY-' . strtoupper($date->format('D'));
        return $this->employeeShifts[$employeeId][$weekly] ?? null;
    }
}
