<?php
/**
 * 后台仪表盘：核心指标、最近梦境、最近注册用户。
 */
$active_admin = 'index';
require_once __DIR__ . '/_header.php';

// ===== 核心指标 =====
$userTotal = (int)db_one("SELECT COUNT(*) AS c FROM users")['c'];
$userActive = (int)db_one("SELECT COUNT(*) AS c FROM users WHERE status = 'active'")['c'];
$dreamTotal = (int)db_one("SELECT COUNT(*) AS c FROM dream_log")['c'];
$dreamPublic = (int)db_one("SELECT COUNT(*) AS c FROM dream_log WHERE visibility = 'public'")['c'];
$dreamPrivate = (int)db_one("SELECT COUNT(*) AS c FROM dream_log WHERE visibility = 'private'")['c'];
$weekDream = (int)db_one("SELECT COUNT(*) AS c FROM dream_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['c'];
$weekUser = (int)db_one("SELECT COUNT(*) AS c FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['c'];
$commentTotal = (int)db_one("SELECT COUNT(*) AS c FROM comments WHERE status = 'normal'")['c'];

// ===== 最近梦境 =====
$recentDreams = db_all(
    "SELECT d.id, d.title, d.visibility, d.mood, d.dream_date, d.view_count, d.created_at,
            u.nickname, u.username
     FROM dream_log d LEFT JOIN users u ON d.user_id = u.id
     ORDER BY d.id DESC LIMIT 10"
);

// ===== 最近注册用户 =====
$recentUsers = db_all(
    "SELECT id, username, nickname, role, status, created_at, last_login_at
     FROM users ORDER BY id DESC LIMIT 10"
);
?>

<h1 class="page-title">仪表盘</h1>
<p class="page-sub">站点账号与梦境内容概览</p>

<div class="stats">
  <div class="stat"><div class="stat-num"><?php echo $dreamTotal; ?></div><div class="stat-label">梦境总数</div></div>
  <div class="stat"><div class="stat-num"><?php echo $dreamPublic; ?></div><div class="stat-label">公开梦境</div></div>
  <div class="stat"><div class="stat-num"><?php echo $dreamPrivate; ?></div><div class="stat-label">隐私梦境</div></div>
</div>
<div class="stats">
  <div class="stat"><div class="stat-num"><?php echo $userTotal; ?></div><div class="stat-label">注册用户</div></div>
  <div class="stat"><div class="stat-num"><?php echo $userActive; ?></div><div class="stat-label">启用账号</div></div>
  <div class="stat"><div class="stat-num"><?php echo $weekDream; ?></div><div class="stat-label">近 7 天梦境</div></div>
  <div class="stat"><div class="stat-num"><?php echo $commentTotal; ?></div><div class="stat-label">评论总数</div></div>
</div>

<div class="card">
  <h2 class="card-title">最近梦境</h2>
  <?php if (empty($recentDreams)): ?>
    <div class="empty"><div>暂无梦境记录。</div></div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="admin-table">
        <thead>
          <tr><th>ID</th><th>标题</th><th>作者</th><th>可见性</th><th>心情</th><th>做梦日期</th><th>浏览</th><th>录入时间</th></tr>
        </thead>
        <tbody>
          <?php foreach ($recentDreams as $r): ?>
            <tr>
              <td><?php echo (int)$r['id']; ?></td>
              <td><a href="/detail.php?id=<?php echo (int)$r['id']; ?>"><?php echo e($r['title']); ?></a></td>
              <td><?php echo e($r['nickname'] ?? ($r['username'] ?? '匿名')); ?></td>
              <td><?php echo $r['visibility'] === 'private' ? '<span class="badge badge-private">隐私</span>' : '<span class="badge badge-public">公开</span>'; ?></td>
              <td><span class="badge badge-mood-<?php echo e($r['mood']); ?>"><?php echo e($r['mood']); ?></span></td>
              <td><?php echo e($r['dream_date']); ?></td>
              <td><?php echo (int)$r['view_count']; ?></td>
              <td><?php echo e(fmt_datetime($r['created_at'])); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<div class="card">
  <h2 class="card-title">最近注册用户</h2>
  <?php if (empty($recentUsers)): ?>
    <div class="empty"><div>暂无注册用户。</div></div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="admin-table">
        <thead>
          <tr><th>ID</th><th>昵称</th><th>登录名</th><th>角色</th><th>状态</th><th>注册时间</th><th>最后登录</th></tr>
        </thead>
        <tbody>
          <?php foreach ($recentUsers as $u): ?>
            <tr>
              <td><?php echo (int)$u['id']; ?></td>
              <td><?php echo e($u['nickname']); ?></td>
              <td><?php echo e($u['username']); ?></td>
              <td><?php echo $u['role'] === 'admin' ? '管理员' : '用户'; ?></td>
              <td><?php echo $u['status'] === 'active' ? '启用' : '禁用'; ?></td>
              <td><?php echo e(fmt_datetime($u['created_at'])); ?></td>
              <td><?php echo e($u['last_login_at'] ? fmt_datetime($u['last_login_at']) : '—'); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/_footer.php'; ?>
