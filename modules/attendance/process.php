<?php
require_once __DIR__ . '/../../src/AttendanceProcessor.php';
$from = $_POST['from'] ?? ($_GET['from'] ?? date('Y-m-01'));
$to = $_POST['to'] ?? ($_GET['to'] ?? date('Y-m-d'));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $processor = new AttendanceProcessor(db());
    $count = $processor->processRange($from, $to);
    audit_log('process', 'attendance_daily', null, null, ['from' => $from, 'to' => $to, 'count' => $count]);
    flash('success', "Đã xử lý {$count} dòng công ngày.");
    redirect(url('attendance_process', ['from' => $from, 'to' => $to]));
}
$stmt = db()->prepare("SELECT a.*, e.employee_code, e.full_name, s.shift_name FROM attendance_daily a JOIN employees e ON e.id=a.employee_id LEFT JOIN work_shifts s ON s.id=a.shift_id WHERE a.work_date BETWEEN ? AND ? ORDER BY a.work_date DESC, e.employee_code LIMIT 500");
$stmt->bind_param('ss', $from, $to);
$stmt->execute();
$rows = $stmt->get_result();
require __DIR__ . '/../../includes/layout/header.php';
?>
<div class="card">
    <h2>Xử lý bảng công</h2>
    <p class="muted">Hệ thống lấy log thô, map theo mã chấm công của nhân viên, xác định vào/ra, tính công, đi trễ, về sớm, tăng ca và ca đêm.</p>
    <form method="post" class="actions">
        <?= csrf_field() ?>
        <input type="date" name="from" value="<?= e($from) ?>">
        <input type="date" name="to" value="<?= e($to) ?>">
        <button class="btn">Chạy xử lý công</button>
    </form>
</div>
<div class="card">
    <h2>Bảng công đã xử lý</h2>
    <div class="table-wrap"><table><tr><th>Ngày</th><th>Mã NV</th><th>Nhân viên</th><th>Ca</th><th>Vào</th><th>Ra</th><th>Phút làm</th><th>Trễ</th><th>Sớm</th><th>TC</th><th>Đêm</th><th>Công</th><th>TT</th></tr>
    <?php while ($r=$rows->fetch_assoc()): ?><tr><td><?= e($r['work_date']) ?></td><td><?= e($r['employee_code']) ?></td><td><?= e($r['full_name']) ?></td><td><?= e($r['shift_name']) ?></td><td><?= e($r['check_in']) ?></td><td><?= e($r['check_out']) ?></td><td><?= e($r['work_minutes']) ?></td><td><?= e($r['late_minutes']) ?></td><td><?= e($r['early_leave_minutes']) ?></td><td><?= e($r['overtime_minutes']) ?></td><td><?= e($r['night_minutes']) ?></td><td><?= e($r['paid_work_day']) ?></td><td><span class="badge"><?= e($r['status']) ?></span></td></tr><?php endwhile; ?>
    </table></div>
</div>
<?php require __DIR__ . '/../../includes/layout/footer.php'; ?>
