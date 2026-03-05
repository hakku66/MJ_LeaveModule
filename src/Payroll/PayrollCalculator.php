<?php

declare(strict_types=1);

namespace MJ\LeaveModule\Payroll;

use DateTimeImmutable;
use MJ\LeaveModule\Shared\DateRange;

final class PayrollCalculator
{
    public function __construct(
        private readonly int $cycleStartDay = 26,
        private readonly int $cycleEndDay = 25,
        private readonly float $holidayPayMultiplier = 1.5,
        private readonly int $lateLeaveGraceDays = 1,
    ) {
    }

    public function resolveCycle(DateTimeImmutable $date): DateRange
    {
        $day = (int) $date->format('d');

        if ($day >= $this->cycleStartDay) {
            $start = $date->setDate((int) $date->format('Y'), (int) $date->format('m'), $this->cycleStartDay);
            $end = $start->modify('+1 month')->setDate(
                (int) $start->modify('+1 month')->format('Y'),
                (int) $start->modify('+1 month')->format('m'),
                $this->cycleEndDay
            );
        } else {
            $end = $date->setDate((int) $date->format('Y'), (int) $date->format('m'), $this->cycleEndDay);
            $start = $end->modify('-1 month')->setDate(
                (int) $end->modify('-1 month')->format('Y'),
                (int) $end->modify('-1 month')->format('m'),
                $this->cycleStartDay
            );
        }

        return new DateRange($start->setTime(0, 0), $end->setTime(0, 0));
    }

    public function computeHolidayPay(float $monthlyGrossSalary, int $holidayDaysWorked): float
    {
        $dailyRate = $monthlyGrossSalary / 30;
        return round($dailyRate * $holidayDaysWorked * $this->holidayPayMultiplier, 2);
    }

    public function computeLopDeduction(float $monthlyGrossSalary, int $lopDays): float
    {
        $dailyRate = $monthlyGrossSalary / 30;
        return round($dailyRate * max(0, $lopDays), 2);
    }

    public function isLeaveApplicationLate(DateTimeImmutable $leaveStart, DateTimeImmutable $appliedAt): bool
    {
        $days = (int) $leaveStart->setTime(0, 0)->diff($appliedAt->setTime(0, 0))->format('%r%a');
        return $days > $this->lateLeaveGraceDays;
    }

    public function finalPayout(
        float $grossSalary,
        float $lopDeduction,
        float $holidayPay
    ): float {
        return round($grossSalary - $lopDeduction + $holidayPay, 2);
    }
}
