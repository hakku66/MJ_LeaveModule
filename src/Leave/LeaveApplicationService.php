<?php

declare(strict_types=1);

namespace MJ\LeaveModule\Leave;

use DateTimeImmutable;
use MJ\LeaveModule\Employee\EmployeeDirectory;
use RuntimeException;

final class LeaveApplicationService
{
    /** @var array<int, LeaveRequest> */
    private array $requests = [];
    private int $nextId = 1;

    public function __construct(
        private readonly LeavePolicyService $policy,
        private readonly EmployeeDirectory $directory,
    ) {
    }

    public function apply(
        int $employeeId,
        LeaveType $type,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        DateTimeImmutable $requestedAt,
    ): LeaveRequest {
        $employee = $this->directory->find($employeeId);

        if (!$this->policy->canRequestLeaveType($employee->hireDate, $startDate, $type)) {
            throw new RuntimeException('Leave type is not allowed for employee probation status.');
        }

        if (!$this->policy->isAdvanceNoticeSatisfied($requestedAt, $startDate)) {
            throw new RuntimeException('Leave request must be submitted at least 3 days in advance.');
        }

        $request = new LeaveRequest(
            $this->nextId++,
            $employeeId,
            $type,
            $startDate,
            $endDate,
            $requestedAt,
            'PENDING'
        );

        $this->requests[$request->id] = $request;

        return $request;
    }

    public function approve(int $requestId): void
    {
        $request = $this->find($requestId);
        $request->status = 'APPROVED';
    }

    public function reject(int $requestId): void
    {
        $request = $this->find($requestId);
        $request->status = 'REJECTED';
    }

    /** @return LeaveRequest[] */
    public function requestsForEmployee(int $employeeId): array
    {
        return array_values(array_filter(
            $this->requests,
            static fn(LeaveRequest $request): bool => $request->employeeId === $employeeId
        ));
    }

    private function find(int $requestId): LeaveRequest
    {
        if (!isset($this->requests[$requestId])) {
            throw new RuntimeException("Leave request {$requestId} not found.");
        }

        return $this->requests[$requestId];
    }
}
