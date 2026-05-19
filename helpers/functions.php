<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';

function e(string $val): string {
    return htmlspecialchars($val, ENT_QUOTES, 'UTF-8');
}
function sanitize(string $val): string {
    return trim(strip_tags($val));
}
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}
function csrf_verify(): void {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        http_response_code(403); die('Token CSRF invalide.');
    }
}
function auth_check(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . APP_URL . '/index.php?page=login'); exit;
    }
}
function auth_admin(): void {
    auth_check();
    if ($_SESSION['user_role'] !== 'admin') {
        header('Location: ' . APP_URL . '/index.php?page=dashboard'); exit;
    }
}
function current_user(): array {
    return [
        'id'    => $_SESSION['user_id']    ?? null,
        'nom'   => $_SESSION['user_nom']   ?? '',
        'role'  => $_SESSION['user_role']  ?? '',
        'email' => $_SESSION['user_email'] ?? '',
    ];
}
function format_money(float $amount): string {
    return number_format($amount, 0, ',', ' ') . ' ' . DEVISE;
}
function format_date(string $date): string {
    if (!$date) return '—';
    return date('d/m/Y', strtotime($date));
}
function format_datetime(string $dt): string {
    if (!$dt) return '—';
    return date('d/m/Y H:i', strtotime($dt));
}
function paginate(int $total, int $page, int $perPage = 20): array {
    $totalPages = max(1, (int) ceil($total / $perPage));
    $page       = max(1, min($page, $totalPages));
    $offset     = ($page - 1) * $perPage;
    return ['total'=>$total,'per_page'=>$perPage,'current'=>$page,'total_pages'=>$totalPages,'offset'=>$offset];
}
function generate_vente_ref(): string {
    $db   = getDB();
    $date = date('Ymd');
    do {
        $stmt = $db->query("SELECT COUNT(*) FROM ventes WHERE reference LIKE 'VTE-{$date}-%'");
        $n    = (int)$stmt->fetchColumn() + mt_rand(1, 9);
        $ref  = sprintf('VTE-%s-%04d', $date, $n);
        $chk  = $db->prepare("SELECT COUNT(*) FROM ventes WHERE reference=:r");
        $chk->execute([':r' => $ref]);
    } while ((int)$chk->fetchColumn() > 0);
    return $ref;
}
function upload_image(array $file, string $subdir = 'vendeurs'): ?string {
    $allowed = ['image/jpeg','image/png','image/webp'];
    if (!in_array($file['type'], $allowed)) return null;
    if ($file['size'] > 2 * 1024 * 1024) return null;
    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('img_') . '.' . strtolower($ext);
    $dir      = UPLOAD_DIR . $subdir . '/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    if (move_uploaded_file($file['tmp_name'], $dir . $filename)) return $subdir . '/' . $filename;
    return null;
}
function set_flash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}
function get_flash(): ?array {
    if (!empty($_SESSION['flash'])) { $f=$_SESSION['flash']; unset($_SESSION['flash']); return $f; }
    return null;
}
function render_flash(): void {
    $flash = get_flash();
    if (!$flash) return;
    $icons = ['success'=>'check-circle','danger'=>'exclamation-triangle','warning'=>'exclamation-circle','info'=>'info-circle'];
    $icon  = $icons[$flash['type']] ?? 'info-circle';
    echo '<div class="alert alert-'.e($flash['type']).' alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
        <i class="fas fa-'.$icon.'"></i><span>'.e($flash['msg']).'</span>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
}