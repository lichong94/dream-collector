<?php
require_once __DIR__ . '/init.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/');
}
csrf_check();

$id = (int)($_POST['id'] ?? 0);
$me = current_user_id();

$record = db_one("SELECT id, user_id FROM dream_log WHERE id = ?", [$id]);
if ($record) {
    $isOwner = (int)$record['user_id'] === $me;
    if (!$isOwner && !is_admin()) {
        http_response_code(403);
        exit('无权删除这条梦境记录。');
    }
    db_query("DELETE FROM dream_log WHERE id = ?", [$id]);
    $_SESSION['flash'] = '梦境记录已删除。';
}
redirect('/profile.php');
