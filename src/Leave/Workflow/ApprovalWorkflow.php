<?php

declare(strict_types=1);

namespace MJ\LeaveModule\Leave\Workflow;

final class ApprovalWorkflow
{
    /** @param ApprovalNode[] $nodes */
    public function __construct(public readonly array $nodes)
    {
    }
}
