<?php
/**
 * 个人中心：当前用户自己的全部梦境（含隐私），可查看、编辑、切换公开/隐私、删除。
 */
require_once __DIR__ . '/init.php';
require_login();

$me = current_user_id();

// 本人记录统计
$stats = db_one(
    "SELECT
        COUNT(*) AS total,
        SUM(visibility = 'public') AS public_count,
        SUM(visibility = 'private') AS private_count
     FROM dream_log WHERE user_id = ?",
    [$me]
);
$total = (int)($stats['total'] ?? 0);
$publicCount = (int)($stats['public_count'] ?? 0);
$privateCount = (int)($stats['private_count'] ?? 0);

// 本人记录列表
$records = db_all(
    "SELECT id, title, content, mood, tags, visibility, dream_date, view_count, created_at
     FROM dream_log WHERE user_id = ?
     ORDER BY dream_date DESC, id DESC",
    [$me]
);

$page_meta = [
    'title' => '我的梦境 - ' . SITE_NAME,
    'description' => '管理你记录的全部梦境，可设置公开分享或仅自己可见。',
    'canonical' => SITE_URL . '/profile.php',
    'type' => 'website',
    'robots' => 'noindex',
];
$active_nav = 'profile';
require_once __DIR__ . '/header.php';
?>

<div class="stats">
  <div class="stat">
    <div class="stat-num"><?php echo $total; ?></div>
    <div class="stat-label">我的记录</div>
  </div>
  <div class="stat">
    <div class="stat-num"><?php echo $publicCount; ?></div>
    <div class="stat-label">公开</div>
  </div>
  <div class="stat">
    <div class="stat-num"><?php echo $privateCount; ?></div>
    <div class="stat-label">隐私</div>
  </div>
</div>

<div class="card">
  <div class="dream-head" style="margin-bottom:16px;">
    <div>
      <h2 class="card-title" style="margin-bottom:4px;">你好，<?php echo e(current_user()['nickname']); ?></h2>
      <div class="dream-meta">隐私梦境只有你本人能看到；公开梦境会在网站首页展示给访客。</div>
    </div>
    <a class="btn btn-primary btn-sm" href="/edit.php">记录新梦境</a>
  </div>

  <?php if (!empty($_SESSION['flash'])): ?>
    <div class="alert alert-success"><?php echo e($_SESSION['flash']); ?></div>
    <?php unset($_SESSION['flash']); ?>
  <?php endif; ?>

  <?php if (empty($records)): ?>
    <div class="empty">
      <div class="empty-icon">🌙</div>
      <div>还没有记录，写下你的第一个梦吧。</div>
    </div>
  <?php else: ?>
    <div class="dream-list">
      <?php foreach ($records as $r): ?>
        <article class="dream-item">
          <div class="dream-head">
            <div>
              <a class="dream-title" href="/detail.php?id=<?php echo (int)$r['id']; ?>">
                <?php echo e($r['title']); ?>
              </a>
              <div class="dream-meta">
                <span>做梦日期：<?php echo e($r['dream_date']); ?></span>
                <span class="badge badge-mood-<?php echo e($r['mood']); ?>"><?php echo e($r['mood']); ?></span>
                <?php if ($r['visibility'] === 'private'): ?>
                  <span class="badge badge-private">仅自己可见</span>
                <?php else: ?>
                  <span class="badge badge-public">公开</span>
                <?php endif; ?>
                <span>浏览 <?php echo (int)$r['view_count']; ?></span>
              </div>
            </div>
            <div class="dream-actions">
              <a class="btn btn-ghost btn-sm" href="/detail.php?id=<?php echo (int)$r['id']; ?>">查看</a>
              <a class="btn btn-ghost btn-sm" href="/edit.php?id=<?php echo (int)$r['id']; ?>">编辑</a>
            </div>
          </div>
          <p class="dream-excerpt"><?php echo e(mb_substr($r['content'], 0, 120)); ?></p>
          <div class="dream-actions" style="margin-top:12px;">
            <form method="post" action="/toggle_visibility.php">
              <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
              <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
              <button class="btn btn-ghost btn-sm" type="submit">
                <?php echo $r['visibility'] === 'private' ? '改为公开' : '改为隐私'; ?>
              </button>
            </form>
            <form method="post" action="/delete.php" onsubmit="return confirm('确定删除这条梦境记录吗？删除后不可恢复。');">
              <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
              <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
              <button class="btn btn-danger-ghost btn-sm" type="submit">删除</button>
            </form>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
