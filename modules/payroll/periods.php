<?php
require_once __DIR__ . '/../../src/PayrollCalculator.php';

if (($_GET['action'] ?? '') === 'generate') {
    $id = (int)($_GET['id'] ?? 0);
    $calculator = new PayrollCalculator(db());
    $count = $calculator->generateForPeriod($id);
    audit_log('generate', 'payroll_periods', $id, null, ['employees' => $count]);
    flash('success', "Đã tính lương cho {$count} nhân viên.");
    redirect(url('payrolls', ['period_id' => $id]));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $data = [
        'period_code' => trim($_POST['period_code'] ?? ''),
        'period_name' => trim($_POST['period_name'] ?? ''),
        'from_date' => $_POST['from_date'] ?? date('Y-m-01'),
        'to_date' => $_POST['to_date'] ?? date('Y-m-t'),
        'payment_date' => $_POST['payment_date'] ?: null,
        'standard_work_days' => (float)($_POST['standard_work_days'] ?? 26),
        'status' => $_POST['status'] ?? 'draft',
    ];
    if ($id > 0) {
        $stmt = db()->prepare("UPDATE payroll_periods SET period_code=?, period_name=?, from_date=?, to_date=?, payment_date=?, standard_work_days=?, status=? WHERE id=?");
        $stmt->bind_param('sssssdsi', $data['period_code'], $data['period_name'], $data['from_date'], $data['to_date'], $data['payment_date'], $data['standard_work_days'], $data['status'], $id);
    } else {
        $stmt = db()->prepare("INSERT INTO payroll_periods (period_code, period_name, from_date, to_date, payment_date, standard_work_days, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('sssssds', $data['period_code'], $data['period_name'], $data['from_date'], $data['to_date'], $data['payment_date'], $data['standard_work_days'], $data['status']);
    }
    $stmt->execute();
    audit_log($id > 0 ? 'update' : 'create', 'payroll_periods', $id ?: db()->insert_id, null, $data);
    flash('success', 'Đã lưu kỳ lương.');
    redirect(url('payroll_periods'));
}
$edit = null;
if (($_GET['action'] ?? '') === 'edit') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = db()->prepare("SELECT * FROM payroll_periods WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc();
}
$rows = db()->query("SELECT * FROM payroll_periods ORDER BY from_date DESC");
require __DIR__ . '/../../includes/layout/header.php';
?>
<div class="card"><h2><?= $edit ? 'Sửa kỳ lương' : 'Thêm kỳ lương' ?></h2>
<form method="post" class="form-grid"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($edit['id'] ?? 0) ?>">
<div class="form-group"><label>Mã kỳ</label><input name="period_code" value="<?= e($edit['period_code'] ?? date('Y-m')) ?>" required></div>
<div class="form-group"><label>Tên kỳ</label><input name="period_name" value="<?= e($edit['period_name'] ?? ('Lương tháng ' . date('m/Y'))) ?>" required></div>
<div class="form-group"><label>Từ ngày</label><input type="date" name="from_date" value="<?= e($edit['from_date'] ?? date('Y-m-01')) ?>" required></div>
<div class="form-group"><label>Đến ngày</label><input type="date" name="to_date" value="<?= e($edit['to_date'] ?? date('Y-m-t')) ?>" required></div>
<div class="form-group"><label>Ngày trả</label><input type="date" name="payment_date" value="<?= e($edit['payment_date'] ?? '') ?>"></div>
<div class="form-group"><label>Công chuẩn</label><input type="number" step="0.01" name="standard_work_days" value="<?= e($edit['standard_work_days'] ?? 26) ?>"></div>
<div class="form-group"><label>Trạng thái</label><select name="status"><option value="draft" <?= selected($edit['status'] ?? '', 'draft') ?>>Draft</option><option value="calculated" <?= selected($edit['status'] ?? '', 'calculated') ?>>Calculated</option><option value="locked" <?= selected($edit['status'] ?? '', 'locked') ?>>Locked</option><option value="paid" <?= selected($edit['status'] ?? '', 'paid') ?>>Paid</option></select></div>
<div class="actions"><button class="btn">Lưu kỳ</button><a class="btn light" href="<?= e(url('payroll_periods')) ?>">Làm mới</a></div>
</form></div>
<div class="card"><h2>Danh sách kỳ lương</h2><div class="table-wrap"><table><tr><th>Mã</th><th>Tên</th><th>Thời gian</th><th>Công chuẩn</th><th>Trạng thái</th><th></th></tr>
<?php while ($r=$rows->fetch_assoc()): ?><tr><td><?= e($r['period_code']) ?></td><td><?= e($r['period_name']) ?></td><td><?= e($r['from_date']) ?> → <?= e($r['to_date']) ?></td><td><?= e($r['standard_work_days']) ?></td><td><span class="badge"><?= e($r['status']) ?></span></td><td class="actions"><a href="<?= e(url('payroll_periods',['action'=>'edit','id'=>$r['id']])) ?>">Sửa</a><a href="<?= e(url('payrolls',['period_id'=>$r['id']])) ?>">Xem lương</a><a onclick="return confirm('Tính lại lương kỳ này?')" href="<?= e(url('payroll_periods',['action'=>'generate','id'=>$r['id']])) ?>">Tính lương</a></td></tr><?php endwhile; ?>
</table></div></div>
<?php require __DIR__ . '/../../includes/layout/footer.php'; ?>
