<?php

declare(strict_types=1);

namespace MJ\LeaveModule\Leave;

enum LeaveType: string
{
    case PERSONAL = 'PL';
    case SICK = 'SL';
    case MARRIAGE = 'ML';
    case WITHOUT_PAY = 'LWP';
}
