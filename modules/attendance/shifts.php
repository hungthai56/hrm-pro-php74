<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $data = [
        'shift_code' => trim($_POST['shift_code'] ?? ''),
        'shift_name' => trim($_POST['shift_name'] ?? ''),
        'start_time' => $_POST['start_time'] ?? '08:00',
        'end_time' => $_POST['end_time'] ?? '17:00',
        'checkin_from' => $_POST['checkin_from'] ?: null,
        'checkin_to' => $_POST['checkin_to'] ?: null,
        'checkout_from' => $_POST['checkout_from'] ?: null,
        'checkout_to' => $_POST['checkout_to'] ?: null,
        'break_minutes' => (int)($_POST['break_minutes'] ?? 0),
        'standard_work_minutes' => (int)($_POST['standard_work_minutes'] ?? 480),
        'allow_late_minutes' => (int)($_POST['allow_late_minutes'] ?? 0),
        'allow_early_minutes' => (int)($_POST['allow_early_minutes'] ?? 0),
        'overtime_after_minutes' => (int)($_POST['overtime_after_minutes'] ?? 0),
        'is_overnight' => isset($_POST['is_overnight']) ? 1 : 0,
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
    ];
    if ($id > 0) {
        $stmt = db()->prepare("UPDATE work_shifts SET shift_code=?, shift_name=?, start_time=?, end_time=?, checkin_from=?, checkin_to=?, checkout_from=?, checkout_to=?, break_minutes=?, standard_work_minutes=?, allow_late_minutes=?, allow_early_minutes=?, overtime_after_minutes=?, is_overnight=?, is_active=? WHERE id=?");
        $stmt->bind_param('ssssssssiiiiiiii', $data['shift_code'], $data['shift_name'], $data['start_time'], $data['end_time'], $data['checkin_from'], $data['checkin_to'], $data['checkout_from'], $data['checkout_to'], $data['break_minutes'], $data['standard_work_minutes'], $data['allow_late_minutes'], $data['allow_early_minutes'], $data['overtime_after_minutes'], $data['is_overnight'], $data['is_active'], $id);
        $stmt->execute();
        audit_log('update', 'work_shifts', $id, null, $data);
        flash('success', 'Đã cập nhật ca làm.');
    } else {
        $stmt = db()->prepare("INSERT INTO work_shifts (shift_code, shift_name, start_time, end_time, checkin_from, checkin_to, checkout_from, checkout_to, break_minutes, standard_work_minutes, allow_late_minutes, allow_early_minutes, overtime_after_minutes, is_overnight, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssssssiiiiiii', $data['shift_code'], $data['shift_name'], $data['start_time'], $data['end_time'], $data['checkin_from'], $data['checkin_to'], $data['checkout_from'], $data['checkout_to'], $data['break_minutes'], $data['standard_work_minutes'], $data['allow_late_minutes'], $data['allow_early_minutes'], $data['overtime_after_minutes'], $data['is_overnight'], $data['is_active']);
        $stmt->execute();
        audit_log('create', 'work_shifts', db()->insert_id, null, $data);
        flash('success', 'Đã thêm ca làm.');
    }
    redirect(url('work_shifts'));
}
if (($_GET['action'] ?? '') === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = db()->prepare("DELETE FROM work_shifts WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    flash('success', 'Đã xóa ca nếu không có dữ liệu liên quan.');
    redirect(url('work_shifts'));
}
$edit = null;
if (($_GET['action'] ?? '') === 'edit') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = db()->prepare("SELECT * FROM work_shifts WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc();
}
$rows = db()->query("SELECT * FROM work_shifts ORDER BY shift_code");
require __DIR__ . '/../../includes/layout/header.php';
?>
<div class="card"><h2><?= $edit ? 'Sửa ca làm' : 'Thêm ca làm' ?></h2>
<form method="post" class="form-grid"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($edit['id'] ?? 0) ?>">
<div class="form-group"><label>Mã ca</label><input name="shift_code" value="<?= e($edit['shift_code'] ?? '') ?>" required></div>
<div class="form-group"><label>Tên ca</label><input name="shift_name" value="<?= e($edit['shift_name'] ?? '') ?>" required></div>
<div class="form-group"><label>Giờ vào chuẩn</label><input type="time" name="start_time" value="<?= e(substr($edit['start_time'] ?? '08:00',0,5)) ?>" required></div>
<div class="form-group"><label>Giờ ra chuẩn</label><input type="time" name="end_time" value="<?= e(substr($edit['end_time'] ?? '17:00',0,5)) ?>" required></div>
<div class="form-group"><label>Cho chấm vào từ</label><input type="time" name="checkin_from" value="<?= e(substr($edit['checkin_from'] ?? '',0,5)) ?>"></div>
<div class="form-group"><label>Cho chấm vào đến</label><input type="time" name="checkin_to" value="<?= e(substr($edit['checkin_to'] ?? '',0,5)) ?>"></div>
<div class="form-group"><label>Cho chấm ra từ</label><input type="time" name="checkout_from" value="<?= e(substr($edit['checkout_from'] ?? '',0,5)) ?>"></div>
<div class="form-group"><label>Cho chấm ra đến</label><input type="time" name="checkout_to" value="<?= e(substr($edit['checkout_to'] ?? '',0,5)) ?>"></div>
<div class="form-group"><label>Phút nghỉ</label><input type="number" name="break_minutes" value="<?= e($edit['break_minutes'] ?? 60) ?>"></div>
<div class="form-group"><label>Phút công chuẩn</label><input type="number" name="standard_work_minutes" value="<?= e($edit['standard_work_minutes'] ?? 480) ?>"></div>
<div class="form-group"><label>Cho phép trễ phút</label><input type="number" name="allow_late_minutes" value="<?= e($edit['allow_late_minutes'] ?? 5) ?>"></div>
<div class="form-group"><label>Cho phép về sớm phút</label><input type="number" name="allow_early_minutes" value="<?= e($edit['allow_early_minutes'] ?? 5) ?>"></div>
<div class="form-group"><label>Tính tăng ca sau phút</label><input type="number" name="overtime_after_minutes" value="<?= e($edit['overtime_after_minutes'] ?? 30) ?>"></div>
<div class="form-group"><label><input type="checkbox" name="is_overnight" <?= checked($edit['is_overnight'] ?? 0) ?>> Ca qua đêm</label><br><label><input type="checkbox" name="is_active" <?= checked($edit['is_active'] ?? 1) ?>> Đang dùng</label></div>
<div class="actions"><button class="btn">Lưu ca</button><a class="btn light" href="<?= e(url('work_shifts')) ?>">Làm mới</a></div>
</form></div>
<div class="card"><h2>Danh sách ca</h2><div class="table-wrap"><table><tr><th>Mã</th><th>Tên</th><th>Giờ</th><th>Nghỉ</th><th>Công chuẩn</th><th>Ca đêm</th><th></th></tr>
<?php while ($r=$rows->fetch_assoc()): ?><tr><td><?= e($r['shift_code']) ?></td><td><?= e($r['shift_name']) ?></td><td><?= e(substr($r['start_time'],0,5)) ?> - <?= e(substr($r['end_time'],0,5)) ?></td><td><?= e($r['break_minutes']) ?>p</td><td><?= e($r['standard_work_minutes']) ?>p</td><td><?= $r['is_overnight']?'Có':'Không' ?></td><td class="actions"><a href="<?= e(url('work_shifts', ['action'=>'edit','id'=>$r['id']])) ?>">Sửa</a><a onclick="return confirmDelete()" href="<?= e(url('work_shifts', ['action'=>'delete','id'=>$r['id']])) ?>">Xóa</a></td></tr><?php endwhile; ?>
</table></div></div>
<?php require __DIR__ . '/../../includes/layout/footer.php'; ?>
