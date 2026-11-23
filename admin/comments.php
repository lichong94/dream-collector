<?php
/**
 * 后台评论管理：查看全站评论与回复，可隐藏、恢复或删除；顶级评论删除时级联删除其回复。
 */
$active_admin = 'comments';
require_once __DIR__ . '/_header.php';

// ===== 后台动作处理（删除 / 隐藏 / 恢复） =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $comment = db_one(
        "SELECT id, dream_id, parent_id, status FROM comments WHERE id = ?",
        [$id]
    );

    if (!$comment) {
        $_SESSION['flash'] = '评论不存在或已被删除。';
        redirect('/admin/comments.php');
    }

    if ($action === 'delete') {
        if ($comment['parent_id'] === null) {
            // 顶级评论：级联删除其下全部回复
            db_query("DELETE FROM comments WHERE parent_id = ?", [$id]);
        }
        db_query("DELETE FROM comments WHERE id = ?", [$id]);
        $_SESSION['flash'] = '评论已删除。';
    } elseif ($action === 'hide') {
        db_query("UPDATE comments SET status = 'hidden' WHERE id = ?", [$id]);
        $_SESSION['flash'] = '评论已隐藏，前台不再展示。';
    } elseif ($action === 'show') {
        db_query("UPDATE comments SET status = 'normal' WHERE id = ?", [$id]);
        $_SESSION['flash'] = '评论已恢复展示。';
    }
    redirect('/admin/comments.php');
}

// ===== 筛选条件 =====
$kw = trim($_GET['kw'] ?? '');
$status = trim($_GET['status'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];
if ($kw !== '') {
    $where[] = '(c.content LIKE ? OR u.username LIKE ? OR u.nickname LIKE ? OR d.title LIKE ?)';
    $like = '%' . $kw . '%';
    array_push($params, $like, $like, $like, $like);
}
if (in_array($status, ['normal', 'hidden'], true)) {
    $where[] = 'c.status = ?';
    $params[] = $status;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$total = (int)db_one(
    "SELECT COUNT(*) AS c
     FROM comments c
     LEFT JOIN dream_log d ON c.dream_id = d.id
     LEFT JOIN users u ON c.user_id = u.id
     $whereSql",
    $params
)['c'];
$totalPages = max(1, (int)ceil($total / $perPage));

$records = db_all(
    "SELECT c.id, c.dream_id, c.user_id, c.parent_id, c.content, c.status, c.created_at,
            d.title, u.nickname, u.username
     FROM comments c
     LEFT JOIN dream_log d ON c.dream_id = d.id
     LEFT JOIN users u ON c.user_id = u.id
     $whereSql
     ORDER BY c.id DESC LIMIT ? OFFSET ?",
    array_merge($params, [$perPage, $offset])
);

function admin_query(array $overrides = [])
{
    $query = array_merge($_GET, $overrides);
    $query = array_filter($query, function ($v) { return $v !== ''; });
    return '/admin/comments.php' . ($query ? ('?' . http_build_query($query)) : '');
}
?>

<h1 class="page-title">评论管理</h1>
<p class="page-sub">查看与管理全站梦境评论及回复，可隐藏违规内容或直接删除。</p>

<?php if (!empty($_SESSION['flash'])): ?>
  <div class="alert alert-success"><?php echo e($_SESSION['flash']); ?></div>
  <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<div class="card">
  <form class="toolbar" method="get" action="/admin/comments.php">
    <input type="search" name="kw" placeholder="评论内容、评论者或梦境标题" value="<?php echo e($kw); ?>">
    <select name="status">
      <option value="">全部状态</option>
      <option value="normal" <?php echo $status === 'normal' ? 'selected' : ''; ?>>正常</option>
      <option value="hidden" <?php echo $status === 'hidden' ? 'selected' : ''; ?>>已隐藏</option>
    </select>
    <button class="btn btn-ghost" type="submit">筛选</button>
  </form>

  <?php if (empty($records)): ?>
    <div class="empty"><div>没有符合条件的评论。</div></div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="admin-table">
        <thead>
          <tr><th>ID</th><th>梦境</th><th>评论者</th><th>类型</th><th>内容</th><th>状态</th><th>时间</th><th>操作</th></tr>
        </thead>
        <tbody>
          <?php foreach ($records as $r): ?>
            <tr>
              <td><?php echo (int)$r['id']; ?></td>
              <td>
                <?php if ($r['title'] !== null): ?>
                  <a href="/detail.php?id=<?php echo (int)$r['dream_id']; ?>"><?php echo e($r['title']); ?></a>
                <?php else: ?>
                  <span class="muted">梦境已删除</span>
                <?php endif; ?>
              </td>
              <td><?php echo e($r['nickname'] ?? ($r['username'] ?? '匿名')); ?></td>
              <td><?php echo $r['parent_id'] !== null ? '回复' : '评论'; ?></td>
              <td class="comment-content-cell"><?php echo e($r['content']); ?></td>
              <td>
                <?php if ($r['status'] === 'hidden'): ?>
                  <span class="badge badge-private">已隐藏</span>
                <?php else: ?>
                  <span class="badge badge-public">正常</span>
                <?php endif; ?>
              </td>
              <td><?php echo e(fmt_datetime($r['created_at'])); ?></td>
              <td class="cell-actions">
                <?php if ($r['status'] === 'hidden'): ?>
                <form method="post" action="/admin/comments.php">
                  <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                  <input type="hidden" name="action" value="show">
                  <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                  <button class="btn btn-ghost btn-sm" type="submit">恢复</button>
                </form>
                <?php else: ?>
                <form method="post" action="/admin/comments.php">
                  <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                  <input type="hidden" name="action" value="hide">
                  <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                  <button class="btn btn-ghost btn-sm" type="submit">隐藏</button>
                </form>
                <?php endif; ?>
                <form method="post" action="/admin/comments.php" onsubmit="return confirm('确定删除这条评论吗？顶级评论将连同其回复一并删除。');">
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
