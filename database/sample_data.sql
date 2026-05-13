USE hrm_pro;

INSERT INTO roles (id, role_name, description) VALUES
(1, 'admin', 'Quản trị hệ thống'),
(2, 'hr', 'Nhân sự'),
(3, 'accountant', 'Kế toán lương')
ON DUPLICATE KEY UPDATE role_name = VALUES(role_name);

INSERT INTO users (role_id, full_name, email, password_hash, is_active) VALUES
(1, 'Administrator', 'admin@hrm.local', '$2y$12$57ic/X5bU/jeiqIVL.XL8OSHinj626OglM31nnlZRBZGKG/mUA/AC', 1)
ON DUPLICATE KEY UPDATE email = VALUES(email);

INSERT INTO departments (department_code, department_name) VALUES
('HR', 'Phòng Nhân sự'),
('ACC', 'Phòng Kế toán'),
('OPS', 'Vận hành')
ON DUPLICATE KEY UPDATE department_name = VALUES(department_name);

INSERT INTO positions (position_code, position_name) VALUES
('NV', 'Nhân viên'),
('TBP', 'Trưởng bộ phận'),
('QL', 'Quản lý')
ON DUPLICATE KEY UPDATE position_name = VALUES(position_name);

INSERT INTO work_shifts (shift_code, shift_name, start_time, end_time, checkin_from, checkin_to, checkout_from, checkout_to, break_minutes, standard_work_minutes, allow_late_minutes, allow_early_minutes, overtime_after_minutes, is_overnight) VALUES
('HC', 'Ca hành chính', '08:00:00', '17:00:00', '06:00:00', '09:30:00', '16:00:00', '20:00:00', 60, 480, 5, 5, 30, 0),
('DEM', 'Ca đêm', '22:00:00', '06:00:00', '20:00:00', '23:30:00', '05:00:00', '08:00:00', 30, 450, 5, 5, 30, 1)
ON DUPLICATE KEY UPDATE shift_name = VALUES(shift_name);

INSERT INTO insurance_policies (policy_name, employee_social_rate, employee_health_rate, employee_unemployment_rate, employer_social_rate, employer_health_rate, employer_unemployment_rate, min_base_salary, max_base_salary, effective_from, is_active) VALUES
('Chính sách BH mặc định', 0.08, 0.015, 0.01, 0.175, 0.03, 0.01, 0, 0, '2026-01-01', 1);

INSERT INTO employees (employee_code, attendance_code, full_name, gender, phone, email, department_id, position_id, join_date, status, salary_type, base_salary, insurance_base_salary) VALUES
('NV001', '1', 'Nguyễn Văn A', 'male', '0900000001', 'a@example.com', 1, 1, '2026-01-01', 'working', 'gross', 10000000, 8000000),
('NV002', '2', 'Trần Thị B', 'female', '0900000002', 'b@example.com', 3, 1, '2026-01-05', 'working', 'gross', 9000000, 7500000)
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name);

INSERT INTO employee_insurances (employee_id, insurance_number, insurance_base_salary, effective_from)
SELECT id, CONCAT('BH', employee_code), insurance_base_salary, '2026-01-01'
FROM employees
ON DUPLICATE KEY UPDATE insurance_base_salary = VALUES(insurance_base_salary);

INSERT INTO attendance_devices (device_name, device_ip, device_port, device_type, location_name, is_active) VALUES
('Ronald Jack Demo', '192.168.1.201', 4370, 'ronaldjack', 'Cổng chính', 1)
ON DUPLICATE KEY UPDATE device_name = VALUES(device_name);

INSERT INTO payroll_periods (period_code, period_name, from_date, to_date, payment_date, standard_work_days) VALUES
('2026-05', 'Lương tháng 05/2026', '2026-05-01', '2026-05-31', '2026-06-05', 26)
ON DUPLICATE KEY UPDATE period_name = VALUES(period_name);

INSERT IGNORE INTO attendance_raw_logs (device_id, attendance_code, punch_time, punch_date, raw_data)
SELECT d.id, '1', '2026-05-01 07:58:00', '2026-05-01', '{"sample":true}' FROM attendance_devices d WHERE d.device_ip='192.168.1.201' LIMIT 1;
INSERT IGNORE INTO attendance_raw_logs (device_id, attendance_code, punch_time, punch_date, raw_data)
SELECT d.id, '1', '2026-05-01 17:12:00', '2026-05-01', '{"sample":true}' FROM attendance_devices d WHERE d.device_ip='192.168.1.201' LIMIT 1;
INSERT IGNORE INTO attendance_raw_logs (device_id, attendance_code, punch_time, punch_date, raw_data)
SELECT d.id, '2', '2026-05-01 08:12:00', '2026-05-01', '{"sample":true}' FROM attendance_devices d WHERE d.device_ip='192.168.1.201' LIMIT 1;
INSERT IGNORE INTO attendance_raw_logs (device_id, attendance_code, punch_time, punch_date, raw_data)
SELECT d.id, '2', '2026-05-01 16:50:00', '2026-05-01', '{"sample":true}' FROM attendance_devices d WHERE d.device_ip='192.168.1.201' LIMIT 1;
