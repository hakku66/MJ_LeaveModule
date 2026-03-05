<?php

declare(strict_types=1);

namespace MJ\LeaveModule\Payroll;

final class LoanRefund
{
    public function __construct(
        public readonly float $totalAmount,
        public readonly int $refundCycleMonths,
    ) {
    }

    public function monthlyDeduction(): float
    {
        return round($this->totalAmount / max(1, $this->refundCycleMonths), 2);
    }
}
