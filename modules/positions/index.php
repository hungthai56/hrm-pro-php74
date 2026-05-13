<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $code = trim($_POST['position_code'] ?? '');
    $name = trim($_POST['position_name'] ?? '');
    $active = isset($_POST['is_active']) ? 1 : 0;
    if ($id > 0) {
        $stmt = db()->prepare("UPDATE positions SET position_code=?, position_name=?, is_active=? WHERE id=?");
        $stmt->bind_param('ssii', $code, $name, $active, $id);
        $stmt->execute();
        audit_log('update', 'positions', $id, null, $_POST);
        flash('success', 'Đã cập nhật chức vụ.');
    } else {
        $stmt = db()->prepare("INSERT INTO positions (position_code, position_name, is_active) VALUES (?, ?, ?)");
        $stmt->bind_param('ssi', $code, $name, $active);
        $stmt->execute();
        audit_log('create', 'positions', db()->insert_id, null, $_POST);
        flash('success', 'Đã thêm chức vụ.');
    }
    redirect(url('positions'));
}
if (($_GET['action'] ?? '') === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = db()->prepare("DELETE FROM positions WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    audit_log('delete', 'positions', $id);
    flash('success', 'Đã xóa chức vụ nếu không bị ràng buộc dữ liệu.');
    redirect(url('positions'));
}
$edit = null;
if (($_GET['action'] ?? '') === 'edit') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = db()->prepare("SELECT * FROM positions WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc();
}
$rows = db()->query("SELECT * FROM positions ORDER BY position_name");
require __DIR__ . '/../../includes/layout/header.php';
?>
<div class="card">
    <h2><?= $edit ? 'Sửa chức vụ' : 'Thêm chức vụ' ?></h2>
    <form method="post" class="form-grid">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= e($edit['id'] ?? 0) ?>">
        <div class="form-group"><label>Mã chức vụ</label><input name="position_code" value="<?= e($edit['position_code'] ?? '') ?>" required></div>
        <div class="form-group"><label>Tên chức vụ</label><input name="position_name" value="<?= e($edit['position_name'] ?? '') ?>" required></div>
        <div class="form-group"><label><input type="checkbox" name="is_active" <?= checked($edit['is_active'] ?? 1) ?>> Đang sử dụng</label></div>
        <div class="actions"><button class="btn">Lưu</button><a class="btn light" href="<?= e(url('positions')) ?>">Làm mới</a></div>
    </form>
</div>
<div class="card">
    <h2>Danh sách chức vụ</h2>
    <div class="table-wrap"><table><tr><th>Mã</th><th>Tên</th><th>Trạng thái</th><th></th></tr>
        <?php while ($r = $rows->fetch_assoc()): ?>
            <tr><td><?= e($r['position_code']) ?></td><td><?= e($r['position_name']) ?></td><td><?= $r['is_active'] ? 'Active' : 'Inactive' ?></td><td class="actions"><a href="<?= e(url('positions', ['action'=>'edit','id'=>$r['id']])) ?>">Sửa</a><a onclick="return confirmDelete()" href="<?= e(url('positions', ['action'=>'delete','id'=>$r['id']])) ?>">Xóa</a></td></tr>
        <?php endwhile; ?>
    </table></div>
</div>
<?php require __DIR__ . '/../../includes/layout/footer.php'; ?>
