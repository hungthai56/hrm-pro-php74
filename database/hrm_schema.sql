CREATE DATABASE IF NOT EXISTS hrm_pro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hrm_pro;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS payroll_items;
DROP TABLE IF EXISTS payrolls;
DROP TABLE IF EXISTS payroll_periods;
DROP TABLE IF EXISTS employee_insurances;
DROP TABLE IF EXISTS insurance_policies;
DROP TABLE IF EXISTS attendance_adjustments;
DROP TABLE IF EXISTS attendance_daily;
DROP TABLE IF EXISTS employee_shifts;
DROP TABLE IF EXISTS work_shifts;
DROP TABLE IF EXISTS attendance_raw_logs;
DROP TABLE IF EXISTS attendance_devices;
DROP TABLE IF EXISTS employee_salary_histories;
DROP TABLE IF EXISTS employees;
DROP TABLE IF EXISTS positions;
DROP TABLE IF EXISTS departments;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;

CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NULL,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    is_active TINYINT DEFAULT 1,
    last_login_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB;

CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_code VARCHAR(50) NOT NULL UNIQUE,
    department_name VARCHAR(150) NOT NULL,
    parent_id INT NULL,
    manager_employee_id INT NULL,
    is_active TINYINT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE positions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    position_code VARCHAR(50) NOT NULL UNIQUE,
    position_name VARCHAR(150) NOT NULL,
    is_active TINYINT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_code VARCHAR(50) NOT NULL UNIQUE,
    attendance_code VARCHAR(50) NULL UNIQUE,
    full_name VARCHAR(150) NOT NULL,
    gender ENUM('male','female','other') DEFAULT 'other',
    date_of_birth DATE NULL,
    phone VARCHAR(50) NULL,
    email VARCHAR(160) NULL,
    department_id INT NULL,
    position_id INT NULL,
    join_date DATE NULL,
    probation_end_date DATE NULL,
    contract_end_date DATE NULL,
    status ENUM('working','probation','resigned','suspended') DEFAULT 'working',
    salary_type ENUM('gross','net') DEFAULT 'gross',
    base_salary DECIMAL(15,2) DEFAULT 0,
    insurance_base_salary DECIMAL(15,2) DEFAULT 0,
    bank_name VARCHAR(120) NULL,
    bank_account VARCHAR(80) NULL,
    tax_code VARCHAR(80) NULL,
    id_number VARCHAR(80) NULL,
    address TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id),
    FOREIGN KEY (position_id) REFERENCES positions(id),
    INDEX idx_employee_status(status),
    INDEX idx_employee_attendance_code(attendance_code)
) ENGINE=InnoDB;

CREATE TABLE employee_salary_histories (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    old_salary DECIMAL(15,2) DEFAULT 0,
    new_salary DECIMAL(15,2) DEFAULT 0,
    old_insurance_salary DECIMAL(15,2) DEFAULT 0,
    new_insurance_salary DECIMAL(15,2) DEFAULT 0,
    effective_from DATE NOT NULL,
    reason TEXT NULL,
    created_by INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE attendance_devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_name VARCHAR(120) NOT NULL,
    device_ip VARCHAR(60) NOT NULL,
    device_port INT DEFAULT 4370,
    device_type VARCHAR(60) DEFAULT 'ronaldjack',
    location_name VARCHAR(150) NULL,
    is_active TINYINT DEFAULT 1,
    last_sync_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_device_ip_port(device_ip, device_port)
) ENGINE=InnoDB;

CREATE TABLE attendance_raw_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    device_id INT NULL,
    attendance_code VARCHAR(50) NOT NULL,
    punch_time DATETIME NOT NULL,
    punch_date DATE NOT NULL,
    punch_type VARCHAR(50) NULL,
    verify_type VARCHAR(50) NULL,
    raw_data TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (device_id) REFERENCES attendance_devices(id),
    UNIQUE KEY uniq_att_raw(device_id, attendance_code, punch_time),
    INDEX idx_att_raw_code_date(attendance_code, punch_date),
    INDEX idx_att_raw_date(punch_date)
) ENGINE=InnoDB;

CREATE TABLE work_shifts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shift_code VARCHAR(50) NOT NULL UNIQUE,
    shift_name VARCHAR(120) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    checkin_from TIME NULL,
    checkin_to TIME NULL,
    checkout_from TIME NULL,
    checkout_to TIME NULL,
    break_minutes INT DEFAULT 0,
    standard_work_minutes INT DEFAULT 480,
    allow_late_minutes INT DEFAULT 0,
    allow_early_minutes INT DEFAULT 0,
    overtime_after_minutes INT DEFAULT 0,
    night_shift_start TIME DEFAULT '22:00:00',
    night_shift_end TIME DEFAULT '06:00:00',
    is_overnight TINYINT DEFAULT 0,
    is_active TINYINT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE employee_shifts (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    shift_id INT NOT NULL,
    work_date DATE NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    FOREIGN KEY (shift_id) REFERENCES work_shifts(id),
    UNIQUE KEY uniq_employee_shift_date(employee_id, work_date)
) ENGINE=InnoDB;

