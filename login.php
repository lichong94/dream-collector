<?php
/**
 * 用户登录：校验登录名与密码，成功后写入登录态并按站内地址回跳。
 */
require_once __DIR__ . '/init.php';

if (is_logged_in()) {
    redirect(safe_next_url($_GET['next'] ?? '/profile.php'));
}

$errors = [];
$oldUsername = '';
$next = safe_next_url($_GET['next'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $oldUsername = $username;
    $next = safe_next_url($_POST['next'] ?? '');

    if ($username === '' || $password === '') {
        $errors[] = '请填写登录名和密码。';
    } else {
        $user = db_one(
            "SELECT id, username, password_hash, role, status FROM users WHERE username = ?",
            [$username]
        );
        // 统一提示，避免通过报错区分账号是否存在
        if (!$user || $user['status'] !== 'active' || !password_verify($password, $user['password_hash'])) {
            $errors[] = '登录名或密码不正确，或账号已被禁用。';
        } else {
            db_query("UPDATE users SET last_login_at = NOW() WHERE id = ?", [$user['id']]);
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            redirect($next !== '' ? $next : '/profile.php');
        }
    }
}

$page_meta = [
    'title' => '登录 - ' . SITE_NAME,
    'description' => '登录梦境收集器，查看自己的梦境记录并管理公开或隐私设置。',
    'canonical' => SITE_URL . '/login.php',
    'type' => 'website',
    'robots' => 'noindex',
];
$active_nav = '';
require_once __DIR__ . '/header.php';
?>

<div class="auth-wrap">
  <div class="card auth-card">
    <h1 class="card-title">登录账号</h1>

    <?php if ($errors): ?>
      <div class="alert alert-error"><?php echo e(implode(' ', $errors)); ?></div>
    <?php endif; ?>

    <form method="post" action="/login.php" novalidate>
      <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
      <input type="hidden" name="next" value="<?php echo e($next); ?>">

      <div class="form-item">
        <label for="username">登录名</label>
        <input type="text" id="username" name="username" required maxlength="20"
               placeholder="注册时使用的登录名" value="<?php echo e($oldUsername); ?>">
      </div>

      <div class="form-item">
        <label for="password">密码</label>
        <input type="password" id="password" name="password" required maxlength="32"
               placeholder="输入密码">
      </div>

      <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <button class="btn btn-primary" type="submit">登录</button>
        <a class="btn btn-ghost" href="/register.php">注册新账号</a>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
