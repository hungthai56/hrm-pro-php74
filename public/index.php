<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$page = $_GET['page'] ?? 'dashboard';
$routes = [
    'login' => __DIR__ . '/../modules/auth/login.php',
    'logout' => __DIR__ . '/../modules/auth/logout.php',
    'dashboard' => __DIR__ . '/../modules/dashboard/index.php',
    'product_suite' => __DIR__ . '/../modules/product_suite/index.php',
    'reports' => __DIR__ . '/../modules/reports/index.php',
    'workforce' => __DIR__ . '/../modules/workforce/index.php',
    'employees' => __DIR__ . '/../modules/employees/index.php',
    'departments' => __DIR__ . '/../modules/departments/index.php',
    'positions' => __DIR__ . '/../modules/positions/index.php',
    'onboarding' => __DIR__ . '/../modules/onboarding/index.php',
    'contracts' => __DIR__ . '/../modules/contracts/index.php',
    'assets' => __DIR__ . '/../modules/assets/index.php',
    'attendance_center' => __DIR__ . '/../modules/attendance/center.php',
    'attendance_devices' => __DIR__ . '/../modules/attendance/devices.php',
    'mobile_attendance' => __DIR__ . '/../modules/mobile_attendance/index.php',
    'scheduling' => __DIR__ . '/../modules/scheduling/index.php',
    'work_shifts' => __DIR__ . '/../modules/attendance/shifts.php',
    'attendance_logs' => __DIR__ . '/../modules/attendance/logs.php',
    'attendance_process' => __DIR__ . '/../modules/attendance/process.php',
    'leave_requests' => __DIR__ . '/../modules/leave/index.php',
    'approvals' => __DIR__ . '/../modules/approvals/index.php',
    'payroll_periods' => __DIR__ . '/../modules/payroll/periods.php',
    'payrolls' => __DIR__ . '/../modules/payroll/payrolls.php',
    'salary_components' => __DIR__ . '/../modules/payroll/components.php',
    'insurance_policies' => __DIR__ . '/../modules/insurance/policies.php',
    'recruitment' => __DIR__ . '/../modules/recruitment/index.php',
    'training' => __DIR__ . '/../modules/training/index.php',
    'audit_logs' => __DIR__ . '/../modules/settings/audit_logs.php',
];

if (!isset($routes[$page])) {
    http_response_code(404);
    echo 'Không tìm thấy trang.';
    exit;
}

if ($page !== 'login') {
    require_login();
}

require $routes[$page];
