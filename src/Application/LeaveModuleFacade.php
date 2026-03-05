<?php

declare(strict_types=1);

namespace MJ\LeaveModule\Application;

use DateTimeImmutable;
use MJ\LeaveModule\Attendance\AttendanceCalculator;
use MJ\LeaveModule\Attendance\AttendancePolicy;
use MJ\LeaveModule\Attendance\AttendanceService;
use MJ\LeaveModule\Attendance\Timetable;
use MJ\LeaveModule\Device\DeviceService;
use MJ\LeaveModule\Device\HttpF22DeviceClient;
use MJ\LeaveModule\Device\InMemoryHttpTransport;
use MJ\LeaveModule\Device\DeviceConnectionSettings;
use MJ\LeaveModule\Employee\EmployeeDirectory;
use MJ\LeaveModule\Employee\EmployeeProfile;
use MJ\LeaveModule\Leave\LeaveApplicationService;
use MJ\LeaveModule\Leave\LeaveApprovalService;
use MJ\LeaveModule\Leave\LeavePolicyService;
use MJ\LeaveModule\Leave\LeaveType;
use MJ\LeaveModule\Leave\Workflow\ApprovalNode;
use MJ\LeaveModule\Leave\Workflow\ApprovalWorkflow;
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

final class LeaveModuleFacade
{
    private DepartmentService $departmentService;
    private EmployeeDirectory $employeeDirectory;
    private LeaveApplicationService $leaveApplicationService;
    private LeaveApprovalService $leaveApprovalService;
    private AttendanceService $attendanceService;
    private AttendanceCalculator $attendanceCalculator;
    private PayrollService $payrollService;
    private DeviceService $deviceService;
    private ReportService $reportService;

    /** @var AttendanceRecord[] */
    private array $attendanceRecords = [];

    /** @var LeaveRecord[] */
    private array $leaveRecords = [];

    /** @var array<string, Area> */
    private array $areas = [];

    public function __construct()
    {
        $this->departmentService = new DepartmentService();

        $employees = [
            new EmployeeProfile(1, 'Asha', 'Engineering', new DateTimeImmutable('2025-01-01'), 30000, EmploymentType::OFFICIAL, ['NORTH']),
            new EmployeeProfile(2, 'Ravi', 'Engineering', new DateTimeImmutable('2026-02-01'), 28000, EmploymentType::PROBATION, ['NORTH']),
            new EmployeeProfile(3, 'Nita', 'Sales', new DateTimeImmutable('2024-12-01'), 26000, EmploymentType::TEMPORARY, ['SOUTH']),
        ];
        $this->employeeDirectory = new EmployeeDirectory($employees);

        $leavePolicy = new LeavePolicyService();
        $this->leaveApplicationService = new LeaveApplicationService($leavePolicy, $this->employeeDirectory);
        $this->leaveApprovalService = new LeaveApprovalService();
        $this->attendanceService = new AttendanceService(new AttendancePolicy(1, true, true));
        $this->attendanceCalculator = new AttendanceCalculator();
        $this->payrollService = new PayrollService(new PayrollCalculator(), new PayrollExceptionPolicy());

        $transport = new InMemoryHttpTransport(
            [
                'http://10.10.10.20/api/heartbeat?serial=F22-REAL-01' => ['status' => 'ok'],
                'http://10.10.10.20/api/punch-logs?serial=F22-REAL-01' => ['logs' => []],
            ],
            [
                'http://10.10.10.20/api/employees/sync' => ['ok' => true],
                'http://10.10.10.20/api/enrollment/remote' => ['ok' => true],
            ]
        );
        $httpClient = new HttpF22DeviceClient($transport, [new DeviceConnectionSettings('10.10.10.20', 'F22-REAL-01')]);
        $this->deviceService = new DeviceService($httpClient);
        $this->reportService = new ReportService();

        $this->areas = [
            'NORTH' => new Area('NORTH', 'North Area', ['F22-REAL-01']),
            'SOUTH' => new Area('SOUTH', 'South Area', ['F22-SALES-01']),
        ];
    }