CREATE TABLE attendance_daily (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    work_date DATE NOT NULL,
    shift_id INT NULL,
    check_in DATETIME NULL,
    check_out DATETIME NULL,
    work_minutes INT DEFAULT 0,
    late_minutes INT DEFAULT 0,
    early_leave_minutes INT DEFAULT 0,
    overtime_minutes INT DEFAULT 0,
    night_minutes INT DEFAULT 0,
    work_day DECIMAL(6,2) DEFAULT 0,
    paid_work_day DECIMAL(6,2) DEFAULT 0,
    status VARCHAR(50) DEFAULT 'normal',
    note TEXT NULL,
    is_locked TINYINT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    FOREIGN KEY (shift_id) REFERENCES work_shifts(id),
    UNIQUE KEY uniq_att_daily(employee_id, work_date),
    INDEX idx_att_daily_date(work_date)
) ENGINE=InnoDB;

CREATE TABLE attendance_adjustments (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    attendance_daily_id BIGINT NOT NULL,
    old_data TEXT NULL,
    new_data TEXT NULL,
    reason TEXT NULL,
    adjusted_by INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (attendance_daily_id) REFERENCES attendance_daily(id),
    FOREIGN KEY (adjusted_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE insurance_policies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    policy_name VARCHAR(120) NOT NULL,
    employee_social_rate DECIMAL(8,5) DEFAULT 0.08000,
    employee_health_rate DECIMAL(8,5) DEFAULT 0.01500,
    employee_unemployment_rate DECIMAL(8,5) DEFAULT 0.01000,
    employer_social_rate DECIMAL(8,5) DEFAULT 0.17500,
    employer_health_rate DECIMAL(8,5) DEFAULT 0.03000,
    employer_unemployment_rate DECIMAL(8,5) DEFAULT 0.01000,
    min_base_salary DECIMAL(15,2) DEFAULT 0,
    max_base_salary DECIMAL(15,2) DEFAULT 0,
    effective_from DATE NOT NULL,
    effective_to DATE NULL,
    is_active TINYINT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE employee_insurances (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    insurance_number VARCHAR(80) NULL,
    is_social_insurance TINYINT DEFAULT 1,
    is_health_insurance TINYINT DEFAULT 1,
    is_unemployment_insurance TINYINT DEFAULT 1,
    insurance_base_salary DECIMAL(15,2) DEFAULT 0,
    effective_from DATE NOT NULL,
    effective_to DATE NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    UNIQUE KEY uniq_employee_insurance(employee_id, effective_from),
    INDEX idx_employee_insurance_effective(employee_id, effective_from, effective_to)
) ENGINE=InnoDB;

CREATE TABLE payroll_periods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    period_code VARCHAR(50) NOT NULL UNIQUE,
    period_name VARCHAR(120) NOT NULL,
    from_date DATE NOT NULL,
    to_date DATE NOT NULL,
    payment_date DATE NULL,
    standard_work_days DECIMAL(6,2) DEFAULT 26,
    status ENUM('draft','calculated','locked','paid') DEFAULT 'draft',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE payrolls (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    payroll_period_id INT NOT NULL,
    employee_id INT NOT NULL,
    standard_work_days DECIMAL(6,2) DEFAULT 0,
    actual_work_days DECIMAL(6,2) DEFAULT 0,
    base_salary DECIMAL(15,2) DEFAULT 0,
    working_salary DECIMAL(15,2) DEFAULT 0,
    gross_salary DECIMAL(15,2) DEFAULT 0,
    total_earning DECIMAL(15,2) DEFAULT 0,
    total_deduction DECIMAL(15,2) DEFAULT 0,
    employee_insurance_amount DECIMAL(15,2) DEFAULT 0,
    employer_insurance_amount DECIMAL(15,2) DEFAULT 0,
    personal_income_tax DECIMAL(15,2) DEFAULT 0,
    net_salary DECIMAL(15,2) DEFAULT 0,
    status ENUM('draft','approved','locked','paid') DEFAULT 'draft',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    FOREIGN KEY (payroll_period_id) REFERENCES payroll_periods(id),
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    UNIQUE KEY uniq_payroll_employee_period(payroll_period_id, employee_id)
) ENGINE=InnoDB;

CREATE TABLE payroll_items (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    payroll_id BIGINT NOT NULL,
    component_code VARCHAR(60) NOT NULL,
    component_name VARCHAR(160) NOT NULL,
    component_type ENUM('earning','deduction','company_cost') DEFAULT 'earning',
    amount DECIMAL(15,2) DEFAULT 0,
    formula_snapshot TEXT NULL,
    note TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payroll_id) REFERENCES payrolls(id) ON DELETE CASCADE,
    INDEX idx_payroll_item_code(component_code)
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(80) NOT NULL,
    entity_type VARCHAR(80) NOT NULL,
    entity_id VARCHAR(80) NULL,
    old_data TEXT NULL,
    new_data TEXT NULL,
    ip_address VARCHAR(80) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_audit_entity(entity_type, entity_id),
    INDEX idx_audit_created(created_at)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;
