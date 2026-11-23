<?php
require_once __DIR__ . '/init.php';

$id = (int)($_GET['id'] ?? 0);
$record = $id > 0 ? db_one("SELECT * FROM dream_log WHERE id = ?", [$id]) : null;

// 隐私记录仅作者本人或管理员可见；不可见时按 404 处理，不泄露记录是否存在
if (!$record || !can_view_dream($record)) {
    http_response_code(404);
    $page_meta = [
        'title' => '梦境未找到 - ' . SITE_NAME,
        'description' => '你访问的梦境记录不存在、已被删除，或为隐私内容。',
        'robots' => 'noindex',
    ];
    $active_nav = 'index';
    require_once __DIR__ . '/header.php';
    echo '<div class="empty"><div class="empty-icon">🌙</div><div>这条梦境记录不存在、已被删除，或仅作者本人可见。</div><p style="margin-top:12px;"><a class="btn btn-ghost" href="/">返回首页</a></p></div>';
    require_once __DIR__ . '/footer.php';
    exit;
}

$isOwner = current_user_id() > 0 && ((int)$record['user_id'] === current_user_id() || is_admin());

// 浏览量自增（仅详情页访问时累加）
db_query("UPDATE dream_log SET view_count = view_count + 1 WHERE id = ?", [$id]);
$record['view_count']++;

// 相关推荐：同心情优先，其次最近记录（仅公开梦境）
$related = db_all(
    "SELECT id, title, mood, dream_date FROM dream_log
     WHERE id <> ? AND mood = ? AND visibility = 'public'
     ORDER BY dream_date DESC, id DESC LIMIT 3",
    [$id, $record['mood']]
);
if (count($related) < 3) {
    $more = db_all(
        "SELECT id, title, mood, dream_date FROM dream_log
         WHERE id <> ? AND mood <> ? AND visibility = 'public'
         ORDER BY dream_date DESC, id DESC LIMIT ?",
        [$id, $record['mood'], 3 - count($related)]
    );
    $related = array_merge($related, $more);
}

$tags = array_filter(explode(',', $record['tags']));

// ===== 评论数据（仅公开梦境开放评论） =====
$me = current_user_id();
$isPublicDream = $record['visibility'] === 'public';
$commentCount = 0;
$commentTop = [];
$commentReplies = [];
if ($isPublicDream) {
    $commentCount = (int)db_one(
        "SELECT COUNT(*) AS c FROM comments WHERE dream_id = ? AND status = 'normal'",
        [$id]
    )['c'];
    $commentRows = db_all(
        "SELECT c.id, c.dream_id, c.user_id, c.parent_id, c.content, c.created_at,
                u.nickname, u.username
         FROM comments c LEFT JOIN users u ON c.user_id = u.id
         WHERE c.dream_id = ? AND c.status = 'normal'
         ORDER BY c.id ASC",
        [$id]
    );
    foreach ($commentRows as $cr) {
        if ($cr['parent_id'] === null) {
            $commentTop[] = $cr;
        } else {
            $commentReplies[(int)$cr['parent_id']][] = $cr;
        }
    }
}
// 评论删除权限：评论本人 / 梦境作者 / 管理员
$canModerateComment = function ($cr) use ($record, $me) {
    return (int)$cr['user_id'] === $me || (int)$record['user_id'] === $me || is_admin();
};

$detailUrl = SITE_URL . '/dream/' . $record['id'] . '.html';
$page_meta = [
    'title' => $record['title'] . ' - ' . SITE_NAME,
    'description' => mb_substr(strip_tags($record['content']), 0, 120),
    'keywords' => '梦境记录,' . implode(',', $tags),
    'canonical' => $detailUrl,
    'type' => 'article',
    'robots' => $record['visibility'] === 'private' ? 'noindex, nofollow' : 'index, follow',
];
$active_nav = 'index';
require_once __DIR__ . '/header.php';

// 文章发布/修改时间
$published = date('c', strtotime($record['created_at']));
$modified = date('c', strtotime($record['updated_at']));
?>

