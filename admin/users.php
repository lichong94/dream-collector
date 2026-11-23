<?php
/**
 * 后台用户管理：查看账号、调整状态与角色；保护当前账号与最后一个管理员。
 */
$active_admin = 'users';
require_once __DIR__ . '/_header.php';

$me = current_user();

// ===== 后台动作处理 =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $uid = (int)($_POST['uid'] ?? 0);
    $target = db_one("SELECT id, username, nickname, role, status FROM users WHERE id = ?", [$uid]);

    if (!$target) {
        $_SESSION['flash'] = '用户不存在。';
        redirect('/admin/users.php');
    }
    // 不允许修改自己的账号状态与角色
    if ((int)$target['id'] === (int)$me['id']) {
        $_SESSION['flash'] = '不能修改当前登录的账号。';
        redirect('/admin/users.php');
    }

    $adminCount = (int)db_one("SELECT COUNT(*) AS c FROM users WHERE role = 'admin' AND status = 'active'")['c'];

    if ($action === 'toggle_status') {
        $nextStatus = $target['status'] === 'active' ? 'disabled' : 'active';
        // 最后一个启用的管理员不可禁用
        if ($target['role'] === 'admin' && $nextStatus === 'disabled' && $adminCount <= 1) {
            $_SESSION['flash'] = '至少需要保留一个启用的管理员。';
            redirect('/admin/users.php');
        }
        db_query("UPDATE users SET status = ? WHERE id = ?", [$nextStatus, $uid]);
        $_SESSION['flash'] = $nextStatus === 'active' ? '账号已启用。' : '账号已禁用。';
    } elseif ($action === 'toggle_role') {
        $nextRole = $target['role'] === 'admin' ? 'user' : 'admin';
        // 最后一个启用的管理员不可降级
        if ($target['role'] === 'admin' && $nextRole === 'user' && $adminCount <= 1) {
            $_SESSION['flash'] = '至少需要保留一个管理员。';
            redirect('/admin/users.php');
        }
        db_query("UPDATE users SET role = ? WHERE id = ?", [$nextRole, $uid]);
        $_SESSION['flash'] = $nextRole === 'admin' ? '已设为管理员。' : '已降为普通用户。';
    }
    redirect('/admin/users.php');
}

// ===== 筛选 =====
$kw = trim($_GET['kw'] ?? '');
$role = trim($_GET['role'] ?? '');
$status = trim($_GET['status'] ?? '');

$where = [];
$params = [];
if ($kw !== '') {
    $where[] = '(username LIKE ? OR nickname LIKE ?)';
    $like = '%' . $kw . '%';
    array_push($params, $like, $like);
}
if (in_array($role, ['user', 'admin'], true)) {
    $where[] = 'role = ?';
    $params[] = $role;
}
if (in_array($status, ['active', 'disabled'], true)) {
    $where[] = 'status = ?';
    $params[] = $status;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$users = db_all(
    "SELECT u.id, u.username, u.nickname, u.role, u.status, u.created_at, u.last_login_at,
            (SELECT COUNT(*) FROM dream_log d WHERE d.user_id = u.id) AS dream_count
     FROM users u $whereSql
     ORDER BY u.id DESC",
    $params
);

function user_query(array $overrides = [])
{
    $query = array_merge($_GET, $overrides);
    $query = array_filter($query, function ($v) { return $v !== ''; });
    return '/admin/users.php' . ($query ? ('?' . http_build_query($query)) : '');
}
?>

<h1 class="page-title">用户管理</h1>
<p class="page-sub">管理注册账号的状态与角色。</p>

<?php if (!empty($_SESSION['flash'])): ?>
  <div class="alert alert-success"><?php echo e($_SESSION['flash']); ?></div>
  <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<div class="card">
  <form class="toolbar" method="get" action="/admin/users.php">
    <input type="search" name="kw" placeholder="昵称或登录名" value="<?php echo e($kw); ?>">
    <select name="role">
      <option value="">全部角色</option>
      <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>管理员</option>
      <option value="user" <?php echo $role === 'user' ? 'selected' : ''; ?>>用户</option>
    </select>
    <select name="status">
      <option value="">全部状态</option>
      <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>启用</option>
      <option value="disabled" <?php echo $status === 'disabled' ? 'selected' : ''; ?>>禁用</option>
    </select>
    <button class="btn btn-ghost" type="submit">筛选</button>
  </form>

  <?php if (empty($users)): ?>
    <div class="empty"><div>没有符合条件的用户。</div></div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="admin-table">
        <thead>
          <tr><th>ID</th><th>昵称</th><th>登录名</th><th>角色</th><th>状态</th><th>梦境数</th><th>注册时间</th><th>最后登录</th><th>操作</th></tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
            <tr>
              <td><?php echo (int)$u['id']; ?></td>
              <td><?php echo e($u['nickname']); ?></td>
              <td><?php echo e($u['username']); ?></td>
              <td><?php echo $u['role'] === 'admin' ? '管理员' : '用户'; ?></td>
              <td><?php echo $u['status'] === 'active' ? '启用' : '禁用'; ?></td>
              <td><?php echo (int)$u['dream_count']; ?></td>
              <td><?php echo e(fmt_datetime($u['created_at'])); ?></td>
              <td><?php echo e($u['last_login_at'] ? fmt_datetime($u['last_login_at']) : '—'); ?></td>
              <td class="cell-actions">
                <?php if ((int)$u['id'] !== (int)$me['id']): ?>
                  <form method="post" action="/admin/users.php">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="uid" value="<?php echo (int)$u['id']; ?>">
                    <button class="btn btn-ghost btn-sm" type="submit"><?php echo $u['status'] === 'active' ? '禁用' : '启用'; ?></button>
                  </form>
                  <form method="post" action="/admin/users.php">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="action" value="toggle_role">
                    <input type="hidden" name="uid" value="<?php echo (int)$u['id']; ?>">
                    <button class="btn btn-ghost btn-sm" type="submit"><?php echo $u['role'] === 'admin' ? '降为用户' : '设为管理员'; ?></button>
                  </form>
                <?php else: ?>
                  <span class="dream-meta">当前账号</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/_footer.php'; ?>
