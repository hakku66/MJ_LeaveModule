<?php

declare(strict_types=1);

namespace MJ\LeaveModule\Leave;

use DateTimeImmutable;

final class LeaveRequest
{
    public function __construct(
        public readonly int $id,
        public readonly int $employeeId,
        public readonly LeaveType $leaveType,
        public readonly DateTimeImmutable $startDate,
        public readonly DateTimeImmutable $endDate,
        public readonly DateTimeImmutable $requestedAt,
        public string $status = 'PENDING',
    ) {
    }
}
