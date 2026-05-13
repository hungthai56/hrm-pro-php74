<?php
function get_select_options(string $table, string $idField, string $nameField): array
{
    $rows = [];
    $res = db()->query("SELECT {$idField}, {$nameField} FROM {$table} WHERE is_active = 1 ORDER BY {$nameField}");
    while ($r = $res->fetch_assoc()) {
        $rows[] = $r;
    }
    return $rows;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $data = [
        'employee_code' => trim($_POST['employee_code'] ?? ''),
        'attendance_code' => trim($_POST['attendance_code'] ?? ''),
        'full_name' => trim($_POST['full_name'] ?? ''),
        'gender' => $_POST['gender'] ?? 'other',
        'date_of_birth' => $_POST['date_of_birth'] ?: null,
        'phone' => trim($_POST['phone'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'department_id' => (int)($_POST['department_id'] ?? 0) ?: null,
        'position_id' => (int)($_POST['position_id'] ?? 0) ?: null,
        'join_date' => $_POST['join_date'] ?: null,
        'probation_end_date' => $_POST['probation_end_date'] ?: null,
        'contract_end_date' => $_POST['contract_end_date'] ?: null,
        'status' => $_POST['status'] ?? 'working',
        'salary_type' => $_POST['salary_type'] ?? 'gross',
        'base_salary' => (float)($_POST['base_salary'] ?? 0),
        'insurance_base_salary' => (float)($_POST['insurance_base_salary'] ?? 0),
        'bank_name' => trim($_POST['bank_name'] ?? ''),
        'bank_account' => trim($_POST['bank_account'] ?? ''),
        'tax_code' => trim($_POST['tax_code'] ?? ''),
        'id_number' => trim($_POST['id_number'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
    ];

    if ($id > 0) {
        $oldStmt = db()->prepare("SELECT * FROM employees WHERE id=?");
        $oldStmt->bind_param('i', $id);
        $oldStmt->execute();
        $old = $oldStmt->get_result()->fetch_assoc();

        $stmt = db()->prepare("UPDATE employees SET employee_code=?, attendance_code=?, full_name=?, gender=?, date_of_birth=?, phone=?, email=?, department_id=?, position_id=?, join_date=?, probation_end_date=?, contract_end_date=?, status=?, salary_type=?, base_salary=?, insurance_base_salary=?, bank_name=?, bank_account=?, tax_code=?, id_number=?, address=?, updated_at=NOW() WHERE id=?");
        $stmt->bind_param('sssssssiisssssddsssssi', $data['employee_code'], $data['attendance_code'], $data['full_name'], $data['gender'], $data['date_of_birth'], $data['phone'], $data['email'], $data['department_id'], $data['position_id'], $data['join_date'], $data['probation_end_date'], $data['contract_end_date'], $data['status'], $data['salary_type'], $data['base_salary'], $data['insurance_base_salary'], $data['bank_name'], $data['bank_account'], $data['tax_code'], $data['id_number'], $data['address'], $id);
        $stmt->execute();
        audit_log('update', 'employees', $id, $old, $data);
        flash('success', 'Đã cập nhật nhân viên.');
    } else {
        $stmt = db()->prepare("INSERT INTO employees (employee_code, attendance_code, full_name, gender, date_of_birth, phone, email, department_id, position_id, join_date, probation_end_date, contract_end_date, status, salary_type, base_salary, insurance_base_salary, bank_name, bank_account, tax_code, id_number, address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('sssssssiisssssddsssss', $data['employee_code'], $data['attendance_code'], $data['full_name'], $data['gender'], $data['date_of_birth'], $data['phone'], $data['email'], $data['department_id'], $data['position_id'], $data['join_date'], $data['probation_end_date'], $data['contract_end_date'], $data['status'], $data['salary_type'], $data['base_salary'], $data['insurance_base_salary'], $data['bank_name'], $data['bank_account'], $data['tax_code'], $data['id_number'], $data['address']);
        $stmt->execute();
        audit_log('create', 'employees', db()->insert_id, null, $data);
        flash('success', 'Đã thêm nhân viên.');
    }
    redirect(url('employees'));
}

if (($_GET['action'] ?? '') === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = db()->prepare("UPDATE employees SET status='resigned', updated_at=NOW() WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    audit_log('soft_delete', 'employees', $id);
    flash('success', 'Đã chuyển nhân viên sang trạng thái nghỉ việc.');
    redirect(url('employees'));
}

$edit = null;
if (($_GET['action'] ?? '') === 'edit') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = db()->prepare("SELECT * FROM employees WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc();
}
$departments = get_select_options('departments', 'id', 'department_name');
$positions = get_select_options('positions', 'id', 'position_name');
$keyword = trim($_GET['q'] ?? '');
$sql = "SELECT e.*, d.department_name, p.position_name FROM employees e LEFT JOIN departments d ON d.id=e.department_id LEFT JOIN positions p ON p.id=e.position_id";
if ($keyword !== '') {
    $like = '%' . $keyword . '%';
    $stmt = db()->prepare($sql . " WHERE e.employee_code LIKE ? OR e.full_name LIKE ? OR e.attendance_code LIKE ? ORDER BY e.id DESC");
    $stmt->bind_param('sss', $like, $like, $like);
    $stmt->execute();
    $rows = $stmt->get_result();
} else {
    $rows = db()->query($sql . " ORDER BY e.id DESC LIMIT 200");
}
require __DIR__ . '/../../includes/layout/header.php';
?>
<div class="card">
    <h2><?= $edit ? 'Sửa nhân viên' : 'Thêm nhân viên' ?></h2>
    <form method="post" class="form-grid">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= e($edit['id'] ?? 0) ?>">
        <div class="form-group"><label>Mã nhân viên</label><input name="employee_code" value="<?= e($edit['employee_code'] ?? '') ?>" required></div>
        <div class="form-group"><label>Mã trên máy chấm công</label><input name="attendance_code" value="<?= e($edit['attendance_code'] ?? '') ?>"></div>
        <div class="form-group"><label>Họ tên</label><input name="full_name" value="<?= e($edit['full_name'] ?? '') ?>" required></div>
        <div class="form-group"><label>Giới tính</label><select name="gender"><option value="male" <?= selected($edit['gender'] ?? '', 'male') ?>>Nam</option><option value="female" <?= selected($edit['gender'] ?? '', 'female') ?>>Nữ</option><option value="other" <?= selected($edit['gender'] ?? 'other', 'other') ?>>Khác</option></select></div>
        <div class="form-group"><label>Ngày sinh</label><input type="date" name="date_of_birth" value="<?= e($edit['date_of_birth'] ?? '') ?>"></div>
        <div class="form-group"><label>Điện thoại</label><input name="phone" value="<?= e($edit['phone'] ?? '') ?>"></div>
        <div class="form-group"><label>Email</label><input type="email" name="email" value="<?= e($edit['email'] ?? '') ?>"></div>
        <div class="form-group"><label>Phòng ban</label><select name="department_id"><option value="">-- Chọn --</option><?php foreach ($departments as $d): ?><option value="<?= e($d['id']) ?>" <?= selected($edit['department_id'] ?? '', $d['id']) ?>><?= e($d['department_name']) ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label>Chức vụ</label><select name="position_id"><option value="">-- Chọn --</option><?php foreach ($positions as $p): ?><option value="<?= e($p['id']) ?>" <?= selected($edit['position_id'] ?? '', $p['id']) ?>><?= e($p['position_name']) ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label>Ngày vào làm</label><input type="date" name="join_date" value="<?= e($edit['join_date'] ?? '') ?>"></div>
        <div class="form-group"><label>Hết thử việc</label><input type="date" name="probation_end_date" value="<?= e($edit['probation_end_date'] ?? '') ?>"></div>
        <div class="form-group"><label>Hết hợp đồng</label><input type="date" name="contract_end_date" value="<?= e($edit['contract_end_date'] ?? '') ?>"></div>
        <div class="form-group"><label>Trạng thái</label><select name="status"><option value="working" <?= selected($edit['status'] ?? '', 'working') ?>>Đang làm</option><option value="probation" <?= selected($edit['status'] ?? '', 'probation') ?>>Thử việc</option><option value="resigned" <?= selected($edit['status'] ?? '', 'resigned') ?>>Nghỉ việc</option><option value="suspended" <?= selected($edit['status'] ?? '', 'suspended') ?>>Tạm ngưng</option></select></div>
        <div class="form-group"><label>Loại lương</label><select name="salary_type"><option value="gross" <?= selected($edit['salary_type'] ?? '', 'gross') ?>>Gross</option><option value="net" <?= selected($edit['salary_type'] ?? '', 'net') ?>>Net</option></select></div>
        <div class="form-group"><label>Lương cơ bản</label><input type="number" name="base_salary" value="<?= e($edit['base_salary'] ?? 0) ?>"></div>
        <div class="form-group"><label>Lương đóng bảo hiểm</label><input type="number" name="insurance_base_salary" value="<?= e($edit['insurance_base_salary'] ?? 0) ?>"></div>
        <div class="form-group"><label>Ngân hàng</label><input name="bank_name" value="<?= e($edit['bank_name'] ?? '') ?>"></div>
        <div class="form-group"><label>Số tài khoản</label><input name="bank_account" value="<?= e($edit['bank_account'] ?? '') ?>"></div>
        <div class="form-group"><label>Mã số thuế</label><input name="tax_code" value="<?= e($edit['tax_code'] ?? '') ?>"></div>
        <div class="form-group"><label>CCCD/CMND</label><input name="id_number" value="<?= e($edit['id_number'] ?? '') ?>"></div>
        <div class="form-group" style="grid-column: 1 / -1;"><label>Địa chỉ</label><textarea name="address"><?= e($edit['address'] ?? '') ?></textarea></div>
        <div class="actions"><button class="btn">Lưu nhân viên</button><a class="btn light" href="<?= e(url('employees')) ?>">Làm mới</a></div>
    </form>
</div>
<div class="card">
    <div class="actions" style="justify-content: space-between;">
        <h2>Danh sách nhân viên</h2>
        <form method="get" class="actions"><input type="hidden" name="page" value="employees"><input name="q" placeholder="Tìm mã, tên, mã chấm công" value="<?= e($keyword) ?>"><button class="btn light">Tìm</button></form>
    </div>
    <div class="table-wrap"><table><tr><th>Mã NV</th><th>Mã MCC</th><th>Họ tên</th><th>Phòng ban</th><th>Chức vụ</th><th>Lương</th><th>BH</th><th>Trạng thái</th><th></th></tr>
        <?php while ($r = $rows->fetch_assoc()): ?>
            <tr>
                <td><?= e($r['employee_code']) ?></td><td><?= e($r['attendance_code']) ?></td><td><?= e($r['full_name']) ?></td><td><?= e($r['department_name']) ?></td><td><?= e($r['position_name']) ?></td><td><?= format_money($r['base_salary']) ?></td><td><?= format_money($r['insurance_base_salary']) ?></td><td><span class="badge"><?= e($r['status']) ?></span></td>
                <td class="actions"><a href="<?= e(url('employees', ['action'=>'edit','id'=>$r['id']])) ?>">Sửa</a><a onclick="return confirmDelete('Chuyển nhân viên này sang nghỉ việc?')" href="<?= e(url('employees', ['action'=>'delete','id'=>$r['id']])) ?>">Nghỉ việc</a></td>
            </tr>
        <?php endwhile; ?>
    </table></div>
</div>
<?php require __DIR__ . '/../../includes/layout/footer.php'; ?>
