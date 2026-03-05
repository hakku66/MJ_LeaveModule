<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../src/Application/LeaveModuleFacade.php';

use MJ\LeaveModule\Application\LeaveModuleFacade;

header('Content-Type: application/json');

$action = (string) ($_GET['action'] ?? 'help');
$app = new LeaveModuleFacade();

try {
    $result = match ($action) {
        'create_department' => $app->createDepartment(
            (string) ($_GET['code'] ?? '100'),
            (string) ($_GET['name'] ?? 'Level 1'),
            ($_GET['superior'] ?? null) !== null ? (string) $_GET['superior'] : null,
            ($_GET['manager_id'] ?? null) !== null ? (int) $_GET['manager_id'] : null,
            (int) ($_GET['level'] ?? 1),
        ),
        'apply_leave' => $app->applyLeave(
            (int) ($_GET['employee_id'] ?? 1),
            (string) ($_GET['leave_type'] ?? 'PL'),
            (string) ($_GET['start'] ?? '2026-04-15'),
            (string) ($_GET['end'] ?? '2026-04-16'),
            (string) ($_GET['requested_at'] ?? '2026-04-10'),
        ),
        'ingest_punch' => [
            'accepted' => $app->ingestPunch(
                (int) ($_GET['employee_id'] ?? 1),
                (string) ($_GET['team'] ?? 'Engineering'),
                (string) ($_GET['punch_time'] ?? '2026-04-01 09:00:00'),
                (float) ($_GET['hours'] ?? 8),
                (bool) ($_GET['present'] ?? true),
            ),
        ],
        'attendance_day' => $app->evaluateAttendanceDay(
            (string) ($_GET['checkin'] ?? '2026-04-01 09:20:00'),
            (string) ($_GET['checkout'] ?? '2026-04-01 17:20:00'),
        ),
        'payroll' => $app->payroll(
            (float) ($_GET['gross_salary'] ?? 30000),
            (int) ($_GET['lop_days'] ?? 1),
            (int) ($_GET['holiday_days'] ?? 1),
            (int) ($_GET['late_minutes'] ?? 0),
            (int) ($_GET['early_minutes'] ?? 0),
            (bool) ($_GET['absent'] ?? false),
        ),
        'sync_device' => ['heartbeat_ok' => $app->syncDevice((string) ($_GET['serial'] ?? 'F22-REAL-01'))],
        'can_use_device' => ['allowed' => $app->canUseDevice((int) ($_GET['employee_id'] ?? 1), (string) ($_GET['serial'] ?? 'F22-REAL-01'))],
        'report' => $app->report(
            (string) ($_GET['role'] ?? 'ADMIN'),
            (string) ($_GET['granularity'] ?? 'MONTHLY'),
            ($_GET['viewer_employee_id'] ?? null) !== null ? (int) $_GET['viewer_employee_id'] : null,
            ($_GET['team'] ?? null) !== null ? (string) $_GET['team'] : null,
        ),
        default => [
            'actions' => [
                'create_department', 'apply_leave', 'ingest_punch', 'attendance_day',
                'payroll', 'sync_device', 'can_use_device', 'report'
            ],
        ],
    };

    echo json_encode(['ok' => true, 'action' => $action, 'result' => $result], JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'action' => $action, 'error' => $e->getMessage()], JSON_PRETTY_PRINT);
}
