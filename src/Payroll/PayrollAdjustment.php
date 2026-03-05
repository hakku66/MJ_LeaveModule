<?php

declare(strict_types=1);

namespace MJ\LeaveModule\Payroll;

final class PayrollAdjustment
{
    public function __construct(
        public readonly string $type, // INCREASE|DEDUCTION
        public readonly float $amount,
        public readonly string $reason,
    ) {
    }
}
