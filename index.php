<?php
require_once __DIR__ . '/init.php';

// ===== 筛选条件 =====
$kw = trim($_GET['kw'] ?? '');
$mood = trim($_GET['mood'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];
// 首页仅展示公开梦境，隐私记录仅作者本人在个人中心查看
$where[] = "visibility = 'public'";
if ($kw !== '') {
    $where[] = '(title LIKE ? OR content LIKE ? OR tags LIKE ?)';
    $like = '%' . $kw . '%';
    array_push($params, $like, $like, $like);
}
if (in_array($mood, mood_options(), true)) {
    $where[] = 'mood = ?';
    $params[] = $mood;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// ===== 统计数据（均按公开口径） =====
$total = (int)db_one("SELECT COUNT(*) AS c FROM dream_log $whereSql", $params)['c'];
$weekCount = (int)db_one(
    "SELECT COUNT(*) AS c FROM dream_log WHERE visibility = 'public' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
)['c'];
$moodDist = db_all("SELECT mood, COUNT(*) AS c FROM dream_log WHERE visibility = 'public' GROUP BY mood");

// ===== 分页查询 =====
$records = db_all(
    "SELECT id, title, content, mood, tags, dream_date, view_count, created_at
     FROM dream_log $whereSql
     ORDER BY dream_date DESC, id DESC
     LIMIT ? OFFSET ?",
    array_merge($params, [$perPage, $offset])
);
$totalPages = max(1, (int)ceil($total / $perPage));

// ===== SEO 元数据 =====
$page_meta = [
    'title' => SITE_NAME . ' - 在线记录与回顾你的梦境',
    'description' => SITE_DESC,
    'keywords' => '梦境记录,梦境收集器,记梦日记,梦境回顾,心情记录,在线记梦工具',
    'canonical' => SITE_URL . '/',
];
$active_nav = 'index';
require_once __DIR__ . '/header.php';

// 组装筛选后的分页链接
function build_query(array $overrides = [])
{
    $query = array_merge($_GET, $overrides);
    $query = array_filter($query, function ($v) { return $v !== ''; });
    return $query ? ('?' . http_build_query($query)) : '/';
}
?>

<?php if (!empty($_SESSION['flash'])): ?>
  <div class="alert alert-success"><?php echo e($_SESSION['flash']); ?></div>
  <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<div class="stats">
  <div class="stat">
    <div class="stat-num"><?php echo $total; ?></div>
    <div class="stat-label">累计梦境记录</div>
  </div>
  <div class="stat">
    <div class="stat-num"><?php echo $weekCount; ?></div>
    <div class="stat-label">近 7 天新增</div>
  </div>
  <div class="stat">
    <div class="stat-num"><?php echo count($moodDist); ?></div>
    <div class="stat-label">覆盖心情类型</div>
  </div>
</div>

<div class="layout">
  <!-- 左侧：快捷记录 + 说明 -->
  <aside>
    <div class="card">
      <h2 class="card-title">记录新梦境</h2>
      <p style="color:var(--text-2);margin-bottom:16px;">
        醒来后趁记忆清晰，用几句话记下梦境标题、情节与当时的心情，方便日后回顾。
      </p>
      <?php $recordEntry = is_logged_in() ? '/edit.php' : '/login.php?next=' . urlencode('/edit.php'); ?>
      <a class="btn btn-primary btn-block" href="<?php echo e($recordEntry); ?>">开始记录梦境</a>
    </div>
    <div class="card">
      <h2 class="card-title">怎么记梦更有效</h2>
      <div class="prose" style="color:var(--text-2);font-size:13px;">
        <ul>
          <li>醒来后先躺着回忆，再动笔或打字；</li>
          <li>先写情节主线，再补充人物、场景与情绪；</li>
          <li>给梦境打一个心情标签，便于按情绪回顾；</li>
          <li>定期翻看旧记录，观察梦境重复出现的主题。</li>
        </ul>
      </div>
    </div>
  </aside>

  <!-- 右侧：搜索筛选 + 列表 -->
  <section>
    <div class="card">
      <form class="toolbar" method="get" action="/">
        <input type="search" name="kw" placeholder="搜索标题、内容或标签" value="<?php echo e($kw); ?>">
        <select name="mood">
          <option value="">全部心情</option>
          <?php foreach (mood_options() as $m): ?>
            <option value="<?php echo e($m); ?>" <?php echo $mood === $m ? 'selected' : ''; ?>>
              <?php echo e($m); ?>
            </option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-ghost" type="submit">筛选</button>
      </form>

      <?php if (empty($records)): ?>
        <div class="empty">
          <div class="empty-icon">🌙</div>
          <div><?php echo $kw || $mood ? '没有符合条件的梦境记录。' : '还没有梦境记录，写下你的第一个梦吧。'; ?></div>
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
                    <?php if ($r['tags']): ?>
                      <?php foreach (array_filter(explode(',', $r['tags'])) as $tag): ?>
                        <span class="tag"><?php echo e(trim($tag)); ?></span>
                      <?php endforeach; ?>
                    <?php endif; ?>
                    <span>浏览 <?php echo (int)$r['view_count']; ?></span>
                  </div>
                </div>
                <div class="dream-actions">
                  <a class="btn btn-ghost btn-sm" href="/detail.php?id=<?php echo (int)$r['id']; ?>">查看</a>
                </div>
              </div>
              <p class="dream-excerpt"><?php echo e(mb_substr($r['content'], 0, 120)); ?></p>
            </article>
          <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
          <nav class="pagination" aria-label="分页">
            <?php if ($page > 1): ?>
              <a href="<?php echo e(build_query(['page' => $page - 1])); ?>">上一页</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
              <?php if ($i === $page): ?>
                <span class="current"><?php echo $i; ?></span>
              <?php else: ?>
                <a href="<?php echo e(build_query(['page' => $i])); ?>"><?php echo $i; ?></a>
              <?php endif; ?>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
              <a href="<?php echo e(build_query(['page' => $page + 1])); ?>">下一页</a>
            <?php endif; ?>
          </nav>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </section>
</div>

<?php
// 首页结构化数据：常见问题入口由 faq.php 提供 FAQPage；此处输出网站描述性 JSON-LD
render_jsonld([
    '@context' => 'https://schema.org',
    '@type' => 'WebApplication',
    'name' => SITE_NAME,
    'url' => SITE_URL . '/',
    'description' => SITE_DESC,
    'applicationCategory' => 'LifestyleApplication',
    'operatingSystem' => 'Web',
    'inLanguage' => 'zh-CN',
]);
require_once __DIR__ . '/footer.php';
