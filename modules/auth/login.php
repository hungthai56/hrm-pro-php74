<?php
if (is_logged_in()) {
    redirect(url('dashboard'));
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (login_attempt($email, $password)) {
        flash('success', 'Đăng nhập thành công.');
        redirect(url('dashboard'));
    }
    $error = 'Email hoặc mật khẩu không đúng.';
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập - <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="login-page">
    <div class="card login-card">
        <h1>HRM Pro</h1>
        <p class="muted">Đăng nhập hệ thống quản lý nhân sự, chấm công, lương và bảo hiểm.</p>
        <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
        <form method="post">
            <?= csrf_field() ?>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="admin@hrm.local" required>
            </div>
            <div class="form-group" style="margin-top: 12px;">
                <label>Mật khẩu</label>
                <input type="password" name="password" value="admin123" required>
            </div>
            <button class="btn" style="margin-top: 16px; width: 100%;">Đăng nhập</button>
        </form>
        <p class="muted">Tài khoản demo: admin@hrm.local / admin123</p>
    </div>
</div>
</body>
</html>
