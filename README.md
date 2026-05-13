# HRM Pro PHP 7.4 + MySQLi

Bản khởi đầu phần mềm quản lý nhân sự theo hướng: **nhân viên → máy chấm công Ronald Jack → log thô → xử lý công → bảo hiểm → tính lương**.

## Chức năng đã có trong bản scaffold

- Đăng nhập tài khoản quản trị.
- Quản lý phòng ban.
- Quản lý chức vụ.
- Quản lý hồ sơ nhân viên, mã chấm công, lương cơ bản, lương đóng bảo hiểm.
- Quản lý máy chấm công Ronald Jack/ZK.
- Lưu log chấm công thô.
- Nhập log chấm công thủ công.
- Cấu hình ca làm: ca hành chính, ca đêm, phút nghỉ, đi trễ, về sớm, tăng ca.
- Xử lý bảng công ngày từ log thô.
- Cấu hình tỷ lệ bảo hiểm.
- Tạo kỳ lương.
- Tính lương theo công, tăng ca, ca đêm và bảo hiểm.
- Xem bảng lương và chi tiết phiếu lương.
- Audit log cơ bản.

## Yêu cầu môi trường

- PHP 7.4+
- MySQL hoặc MariaDB
- Extension mysqli
- Web server Apache/Nginx hoặc PHP built-in server để test

## Cài đặt nhanh

1. Tạo database và import schema:

```bash
mysql -u root -p < database/hrm_schema.sql
mysql -u root -p < database/sample_data.sql
```

2. Sửa cấu hình database tại:

```text
config/database.php
```

3. Chạy test bằng PHP built-in server:

```bash
php -S localhost:8000 -t public
```

4. Mở trình duyệt:

```text
http://localhost:8000
```

Tài khoản demo:

```text
Email: admin@hrm.local
Mật khẩu: admin123
```

## Luồng test nhanh

1. Vào **Log chấm công** để xem log mẫu.
2. Vào **Xử lý công**, chọn 2026-05-01 đến 2026-05-31, bấm **Chạy xử lý công**.
3. Vào **Kỳ lương**, bấm **Tính lương** cho kỳ `2026-05`.
4. Vào **Bảng lương** để xem kết quả.

## Kết nối máy Ronald Jack thật

File wrapper:

```text
src/RonaldJackClient.php
```

Cron đồng bộ:

```text
cron/sync_attendance.php
```

Bản này chưa đóng gói thư viện ZKLib vì mỗi dòng máy Ronald Jack có thể trả format khác nhau. Khi triển khai thật:

1. Đặt thư viện ZKLib PHP vào `libs/ronaldjack/zklib.php`.
2. Kiểm tra class `ZKLib` và method `getAttendance()`.
3. Chỉnh map dữ liệu trong `RonaldJackClient::fetchAttendance()`.
4. Chạy thử:

```bash
php cron/sync_attendance.php
```

Có thể đặt cron mỗi 5 phút:

```bash
*/5 * * * * /usr/bin/php /path/to/hrm_pro_php74/cron/sync_attendance.php >> /path/to/hrm_pro_php74/storage/logs/cron.log 2>&1
```

## Các bước nâng cấp tiếp theo

- Phân quyền chi tiết theo vai trò.
- Import nhân viên từ Excel.
- Import phân ca từ Excel.
- Duyệt bổ sung công.
- Duyệt nghỉ phép.
- Công thức lương động đầy đủ.
- Thuế TNCN.
- NET/GROSS converter.
- Service charge.
- Suất ăn.
- Đào tạo và cam kết đào tạo.
- Tuyển dụng.
- Employee Portal.
- Xuất Excel/PDF phiếu lương.
- Gửi email phiếu lương.

## Ghi chú kỹ thuật

- Dữ liệu từ máy chấm công luôn lưu vào `attendance_raw_logs` trước.
- Bảng công đã xử lý nằm ở `attendance_daily`.
- Bảng lương tổng nằm ở `payrolls`, chi tiết từng khoản nằm ở `payroll_items`.
- Lương cơ bản và lương đóng bảo hiểm được tách riêng.
- Chính sách bảo hiểm có ngày hiệu lực để dễ thay đổi sau này.
