<?php

declare(strict_types=1);

namespace MJ\LeaveModule\Personnel;

use RuntimeException;

final class DepartmentService
{
    /** @var array<string, Department> */
    private array $departments = [];

    public function add(Department $department): void
    {
        if (strlen($department->code) > 50) {
            throw new RuntimeException('Department code cannot exceed 50 characters.');
        }

        if (!ctype_digit($department->code)) {
            throw new RuntimeException('Department code must be numeric.');
        }

        if (strlen($department->name) > 100) {
            throw new RuntimeException('Department name cannot exceed 100 characters.');
        }

        if ($department->superiorDepartmentCode !== null && !isset($this->departments[$department->superiorDepartmentCode])) {
            throw new RuntimeException('Superior department does not exist.');
        }

        $this->departments[$department->code] = $department;
    }

    public function get(string $code): Department
    {
        if (!isset($this->departments[$code])) {
            throw new RuntimeException("Department {$code} not found.");
        }

        return $this->departments[$code];
    }
}