<nav class="breadcrumb" aria-label="面包屑">
  <a href="/">首页</a> / <span><?php echo e($record['title']); ?></span>
</nav>

<article class="card">
  <h1 class="page-title"><?php echo e($record['title']); ?></h1>
  <div class="detail-meta">
    <span>做梦日期：<?php echo e($record['dream_date']); ?></span>
    <span class="badge badge-mood-<?php echo e($record['mood']); ?>"><?php echo e($record['mood']); ?></span>
    <?php if ($record['visibility'] === 'private'): ?>
      <span class="badge badge-private">仅自己可见</span>
    <?php else: ?>
      <span class="badge badge-public">公开</span>
    <?php endif; ?>
    <span>记录于：<?php echo e(fmt_datetime($record['created_at'])); ?></span>
    <span>浏览 <?php echo (int)$record['view_count']; ?></span>
  </div>

  <div class="detail-content"><?php echo e($record['content']); ?></div>

  <?php if ($tags): ?>
    <div class="detail-tags">
      <?php foreach ($tags as $tag): ?>
        <span class="tag"><?php echo e(trim($tag)); ?></span>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:24px;">
    <?php if ($isOwner): ?>
      <a class="btn btn-ghost" href="/edit.php?id=<?php echo (int)$record['id']; ?>">编辑这条梦境</a>
      <form method="post" action="/delete.php" onsubmit="return confirm('确定删除这条梦境记录吗？删除后不可恢复。');">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
        <input type="hidden" name="id" value="<?php echo (int)$record['id']; ?>">
        <button class="btn btn-danger-ghost" type="submit">删除</button>
      </form>
    <?php endif; ?>
    <a class="btn btn-ghost" href="/">返回列表</a>
  </div>
</article>

