<?php

declare(strict_types=1);

namespace MJ\LeaveModule\Employee;

use DateTimeImmutable;
use MJ\LeaveModule\Personnel\EmploymentType;

final class EmployeeProfile
{
    /** @param string[] $assignedAreas */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $team,
        public readonly DateTimeImmutable $hireDate,
        public readonly float $monthlySalary,
        public readonly EmploymentType $employmentType = EmploymentType::OFFICIAL,
        public readonly array $assignedAreas = [],
    ) {
    }

    public function isOnProbation(DateTimeImmutable $referenceDate): bool
    {
        if ($this->employmentType === EmploymentType::PROBATION) {
            return true;
        }

        return $referenceDate < $this->hireDate->modify('+3 months');
    }
}
