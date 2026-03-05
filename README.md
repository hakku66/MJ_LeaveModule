# MJ Leave Module

A PHP/MySQL (or PostgreSQL) based attendance, leave, and payroll module designed to integrate with **ZKTeco F22** biometric devices.

## Scope

This project covers:
- Attendance management with ZKTeco F22 device sync
- Leave management with accrual + probation rules
- Payroll processing for a 26th–25th salary cycle
- Monthly reporting for attendance and salary

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

## Technical Baseline
- Language: PHP
- Database: MySQL 8.0 or PostgreSQL
- Minimum server hardware:
  - CPU: 2.0 GHz
  - RAM: 8 GB
  - Disk: 100 GB

## Suggested Project Modules
- `src/Device/` – F22 communication + sync jobs
- `src/Attendance/` – punch normalization + policy engine
- `src/Leave/` – accrual, eligibility, and workflow
- `src/Payroll/` – salary cycle processor and deductions
- `src/Reports/` – monthly attendance/payroll outputs

## Implementation Sequence
1. Configure PHP runtime + DB connection
2. Apply database schema (`db/schema.sql`)
3. Define departments and positions
4. Register/link F22 devices (IP + serial)
5. Configure attendance/leave/payroll policies
6. Onboard employees and sync biometrics
7. Schedule attendance pull + payroll jobs
8. Generate monthly reports

## Notes for Production
- Secure device communication on trusted network segments
- Track every synchronization and policy change in audit logs
- Add retry/backoff + idempotency for log ingestion jobs
- Ensure timezone normalization before attendance calculations

## Initial Implementation (Started)

The first implementation slice is now available under `src/`:
- `src/Attendance/AttendancePolicy.php`
  - duplicate-punch filtering
  - mandatory in/out policy flags
- `src/Leave/LeavePolicyService.php`
  - probation validation (LWP-only for first 3 months)
  - advance notice validation (3 days)
  - prorated annual entitlement helper (PL/SL)
- `src/Payroll/PayrollCalculator.php`
  - salary cycle resolver (26th to 25th)
  - holiday pay calculator (1.5x)
  - LOP deduction and final payout formulas

### Run checks
```bash
php tests/run.php
```
