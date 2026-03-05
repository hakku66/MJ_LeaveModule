<?php

declare(strict_types=1);

require_once __DIR__ . '/../public/bootstrap.php';

use MJ\LeaveModule\Attendance\AttendanceCalculator;
use MJ\LeaveModule\Attendance\AttendancePolicy;
use MJ\LeaveModule\Attendance\AttendanceService;
use MJ\LeaveModule\Attendance\Shift\ShiftDefinition;
use MJ\LeaveModule\Attendance\Shift\ShiftScheduler;
use MJ\LeaveModule\Attendance\Timetable;
use MJ\LeaveModule\Device\DeviceLogSyncService;
use MJ\LeaveModule\Device\DeviceService;
use MJ\LeaveModule\Device\DeviceConnectionSettings;
use MJ\LeaveModule\Device\HttpF22DeviceClient;
use MJ\LeaveModule\Device\InMemoryF22DeviceClient;
use MJ\LeaveModule\Device\InMemoryHttpTransport;
use MJ\LeaveModule\Employee\EmployeeDirectory;
use MJ\LeaveModule\Employee\EmployeeProfile;
use MJ\LeaveModule\Leave\LeaveApplicationService;
use MJ\LeaveModule\Leave\LeaveApprovalService;
use MJ\LeaveModule\Leave\LeavePolicyService;
use MJ\LeaveModule\Leave\LeaveType;
use MJ\LeaveModule\Leave\Workflow\ApprovalNode;
use MJ\LeaveModule\Leave\Workflow\ApprovalWorkflow;
use MJ\LeaveModule\Payroll\LoanRefund;
use MJ\LeaveModule\Payroll\PayrollAdjustment;
use MJ\LeaveModule\Payroll\PayrollCalculator;
use MJ\LeaveModule\Payroll\PayrollExceptionPolicy;
use MJ\LeaveModule\Payroll\PayrollService;
use MJ\LeaveModule\Personnel\Area;
use MJ\LeaveModule\Personnel\Department;
use MJ\LeaveModule\Personnel\DepartmentService;
use MJ\LeaveModule\Personnel\EmploymentType;
use MJ\LeaveModule\Reporting\AttendanceRecord;
use MJ\LeaveModule\Reporting\Granularity;
use MJ\LeaveModule\Reporting\LeaveRecord;
use MJ\LeaveModule\Reporting\ReportFilter;
use MJ\LeaveModule\Reporting\ReportService;
use MJ\LeaveModule\Reporting\ViewerRole;

function assertTrue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException("Assertion failed: {$message}");
    }
}

$departmentService = new DepartmentService();
$departmentService->add(new Department('100', 'Level 1', null, 1, 1));
$departmentService->add(new Department('110', 'Engineering', '100', 1, 2));
assertTrue($departmentService->get('110')->superiorDepartmentCode === '100', 'Department hierarchy should be stored.');

$directory = new EmployeeDirectory([
    new EmployeeProfile(1, 'Asha', 'Engineering', new DateTimeImmutable('2025-01-01'), 30000, EmploymentType::OFFICIAL, ['NORTH']),
    new EmployeeProfile(2, 'Ravi', 'Engineering', new DateTimeImmutable('2026-02-01'), 28000, EmploymentType::PROBATION, ['NORTH']),
    new EmployeeProfile(3, 'Nita', 'Sales', new DateTimeImmutable('2024-12-01'), 26000, EmploymentType::TEMPORARY, ['SOUTH']),
]);
assertTrue($directory->find(2)->isOnProbation(new DateTimeImmutable('2026-04-01')), 'Probation flag should be detected.');

