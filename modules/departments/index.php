<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $code = trim($_POST['department_code'] ?? '');
    $name = trim($_POST['department_name'] ?? '');
    $active = isset($_POST['is_active']) ? 1 : 0;

    if ($id > 0) {
        $stmt = db()->prepare("UPDATE departments SET department_code=?, department_name=?, is_active=? WHERE id=?");
        $stmt->bind_param('ssii', $code, $name, $active, $id);
        $stmt->execute();
        audit_log('update', 'departments', $id, null, $_POST);
        flash('success', 'Đã cập nhật phòng ban.');
    } else {
        $stmt = db()->prepare("INSERT INTO departments (department_code, department_name, is_active) VALUES (?, ?, ?)");
        $stmt->bind_param('ssi', $code, $name, $active);
        $stmt->execute();
        audit_log('create', 'departments', db()->insert_id, null, $_POST);
        flash('success', 'Đã thêm phòng ban.');
    }
    redirect(url('departments'));
}

if (($_GET['action'] ?? '') === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = db()->prepare("DELETE FROM departments WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    audit_log('delete', 'departments', $id);
    flash('success', 'Đã xóa phòng ban nếu không bị ràng buộc dữ liệu.');
    redirect(url('departments'));
}

$edit = null;
if (($_GET['action'] ?? '') === 'edit') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = db()->prepare("SELECT * FROM departments WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc();
}
$rows = db()->query("SELECT * FROM departments ORDER BY department_name");
require __DIR__ . '/../../includes/layout/header.php';
?>
<div class="card">
    <h2><?= $edit ? 'Sửa phòng ban' : 'Thêm phòng ban' ?></h2>
    <form method="post" class="form-grid">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= e($edit['id'] ?? 0) ?>">
        <div class="form-group"><label>Mã phòng ban</label><input name="department_code" value="<?= e($edit['department_code'] ?? '') ?>" required></div>
        <div class="form-group"><label>Tên phòng ban</label><input name="department_name" value="<?= e($edit['department_name'] ?? '') ?>" required></div>
        <div class="form-group"><label><input type="checkbox" name="is_active" <?= checked($edit['is_active'] ?? 1) ?>> Đang sử dụng</label></div>
        <div class="actions"><button class="btn">Lưu</button><a class="btn light" href="<?= e(url('departments')) ?>">Làm mới</a></div>
    </form>
</div>
<div class="card">
    <h2>Danh sách phòng ban</h2>
    <div class="table-wrap"><table><tr><th>Mã</th><th>Tên</th><th>Trạng thái</th><th></th></tr>
        <?php while ($r = $rows->fetch_assoc()): ?>
            <tr><td><?= e($r['department_code']) ?></td><td><?= e($r['department_name']) ?></td><td><?= $r['is_active'] ? 'Active' : 'Inactive' ?></td><td class="actions"><a href="<?= e(url('departments', ['action'=>'edit','id'=>$r['id']])) ?>">Sửa</a><a class="danger-link" onclick="return confirmDelete()" href="<?= e(url('departments', ['action'=>'delete','id'=>$r['id']])) ?>">Xóa</a></td></tr>
        <?php endwhile; ?>
    </table></div>
</div>
<?php require __DIR__ . '/../../includes/layout/footer.php'; ?>
