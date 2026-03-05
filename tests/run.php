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

use MJ\LeaveModule\Attendance\AttendancePolicy;
use MJ\LeaveModule\Attendance\AttendanceService;
use MJ\LeaveModule\Device\DeviceService;
use MJ\LeaveModule\Device\InMemoryF22DeviceClient;
use MJ\LeaveModule\Employee\EmployeeDirectory;
use MJ\LeaveModule\Employee\EmployeeProfile;
use MJ\LeaveModule\Leave\LeaveApplicationService;
use MJ\LeaveModule\Leave\LeavePolicyService;
use MJ\LeaveModule\Leave\LeaveType;
use MJ\LeaveModule\Payroll\PayrollCalculator;
use MJ\LeaveModule\Payroll\PayrollService;
use MJ\LeaveModule\Reporting\AttendanceRecord;
use MJ\LeaveModule\Reporting\Granularity;
use MJ\LeaveModule\Reporting\LeaveRecord;
use MJ\LeaveModule\Reporting\ReportFilter;
use MJ\LeaveModule\Reporting\ReportService;
use MJ\LeaveModule\Reporting\ViewerRole;
use MJ\LeaveModule\Shared\DateRange;

function assertTrue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException("Assertion failed: {$message}");
    }
}

$directory = new EmployeeDirectory([
    new EmployeeProfile(1, 'Asha', 'Engineering', new DateTimeImmutable('2025-01-01'), 30000),
    new EmployeeProfile(2, 'Ravi', 'Engineering', new DateTimeImmutable('2026-02-01'), 28000),
    new EmployeeProfile(3, 'Nita', 'Sales', new DateTimeImmutable('2024-12-01'), 26000),
]);

$attendancePolicy = new AttendancePolicy(1, true, true);
$attendanceService = new AttendanceService($attendancePolicy);
$rawPunches = [
    new AttendanceRecord(1, 'Engineering', new DateTimeImmutable('2026-04-01 09:00:00'), 8.0, true),
    new AttendanceRecord(1, 'Engineering', new DateTimeImmutable('2026-04-01 09:00:20'), 0.0, true),
    new AttendanceRecord(2, 'Engineering', new DateTimeImmutable('2026-04-01 09:05:00'), 9.0, true),
];
$acceptedPunches = $attendanceService->ingest($rawPunches);
assertTrue(count($acceptedPunches) === 2, 'AttendanceService should filter duplicate punches by policy.');

$leavePolicy = new LeavePolicyService();
$leaveApp = new LeaveApplicationService($leavePolicy, $directory);
$approvedLeave = $leaveApp->apply(
    1,
    LeaveType::PERSONAL,
    new DateTimeImmutable('2026-04-15'),
    new DateTimeImmutable('2026-04-16'),
    new DateTimeImmutable('2026-04-10')
);
$leaveApp->approve($approvedLeave->id);
assertTrue($approvedLeave->status === 'APPROVED', 'Leave request should be approved.');

$probationBlocked = false;
try {
    $leaveApp->apply(
        2,
        LeaveType::PERSONAL,
        new DateTimeImmutable('2026-04-06'),
        new DateTimeImmutable('2026-04-06'),
        new DateTimeImmutable('2026-04-02')
    );
} catch (RuntimeException) {
    $probationBlocked = true;
}
assertTrue($probationBlocked, 'Probation employee should not be able to apply PL.');

$deviceLogs = [
    'F22-ENG-001' => [
        new AttendanceRecord(1, 'Engineering', new DateTimeImmutable('2026-04-01 09:00:00'), 8.0, true),
        new AttendanceRecord(2, 'Engineering', new DateTimeImmutable('2026-04-01 09:05:00'), 9.0, true),
    ],
];
$deviceClient = new InMemoryF22DeviceClient($deviceLogs);
$deviceService = new DeviceService($deviceClient);
$deviceService->syncEmployeesToDevice($directory, 'F22-ENG-001');
assertTrue(count($deviceClient->syncedEmployees('F22-ENG-001')) === 3, 'Device sync should push all employee profiles.');
assertTrue(count($deviceService->fetchDevicePunches('F22-ENG-001')) === 2, 'Device service should pull device punch logs.');

$payroll = new PayrollCalculator();
$payrollService = new PayrollService($payroll);
$payout = $payrollService->calculatePayout(30000, 2, 1);
assertTrue($payout['lop_deduction'] === 2000.00, 'Payroll service should compute LOP.');
assertTrue($payout['holiday_pay'] === 1500.00, 'Payroll service should compute holiday pay.');
assertTrue($payout['final_payout'] === 29500.00, 'Payroll service should compute final payout.');
assertTrue(
    $leavePolicy->leaveDays(new DateRange(new DateTimeImmutable('2026-03-10'), new DateTimeImmutable('2026-03-12'))) === 3,
    'Leave day range should be inclusive.'
);

$reportService = new ReportService();
$attendance = [
    new AttendanceRecord(1, 'Engineering', new DateTimeImmutable('2026-04-01'), 8.0, true),
    new AttendanceRecord(1, 'Engineering', new DateTimeImmutable('2026-04-02'), 7.5, true),
    new AttendanceRecord(2, 'Engineering', new DateTimeImmutable('2026-04-01'), 9.0, true),
    new AttendanceRecord(3, 'Sales', new DateTimeImmutable('2026-04-01'), 8.5, true),
];
$leaves = [
    new LeaveRecord(1, 'Engineering', new DateTimeImmutable('2026-04-10'), new DateTimeImmutable('2026-04-11'), 'PL', 'APPROVED'),
    new LeaveRecord(2, 'Engineering', new DateTimeImmutable('2026-04-20'), new DateTimeImmutable('2026-04-20'), 'SL', 'PENDING'),
    new LeaveRecord(3, 'Sales', new DateTimeImmutable('2026-04-05'), new DateTimeImmutable('2026-04-06'), 'PL', 'APPROVED'),
];

$adminReport = $reportService->generate(
    $attendance,
    $leaves,
    new ReportFilter(ViewerRole::ADMIN, null, null, [], Granularity::MONTHLY)
);
assertTrue(isset($adminReport['attendance']['by_period_and_team']['2026-04']['Engineering']), 'Admin report includes team view.');

$managerReport = $reportService->generate(
    $attendance,
    $leaves,
    new ReportFilter(ViewerRole::MANAGER, 99, 'Engineering', [], Granularity::DAILY)
);
assertTrue(!isset($managerReport['attendance']['by_period_and_team']['2026-04-01']['Sales']), 'Manager scope should exclude other teams.');

$employeeReport = $reportService->generate(
    $attendance,
    $leaves,
    new ReportFilter(ViewerRole::EMPLOYEE, 1, null, [], Granularity::WEEKLY)
);
assertTrue(count($employeeReport['attendance']['by_period_and_user']) === 1, 'Employee should only see own data.');

echo "All tests passed\n";