    public function createDepartment(string $code, string $name, ?string $superior, ?int $managerId, int $level = 1): Department
    {
        $department = new Department($code, $name, $superior, $managerId, $level);
        $this->departmentService->add($department);
        return $department;
    }

    public function applyLeave(int $employeeId, string $leaveType, string $startDate, string $endDate, string $requestedAt): array
    {
        $request = $this->leaveApplicationService->apply(
            $employeeId,
            LeaveType::from($leaveType),
            new DateTimeImmutable($startDate),
            new DateTimeImmutable($endDate),
            new DateTimeImmutable($requestedAt)
        );

        $this->leaveRecords[] = new LeaveRecord(
            $employeeId,
            $this->employeeDirectory->find($employeeId)->team,
            new DateTimeImmutable($startDate),
            new DateTimeImmutable($endDate),
            $leaveType,
            $request->status
        );

        return ['request_id' => $request->id, 'status' => $request->status];
    }

    public function approveLeave(int $requestId, int $managerId, int $bossId): string
    {
        $requests = $this->leaveApplicationService->requestsForEmployee(1);
        $target = null;
        foreach ($requests as $request) {
            if ($request->id === $requestId) {
                $target = $request;
                break;
            }
        }
        if ($target === null) {
            return 'NOT_FOUND';
        }

        $workflow = new ApprovalWorkflow([
            new ApprovalNode(1, 'Manager', $managerId),
            new ApprovalNode(2, 'Boss', $bossId),
        ]);

        $this->leaveApprovalService->processApproval($target, $managerId, $workflow);
        $this->leaveApprovalService->processApproval($target, $bossId, $workflow);

        return $target->status;
    }

    public function ingestPunch(int $employeeId, string $team, string $punchTime, float $hours, bool $present): int
    {
        $accepted = $this->attendanceService->ingest([
            new AttendanceRecord($employeeId, $team, new DateTimeImmutable($punchTime), $hours, $present),
        ]);

        foreach ($accepted as $record) {
            $this->attendanceRecords[] = $record;
        }

        return count($accepted);
    }

    /** @return array<string,mixed> */
    public function evaluateAttendanceDay(string $checkin, string $checkout): array
    {
        $timetable = new Timetable('09:00:00', '09:30:00', '17:00:00', '18:00:00', true, 5, 5, 30, '08:00:00');
        return $this->attendanceCalculator->evaluateDay(new DateTimeImmutable($checkin), new DateTimeImmutable($checkout), $timetable);
    }

    /** @return array<string,mixed> */
    public function payroll(float $grossSalary, int $lopDays, int $holidayDays, int $lateMinutes = 0, int $earlyMinutes = 0, bool $absent = false): array
    {
        return $this->payrollService->calculatePayout($grossSalary, $lopDays, $holidayDays, [], null, $lateMinutes, $earlyMinutes, $absent);
    }

    public function syncDevice(string $serial): bool
    {
        $this->deviceService->syncEmployeesToDevice($this->employeeDirectory, $serial);
        return $this->deviceService->checkHeartbeat($serial);
    }

    public function canUseDevice(int $employeeId, string $serial): bool
    {
        return $this->deviceService->canUseDevice($this->employeeDirectory->find($employeeId), $serial, $this->areas);
    }

    /** @return array<string,mixed> */
    public function report(string $role, string $granularity, ?int $viewerEmployeeId = null, ?string $team = null): array
    {
        return $this->reportService->generate(
            $this->attendanceRecords,
            $this->leaveRecords,
            new ReportFilter(
                ViewerRole::from($role),
                $viewerEmployeeId,
                $team,
                [],
                Granularity::from($granularity),
                true,
                true,
            )
        );
    }
}