$attendancePolicy = new AttendancePolicy(1, true, true);
$attendanceService = new AttendanceService($attendancePolicy);
$acceptedPunches = $attendanceService->ingest([
    new AttendanceRecord(1, 'Engineering', new DateTimeImmutable('2026-04-01 09:00:00'), 8.0, true),
    new AttendanceRecord(1, 'Engineering', new DateTimeImmutable('2026-04-01 09:00:20'), 0.0, true),
    new AttendanceRecord(2, 'Engineering', new DateTimeImmutable('2026-04-01 09:05:00'), 9.0, true),
]);
assertTrue(count($acceptedPunches) === 2, 'Attendance ingest should remove duplicate punch records.');

$calculator = new AttendanceCalculator();
$timetable = new Timetable('09:00:00', '09:30:00', '17:00:00', '18:00:00', true, 5, 5, 30, '08:00:00');
$dayStatus = $calculator->evaluateDay(
    new DateTimeImmutable('2026-04-01 09:20:00'),
    new DateTimeImmutable('2026-04-01 17:20:00'),
    $timetable
);
assertTrue($dayStatus['late_minutes'] === 15, 'Late minutes should respect grace period.');
assertTrue($dayStatus['early_out_minutes'] === 35, 'Early-out minutes should respect grace period.');
assertTrue($dayStatus['is_absent'], 'Exceeding absence threshold should mark absent.');

$shiftScheduler = new ShiftScheduler();
$shiftScheduler->assignRecurring(1, 'WEEKDAY-MON', new ShiftDefinition('General Shift', '09:00', '18:00', 'WEEKLY'));
$shiftScheduler->assignTemporary(1, new DateTimeImmutable('2026-04-14'), new ShiftDefinition('Holiday OT', '10:00', '16:00', 'TEMPORARY'));
assertTrue($shiftScheduler->getShift(1, new DateTimeImmutable('2026-04-14'))?->name === 'Holiday OT', 'Temporary shift should override recurring shift.');

$leavePolicy = new LeavePolicyService();
$leaveApp = new LeaveApplicationService($leavePolicy, $directory);
$leaveApprovalService = new LeaveApprovalService();
$request = $leaveApp->apply(1, LeaveType::PERSONAL, new DateTimeImmutable('2026-04-15'), new DateTimeImmutable('2026-04-16'), new DateTimeImmutable('2026-04-10'));
$workflow = new ApprovalWorkflow([
    new ApprovalNode(1, 'Manager', 1),
    new ApprovalNode(2, 'Boss', 3),
]);
$leaveApprovalService->processApproval($request, 1, $workflow);
$leaveApprovalService->processApproval($request, 3, $workflow);
assertTrue($request->status === 'APPROVED', 'Leave should be approved after all workflow nodes.');
assertTrue($leaveApprovalService->shouldAutoLop(new DateTimeImmutable('2026-04-01'), new DateTimeImmutable('2026-04-03')), 'Late leave application should trigger auto-LOP.');

$probationBlocked = false;
try {
    $leaveApp->apply(2, LeaveType::PERSONAL, new DateTimeImmutable('2026-04-06'), new DateTimeImmutable('2026-04-06'), new DateTimeImmutable('2026-04-02'));
} catch (RuntimeException) {
    $probationBlocked = true;
}
assertTrue($probationBlocked, 'Probation employee should be restricted to LWP.');

$areas = [
    'NORTH' => new Area('NORTH', 'North Area', ['F22-ENG-001']),
    'SOUTH' => new Area('SOUTH', 'South Area', ['F22-SALES-001']),
];
$deviceClient = new InMemoryF22DeviceClient([
    'F22-ENG-001' => [new AttendanceRecord(1, 'Engineering', new DateTimeImmutable('2026-04-01 09:00:00'), 8.0, true)],
]);
$deviceService = new DeviceService($deviceClient);
$deviceService->syncEmployeesToDevice($directory, 'F22-ENG-001');
assertTrue($deviceService->checkHeartbeat('F22-ENG-001'), 'Heartbeat should be healthy.');
assertTrue($deviceService->startRemoteEnrollment('F22-ENG-001', 1, 'FINGERPRINT'), 'Remote enrollment should be triggered.');
assertTrue($deviceService->canUseDevice($directory->find(1), 'F22-ENG-001', $areas), 'Area mapping should allow device usage.');
assertTrue(count($deviceService->fetchDevicePunches('F22-ENG-001')) === 1, 'Device logs should be fetched.');

