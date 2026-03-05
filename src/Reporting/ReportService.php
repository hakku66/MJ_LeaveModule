<?php

declare(strict_types=1);

namespace MJ\LeaveModule\Reporting;

use InvalidArgumentException;

final class ReportService
{
    /**
     * @param AttendanceRecord[] $attendance
     * @param LeaveRecord[] $leaves
     * @return array<string, mixed>
     */
    public function generate(array $attendance, array $leaves, ReportFilter $filter): array
    {
        [$attendanceScope, $leaveScope] = $this->applyScope($attendance, $leaves, $filter);

        $report = [
            'meta' => [
                'role' => $filter->viewerRole->value,
                'team' => $filter->team,
                'employee_ids' => $filter->employeeIds,
                'granularity' => $filter->granularity->value,
            ],
        ];

        if ($filter->includeAttendance) {
            $report['attendance'] = [
                'by_period_and_user' => $this->aggregateAttendanceByPeriodAndUser($attendanceScope, $filter->granularity),
                'by_period_and_team' => $this->aggregateAttendanceByPeriodAndTeam($attendanceScope, $filter->granularity),
            ];
        }

        if ($filter->includeLeaves) {
            $report['leaves'] = [
                'by_period_and_user' => $this->aggregateLeavesByPeriodAndUser($leaveScope, $filter->granularity),
                'by_period_and_team' => $this->aggregateLeavesByPeriodAndTeam($leaveScope, $filter->granularity),
            ];
        }

        return $report;
    }

    /**
     * @param AttendanceRecord[] $attendance
     * @param LeaveRecord[] $leaves
     * @return array{0: AttendanceRecord[], 1: LeaveRecord[]}
     */
    private function applyScope(array $attendance, array $leaves, ReportFilter $filter): array
    {
        return match ($filter->viewerRole) {
            ViewerRole::ADMIN => [
                $this->filterAttendance($attendance, $filter->team, $filter->employeeIds),
                $this->filterLeaves($leaves, $filter->team, $filter->employeeIds),
            ],
            ViewerRole::MANAGER => $this->managerScope($attendance, $leaves, $filter),
            ViewerRole::EMPLOYEE => $this->employeeScope($attendance, $leaves, $filter),
        };
    }

    /**
     * @param AttendanceRecord[] $attendance
     * @param LeaveRecord[] $leaves
     * @return array{0: AttendanceRecord[], 1: LeaveRecord[]}
     */
    private function managerScope(array $attendance, array $leaves, ReportFilter $filter): array
    {
        if ($filter->team === null || $filter->team === '') {
            throw new InvalidArgumentException('Manager view requires a team scope.');
        }

        return [
            $this->filterAttendance($attendance, $filter->team, $filter->employeeIds),
            $this->filterLeaves($leaves, $filter->team, $filter->employeeIds),
        ];
    }

    /**
     * @param AttendanceRecord[] $attendance
     * @param LeaveRecord[] $leaves
     * @return array{0: AttendanceRecord[], 1: LeaveRecord[]}
     */
    private function employeeScope(array $attendance, array $leaves, ReportFilter $filter): array
    {
        if ($filter->viewerEmployeeId === null) {
            throw new InvalidArgumentException('Employee view requires viewerEmployeeId.');
        }

        return [
            $this->filterAttendance($attendance, null, [$filter->viewerEmployeeId]),
            $this->filterLeaves($leaves, null, [$filter->viewerEmployeeId]),
        ];
    }

    /**
     * @param AttendanceRecord[] $records
     * @param int[] $employeeIds
     * @return AttendanceRecord[]
     */
    private function filterAttendance(array $records, ?string $team, array $employeeIds): array
    {
        return array_values(array_filter(
            $records,
            static function (AttendanceRecord $record) use ($team, $employeeIds): bool {
                if ($team !== null && $record->team !== $team) {
                    return false;
                }

                return $employeeIds === [] || in_array($record->employeeId, $employeeIds, true);
            }
        ));
    }

