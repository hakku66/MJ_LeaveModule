<?php

declare(strict_types=1);

namespace MJ\LeaveModule\Shared;

use DateTimeImmutable;
use InvalidArgumentException;

final class DateRange
{
    public function __construct(
        public readonly DateTimeImmutable $start,
        public readonly DateTimeImmutable $end,
    ) {
        if ($this->end < $this->start) {
            throw new InvalidArgumentException('End date cannot be earlier than start date.');
        }
    }

    public function daysInclusive(): int
    {
        return ((int) $this->start->diff($this->end)->format('%a')) + 1;
    }
}
