# MJ Leave Module

A user-friendly PHP attendance, leave, and payroll module designed to integrate with **ZKTeco F22** biometric devices.

## Run inside XAMPP (Windows/Linux)

1. Copy this repository into your XAMPP web root:
   - Windows: `C:\xampp\htdocs\MJ_LeaveModule`
   - Linux: `/opt/lampp/htdocs/MJ_LeaveModule`
2. Start **Apache** from the XAMPP control panel.
3. Open in browser:
   - `http://localhost/MJ_LeaveModule/public/`
4. Use the filters at the top:
   - Role: `ADMIN`, `MANAGER`, or `EMPLOYEE`
   - Granularity: `DAILY`, `WEEKLY`, or `MONTHLY`
   - Team: required for manager-scoped views
   - Employee ID: required for employee role

> Note: This current UI is a demo/reporting shell using in-memory sample data so you can run quickly in XAMPP before DB wiring.

> DB schema note: `db/schema.sql` is written for MariaDB/MySQL (XAMPP) using `AUTO_INCREMENT` and InnoDB foreign keys.


## Branding
The demo UI in `public/index.php` follows the MJ brand palette:
- MJ Teal Light `#7FCED3`
- MJ Teal Core `#41969F`
- MJ Teal Deep `#0E4A50`
- MJ Mist White `#E6F5F4`
- MJ Pure White `#FEFEFE`
- MJ Graphite `#404142`

## What this system does
- Tracks attendance from ZKTeco F22 devices.
- Applies leave and probation rules automatically.
- Calculates payroll outputs such as LOP and holiday pay.
- Provides role-based views for **Admin**, **Manager**, and **Employee**.

## Role-based views (implemented)

### Admin
Admins can view all combinations across the organization:
- Attendance and leave data for any employee
- Team-level summaries across all teams
- Daily, weekly, and monthly breakdowns
- User-level and team-level totals in the same report payload

### Manager
Managers can view all combinations within their own team scope:
- Attendance and leave for all team members
- Daily, weekly, and monthly views
- Team totals + per-user totals for their team

### Employee
Employees can view only their own data:
- Personal attendance and leave history
- Daily, weekly, and monthly summaries

## Functional Coverage

### 1) Attendance Management
- HTTP-based device communication for F22 integration
- Employee sync: ID, name, biometric template references
- Punch log ingestion: Check-in, Check-out, OT-in, OT-out
- Duplicate punch policy (`minimum interval`, default: 1 minute)
- Configurable mandatory check-in/check-out policy

### 2) Leave Management
- Leave types:
  - PL (Personal Leave)
  - SL (Sick Leave)
  - ML (Marriage Leave)
  - LWP (Leave Without Pay)
- Accrual:
  - PL = 1 day/month (12/year)
  - SL = 3/year
- Probation restriction:
  - First 3 months = only LWP allowed
- Workflow:
  - Leave request at least 3 days in advance

### 3) Payroll Processing
- Salary cycle: 26th to 25th
- Loss of Pay (LOP) rules:
  - Unapproved leave
  - Late leave application (submitted after 1 day from leave start)
- Absence trigger:
  - Late-coming/early-leaving beyond configured limits
- Holiday compensation:
  - 1.5x regular daily salary for work on observed holidays

## Newly implemented operational modules
- Personnel Management
  - `src/Personnel/DepartmentService.php`: multi-level department tree with manager assignment and code/name validation
  - `src/Personnel/EmploymentType.php`: Official / Temporary / Probation classifications
  - `src/Personnel/Area.php`: area-to-device mapping for device access control
- Device Management (ZKTeco F22)
  - `src/Device/DeviceService.php`: sync employees, pull punches, heartbeat checks, and remote enrollment trigger
  - `src/Device/DeviceConnectionSettings.php`: HTTP/IP/serial + heartbeat + real-time sync settings
  - `src/Device/HttpF22DeviceClient.php`: real HTTP client implementation for F22 endpoints (sync/punch/heartbeat/enrollment)
  - `src/Device/DeviceLogSyncService.php`: pulls raw logs from device and ingests through attendance dedupe policy
- Attendance Management
  - `src/Attendance/Timetable.php`: check-in/check-out windows, grace minutes, mandatory clock and day-change time
  - `src/Attendance/AttendanceCalculator.php`: late/early/absent evaluation logic
  - `src/Attendance/Shift/ShiftScheduler.php`: recurring + temporary shift assignment
- Leave and Approval
  - `src/Leave/LeaveApplicationService.php`: leave apply with probation and deadline enforcement
  - `src/Leave/LeaveApprovalService.php`: node-based multi-level approval + auto-LOP check
- Payroll
  - `src/Payroll/PayrollService.php`: cycle payout with LOP, holiday pay, exception deductions, loan refunds, and manual adjustments

## Implemented code modules
- `src/Attendance/AttendancePolicy.php`
- `src/Leave/LeaveType.php`
- `src/Leave/LeavePolicyService.php`
- `src/Payroll/PayrollCalculator.php`
- `src/Reporting/ReportService.php`
  - Role-based access scopes (Admin/Manager/Employee)
  - Day/week/month report modes
  - Attendance + leave aggregations by user and by team
- `public/index.php`
  - XAMPP-ready UI entrypoint for report viewing

## Module API (implemented now)
A single integrated API entrypoint is available at `public/api.php` for all major modules.

Example calls in browser (XAMPP):
- `http://localhost/MJ_LeaveModule/public/api.php?action=create_department&code=100&name=Level%201`
- `http://localhost/MJ_LeaveModule/public/api.php?action=apply_leave&employee_id=1&leave_type=PL&start=2026-05-10&end=2026-05-11&requested_at=2026-05-05`
- `http://localhost/MJ_LeaveModule/public/api.php?action=ingest_punch&employee_id=1&team=Engineering&punch_time=2026-05-01%2009:00:00&hours=8`
- `http://localhost/MJ_LeaveModule/public/api.php?action=attendance_day&checkin=2026-05-01%2009:20:00&checkout=2026-05-01%2017:20:00`
- `http://localhost/MJ_LeaveModule/public/api.php?action=payroll&gross_salary=30000&lop_days=1&holiday_days=1`
- `http://localhost/MJ_LeaveModule/public/api.php?action=sync_device&serial=F22-REAL-01`
- `http://localhost/MJ_LeaveModule/public/api.php?action=can_use_device&employee_id=1&serial=F22-REAL-01`
- `http://localhost/MJ_LeaveModule/public/api.php?action=report&role=ADMIN&granularity=MONTHLY`

## Technical Baseline
- Language: PHP (>= 8.1)
- Database: MySQL 8.0 or PostgreSQL
- Minimum server hardware:
  - CPU: 2.0 GHz
  - RAM: 8 GB
  - Disk: 100 GB

## Run checks
```bash
php tests/run.php
```


## Real F22 integration implementation
- Use `HttpF22DeviceClient` with `CurlHttpTransport` and `DeviceConnectionSettings` to connect to live devices over HTTP.
- Current endpoint paths are configurable in code assumptions (`/api/employees/sync`, `/api/punch-logs`, `/api/heartbeat`, `/api/enrollment/remote`) and can be adapted to your deployed F22 gateway/API shape.