    /**
     * @param LeaveRecord[] $records
     * @param int[] $employeeIds
     * @return LeaveRecord[]
     */
    private function filterLeaves(array $records, ?string $team, array $employeeIds): array
    {
        return array_values(array_filter(
            $records,
            static function (LeaveRecord $record) use ($team, $employeeIds): bool {
                if ($team !== null && $record->team !== $team) {
                    return false;
                }

                return $employeeIds === [] || in_array($record->employeeId, $employeeIds, true);
            }
        ));
    }

    /** @param AttendanceRecord[] $records */
    private function aggregateAttendanceByPeriodAndUser(array $records, Granularity $granularity): array
    {
        $result = [];

        foreach ($records as $record) {
            $period = $this->periodKey($record->date, $granularity);
            $employee = (string) $record->employeeId;

            if (!isset($result[$period][$employee])) {
                $result[$period][$employee] = [
                    'team' => $record->team,
                    'worked_hours' => 0.0,
                    'present_days' => 0,
                    'records' => 0,
                ];
            }

            $result[$period][$employee]['worked_hours'] += $record->workedHours;
            $result[$period][$employee]['present_days'] += $record->present ? 1 : 0;
            $result[$period][$employee]['records'] += 1;
        }

        return $result;
    }

    /** @param AttendanceRecord[] $records */
    private function aggregateAttendanceByPeriodAndTeam(array $records, Granularity $granularity): array
    {
        $result = [];

        foreach ($records as $record) {
            $period = $this->periodKey($record->date, $granularity);
            $team = $record->team;

            if (!isset($result[$period][$team])) {
                $result[$period][$team] = [
                    'worked_hours' => 0.0,
                    'present_days' => 0,
                    'records' => 0,
                ];
            }

            $result[$period][$team]['worked_hours'] += $record->workedHours;
            $result[$period][$team]['present_days'] += $record->present ? 1 : 0;
            $result[$period][$team]['records'] += 1;
        }

        return $result;
    }

    /** @param LeaveRecord[] $records */
    private function aggregateLeavesByPeriodAndUser(array $records, Granularity $granularity): array
    {
        $result = [];

        foreach ($records as $record) {
            $period = $this->periodKey($record->startDate, $granularity);
            $employee = (string) $record->employeeId;

            if (!isset($result[$period][$employee])) {
                $result[$period][$employee] = [
                    'team' => $record->team,
                    'leave_days' => 0,
                    'leave_requests' => 0,
                    'approved_requests' => 0,
                ];
            }

            $result[$period][$employee]['leave_days'] += $record->leaveDays();
            $result[$period][$employee]['leave_requests'] += 1;
            $result[$period][$employee]['approved_requests'] += $record->status === 'APPROVED' ? 1 : 0;
        }

        return $result;
    }

    /** @param LeaveRecord[] $records */
    private function aggregateLeavesByPeriodAndTeam(array $records, Granularity $granularity): array
    {
        $result = [];

        foreach ($records as $record) {
            $period = $this->periodKey($record->startDate, $granularity);
            $team = $record->team;

            if (!isset($result[$period][$team])) {
                $result[$period][$team] = [
                    'leave_days' => 0,
                    'leave_requests' => 0,
                    'approved_requests' => 0,
                ];
            }

            $result[$period][$team]['leave_days'] += $record->leaveDays();
            $result[$period][$team]['leave_requests'] += 1;
            $result[$period][$team]['approved_requests'] += $record->status === 'APPROVED' ? 1 : 0;
        }

        return $result;
    }

    private function periodKey(\DateTimeImmutable $date, Granularity $granularity): string
    {
        return match ($granularity) {
            Granularity::DAILY => $date->format('Y-m-d'),
            Granularity::WEEKLY => $date->format('o-\\WW'),
            Granularity::MONTHLY => $date->format('Y-m'),
        };
    }
}
