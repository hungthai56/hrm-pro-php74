<?php $user = current_user(); ?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="app-shell">
    <?php if ($user): ?>
        <?php require __DIR__ . '/sidebar.php'; ?>
    <?php endif; ?>
    <main class="main">
        <?php if ($user): ?>
            <header class="topbar">
                <div>
                    <strong><?= e(APP_NAME) ?></strong>
                    <span class="muted">v<?= e(APP_VERSION) ?></span>
                </div>
                <div class="topbar-user">
                    <?= e($user['full_name']) ?> · <?= e($user['role_name']) ?>
                    <a href="<?= e(url('logout')) ?>">Đăng xuất</a>
                </div>
            </header>
        <?php endif; ?>
        <section class="content">
            <?php foreach (get_flash_messages() as $msg): ?>
                <div class="alert alert-<?= e($msg['type']) ?>"><?= e($msg['message']) ?></div>
            <?php endforeach; ?>
