<?php
// Cấu hình database. Nên đổi user/password khi đưa lên server thật.
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hrm_pro');
define('DB_PORT', 3306);

$mysqli = mysqli_init();
mysqli_options($mysqli, MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);

if (!mysqli_real_connect($mysqli, DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT)) {
    die('Không kết nối được database: ' . mysqli_connect_error());
}

mysqli_set_charset($mysqli, 'utf8mb4');

function db(): mysqli
{
    global $mysqli;
    return $mysqli;
}
