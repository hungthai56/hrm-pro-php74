<?php
// HRM Pro - PHP 7.4 + MySQLi
// Đổi APP_URL theo domain/thư mục triển khai thực tế.
define('APP_NAME', 'HRM Pro');
define('APP_VERSION', '0.1.0');
define('BASE_PATH', dirname(__DIR__));
define('APP_URL', 'http://localhost/hrm_pro/public');
define('DEFAULT_TIMEZONE', 'Asia/Ho_Chi_Minh');

date_default_timezone_set(DEFAULT_TIMEZONE);
