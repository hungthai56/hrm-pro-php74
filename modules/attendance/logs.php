<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deviceId = (int)($_POST['device_id'] ?? 0) ?: null;
    $attendanceCode = trim($_POST['attendance_code'] ?? '');
    $punchTime = $_POST['punch_time'] ?? '';
    $punchDate = date('Y-m-d', strtotime($punchTime));
    $raw = json_encode(['manual' => true, 'user' => $_SESSION['user']['email'] ?? 'system'], JSON_UNESCAPED_UNICODE);
    $stmt = db()->prepare("INSERT IGNORE INTO attendance_raw_logs (device_id, attendance_code, punch_time, punch_date, raw_data) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('issss', $deviceId, $attendanceCode, $punchTime, $punchDate, $raw);
    $stmt->execute();
    audit_log('create_manual', 'attendance_raw_logs', db()->insert_id, null, $_POST);
    flash('success', 'Đã thêm log chấm công thủ công.');
    redirect(url('attendance_logs'));
}
$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');
$keyword = trim($_GET['q'] ?? '');
$devices = db()->query("SELECT id, device_name FROM attendance_devices ORDER BY device_name");
$sql = "SELECT l.*, d.device_name, e.full_name FROM attendance_raw_logs l LEFT JOIN attendance_devices d ON d.id=l.device_id LEFT JOIN employees e ON e.attendance_code=l.attendance_code WHERE l.punch_date BETWEEN ? AND ?";
if ($keyword !== '') { $sql .= " AND (l.attendance_code LIKE ? OR e.full_name LIKE ?)"; }
$sql .= " ORDER BY l.punch_time DESC LIMIT 500";
$stmt = db()->prepare($sql);
if ($keyword !== '') { $like = '%' . $keyword . '%'; $stmt->bind_param('ssss', $from, $to, $like, $like); } else { $stmt->bind_param('ss', $from, $to); }
$stmt->execute();
$rows = $stmt->get_result();
require __DIR__ . '/../../includes/layout/header.php';
?>
<div class="card"><h2>Log chấm công thô</h2>
<form method="get" class="actions"><input type="hidden" name="page" value="attendance_logs"><input type="date" name="from" value="<?= e($from) ?>"><input type="date" name="to" value="<?= e($to) ?>"><input name="q" placeholder="Mã chấm công / tên" value="<?= e($keyword) ?>"><button class="btn light">Lọc</button></form>
<p class="muted">Bảng này lưu log gốc từ máy Ronald Jack hoặc nhập tay để làm căn cứ xử lý công.</p></div>
<div class="card"><h3>Thêm log thủ công</h3><form method="post" class="form-grid"><?= csrf_field() ?>
<div class="form-group"><label>Máy</label><select name="device_id"><option value="">-- Không chọn --</option><?php while ($d=$devices->fetch_assoc()): ?><option value="<?= e($d['id']) ?>"><?= e($d['device_name']) ?></option><?php endwhile; ?></select></div>
<div class="form-group"><label>Mã chấm công</label><input name="attendance_code" required></div>
<div class="form-group"><label>Thời gian chấm</label><input type="datetime-local" name="punch_time" required></div>
<div class="actions"><button class="btn">Thêm log</button></div>
</form></div>
<div class="card"><h3>Dữ liệu log</h3><div class="table-wrap"><table><tr><th>Thời gian</th><th>Mã MCC</th><th>Nhân viên</th><th>Máy</th><th>Raw</th></tr>
<?php while ($r = $rows->fetch_assoc()): ?><tr><td><?= e($r['punch_time']) ?></td><td><?= e($r['attendance_code']) ?></td><td><?= e($r['full_name']) ?></td><td><?= e($r['device_name']) ?></td><td><small><?= e(mb_substr((string)$r['raw_data'], 0, 90)) ?></small></td></tr><?php endwhile; ?>
</table></div></div>
<?php require __DIR__ . '/../../includes/layout/footer.php'; ?>
