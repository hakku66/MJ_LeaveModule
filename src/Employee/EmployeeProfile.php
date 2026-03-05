<?php

declare(strict_types=1);

namespace MJ\LeaveModule\Employee;

use DateTimeImmutable;

final class EmployeeProfile
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $team,
        public readonly DateTimeImmutable $hireDate,
        public readonly float $monthlySalary,
    ) {
    }
}
