<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use MJ\LeaveModule\Reporting\AttendanceRecord;
use MJ\LeaveModule\Reporting\Granularity;
use MJ\LeaveModule\Reporting\LeaveRecord;
use MJ\LeaveModule\Reporting\ReportFilter;
use MJ\LeaveModule\Reporting\ReportService;
use MJ\LeaveModule\Reporting\ViewerRole;

$roleInput = strtoupper((string) ($_GET['role'] ?? ViewerRole::ADMIN->value));
$granularityInput = strtoupper((string) ($_GET['granularity'] ?? Granularity::MONTHLY->value));
$team = trim((string) ($_GET['team'] ?? ''));
$employeeIdInput = trim((string) ($_GET['employee_id'] ?? ''));

$role = ViewerRole::tryFrom($roleInput) ?? ViewerRole::ADMIN;
$granularity = Granularity::tryFrom($granularityInput) ?? Granularity::MONTHLY;
$viewerEmployeeId = $employeeIdInput !== '' ? (int) $employeeIdInput : null;

$attendance = [
    new AttendanceRecord(1, 'Engineering', new DateTimeImmutable('2026-04-01'), 8.0, true),
    new AttendanceRecord(1, 'Engineering', new DateTimeImmutable('2026-04-02'), 7.5, true),
    new AttendanceRecord(2, 'Engineering', new DateTimeImmutable('2026-04-01'), 9.0, true),
    new AttendanceRecord(3, 'Sales', new DateTimeImmutable('2026-04-01'), 8.5, true),
    new AttendanceRecord(4, 'HR', new DateTimeImmutable('2026-04-01'), 7.0, true),
];

$leaves = [
    new LeaveRecord(1, 'Engineering', new DateTimeImmutable('2026-04-10'), new DateTimeImmutable('2026-04-11'), 'PL', 'APPROVED'),
    new LeaveRecord(2, 'Engineering', new DateTimeImmutable('2026-04-20'), new DateTimeImmutable('2026-04-20'), 'SL', 'PENDING'),
    new LeaveRecord(3, 'Sales', new DateTimeImmutable('2026-04-05'), new DateTimeImmutable('2026-04-06'), 'PL', 'APPROVED'),
    new LeaveRecord(4, 'HR', new DateTimeImmutable('2026-04-15'), new DateTimeImmutable('2026-04-15'), 'LWP', 'APPROVED'),
];

$service = new ReportService();
$error = null;
$report = [];

try {
    $report = $service->generate(
        $attendance,
        $leaves,
        new ReportFilter(
            $role,
            $viewerEmployeeId,
            $team !== '' ? $team : null,
            [],
            $granularity,
            true,
            true
        )
    );
} catch (Throwable $exception) {
    $error = $exception->getMessage();
}

