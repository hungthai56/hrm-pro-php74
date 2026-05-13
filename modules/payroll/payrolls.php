<?php
$periodId = (int)($_GET['period_id'] ?? 0);
$periods = db()->query("SELECT * FROM payroll_periods ORDER BY from_date DESC");
if ($periodId <= 0) {
    $first = db()->query("SELECT id FROM payroll_periods ORDER BY from_date DESC LIMIT 1")->fetch_assoc();
    $periodId = $first ? (int)$first['id'] : 0;
}

$period = null;
if ($periodId > 0) {
    $stmt = db()->prepare("SELECT * FROM payroll_periods WHERE id=?");
    $stmt->bind_param('i', $periodId);
    $stmt->execute();
    $period = $stmt->get_result()->fetch_assoc();
}

$stmt = db()->prepare("SELECT p.*, e.employee_code, e.full_name, d.department_name FROM payrolls p JOIN employees e ON e.id=p.employee_id LEFT JOIN departments d ON d.id=e.department_id WHERE p.payroll_period_id=? ORDER BY e.employee_code");
$stmt->bind_param('i', $periodId);
$stmt->execute();
$rows = $stmt->get_result();

$viewItems = [];
if (($_GET['view'] ?? '') !== '') {
    $payrollId = (int)$_GET['view'];
    $stmt = db()->prepare("SELECT * FROM payroll_items WHERE payroll_id=? ORDER BY id");
    $stmt->bind_param('i', $payrollId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) { $viewItems[] = $r; }
}
require __DIR__ . '/../../includes/layout/header.php';
?>
<div class="card">
    <h2>Bảng lương</h2>
    <form method="get" class="actions">
        <input type="hidden" name="page" value="payrolls">
        <select name="period_id">
            <?php while ($p=$periods->fetch_assoc()): ?><option value="<?= e($p['id']) ?>" <?= selected($periodId, $p['id']) ?>><?= e($p['period_name']) ?></option><?php endwhile; ?>
        </select>
        <button class="btn light">Xem</button>
        <?php if ($period): ?><a class="btn" href="<?= e(url('payroll_periods',['action'=>'generate','id'=>$periodId])) ?>" onclick="return confirm('Tính lại lương kỳ này?')">Tính lại lương</a><?php endif; ?>
    </form>
    <?php if ($period): ?><p class="muted">Kỳ: <?= e($period['from_date']) ?> → <?= e($period['to_date']) ?> · Trạng thái: <?= e($period['status']) ?></p><?php endif; ?>
</div>
<div class="card"><h3>Danh sách lương</h3><div class="table-wrap"><table><tr><th>Mã NV</th><th>Nhân viên</th><th>Phòng ban</th><th>Công</th><th>Lương cơ bản</th><th>Lương công</th><th>Tổng thu</th><th>BH NV</th><th>BH CT</th><th>Khấu trừ</th><th>Thực lãnh</th><th></th></tr>
<?php while ($r=$rows->fetch_assoc()): ?><tr><td><?= e($r['employee_code']) ?></td><td><?= e($r['full_name']) ?></td><td><?= e($r['department_name']) ?></td><td><?= e($r['actual_work_days']) ?>/<?= e($r['standard_work_days']) ?></td><td><?= format_money($r['base_salary']) ?></td><td><?= format_money($r['working_salary']) ?></td><td><?= format_money($r['total_earning']) ?></td><td><?= format_money($r['employee_insurance_amount']) ?></td><td><?= format_money($r['employer_insurance_amount']) ?></td><td><?= format_money($r['total_deduction']) ?></td><td><strong><?= format_money($r['net_salary']) ?></strong></td><td><a href="<?= e(url('payrolls',['period_id'=>$periodId,'view'=>$r['id']])) ?>">Chi tiết</a></td></tr><?php endwhile; ?>
</table></div></div>
<?php if ($viewItems): ?><div class="card"><h3>Chi tiết phiếu lương</h3><div class="table-wrap"><table><tr><th>Mã</th><th>Tên khoản</th><th>Loại</th><th>Số tiền</th><th>Công thức</th></tr><?php foreach ($viewItems as $item): ?><tr><td><?= e($item['component_code']) ?></td><td><?= e($item['component_name']) ?></td><td><?= e($item['component_type']) ?></td><td><?= format_money($item['amount']) ?></td><td><small><?= e($item['formula_snapshot']) ?></small></td></tr><?php endforeach; ?></table></div></div><?php endif; ?>
<?php require __DIR__ . '/../../includes/layout/footer.php'; ?>
