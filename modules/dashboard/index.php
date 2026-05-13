<?php require __DIR__ . '/../../includes/layout/header.php'; ?>
<div class="grid grid-4">
    <div class="stat"><div class="muted">Nhân viên đang làm</div><div class="num"><?= table_count('employees', "status IN ('working','probation')") ?></div></div>
    <div class="stat"><div class="muted">Máy chấm công</div><div class="num"><?= table_count('attendance_devices', 'is_active = 1') ?></div></div>
    <div class="stat"><div class="muted">Log công tháng này</div><div class="num"><?= table_count('attendance_raw_logs', "punch_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')") ?></div></div>
    <div class="stat"><div class="muted">Kỳ lương draft</div><div class="num"><?= table_count('payroll_periods', "status = 'draft'") ?></div></div>
</div>

<div class="card">
    <h2>Luồng vận hành đề xuất</h2>
    <div class="table-wrap">
        <table>
            <tr><th>Bước</th><th>Chức năng</th><th>Ghi chú</th></tr>
            <tr><td>1</td><td>Nhập nhân viên và mã chấm công</td><td>Mã chấm công phải trùng mã trên máy Ronald Jack.</td></tr>
            <tr><td>2</td><td>Cấu hình ca làm</td><td>Ca thẳng, ca đêm, tăng ca, số phút nghỉ.</td></tr>
            <tr><td>3</td><td>Đồng bộ log từ máy</td><td>Lưu vào bảng log thô để đối chiếu.</td></tr>
            <tr><td>4</td><td>Xử lý công ngày/tháng</td><td>Tính đi trễ, về sớm, công, tăng ca.</td></tr>
            <tr><td>5</td><td>Tính lương</td><td>Tính theo công, bảo hiểm, khấu trừ và thực lãnh.</td></tr>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../../includes/layout/footer.php'; ?>
