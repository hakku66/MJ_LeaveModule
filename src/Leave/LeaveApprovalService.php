<?php

declare(strict_types=1);

namespace MJ\LeaveModule\Leave;

use DateTimeImmutable;
use MJ\LeaveModule\Leave\Workflow\ApprovalWorkflow;
use RuntimeException;

final class LeaveApprovalService
{
    /** @var array<int, int> */
    private array $requestProgress = [];

    public function processApproval(LeaveRequest $request, int $approverEmployeeId, ApprovalWorkflow $workflow): void
    {
        $currentLevel = $this->requestProgress[$request->id] ?? 1;
        $node = $this->findNode($workflow, $currentLevel);

        if ($node->approverEmployeeId !== $approverEmployeeId) {
            throw new RuntimeException('Approver is not authorized for this workflow level.');
        }

        $this->requestProgress[$request->id] = $currentLevel + 1;
        if ($currentLevel >= count($workflow->nodes)) {
            $request->status = 'APPROVED';
        }
    }

    public function shouldAutoLop(DateTimeImmutable $leaveStart, DateTimeImmutable $requestedAt): bool
    {
        $days = (int) $leaveStart->setTime(0, 0)->diff($requestedAt->setTime(0, 0))->format('%r%a');
        return $days > 1;
    }

    private function findNode(ApprovalWorkflow $workflow, int $level): \MJ\LeaveModule\Leave\Workflow\ApprovalNode
    {
        foreach ($workflow->nodes as $node) {
            if ($node->level === $level) {
                return $node;
            }
        }

        throw new RuntimeException("Workflow level {$level} not configured.");
    }
}
