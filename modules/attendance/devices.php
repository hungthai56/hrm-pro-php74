<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['device_name'] ?? '');
    $ip = trim($_POST['device_ip'] ?? '');
    $port = (int)($_POST['device_port'] ?? 4370);
    $type = trim($_POST['device_type'] ?? 'ronaldjack');
    $location = trim($_POST['location_name'] ?? '');
    $active = isset($_POST['is_active']) ? 1 : 0;
    if ($id > 0) {
        $stmt = db()->prepare("UPDATE attendance_devices SET device_name=?, device_ip=?, device_port=?, device_type=?, location_name=?, is_active=? WHERE id=?");
        $stmt->bind_param('ssissii', $name, $ip, $port, $type, $location, $active, $id);
        $stmt->execute();
        audit_log('update', 'attendance_devices', $id, null, $_POST);
        flash('success', 'Đã cập nhật máy chấm công.');
    } else {
        $stmt = db()->prepare("INSERT INTO attendance_devices (device_name, device_ip, device_port, device_type, location_name, is_active) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssissi', $name, $ip, $port, $type, $location, $active);
        $stmt->execute();
        audit_log('create', 'attendance_devices', db()->insert_id, null, $_POST);
        flash('success', 'Đã thêm máy chấm công.');
    }
    redirect(url('attendance_devices'));
}
if (($_GET['action'] ?? '') === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = db()->prepare("DELETE FROM attendance_devices WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    audit_log('delete', 'attendance_devices', $id);
    flash('success', 'Đã xóa máy nếu không có log liên quan.');
    redirect(url('attendance_devices'));
}
$edit = null;
if (($_GET['action'] ?? '') === 'edit') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = db()->prepare("SELECT * FROM attendance_devices WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc();
}
$rows = db()->query("SELECT * FROM attendance_devices ORDER BY id DESC");
require __DIR__ . '/../../includes/layout/header.php';
?>
<div class="card"><h2><?= $edit ? 'Sửa máy chấm công' : 'Thêm máy chấm công Ronald Jack' ?></h2>
<form method="post" class="form-grid"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($edit['id'] ?? 0) ?>">
<div class="form-group"><label>Tên máy</label><input name="device_name" value="<?= e($edit['device_name'] ?? '') ?>" required></div>
<div class="form-group"><label>IP</label><input name="device_ip" value="<?= e($edit['device_ip'] ?? '') ?>" required></div>
<div class="form-group"><label>Port</label><input type="number" name="device_port" value="<?= e($edit['device_port'] ?? 4370) ?>"></div>
<div class="form-group"><label>Loại máy</label><input name="device_type" value="<?= e($edit['device_type'] ?? 'ronaldjack') ?>"></div>
<div class="form-group"><label>Vị trí</label><input name="location_name" value="<?= e($edit['location_name'] ?? '') ?>"></div>
<div class="form-group"><label><input type="checkbox" name="is_active" <?= checked($edit['is_active'] ?? 1) ?>> Đang kết nối</label></div>
<div class="actions"><button class="btn">Lưu</button><a class="btn light" href="<?= e(url('attendance_devices')) ?>">Làm mới</a></div>
</form></div>
<div class="card"><h2>Danh sách máy</h2><div class="table-wrap"><table><tr><th>Tên</th><th>IP:Port</th><th>Loại</th><th>Vị trí</th><th>Sync cuối</th><th></th></tr>
<?php while ($r = $rows->fetch_assoc()): ?><tr><td><?= e($r['device_name']) ?></td><td><?= e($r['device_ip']) ?>:<?= e($r['device_port']) ?></td><td><?= e($r['device_type']) ?></td><td><?= e($r['location_name']) ?></td><td><?= e($r['last_sync_at']) ?></td><td class="actions"><a href="<?= e(url('attendance_devices', ['action'=>'edit','id'=>$r['id']])) ?>">Sửa</a><a onclick="return confirmDelete()" href="<?= e(url('attendance_devices', ['action'=>'delete','id'=>$r['id']])) ?>">Xóa</a></td></tr><?php endwhile; ?>
</table></div></div>
<?php require __DIR__ . '/../../includes/layout/footer.php'; ?>
