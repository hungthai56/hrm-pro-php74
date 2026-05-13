<?php
$nav = [
    'dashboard' => ['Dashboard', '📊'],
    'employees' => ['Nhân viên', '👥'],
    'departments' => ['Phòng ban', '🏢'],
    'positions' => ['Chức vụ', '🏷️'],
    'attendance_devices' => ['Máy chấm công', '🕘'],
    'attendance_logs' => ['Log chấm công', '📥'],
    'work_shifts' => ['Ca làm', '📅'],
    'attendance_process' => ['Xử lý công', '⚙️'],
    'insurance_policies' => ['Bảo hiểm', '🛡️'],
    'payroll_periods' => ['Kỳ lương', '💰'],
    'payrolls' => ['Bảng lương', '🧾'],
];
$current = $_GET['page'] ?? 'dashboard';
?>
<aside class="sidebar">
    <div class="brand">HRM Pro</div>
    <nav>
        <?php foreach ($nav as $page => $item): ?>
            <a class="<?= $current === $page ? 'active' : '' ?>" href="<?= e(url($page)) ?>">
                <span><?= $item[1] ?></span><?= e($item[0]) ?>
            </a>
        <?php endforeach; ?>
    </nav>
</aside>
