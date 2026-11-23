<?php
/**
 * 后台梦境管理：筛选查看全部梦境（含隐私），管理员可切换可见性、删除记录。
 */
$active_admin = 'dreams';
require_once __DIR__ . '/_header.php';

// ===== 后台动作处理（删除 / 切换可见性） =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $record = db_one("SELECT id, visibility FROM dream_log WHERE id = ?", [$id]);

    if (!$record) {
        $_SESSION['flash'] = '记录不存在或已被删除。';
        redirect('/admin/dreams.php');
    }

    if ($action === 'delete') {
        db_query("DELETE FROM dream_log WHERE id = ?", [$id]);
        $_SESSION['flash'] = '梦境记录已删除。';
    } elseif ($action === 'toggle') {
        $next = $record['visibility'] === 'private' ? 'public' : 'private';
        db_query("UPDATE dream_log SET visibility = ? WHERE id = ?", [$next, $id]);
        $_SESSION['flash'] = '可见性已更新。';
    }
    redirect('/admin/dreams.php');
}

// ===== 筛选条件 =====
$kw = trim($_GET['kw'] ?? '');
$mood = trim($_GET['mood'] ?? '');
$vis = trim($_GET['vis'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];
if ($kw !== '') {
    $where[] = '(d.title LIKE ? OR d.content LIKE ? OR d.tags LIKE ? OR u.username LIKE ? OR u.nickname LIKE ?)';
    $like = '%' . $kw . '%';
    array_push($params, $like, $like, $like, $like, $like);
}
if (in_array($mood, mood_options(), true)) {
    $where[] = 'd.mood = ?';
    $params[] = $mood;
}
if (in_array($vis, ['public', 'private'], true)) {
    $where[] = 'd.visibility = ?';
    $params[] = $vis;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$total = (int)db_one(
    "SELECT COUNT(*) AS c FROM dream_log d LEFT JOIN users u ON d.user_id = u.id $whereSql",
    $params
)['c'];
$totalPages = max(1, (int)ceil($total / $perPage));

$records = db_all(
    "SELECT d.id, d.title, d.visibility, d.mood, d.tags, d.dream_date, d.view_count, d.created_at,
            u.nickname, u.username
     FROM dream_log d LEFT JOIN users u ON d.user_id = u.id
     $whereSql
     ORDER BY d.id DESC LIMIT ? OFFSET ?",
    array_merge($params, [$perPage, $offset])
);

function admin_query(array $overrides = [])
{
    $query = array_merge($_GET, $overrides);
    $query = array_filter($query, function ($v) { return $v !== ''; });
    return '/admin/dreams.php' . ($query ? ('?' . http_build_query($query)) : '');
}
?>

<h1 class="page-title">梦境管理</h1>
<p class="page-sub">查看与管理全站梦境记录，隐私内容在此对管理员可见。</p>

<?php if (!empty($_SESSION['flash'])): ?>
  <div class="alert alert-success"><?php echo e($_SESSION['flash']); ?></div>
  <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<div class="card">
  <form class="toolbar" method="get" action="/admin/dreams.php">
    <input type="search" name="kw" placeholder="标题、内容、标签或作者" value="<?php echo e($kw); ?>">
    <select name="mood">
      <option value="">全部心情</option>
      <?php foreach (mood_options() as $m): ?>
        <option value="<?php echo e($m); ?>" <?php echo $mood === $m ? 'selected' : ''; ?>><?php echo e($m); ?></option>
      <?php endforeach; ?>
    </select>
    <select name="vis">
      <option value="">全部可见性</option>
      <option value="public" <?php echo $vis === 'public' ? 'selected' : ''; ?>>公开</option>
      <option value="private" <?php echo $vis === 'private' ? 'selected' : ''; ?>>隐私</option>
    </select>
    <button class="btn btn-ghost" type="submit">筛选</button>
  </form>

  <?php if (empty($records)): ?>
    <div class="empty"><div>没有符合条件的梦境记录。</div></div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="admin-table">
        <thead>
          <tr><th>ID</th><th>标题</th><th>作者</th><th>可见性</th><th>心情</th><th>做梦日期</th><th>浏览</th><th>录入时间</th><th>操作</th></tr>
        </thead>
        <tbody>
          <?php foreach ($records as $r): ?>
            <tr>
              <td><?php echo (int)$r['id']; ?></td>
              <td><a href="/detail.php?id=<?php echo (int)$r['id']; ?>"><?php echo e($r['title']); ?></a></td>
              <td><?php echo e($r['nickname'] ?? ($r['username'] ?? '匿名')); ?></td>
              <td><?php echo $r['visibility'] === 'private' ? '<span class="badge badge-private">隐私</span>' : '<span class="badge badge-public">公开</span>'; ?></td>
              <td><span class="badge badge-mood-<?php echo e($r['mood']); ?>"><?php echo e($r['mood']); ?></span></td>
              <td><?php echo e($r['dream_date']); ?></td>
              <td><?php echo (int)$r['view_count']; ?></td>
              <td><?php echo e(fmt_datetime($r['created_at'])); ?></td>
              <td class="cell-actions">
                <form method="post" action="/admin/dreams.php">
                  <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                  <button class="btn btn-ghost btn-sm" type="submit"><?php echo $r['visibility'] === 'private' ? '改公开' : '改隐私'; ?></button>
                </form>
                <form method="post" action="/admin/dreams.php" onsubmit="return confirm('确定删除这条梦境记录吗？删除后不可恢复。');">
                  <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                  <button class="btn btn-danger-ghost btn-sm" type="submit">删除</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if ($totalPages > 1): ?>
      <nav class="pagination" aria-label="分页">
        <?php if ($page > 1): ?>
          <a href="<?php echo e(admin_query(['page' => $page - 1])); ?>">上一页</a>
        <?php endif; ?>
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <?php if ($i === $page): ?>
            <span class="current"><?php echo $i; ?></span>
          <?php else: ?>
            <a href="<?php echo e(admin_query(['page' => $i])); ?>"><?php echo $i; ?></a>
          <?php endif; ?>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
          <a href="<?php echo e(admin_query(['page' => $page + 1])); ?>">下一页</a>
        <?php endif; ?>
      </nav>
    <?php endif; ?>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/_footer.php'; ?>
