<?php

declare(strict_types=1);

namespace MJ\LeaveModule\Personnel;

enum EmploymentType: string
{
    case OFFICIAL = 'OFFICIAL';
    case TEMPORARY = 'TEMPORARY';
    case PROBATION = 'PROBATION';
}
