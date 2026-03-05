<?php

declare(strict_types=1);

namespace MJ\LeaveModule\Leave;

use DateTimeImmutable;
use MJ\LeaveModule\Shared\DateRange;

final class LeavePolicyService
{
    public function __construct(
        private readonly int $minimumAdvanceDays = 3,
        private readonly int $probationMonths = 3,
        private readonly float $annualPersonalLeave = 12.0,
        private readonly float $annualSickLeave = 3.0,
    ) {
    }

    public function canRequestLeaveType(
        DateTimeImmutable $hireDate,
        DateTimeImmutable $leaveStart,
        LeaveType $leaveType
    ): bool {
        if ($this->isOnProbation($hireDate, $leaveStart)) {
            return $leaveType === LeaveType::WITHOUT_PAY;
        }

        return true;
    }

    public function isAdvanceNoticeSatisfied(
        DateTimeImmutable $requestedAt,
        DateTimeImmutable $leaveStart
    ): bool {
        $diffDays = (int) $requestedAt->setTime(0, 0)->diff($leaveStart->setTime(0, 0))->format('%r%a');
        return $diffDays >= $this->minimumAdvanceDays;
    }

    public function proratedEntitlementForYear(LeaveType $leaveType, int $monthsWorkedInYear): float
    {
        $months = max(0, min(12, $monthsWorkedInYear));

        return match ($leaveType) {
            LeaveType::PERSONAL => round(($this->annualPersonalLeave / 12) * $months, 2),
            LeaveType::SICK => round(($this->annualSickLeave / 12) * $months, 2),
            LeaveType::MARRIAGE, LeaveType::WITHOUT_PAY => 0.0,
        };
    }

    public function leaveDays(DateRange $range): int
    {
        return $range->daysInclusive();
    }

    private function isOnProbation(DateTimeImmutable $hireDate, DateTimeImmutable $referenceDate): bool
    {
        $probationEndExclusive = $hireDate->modify(sprintf('+%d months', $this->probationMonths));
        return $referenceDate < $probationEndExclusive;
    }
}
