<?php

declare(strict_types=1);

namespace MJ\LeaveModule\Reporting;

enum Granularity: string
{
    case DAILY = 'DAILY';
    case WEEKLY = 'WEEKLY';
    case MONTHLY = 'MONTHLY';
}
