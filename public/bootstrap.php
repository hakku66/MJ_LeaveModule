<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Shared/DateRange.php';
require_once __DIR__ . '/../src/Employee/EmployeeProfile.php';
require_once __DIR__ . '/../src/Employee/EmployeeDirectory.php';
require_once __DIR__ . '/../src/Attendance/AttendancePolicy.php';
require_once __DIR__ . '/../src/Attendance/AttendanceService.php';
require_once __DIR__ . '/../src/Leave/LeaveType.php';
require_once __DIR__ . '/../src/Leave/LeavePolicyService.php';
require_once __DIR__ . '/../src/Leave/LeaveRequest.php';
require_once __DIR__ . '/../src/Leave/LeaveApplicationService.php';
require_once __DIR__ . '/../src/Payroll/PayrollCalculator.php';
require_once __DIR__ . '/../src/Payroll/PayrollService.php';
require_once __DIR__ . '/../src/Device/F22DeviceClientInterface.php';
require_once __DIR__ . '/../src/Device/InMemoryF22DeviceClient.php';
require_once __DIR__ . '/../src/Device/DeviceService.php';
require_once __DIR__ . '/../src/Reporting/ViewerRole.php';
require_once __DIR__ . '/../src/Reporting/Granularity.php';
require_once __DIR__ . '/../src/Reporting/AttendanceRecord.php';
require_once __DIR__ . '/../src/Reporting/LeaveRecord.php';
require_once __DIR__ . '/../src/Reporting/ReportFilter.php';
require_once __DIR__ . '/../src/Reporting/ReportService.php';
