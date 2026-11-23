<?php
/**
 * 评论提交接口：登录用户可对公开梦境发表顶级评论或回复顶级评论（两级楼中楼）。
 */
require_once __DIR__ . '/init.php';
require_login();
global $db;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/');
}
csrf_check();

$dreamId = (int)($_POST['dream_id'] ?? 0);
$parentId = (int)($_POST['parent_id'] ?? 0);
$content = trim($_POST['content'] ?? '');
$me = current_user_id();

// 校验梦境存在且当前用户可见
$record = $dreamId > 0 ? db_one("SELECT * FROM dream_log WHERE id = ?", [$dreamId]) : null;
if (!$record || !can_view_dream($record)) {
    http_response_code(404);
    exit('这条梦境记录不存在、已被删除，或为隐私内容。');
}
// 隐私梦境不开放评论
if ($record['visibility'] === 'private') {
    $_SESSION['form_error'] = '隐私梦境不开放评论。';
    redirect('/detail.php?id=' . $dreamId);
}

// 校验评论内容
if ($content === '') {
    $_SESSION['form_error'] = '评论内容不能为空。';
    redirect('/detail.php?id=' . $dreamId);
}
if (mb_strlen($content) > 500) {
    $_SESSION['form_error'] = '评论内容不能超过 500 字。';
    redirect('/detail.php?id=' . $dreamId);
}

// 校验回复归属：父评论必须存在、属于同一梦境、且为顶级评论（两级限制）
$parent = null;
if ($parentId > 0) {
    $parent = db_one(
        "SELECT id, dream_id, parent_id, status FROM comments WHERE id = ?",
        [$parentId]
    );
    if (!$parent
        || (int)$parent['dream_id'] !== $dreamId
        || $parent['parent_id'] !== null
        || $parent['status'] !== 'normal') {
        $parent = null;
        $parentId = 0;
    }
}

db_query(
    "INSERT INTO comments (dream_id, user_id, parent_id, content, status) VALUES (?, ?, ?, ?, 'normal')",
    [$dreamId, $me, $parent ? $parentId : null, $content]
);
$newId = $db->insert_id;

redirect('/detail.php?id=' . $dreamId . '#comment-' . $newId);
