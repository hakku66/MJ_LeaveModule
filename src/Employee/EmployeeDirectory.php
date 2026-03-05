<?php

declare(strict_types=1);

namespace MJ\LeaveModule\Employee;

use RuntimeException;

final class EmployeeDirectory
{
    /** @var array<int, EmployeeProfile> */
    private array $employees = [];

    /** @param EmployeeProfile[] $employees */
    public function __construct(array $employees = [])
    {
        foreach ($employees as $employee) {
            $this->employees[$employee->id] = $employee;
        }
    }

    public function find(int $employeeId): EmployeeProfile
    {
        if (!isset($this->employees[$employeeId])) {
            throw new RuntimeException("Employee {$employeeId} not found.");
        }

        return $this->employees[$employeeId];
    }

    /** @return EmployeeProfile[] */
    public function all(): array
    {
        return array_values($this->employees);
    }
}
