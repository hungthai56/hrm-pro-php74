<?php
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function url(string $page = 'dashboard', array $params = []): string
{
    $params = array_merge(['page' => $page], $params);
    return 'index.php?' . http_build_query($params);
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flash_messages(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function format_money($amount): string
{
    return number_format((float)$amount, 0, ',', '.') . ' đ';
}

function format_date_vn($date): string
{
    if (!$date) {
        return '';
    }
    return date('d/m/Y', strtotime($date));
}

function selected($actual, $expected): string
{
    return (string)$actual === (string)$expected ? 'selected' : '';
}

function checked($actual): string
{
    return (int)$actual === 1 ? 'checked' : '';
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(419);
            die('CSRF token không hợp lệ. Vui lòng tải lại trang.');
        }
    }
}

function audit_log(string $action, string $entityType, $entityId = null, $oldData = null, $newData = null): void
{
    $userId = $_SESSION['user']['id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $oldJson = $oldData === null ? null : json_encode($oldData, JSON_UNESCAPED_UNICODE);
    $newJson = $newData === null ? null : json_encode($newData, JSON_UNESCAPED_UNICODE);

    $stmt = db()->prepare("INSERT INTO audit_logs (user_id, action, entity_type, entity_id, old_data, new_data, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $entityId = $entityId === null ? null : (string)$entityId;
    $stmt->bind_param('issssss', $userId, $action, $entityType, $entityId, $oldJson, $newJson, $ip);
    $stmt->execute();
}

function table_count(string $table, string $where = '1=1'): int
{
    $allowed = preg_match('/^[a-zA-Z0-9_]+$/', $table);
    if (!$allowed) {
        return 0;
    }
    $sql = "SELECT COUNT(*) AS total FROM {$table} WHERE {$where}";
    $res = db()->query($sql);
    $row = $res ? $res->fetch_assoc() : ['total' => 0];
    return (int)$row['total'];
}
