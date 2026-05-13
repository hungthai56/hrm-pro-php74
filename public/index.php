<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$page = $_GET['page'] ?? 'dashboard';
$routes = [
    'login' => __DIR__ . '/../modules/auth/login.php',
    'logout' => __DIR__ . '/../modules/auth/logout.php',
    'dashboard' => __DIR__ . '/../modules/dashboard/index.php',
    'employees' => __DIR__ . '/../modules/employees/index.php',
    'departments' => __DIR__ . '/../modules/departments/index.php',
    'positions' => __DIR__ . '/../modules/positions/index.php',
    'attendance_devices' => __DIR__ . '/../modules/attendance/devices.php',
    'attendance_logs' => __DIR__ . '/../modules/attendance/logs.php',
    'work_shifts' => __DIR__ . '/../modules/attendance/shifts.php',
    'attendance_process' => __DIR__ . '/../modules/attendance/process.php',
    'insurance_policies' => __DIR__ . '/../modules/insurance/policies.php',
    'payroll_periods' => __DIR__ . '/../modules/payroll/periods.php',
    'payrolls' => __DIR__ . '/../modules/payroll/payrolls.php',
];

if (!isset($routes[$page])) {
    http_response_code(404);
    echo 'Không tìm thấy trang.';
    exit;
}

if (!in_array($page, ['login'], true)) {
    require_login();
}

require $routes[$page];
