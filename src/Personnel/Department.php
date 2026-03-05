<?php

declare(strict_types=1);

namespace MJ\LeaveModule\Personnel;

final class Department
{
    public function __construct(
        public readonly string $code,
        public readonly string $name,
        public readonly ?string $superiorDepartmentCode,
        public readonly ?int $managerEmployeeId,
        public readonly int $level = 1,
    ) {
    }
}