assertTrue($deviceClient->enrollmentCalls('F22-ENG-001')[1] === 'FINGERPRINT', 'Enrollment mode should be captured.');

$httpTransport = new InMemoryHttpTransport(
    [
        'http://10.10.10.20/api/heartbeat?serial=F22-REAL-01' => ['status' => 'ok'],
        'http://10.10.10.20/api/punch-logs?serial=F22-REAL-01' => [
            'logs' => [
                ['employee_id' => 1, 'team' => 'Engineering', 'punch_time' => '2026-04-02 09:00:00', 'worked_hours' => 8.0, 'present' => true],
                ['employee_id' => 1, 'team' => 'Engineering', 'punch_time' => '2026-04-02 09:00:20', 'worked_hours' => 0.0, 'present' => true],
            ],
        ],
    ],
    [
        'http://10.10.10.20/api/employees/sync' => ['ok' => true],
        'http://10.10.10.20/api/enrollment/remote' => ['ok' => true],
    ]
);
$httpClient = new HttpF22DeviceClient($httpTransport, [new DeviceConnectionSettings('10.10.10.20', 'F22-REAL-01', 10, 'REAL_TIME')]);
$httpDeviceService = new DeviceService($httpClient);
$httpDeviceService->syncEmployeesToDevice($directory, 'F22-REAL-01');
assertTrue($httpDeviceService->checkHeartbeat('F22-REAL-01'), 'HTTP F22 client heartbeat should be true.');
assertTrue($httpDeviceService->startRemoteEnrollment('F22-REAL-01', 1, 'FACE'), 'HTTP F22 client should trigger enrollment.');
$logSync = new DeviceLogSyncService($httpDeviceService, new AttendanceService(new AttendancePolicy(1, true, true)));
$ingested = $logSync->pullAndIngest('F22-REAL-01');
assertTrue(count($ingested) === 1, 'DeviceLogSyncService should ingest and dedupe real-device logs.');

$payroll = new PayrollService(new PayrollCalculator(), new PayrollExceptionPolicy());
$payout = $payroll->calculatePayout(
    30000,
    1,
    1,
    [new PayrollAdjustment('INCREASE', 500, 'Bonus'), new PayrollAdjustment('DEDUCTION', 200, 'Penalty')],
    new LoanRefund(12000, 12),
    20,
    10,
    false,
);
assertTrue($payout['loan_deduction'] === 1000.00, 'Loan monthly deduction should be applied.');
assertTrue($payout['extra_increase'] === 500.00, 'Extra increase should be applied.');
assertTrue($payout['extra_deduction'] === 200.00, 'Extra deduction should be applied.');

$reportService = new ReportService();
$attendance = [
    new AttendanceRecord(1, 'Engineering', new DateTimeImmutable('2026-04-01'), 8.0, true),
    new AttendanceRecord(2, 'Engineering', new DateTimeImmutable('2026-04-01'), 9.0, true),
    new AttendanceRecord(3, 'Sales', new DateTimeImmutable('2026-04-01'), 8.5, true),
];
$leaves = [
    new LeaveRecord(1, 'Engineering', new DateTimeImmutable('2026-04-10'), new DateTimeImmutable('2026-04-11'), 'PL', 'APPROVED'),
    new LeaveRecord(3, 'Sales', new DateTimeImmutable('2026-04-05'), new DateTimeImmutable('2026-04-06'), 'PL', 'APPROVED'),
];
$adminReport = $reportService->generate($attendance, $leaves, new ReportFilter(ViewerRole::ADMIN, null, null, [], Granularity::MONTHLY));
assertTrue(isset($adminReport['attendance']['by_period_and_team']['2026-04']['Engineering']), 'Admin report should include engineering team.');

echo "All tests passed\n";
