<?php
/**
 * 切换梦境公开/隐私：仅记录作者本人可操作，POST 提交。
 */
require_once __DIR__ . '/init.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/profile.php');
}
csrf_check();

$id = (int)($_POST['id'] ?? 0);
$me = current_user_id();

$record = db_one("SELECT id, user_id, visibility FROM dream_log WHERE id = ?", [$id]);
if (!$record || (int)$record['user_id'] !== $me) {
    http_response_code(403);
    exit('无权操作这条记录。');
}

$nextVisibility = $record['visibility'] === 'private' ? 'public' : 'private';
db_query("UPDATE dream_log SET visibility = ? WHERE id = ?", [$nextVisibility, $id]);

$_SESSION['flash'] = $nextVisibility === 'private'
    ? '已设为隐私，只有你本人可以看到。'
    : '已设为公开，会在网站首页展示给访客。';
redirect('/profile.php');
