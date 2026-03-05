<?php

declare(strict_types=1);

namespace MJ\LeaveModule\Reporting;

final class ReportFilter
{
    /**
     * @param int[] $employeeIds
     */
    public function __construct(
        public readonly ViewerRole $viewerRole,
        public readonly ?int $viewerEmployeeId,
        public readonly ?string $team,
        public readonly array $employeeIds,
        public readonly Granularity $granularity,
        public readonly bool $includeAttendance = true,
        public readonly bool $includeLeaves = true,
    ) {
    }
}
