<?php

declare(strict_types=1);

namespace MJ\LeaveModule\Reporting;

enum ViewerRole: string
{
    case ADMIN = 'ADMIN';
    case MANAGER = 'MANAGER';
    case EMPLOYEE = 'EMPLOYEE';
}
