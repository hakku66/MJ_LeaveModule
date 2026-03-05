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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MJ Leave Module (XAMPP Demo)</title>
    <style>
        :root {
            --mj-teal-light: #7FCED3;
            --mj-teal-core: #41969F;
            --mj-teal-deep: #0E4A50;
            --mj-mist-white: #E6F5F4;
            --mj-pure-white: #FEFEFE;
            --mj-graphite: #404142;
            --mj-border-soft: rgba(127, 206, 211, 0.35);
        }

        * { box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background: var(--mj-mist-white);
            color: var(--mj-graphite);
        }

        .topbar {
            background: var(--mj-teal-deep);
            color: var(--mj-pure-white);
            padding: 18px 24px;
            border-bottom: 4px solid var(--mj-teal-core);
        }

        .topbar h1 {
            margin: 0;
            font-size: 26px;
            letter-spacing: 0.4px;
        }

        .topbar p {
            margin: 6px 0 0;
            color: var(--mj-teal-light);
            font-size: 14px;
        }

        .container {
            max-width: 1200px;
            margin: 18px auto;
            padding: 0 16px 20px;
        }

        .card {
            background: var(--mj-pure-white);
            border: 1px solid var(--mj-border-soft);
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 16px;
            box-shadow: 0 2px 6px rgba(14, 74, 80, 0.08);
        }

        h2, h3, h4 { color: var(--mj-teal-deep); margin-top: 0; }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 12px;
            align-items: end;
        }

        label { display: block; font-size: 13px; margin-bottom: 6px; color: var(--mj-graphite); }

        input, select {
            width: 100%;
            border: 1px solid var(--mj-border-soft);
            border-radius: 6px;
            padding: 8px 10px;
            background: var(--mj-pure-white);
            color: var(--mj-graphite);
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--mj-teal-core);
            box-shadow: 0 0 0 3px rgba(127, 206, 211, 0.35);
        }

        button {
            width: 100%;
            border: none;
            border-radius: 6px;
            padding: 10px 12px;
            cursor: pointer;
            color: var(--mj-pure-white);
            background: var(--mj-teal-core);
            font-weight: 600;
        }

        button:hover { background: var(--mj-teal-light); color: var(--mj-teal-deep); }
        button:active { background: var(--mj-teal-deep); color: var(--mj-pure-white); }

        table {
            border-collapse: collapse;
            width: 100%;
            background: var(--mj-pure-white);
            border: 1px solid var(--mj-border-soft);
            margin-bottom: 12px;
        }

        th, td { border: 1px solid var(--mj-border-soft); padding: 8px; font-size: 13px; }
        th { background: var(--mj-mist-white); color: var(--mj-teal-deep); text-align: left; }
        tr:nth-child(even) td { background: rgba(230, 245, 244, 0.4); }

        .error {
            color: #7f1d1d;
            border-left: 5px solid #7f1d1d;
            background: #fff1f1;
        }

        pre {
            margin: 0;
            padding: 12px;
            border-radius: 8px;
            background: var(--mj-mist-white);
            border: 1px solid var(--mj-border-soft);
            color: var(--mj-graphite);
        }
    </style>
</head>
<body>
    <header class="topbar">
        <h1>MJ DESIGNS • Leave Module</h1>
        <p>XAMPP-ready reporting dashboard (Admin / Manager / Employee)</p>
    </header>

    <main class="container">
        <section class="card">
            <h2>Report Filters</h2>
            <form method="get" class="form-grid">
                <div>
                    <label for="role">Role</label>
                    <select id="role" name="role">
                        <?php foreach (ViewerRole::cases() as $case): ?>
                            <option value="<?= htmlspecialchars($case->value, ENT_QUOTES, 'UTF-8') ?>" <?= $case === $role ? 'selected' : '' ?>>
                                <?= htmlspecialchars($case->value, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="granularity">Granularity</label>
                    <select id="granularity" name="granularity">
                        <?php foreach (Granularity::cases() as $case): ?>
                            <option value="<?= htmlspecialchars($case->value, ENT_QUOTES, 'UTF-8') ?>" <?= $case === $granularity ? 'selected' : '' ?>>
                                <?= htmlspecialchars($case->value, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="team">Team</label>
                    <input id="team" name="team" placeholder="Engineering" value="<?= htmlspecialchars($team, ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div>
                    <label for="employee_id">Employee ID</label>
                    <input id="employee_id" name="employee_id" placeholder="1" value="<?= htmlspecialchars($employeeIdInput, ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div>
                    <label>&nbsp;</label>
                    <button type="submit">Generate Report</button>
                </div>
            </form>
        </section>

        <?php if ($error !== null): ?>
            <div class="card error">Error: <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php else: ?>
            <section class="card">
                <h3>Report Meta</h3>
                <pre><?= htmlspecialchars(json_encode($report['meta'] ?? [], JSON_PRETTY_PRINT), ENT_QUOTES, 'UTF-8') ?></pre>
            </section>

            <?php if (isset($report['attendance']['by_period_and_user'])): ?>
                <section class="card">
                    <h3>Attendance: By Period and User</h3>
                    <?php foreach ($report['attendance']['by_period_and_user'] as $period => $rows): ?>
                        <h4><?= htmlspecialchars((string) $period, ENT_QUOTES, 'UTF-8') ?></h4>
                        <table>
                            <thead><tr><th>User</th><th>Team</th><th>Worked Hours</th><th>Present Days</th><th>Records</th></tr></thead>
                            <tbody><?php printRows($rows, ['team', 'worked_hours', 'present_days', 'records']); ?></tbody>
                        </table>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>

            <?php if (isset($report['attendance']['by_period_and_team'])): ?>
                <section class="card">
                    <h3>Attendance: By Period and Team</h3>
                    <?php foreach ($report['attendance']['by_period_and_team'] as $period => $rows): ?>
                        <h4><?= htmlspecialchars((string) $period, ENT_QUOTES, 'UTF-8') ?></h4>
                        <table>
                            <thead><tr><th>Team</th><th>Worked Hours</th><th>Present Days</th><th>Records</th></tr></thead>
                            <tbody><?php printRows($rows, ['worked_hours', 'present_days', 'records']); ?></tbody>
                        </table>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>

            <?php if (isset($report['leaves']['by_period_and_user'])): ?>
                <section class="card">
                    <h3>Leaves: By Period and User</h3>
                    <?php foreach ($report['leaves']['by_period_and_user'] as $period => $rows): ?>
                        <h4><?= htmlspecialchars((string) $period, ENT_QUOTES, 'UTF-8') ?></h4>
                        <table>
                            <thead><tr><th>User</th><th>Team</th><th>Leave Days</th><th>Leave Requests</th><th>Approved</th></tr></thead>
                            <tbody><?php printRows($rows, ['team', 'leave_days', 'leave_requests', 'approved_requests']); ?></tbody>
                        </table>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>

            <?php if (isset($report['leaves']['by_period_and_team'])): ?>
                <section class="card">
                    <h3>Leaves: By Period and Team</h3>
                    <?php foreach ($report['leaves']['by_period_and_team'] as $period => $rows): ?>
                        <h4><?= htmlspecialchars((string) $period, ENT_QUOTES, 'UTF-8') ?></h4>
                        <table>
                            <thead><tr><th>Team</th><th>Leave Days</th><th>Leave Requests</th><th>Approved</th></tr></thead>
                            <tbody><?php printRows($rows, ['leave_days', 'leave_requests', 'approved_requests']); ?></tbody>
                        </table>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>
        <?php endif; ?>
    </main>
</body>
</html>
