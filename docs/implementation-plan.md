# Implementation Plan

## Phase 1: Foundation
- Setup PHP project skeleton and environment variables
- Configure MySQL/PostgreSQL connection
- Apply schema from `db/schema.sql`
- Seed policy defaults and leave types

## Phase 2: Device Integration (ZKTeco F22)
- Build HTTP client wrappers for device operations
- Implement employee sync (ID, name, biometrics)
- Implement punch log pull (real-time poll / scheduled batch)
- Add de-duplication by employee + punch state + interval

## Phase 3: Attendance Engine
- Normalize raw logs into attendance events
- Enforce mandatory check-in/out settings
- Track late-in and early-out thresholds
- Flag anomalies for payroll calculations

## Phase 4: Leave Engine
- Build leave request workflow (submit/approve/reject)
- Enforce 3-day advance request rule
- Enforce probation rule (LWP only for first 3 months)
- Implement PL/SL accrual jobs

## Phase 5: Payroll Engine
- Implement 26th–25th cycle resolver
- Compute LOP for unapproved/late leave applications
- Compute holiday compensation at 1.5x daily rate
- Generate final payout records

## Phase 6: Reporting & Operations
- Monthly attendance summary report
- Monthly payroll report
- Sync logs, job status, and exception dashboard
- Production hardening (audit logs, retries, alerts)