<?php if ($isPublicDream): ?>
<section class="card comments-card" id="comments">
  <h2 class="card-title">评论（<?php echo (int)$commentCount; ?>）</h2>

  <?php if (!empty($_SESSION['form_error'])): ?>
    <div class="form-error"><?php echo e($_SESSION['form_error']); unset($_SESSION['form_error']); ?></div>
  <?php endif; ?>
  <?php if (!empty($_SESSION['flash'])): ?>
    <div class="form-success"><?php echo e($_SESSION['flash']); unset($_SESSION['flash']); ?></div>
  <?php endif; ?>

  <?php
  // 评论者显示名：优先昵称，其次登录名，兜底“匿名”
  $commentName = function ($u) {
    $nick = $u['nickname'] ?? '';
    $name = $u['username'] ?? '';
    return $nick !== '' && $nick !== null ? $nick : ($name !== '' ? $name : '匿名');
  };
  ?>

  <?php if (is_logged_in()): ?>
  <form class="comment-form" method="post" action="/comment.php">
    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
    <input type="hidden" name="dream_id" value="<?php echo (int)$record['id']; ?>">
    <textarea name="content" rows="3" maxlength="500" placeholder="写下你的评论…" required></textarea>
    <div class="comment-form-bar">
      <span class="hint">以「<?php echo e($commentName(current_user())); ?>」身份发表</span>
      <button class="btn btn-primary" type="submit">发表评论</button>
    </div>
  </form>
  <?php else: ?>
  <div class="login-tip">
    登录后即可参与评论，<a href="/login.php?next=<?php echo urlencode('/detail.php?id=' . $id); ?>">立即登录</a>
  </div>
  <?php endif; ?>

  <?php if ($commentTop): ?>
  <div class="comment-list">
    <?php foreach ($commentTop as $top): ?>
    <div class="comment-item" id="comment-<?php echo (int)$top['id']; ?>">
      <div class="comment-head">
        <span class="comment-avatar"><?php echo e(mb_substr($commentName($top), 0, 1)); ?></span>
        <span class="comment-author"><?php echo e($commentName($top)); ?></span>
        <span class="comment-time"><?php echo e(fmt_datetime($top['created_at'])); ?></span>
      </div>
      <div class="comment-body"><?php echo e($top['content']); ?></div>
      <div class="comment-actions">
        <?php if (is_logged_in()): ?>
        <button type="button" class="btn btn-ghost btn-sm" onclick="toggleReply(<?php echo (int)$top['id']; ?>)">回复</button>
        <?php endif; ?>
        <?php if ($canModerateComment($top)): ?>
        <form method="post" action="/comment_delete.php" onsubmit="return confirm('确定删除这条评论及其全部回复吗？');">
          <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
          <input type="hidden" name="id" value="<?php echo (int)$top['id']; ?>">
          <button class="btn btn-danger-ghost btn-sm" type="submit">删除</button>
        </form>
        <?php endif; ?>
      </div>

      <?php $replies = $commentReplies[(int)$top['id']] ?? []; ?>
      <?php if ($replies): ?>
      <div class="reply-list">
        <?php foreach ($replies as $rep): ?>
        <div class="reply-item" id="comment-<?php echo (int)$rep['id']; ?>">
          <div class="comment-head">
            <span class="comment-avatar sm"><?php echo e(mb_substr($commentName($rep), 0, 1)); ?></span>
            <span class="comment-author"><?php echo e($commentName($rep)); ?></span>
            <span class="comment-time"><?php echo e(fmt_datetime($rep['created_at'])); ?></span>
          </div>
          <div class="comment-body">回复 @<?php echo e($commentName($top)); ?>：<?php echo e($rep['content']); ?></div>
          <div class="comment-actions">
            <?php if ($canModerateComment($rep)): ?>
            <form method="post" action="/comment_delete.php" onsubmit="return confirm('确定删除这条回复吗？');">
              <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
              <input type="hidden" name="id" value="<?php echo (int)$rep['id']; ?>">
              <button class="btn btn-danger-ghost btn-sm" type="submit">删除</button>
            </form>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <?php if (is_logged_in()): ?>
      <form class="reply-form" id="reply-form-<?php echo (int)$top['id']; ?>" method="post" action="/comment.php">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
        <input type="hidden" name="dream_id" value="<?php echo (int)$record['id']; ?>">
        <input type="hidden" name="parent_id" value="<?php echo (int)$top['id']; ?>">
        <textarea name="content" rows="2" maxlength="500" placeholder="回复 @<?php echo e($commentName($top)); ?>…" required></textarea>
        <div class="comment-form-bar">
          <button class="btn btn-primary btn-sm" type="submit">回复</button>
          <button type="button" class="btn btn-ghost btn-sm" onclick="toggleReply(<?php echo (int)$top['id']; ?>)">取消</button>
        </div>
      </form>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div class="empty-inline">还没有评论，来抢沙发。</div>
  <?php endif; ?>
</section>

<script>
function toggleReply(id) {
  var f = document.getElementById('reply-form-' + id);
  if (f) { f.classList.toggle('show'); }
}
</script>
<?php endif; ?>

<?php if ($related): ?>
  <div class="card" style="margin-top:20px;">
    <h2 class="card-title">相关梦境</h2>
    <div class="dream-list">
      <?php foreach ($related as $r): ?>
        <div class="dream-item" style="padding:14px 18px;">
          <a class="dream-title" href="/detail.php?id=<?php echo (int)$r['id']; ?>"><?php echo e($r['title']); ?></a>
          <div class="dream-meta">
            <span><?php echo e($r['dream_date']); ?></span>
            <span class="badge badge-mood-<?php echo e($r['mood']); ?>"><?php echo e($r['mood']); ?></span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<?php
// 文章结构化数据
render_jsonld([
    '@context' => 'https://schema.org',
    '@type' => 'BlogPosting',
    'headline' => $record['title'],
    'description' => mb_substr(strip_tags($record['content']), 0, 120),
    'datePublished' => $published,
    'dateModified' => $modified,
    'inLanguage' => 'zh-CN',
    'mainEntityOfPage' => $detailUrl,
    'articleBody' => $record['content'],
]);
render_jsonld([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => '首页', 'item' => SITE_URL . '/'],
        ['@type' => 'ListItem', 'position' => 2, 'name' => $record['title'], 'item' => $detailUrl],
    ],
]);
require_once __DIR__ . '/footer.php';
