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
