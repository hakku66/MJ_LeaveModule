<?php

declare(strict_types=1);

namespace MJ\LeaveModule\Leave\Workflow;

final class ApprovalNode
{
    public function __construct(
        public readonly int $level,
        public readonly string $label,
        public readonly int $approverEmployeeId,
    ) {
    }
}
