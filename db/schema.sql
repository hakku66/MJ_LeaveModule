-- Core schema for MJ Leave Module

CREATE TABLE departments (
  id BIGINT PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
  name VARCHAR(120) NOT NULL UNIQUE
);

CREATE TABLE positions (
  id BIGINT PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
  name VARCHAR(120) NOT NULL UNIQUE
);

CREATE TABLE employees (
  id BIGINT PRIMARY KEY,
  employee_code VARCHAR(50) NOT NULL UNIQUE,
  full_name VARCHAR(180) NOT NULL,
  department_id BIGINT REFERENCES departments(id),
  position_id BIGINT REFERENCES positions(id),
  hire_date DATE NOT NULL,
  probation_end_date DATE NOT NULL,
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE devices (
  id BIGINT PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
  serial_number VARCHAR(100) NOT NULL UNIQUE,
  ip_address VARCHAR(64) NOT NULL,
  protocol VARCHAR(20) NOT NULL DEFAULT 'HTTP',
  is_active BOOLEAN NOT NULL DEFAULT TRUE,
  last_synced_at TIMESTAMP NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE employee_biometrics (
  id BIGINT PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
  employee_id BIGINT NOT NULL REFERENCES employees(id),
  template_type VARCHAR(20) NOT NULL, -- fingerprint, face, etc.
  template_data TEXT NOT NULL,
  template_hash VARCHAR(128) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (employee_id, template_type, template_hash)
);

CREATE TABLE attendance_logs (
  id BIGINT PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
  employee_id BIGINT NOT NULL REFERENCES employees(id),
  punch_time TIMESTAMP NOT NULL,
  punch_state VARCHAR(20) NOT NULL, -- CHECK_IN, CHECK_OUT, OT_IN, OT_OUT
  device_id BIGINT NOT NULL REFERENCES devices(id),
  raw_payload JSON,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE leave_types (
  code VARCHAR(10) PRIMARY KEY, -- PL, SL, ML, LWP
  name VARCHAR(80) NOT NULL,
  annual_quota NUMERIC(5,2) NULL
);

INSERT INTO leave_types (code, name, annual_quota) VALUES
  ('PL', 'Personal Leave', 12.00),
  ('SL', 'Sick Leave', 3.00),
  ('ML', 'Marriage Leave', NULL),
  ('LWP', 'Leave Without Pay', NULL);

CREATE TABLE leave_requests (
  id BIGINT PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
  employee_id BIGINT NOT NULL REFERENCES employees(id),
  leave_type_code VARCHAR(10) NOT NULL REFERENCES leave_types(code),
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  reason TEXT,
  approval_status VARCHAR(20) NOT NULL DEFAULT 'PENDING',
  submitted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  approved_by BIGINT NULL REFERENCES employees(id),
  approved_at TIMESTAMP NULL
);

CREATE TABLE payroll_records (
  id BIGINT PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
  employee_id BIGINT NOT NULL REFERENCES employees(id),
  cycle_start DATE NOT NULL,
  cycle_end DATE NOT NULL,
  gross_salary NUMERIC(12,2) NOT NULL,
  lop_deduction NUMERIC(12,2) NOT NULL DEFAULT 0,
  holiday_pay NUMERIC(12,2) NOT NULL DEFAULT 0,
  final_payout NUMERIC(12,2) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (employee_id, cycle_start, cycle_end)
);

CREATE TABLE policy_settings (
  id BIGINT PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
  duplicate_punch_minutes INT NOT NULL DEFAULT 1,
  checkin_mandatory BOOLEAN NOT NULL DEFAULT TRUE,
  checkout_mandatory BOOLEAN NOT NULL DEFAULT TRUE,
  late_limit_minutes INT NOT NULL DEFAULT 15,
  early_leave_limit_minutes INT NOT NULL DEFAULT 15,
  leave_request_min_days_advance INT NOT NULL DEFAULT 3,
  leave_late_application_grace_days INT NOT NULL DEFAULT 1,
  holiday_pay_multiplier NUMERIC(4,2) NOT NULL DEFAULT 1.50,
  salary_cycle_start_day INT NOT NULL DEFAULT 26,
  salary_cycle_end_day INT NOT NULL DEFAULT 25,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
