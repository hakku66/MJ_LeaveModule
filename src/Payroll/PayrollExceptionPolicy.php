<?php

declare(strict_types=1);

namespace MJ\LeaveModule\Payroll;

final class PayrollExceptionPolicy
{
    public function deductionForLateEarlyAbsence(int $lateMinutes, int $earlyMinutes, bool $absent, float $dailyRate): float
    {
        $deduction = 0.0;
        if ($absent) {
            $deduction += $dailyRate;
        }

        $deduction += ($lateMinutes * 2.0);
        $deduction += ($earlyMinutes * 2.0);

        return round($deduction, 2);
    }
}
