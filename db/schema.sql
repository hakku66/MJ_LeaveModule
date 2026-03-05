-- Core schema for MJ Leave Module (MariaDB / MySQL compatible)

CREATE TABLE departments (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_departments_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE positions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uk_positions_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE employees (
  id BIGINT UNSIGNED NOT NULL,
  employee_code VARCHAR(50) NOT NULL,
  full_name VARCHAR(180) NOT NULL,
  department_id BIGINT UNSIGNED NULL,
  position_id BIGINT UNSIGNED NULL,
  hire_date DATE NOT NULL,
  probation_end_date DATE NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_employees_employee_code (employee_code),
  KEY idx_employees_department_id (department_id),
  KEY idx_employees_position_id (position_id),
  CONSTRAINT fk_employees_department FOREIGN KEY (department_id) REFERENCES departments(id),
  CONSTRAINT fk_employees_position FOREIGN KEY (position_id) REFERENCES positions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE devices (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  serial_number VARCHAR(100) NOT NULL,
  ip_address VARCHAR(64) NOT NULL,
  protocol VARCHAR(20) NOT NULL DEFAULT 'HTTP',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_synced_at TIMESTAMP NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_devices_serial_number (serial_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE employee_biometrics (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  employee_id BIGINT UNSIGNED NOT NULL,
  template_type VARCHAR(20) NOT NULL,
  template_data LONGTEXT NOT NULL,
  template_hash VARCHAR(128) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_employee_biometrics_emp_type_hash (employee_id, template_type, template_hash),
  CONSTRAINT fk_employee_biometrics_employee FOREIGN KEY (employee_id) REFERENCES employees(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE attendance_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  employee_id BIGINT UNSIGNED NOT NULL,
  punch_time TIMESTAMP NOT NULL,
  punch_state VARCHAR(20) NOT NULL,
  device_id BIGINT UNSIGNED NOT NULL,
  raw_payload JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_attendance_logs_employee_id (employee_id),
  KEY idx_attendance_logs_device_id (device_id),
  KEY idx_attendance_logs_punch_time (punch_time),
  CONSTRAINT fk_attendance_logs_employee FOREIGN KEY (employee_id) REFERENCES employees(id),
  CONSTRAINT fk_attendance_logs_device FOREIGN KEY (device_id) REFERENCES devices(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE leave_types (
  code VARCHAR(10) NOT NULL,
  name VARCHAR(80) NOT NULL,
  annual_quota DECIMAL(5,2) NULL,
  PRIMARY KEY (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO leave_types (code, name, annual_quota) VALUES
  ('PL', 'Personal Leave', 12.00),
  ('SL', 'Sick Leave', 3.00),
  ('ML', 'Marriage Leave', NULL),
  ('LWP', 'Leave Without Pay', NULL);

CREATE TABLE leave_requests (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  employee_id BIGINT UNSIGNED NOT NULL,
  leave_type_code VARCHAR(10) NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  reason TEXT NULL,
  approval_status VARCHAR(20) NOT NULL DEFAULT 'PENDING',
  submitted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  approved_by BIGINT UNSIGNED NULL,
  approved_at TIMESTAMP NULL,
  PRIMARY KEY (id),
  KEY idx_leave_requests_employee_id (employee_id),
  KEY idx_leave_requests_leave_type_code (leave_type_code),
  KEY idx_leave_requests_approved_by (approved_by),
  CONSTRAINT fk_leave_requests_employee FOREIGN KEY (employee_id) REFERENCES employees(id),
  CONSTRAINT fk_leave_requests_leave_type FOREIGN KEY (leave_type_code) REFERENCES leave_types(code),
  CONSTRAINT fk_leave_requests_approved_by FOREIGN KEY (approved_by) REFERENCES employees(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE payroll_records (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  employee_id BIGINT UNSIGNED NOT NULL,
  cycle_start DATE NOT NULL,
  cycle_end DATE NOT NULL,
  gross_salary DECIMAL(12,2) NOT NULL,
  lop_deduction DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  holiday_pay DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  final_payout DECIMAL(12,2) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_payroll_records_employee_cycle (employee_id, cycle_start, cycle_end),
  CONSTRAINT fk_payroll_records_employee FOREIGN KEY (employee_id) REFERENCES employees(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE policy_settings (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  duplicate_punch_minutes INT NOT NULL DEFAULT 1,
  checkin_mandatory TINYINT(1) NOT NULL DEFAULT 1,
  checkout_mandatory TINYINT(1) NOT NULL DEFAULT 1,
  late_limit_minutes INT NOT NULL DEFAULT 15,
  early_leave_limit_minutes INT NOT NULL DEFAULT 15,
  leave_request_min_days_advance INT NOT NULL DEFAULT 3,
  leave_late_application_grace_days INT NOT NULL DEFAULT 1,
  holiday_pay_multiplier DECIMAL(4,2) NOT NULL DEFAULT 1.50,
  salary_cycle_start_day INT NOT NULL DEFAULT 26,
  salary_cycle_end_day INT NOT NULL DEFAULT 25,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
