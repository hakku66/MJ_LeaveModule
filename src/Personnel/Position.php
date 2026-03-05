<?php

declare(strict_types=1);

namespace MJ\LeaveModule\Personnel;

final class Position
{
    public function __construct(
        public readonly string $code,
        public readonly string $name,
    ) {
    }
}
