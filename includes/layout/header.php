<?php $user = current_user(); ?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(APP_NAME) ?> · <?= e(APP_TAGLINE) ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="app-shell">
    <?php if ($user) require __DIR__ . '/sidebar.php'; ?>
    <main class="main">
        <?php if ($user): ?>
        <header class="topbar">
            <div class="topbar-left">
                <button class="icon-btn mobile-menu" data-sidebar-toggle type="button" aria-label="Mở menu">☰</button>
                <div class="workspace-title">
                    <span class="workspace-label">HRM Pro Workspace</span>
                    <strong><?= e(APP_NAME) ?></strong>
                </div>
            </div>
            <div class="global-search">
                <span>Tìm kiếm</span>
                <input placeholder="Nhân viên, mã công, kỳ lương, đơn duyệt..." onkeydown="if(event.key==='Enter'){location.href='<?= e(url('employees')) ?>&q='+encodeURIComponent(this.value)}">
            </div>
            <div class="topbar-actions">
                <a class="btn btn-soft" href="<?= e(url('approvals')) ?>">Duyệt yêu cầu</a>
                <a class="btn" href="<?= e(url('payroll_periods')) ?>">Tính lương</a>
                <div class="user-menu">
                    <div class="avatar"><?= e(initials($user['full_name'])) ?></div>
                    <div class="user-meta">
                        <b><?= e($user['full_name']) ?></b>
                        <span><?= e($user['role_name']) ?></span>
                    </div>
                    <a class="logout-link" href="<?= e(url('logout')) ?>">Đăng xuất</a>
                </div>
            </div>
        </header>
        <?php endif; ?>
        <section class="content">
            <?php foreach (get_flash_messages() as $msg): ?>
                <div class="alert alert-<?= e($msg['type']) ?>"><?= e($msg['message']) ?></div>
            <?php endforeach; ?>