function printRows(array $rows, array $columns): void
{
    foreach ($rows as $key => $metrics) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8') . '</td>';
        foreach ($columns as $column) {
            $value = $metrics[$column] ?? '';
            echo '<td>' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '</td>';
        }
        echo '</tr>';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>MJ Leave Module (XAMPP Demo)</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; background: #f7f9fc; }
        h1 { margin-bottom: 8px; }
        .sub { color: #555; margin-top: 0; }
        .card { background: #fff; border-radius: 8px; padding: 16px; margin-bottom: 16px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
        label { margin-right: 8px; font-size: 14px; }
        input, select { margin-right: 12px; padding: 6px; }
        button { padding: 8px 12px; }
        table { border-collapse: collapse; width: 100%; background: #fff; }
        th, td { border: 1px solid #ddd; padding: 8px; font-size: 13px; }
        th { background: #f1f5fb; text-align: left; }
        .error { color: #a30000; font-weight: bold; }
    </style>
</head>
<body>
    <h1>MJ Leave Module</h1>
    <p class="sub">XAMPP-ready demo for Admin / Manager / Employee attendance and leave reporting.</p>

    <div class="card">
        <form method="get">
            <label for="role">Role</label>
            <select id="role" name="role">
                <?php foreach (ViewerRole::cases() as $case): ?>
                    <option value="<?= htmlspecialchars($case->value, ENT_QUOTES, 'UTF-8') ?>" <?= $case === $role ? 'selected' : '' ?>>
                        <?= htmlspecialchars($case->value, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="granularity">Granularity</label>
            <select id="granularity" name="granularity">
                <?php foreach (Granularity::cases() as $case): ?>
                    <option value="<?= htmlspecialchars($case->value, ENT_QUOTES, 'UTF-8') ?>" <?= $case === $granularity ? 'selected' : '' ?>>
                        <?= htmlspecialchars($case->value, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="team">Team</label>
            <input id="team" name="team" placeholder="Engineering" value="<?= htmlspecialchars($team, ENT_QUOTES, 'UTF-8') ?>">

            <label for="employee_id">Employee ID (for EMPLOYEE role)</label>
            <input id="employee_id" name="employee_id" placeholder="1" value="<?= htmlspecialchars($employeeIdInput, ENT_QUOTES, 'UTF-8') ?>">

            <button type="submit">Generate Report</button>
        </form>
    </div>

    <?php if ($error !== null): ?>
        <div class="card error">Error: <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php else: ?>
        <div class="card">
            <h3>Report Meta</h3>
            <pre><?= htmlspecialchars(json_encode($report['meta'] ?? [], JSON_PRETTY_PRINT), ENT_QUOTES, 'UTF-8') ?></pre>
        </div>

        <?php if (isset($report['attendance']['by_period_and_user'])): ?>
            <div class="card">
                <h3>Attendance: By Period and User</h3>
                <?php foreach ($report['attendance']['by_period_and_user'] as $period => $rows): ?>
                    <h4><?= htmlspecialchars((string) $period, ENT_QUOTES, 'UTF-8') ?></h4>
                    <table>
                        <thead><tr><th>User</th><th>Team</th><th>Worked Hours</th><th>Present Days</th><th>Records</th></tr></thead>
                        <tbody><?php printRows($rows, ['team', 'worked_hours', 'present_days', 'records']); ?></tbody>
                    </table>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($report['attendance']['by_period_and_team'])): ?>
            <div class="card">
                <h3>Attendance: By Period and Team</h3>
                <?php foreach ($report['attendance']['by_period_and_team'] as $period => $rows): ?>
                    <h4><?= htmlspecialchars((string) $period, ENT_QUOTES, 'UTF-8') ?></h4>
                    <table>
                        <thead><tr><th>Team</th><th>Worked Hours</th><th>Present Days</th><th>Records</th></tr></thead>
                        <tbody><?php printRows($rows, ['worked_hours', 'present_days', 'records']); ?></tbody>
                    </table>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($report['leaves']['by_period_and_user'])): ?>
            <div class="card">
                <h3>Leaves: By Period and User</h3>
                <?php foreach ($report['leaves']['by_period_and_user'] as $period => $rows): ?>
                    <h4><?= htmlspecialchars((string) $period, ENT_QUOTES, 'UTF-8') ?></h4>
                    <table>
                        <thead><tr><th>User</th><th>Team</th><th>Leave Days</th><th>Leave Requests</th><th>Approved</th></tr></thead>
                        <tbody><?php printRows($rows, ['team', 'leave_days', 'leave_requests', 'approved_requests']); ?></tbody>
                    </table>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($report['leaves']['by_period_and_team'])): ?>
            <div class="card">
                <h3>Leaves: By Period and Team</h3>
                <?php foreach ($report['leaves']['by_period_and_team'] as $period => $rows): ?>
                    <h4><?= htmlspecialchars((string) $period, ENT_QUOTES, 'UTF-8') ?></h4>
                    <table>
                        <thead><tr><th>Team</th><th>Leave Days</th><th>Leave Requests</th><th>Approved</th></tr></thead>
                        <tbody><?php printRows($rows, ['leave_days', 'leave_requests', 'approved_requests']); ?></tbody>
                    </table>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</body>
</html>
