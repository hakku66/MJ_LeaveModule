<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Shared/DateRange.php';
require_once __DIR__ . '/../src/Attendance/AttendancePolicy.php';
require_once __DIR__ . '/../src/Leave/LeaveType.php';
require_once __DIR__ . '/../src/Leave/LeavePolicyService.php';
require_once __DIR__ . '/../src/Payroll/PayrollCalculator.php';
require_once __DIR__ . '/../src/Reporting/ViewerRole.php';
require_once __DIR__ . '/../src/Reporting/Granularity.php';
require_once __DIR__ . '/../src/Reporting/AttendanceRecord.php';
require_once __DIR__ . '/../src/Reporting/LeaveRecord.php';
require_once __DIR__ . '/../src/Reporting/ReportFilter.php';
require_once __DIR__ . '/../src/Reporting/ReportService.php';

use MJ\LeaveModule\Attendance\AttendancePolicy;
use MJ\LeaveModule\Leave\LeavePolicyService;
use MJ\LeaveModule\Leave\LeaveType;
use MJ\LeaveModule\Payroll\PayrollCalculator;
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

$attendancePolicy = new AttendancePolicy(1, true, true);
$baseTime = new DateTimeImmutable('2026-01-01 09:00:00');
assertTrue($attendancePolicy->shouldAcceptPunch($baseTime, new DateTimeImmutable('2026-01-01 09:01:00')),
    'Punch after duplicate interval should be accepted.');
assertTrue(!$attendancePolicy->shouldAcceptPunch($baseTime, new DateTimeImmutable('2026-01-01 09:00:30')),
    'Duplicate punch inside interval should be rejected.');

$leavePolicy = new LeavePolicyService();
$hireDate = new DateTimeImmutable('2026-01-01');
assertTrue(
    !$leavePolicy->canRequestLeaveType($hireDate, new DateTimeImmutable('2026-02-01'), LeaveType::PERSONAL),
    'Employee on probation should not request PL.'
);
assertTrue(
    $leavePolicy->canRequestLeaveType($hireDate, new DateTimeImmutable('2026-02-01'), LeaveType::WITHOUT_PAY),
    'Employee on probation should request LWP.'
);
assertTrue(
    $leavePolicy->isAdvanceNoticeSatisfied(new DateTimeImmutable('2026-03-01'), new DateTimeImmutable('2026-03-04')),
    'Advance notice of 3 days should pass.'
);
assertTrue(
    !$leavePolicy->isAdvanceNoticeSatisfied(new DateTimeImmutable('2026-03-01'), new DateTimeImmutable('2026-03-03')),
    'Advance notice below 3 days should fail.'
);
assertTrue(
    $leavePolicy->leaveDays(new DateRange(new DateTimeImmutable('2026-03-10'), new DateTimeImmutable('2026-03-12'))) === 3,
    'Leave day range should be inclusive.'
);

$payroll = new PayrollCalculator();
$cycle = $payroll->resolveCycle(new DateTimeImmutable('2026-04-20'));
assertTrue($cycle->start->format('Y-m-d') === '2026-03-26', 'Cycle start should be previous month 26th.');
assertTrue($cycle->end->format('Y-m-d') === '2026-04-25', 'Cycle end should be current month 25th.');
assertTrue($payroll->computeHolidayPay(30000, 2) === 3000.00, 'Holiday pay should use 1.5x multiplier.');
assertTrue($payroll->computeLopDeduction(30000, 2) === 2000.00, 'LOP should deduct daily rate per day.');
assertTrue($payroll->isLeaveApplicationLate(new DateTimeImmutable('2026-03-01'), new DateTimeImmutable('2026-03-03')),
    'Leave application after 1-day grace should be late.');
assertTrue(!$payroll->isLeaveApplicationLate(new DateTimeImmutable('2026-03-01'), new DateTimeImmutable('2026-03-02')),
    'Leave application within 1-day grace should not be late.');
assertTrue($payroll->finalPayout(30000, 2000, 3000) === 31000.00, 'Final payout formula should match.');

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
assertTrue(isset($adminReport['attendance']['by_period_and_team']['2026-04']['Engineering']),
    'Admin report should include team-level attendance view.');
assertTrue($adminReport['attendance']['by_period_and_team']['2026-04']['Engineering']['worked_hours'] === 24.5,
    'Admin report should aggregate monthly team worked hours.');

$managerReport = $reportService->generate(
    $attendance,
    $leaves,
    new ReportFilter(ViewerRole::MANAGER, 99, 'Engineering', [], Granularity::DAILY)
);
assertTrue(!isset($managerReport['attendance']['by_period_and_team']['2026-04-01']['Sales']),
    'Manager report should not include other teams.');
assertTrue(isset($managerReport['leaves']['by_period_and_user']['2026-04-10']['1']),
    'Manager should view leaves for users in own team.');

$employeeReport = $reportService->generate(
    $attendance,
    $leaves,
    new ReportFilter(ViewerRole::EMPLOYEE, 1, null, [], Granularity::WEEKLY)
);
assertTrue(count($employeeReport['attendance']['by_period_and_user']) === 1,
    'Employee report should only include personal attendance data.');
assertTrue(!isset($employeeReport['leaves']['by_period_and_user']['2026-W14']['2']),
    'Employee report should not include other users leave data.');

echo "All tests passed\n";
