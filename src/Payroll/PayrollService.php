<?php

declare(strict_types=1);

namespace MJ\LeaveModule\Payroll;

final class PayrollService
{
    public function __construct(
        private readonly PayrollCalculator $calculator,
        private readonly ?PayrollExceptionPolicy $exceptionPolicy = null,
    ) {
    }

    /**
     * @param PayrollAdjustment[] $adjustments
     */
    public function calculatePayout(
        float $grossSalary,
        int $lopDays,
        int $holidayDaysWorked,
        array $adjustments = [],
        ?LoanRefund $loanRefund = null,
        int $lateMinutes = 0,
        int $earlyMinutes = 0,
        bool $absent = false,
    ): array {
        $lop = $this->calculator->computeLopDeduction($grossSalary, $lopDays);
        $holidayPay = $this->calculator->computeHolidayPay($grossSalary, $holidayDaysWorked);

        $dailyRate = $grossSalary / 30;
        $exceptionDeduction = $this->exceptionPolicy?->deductionForLateEarlyAbsence($lateMinutes, $earlyMinutes, $absent, $dailyRate) ?? 0.0;
        $loanDeduction = $loanRefund?->monthlyDeduction() ?? 0.0;

        $extraIncrease = 0.0;
        $extraDeduction = 0.0;
        foreach ($adjustments as $adjustment) {
            if ($adjustment->type === 'INCREASE') {
                $extraIncrease += $adjustment->amount;
            } else {
                $extraDeduction += $adjustment->amount;
            }
        }

        $final = round(
            $grossSalary - $lop - $exceptionDeduction - $loanDeduction - $extraDeduction + $holidayPay + $extraIncrease,
            2
        );

        return [
            'gross_salary' => $grossSalary,
            'lop_deduction' => $lop,
            'holiday_pay' => $holidayPay,
            'exception_deduction' => $exceptionDeduction,
            'loan_deduction' => $loanDeduction,
            'extra_increase' => round($extraIncrease, 2),
            'extra_deduction' => round($extraDeduction, 2),
            'final_payout' => $final,
        ];
    }
}
