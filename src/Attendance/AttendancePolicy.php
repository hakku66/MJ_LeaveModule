<?php

declare(strict_types=1);

namespace MJ\LeaveModule\Attendance;

use DateInterval;
use DateTimeImmutable;

final class AttendancePolicy
{
    public function __construct(
        private readonly int $duplicatePunchMinutes = 1,
        private readonly bool $checkinMandatory = true,
        private readonly bool $checkoutMandatory = true,
    ) {
    }

    public function shouldAcceptPunch(
        ?DateTimeImmutable $lastPunchTime,
        DateTimeImmutable $newPunchTime
    ): bool {
        if ($lastPunchTime === null) {
            return true;
        }

        $minimumAllowedTime = $lastPunchTime->add(
            new DateInterval(sprintf('PT%dM', $this->duplicatePunchMinutes))
        );

        return $newPunchTime >= $minimumAllowedTime;
    }

    public function isCheckinMandatory(): bool
    {
        return $this->checkinMandatory;
    }

    public function isCheckoutMandatory(): bool
    {
        return $this->checkoutMandatory;
    }
}
