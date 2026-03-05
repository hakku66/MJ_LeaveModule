<?php

declare(strict_types=1);

namespace MJ\LeaveModule\Payroll;

final class PayrollService
{
    public function __construct(private readonly PayrollCalculator $calculator)
    {
    }

    public function calculatePayout(float $grossSalary, int $lopDays, int $holidayDaysWorked): array
    {
        $lop = $this->calculator->computeLopDeduction($grossSalary, $lopDays);
        $holidayPay = $this->calculator->computeHolidayPay($grossSalary, $holidayDaysWorked);
        $final = $this->calculator->finalPayout($grossSalary, $lop, $holidayPay);

        return [
            'gross_salary' => $grossSalary,
            'lop_deduction' => $lop,
            'holiday_pay' => $holidayPay,
            'final_payout' => $final,
        ];
    }
}
