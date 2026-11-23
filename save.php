<?php
require_once __DIR__ . '/init.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/');
}

csrf_check();

$me = current_user_id();

// ===== 接收并清洗字段 =====
$id = (int)($_POST['id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$dreamDate = trim($_POST['dream_date'] ?? '');
$mood = trim($_POST['mood'] ?? '平淡');
$tagsRaw = trim($_POST['tags'] ?? '');
$content = trim($_POST['content'] ?? '');
$visibility = trim($_POST['visibility'] ?? 'public');

// 标签清洗：中文/英文逗号统一，去重去空白
$tags = '';
if ($tagsRaw !== '') {
    $parts = array_map('trim', preg_split('/[,，]/', $tagsRaw));
    $parts = array_values(array_unique(array_filter($parts)));
    $tags = implode(',', array_slice($parts, 0, 10));
}

// ===== 校验 =====
$errors = [];
if ($title === '') $errors[] = '请填写梦境标题。';
if ($content === '') $errors[] = '请填写梦境详细内容。';
if ($dreamDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dreamDate)) {
    $errors[] = '请选择有效的做梦日期。';
}
if (!in_array($mood, mood_options(), true)) $mood = '平淡';
if (!in_array($visibility, ['public', 'private'], true)) $visibility = 'public';

if ($errors) {
    $_SESSION['form_error'] = implode(' ', $errors);
    $_SESSION['form_old'] = compact('title', 'dreamDate', 'mood', 'tags', 'content', 'visibility');
    redirect($id > 0 ? ('/edit.php?id=' . $id) : '/edit.php');
}

// ===== 写入数据库 =====
if ($id > 0) {
    // 编辑：仅作者本人可改
    $exists = db_one("SELECT id, user_id FROM dream_log WHERE id = ?", [$id]);
    if (!$exists || (int)$exists['user_id'] !== $me) {
        http_response_code(403);
        exit('无权编辑这条梦境记录。');
    }
    db_query(
        "UPDATE dream_log SET title = ?, content = ?, mood = ?, tags = ?, visibility = ?, dream_date = ? WHERE id = ?",
        [$title, $content, $mood, $tags, $visibility, $dreamDate, $id]
    );
    $_SESSION['flash'] = '梦境记录已更新。';
    redirect('/detail.php?id=' . $id);
} else {
    db_query(
        "INSERT INTO dream_log (user_id, title, content, mood, tags, visibility, dream_date)
         VALUES (?, ?, ?, ?, ?, ?, ?)",
        [$me, $title, $content, $mood, $tags, $visibility, $dreamDate]
    );
    $newId = $db->insert_id;
    $_SESSION['flash'] = '梦境记录已保存。';
    redirect('/detail.php?id=' . $newId);
}
