<?php
/**
 * 评论删除接口：评论本人、梦境作者或管理员可删除。
 * 删除顶级评论时连同其下回复一并删除。
 */
require_once __DIR__ . '/init.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/');
}
csrf_check();

$commentId = (int)($_POST['id'] ?? 0);
$me = current_user_id();

$comment = db_one(
    "SELECT c.id, c.dream_id, c.user_id, c.parent_id, d.user_id AS dream_author
     FROM comments c LEFT JOIN dream_log d ON c.dream_id = d.id
     WHERE c.id = ?",
    [$commentId]
);

if (!$comment) {
    $_SESSION['flash'] = '评论不存在或已被删除。';
    redirect('/');
}

$isOwner = (int)$comment['user_id'] === $me;
$isDreamAuthor = (int)$comment['dream_author'] === $me;
if (!$isOwner && !$isDreamAuthor && !is_admin()) {
    http_response_code(403);
    exit('无权删除这条评论。');
}

$dreamId = (int)$comment['dream_id'];
if ($comment['parent_id'] === null) {
    // 顶级评论：级联删除其下全部回复
    db_query("DELETE FROM comments WHERE parent_id = ?", [$commentId]);
}
db_query("DELETE FROM comments WHERE id = ?", [$commentId]);

$_SESSION['flash'] = '评论已删除。';
redirect('/detail.php?id=' . $dreamId);
