<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $data = [
        'policy_name' => trim($_POST['policy_name'] ?? ''),
        'employee_social_rate' => (float)($_POST['employee_social_rate'] ?? 0),
        'employee_health_rate' => (float)($_POST['employee_health_rate'] ?? 0),
        'employee_unemployment_rate' => (float)($_POST['employee_unemployment_rate'] ?? 0),
        'employer_social_rate' => (float)($_POST['employer_social_rate'] ?? 0),
        'employer_health_rate' => (float)($_POST['employer_health_rate'] ?? 0),
        'employer_unemployment_rate' => (float)($_POST['employer_unemployment_rate'] ?? 0),
        'min_base_salary' => (float)($_POST['min_base_salary'] ?? 0),
        'max_base_salary' => (float)($_POST['max_base_salary'] ?? 0),
        'effective_from' => $_POST['effective_from'] ?? date('Y-m-d'),
        'effective_to' => $_POST['effective_to'] ?: null,
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
    ];
    if ($id > 0) {
        $stmt = db()->prepare("UPDATE insurance_policies SET policy_name=?, employee_social_rate=?, employee_health_rate=?, employee_unemployment_rate=?, employer_social_rate=?, employer_health_rate=?, employer_unemployment_rate=?, min_base_salary=?, max_base_salary=?, effective_from=?, effective_to=?, is_active=? WHERE id=?");
        $stmt->bind_param('sddddddddssii', $data['policy_name'], $data['employee_social_rate'], $data['employee_health_rate'], $data['employee_unemployment_rate'], $data['employer_social_rate'], $data['employer_health_rate'], $data['employer_unemployment_rate'], $data['min_base_salary'], $data['max_base_salary'], $data['effective_from'], $data['effective_to'], $data['is_active'], $id);
        $stmt->execute();
        audit_log('update', 'insurance_policies', $id, null, $data);
        flash('success', 'Đã cập nhật chính sách bảo hiểm.');
    } else {
        $stmt = db()->prepare("INSERT INTO insurance_policies (policy_name, employee_social_rate, employee_health_rate, employee_unemployment_rate, employer_social_rate, employer_health_rate, employer_unemployment_rate, min_base_salary, max_base_salary, effective_from, effective_to, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('sddddddddssi', $data['policy_name'], $data['employee_social_rate'], $data['employee_health_rate'], $data['employee_unemployment_rate'], $data['employer_social_rate'], $data['employer_health_rate'], $data['employer_unemployment_rate'], $data['min_base_salary'], $data['max_base_salary'], $data['effective_from'], $data['effective_to'], $data['is_active']);
        $stmt->execute();
        audit_log('create', 'insurance_policies', db()->insert_id, null, $data);
        flash('success', 'Đã thêm chính sách bảo hiểm.');
    }
    redirect(url('insurance_policies'));
}
$edit = null;
if (($_GET['action'] ?? '') === 'edit') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = db()->prepare("SELECT * FROM insurance_policies WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc();
}
$rows = db()->query("SELECT * FROM insurance_policies ORDER BY effective_from DESC");
require __DIR__ . '/../../includes/layout/header.php';
?>
<div class="card"><h2><?= $edit ? 'Sửa chính sách bảo hiểm' : 'Thêm chính sách bảo hiểm' ?></h2>
<form method="post" class="form-grid"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($edit['id'] ?? 0) ?>">
<div class="form-group"><label>Tên chính sách</label><input name="policy_name" value="<?= e($edit['policy_name'] ?? '') ?>" required></div>
<div class="form-group"><label>Hiệu lực từ</label><input type="date" name="effective_from" value="<?= e($edit['effective_from'] ?? date('Y-m-d')) ?>" required></div>
<div class="form-group"><label>Hiệu lực đến</label><input type="date" name="effective_to" value="<?= e($edit['effective_to'] ?? '') ?>"></div>
<div class="form-group"><label>BHXH NV</label><input type="number" step="0.00001" name="employee_social_rate" value="<?= e($edit['employee_social_rate'] ?? '0.08') ?>"></div>
<div class="form-group"><label>BHYT NV</label><input type="number" step="0.00001" name="employee_health_rate" value="<?= e($edit['employee_health_rate'] ?? '0.015') ?>"></div>
<div class="form-group"><label>BHTN NV</label><input type="number" step="0.00001" name="employee_unemployment_rate" value="<?= e($edit['employee_unemployment_rate'] ?? '0.01') ?>"></div>
<div class="form-group"><label>BHXH Công ty</label><input type="number" step="0.00001" name="employer_social_rate" value="<?= e($edit['employer_social_rate'] ?? '0.175') ?>"></div>
<div class="form-group"><label>BHYT Công ty</label><input type="number" step="0.00001" name="employer_health_rate" value="<?= e($edit['employer_health_rate'] ?? '0.03') ?>"></div>
<div class="form-group"><label>BHTN Công ty</label><input type="number" step="0.00001" name="employer_unemployment_rate" value="<?= e($edit['employer_unemployment_rate'] ?? '0.01') ?>"></div>
<div class="form-group"><label>Mức sàn</label><input type="number" name="min_base_salary" value="<?= e($edit['min_base_salary'] ?? 0) ?>"></div>
<div class="form-group"><label>Mức trần</label><input type="number" name="max_base_salary" value="<?= e($edit['max_base_salary'] ?? 0) ?>"></div>
<div class="form-group"><label><input type="checkbox" name="is_active" <?= checked($edit['is_active'] ?? 1) ?>> Đang áp dụng</label></div>
<div class="actions"><button class="btn">Lưu</button><a class="btn light" href="<?= e(url('insurance_policies')) ?>">Làm mới</a></div>
</form></div>
<div class="card"><h2>Danh sách chính sách bảo hiểm</h2><div class="table-wrap"><table><tr><th>Tên</th><th>NV đóng</th><th>Công ty đóng</th><th>Sàn/Trần</th><th>Hiệu lực</th><th></th></tr>
<?php while ($r=$rows->fetch_assoc()): ?><tr><td><?= e($r['policy_name']) ?></td><td><?= e(($r['employee_social_rate']+$r['employee_health_rate']+$r['employee_unemployment_rate'])*100) ?>%</td><td><?= e(($r['employer_social_rate']+$r['employer_health_rate']+$r['employer_unemployment_rate'])*100) ?>%</td><td><?= format_money($r['min_base_salary']) ?> / <?= format_money($r['max_base_salary']) ?></td><td><?= e($r['effective_from']) ?> → <?= e($r['effective_to']) ?></td><td><a href="<?= e(url('insurance_policies',['action'=>'edit','id'=>$r['id']])) ?>">Sửa</a></td></tr><?php endwhile; ?>
</table></div></div>
<?php require __DIR__ . '/../../includes/layout/footer.php'; ?>
