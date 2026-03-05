<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Shared/DateRange.php';
require_once __DIR__ . '/../src/Attendance/AttendancePolicy.php';
require_once __DIR__ . '/../src/Leave/LeaveType.php';
require_once __DIR__ . '/../src/Leave/LeavePolicyService.php';
require_once __DIR__ . '/../src/Payroll/PayrollCalculator.php';

use MJ\LeaveModule\Attendance\AttendancePolicy;
use MJ\LeaveModule\Leave\LeavePolicyService;
use MJ\LeaveModule\Leave\LeaveType;
use MJ\LeaveModule\Payroll\PayrollCalculator;
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

echo "All tests passed\n";
