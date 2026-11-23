<?php
/**
 * 初始化管理员：仅当系统中还没有任何管理员时可用，创建后自动进入后台。
 * 已有管理员后访问本页将直接跳转后台。
 */
require_once __DIR__ . '/../init.php';

$adminCount = (int)db_one("SELECT COUNT(*) AS c FROM users WHERE role = 'admin'")['c'];
if ($adminCount > 0) {
    redirect('/admin/');
}

$errors = [];
$old = ['username' => '', 'nickname' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = trim($_POST['username'] ?? '');
    $nickname = trim($_POST['nickname'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    $old = ['username' => $username, 'nickname' => $nickname];

    if (!preg_match('/^[A-Za-z][A-Za-z0-9_]{2,19}$/', $username)) {
        $errors[] = '登录名需 3-20 位，以字母开头，可含字母、数字、下划线。';
    }
    if ($nickname === '' || mb_strlen($nickname) > 20) {
        $errors[] = '昵称需填写，且不超过 20 个字符。';
    }
    if (mb_strlen($password) < 6 || mb_strlen($password) > 32) {
        $errors[] = '密码长度需 6-32 位。';
    }
    if ($password !== $passwordConfirm) {
        $errors[] = '两次输入的密码不一致。';
    }
    if (!$errors) {
        $exists = db_one("SELECT id FROM users WHERE username = ?", [$username]);
        if ($exists) $errors[] = '该登录名已被使用，请更换。';
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        db_query(
            "INSERT INTO users (username, password_hash, nickname, role, status) VALUES (?, ?, ?, 'admin', 'active')",
            [$username, $hash, $nickname]
        );
        $newId = $db->insert_id;
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$newId;
        $_SESSION['flash'] = '管理员账号已创建。';
        redirect('/admin/');
    }
}

$page_meta = [
    'title' => '初始化管理员 - ' . SITE_NAME,
    'robots' => 'noindex',
    'canonical' => SITE_URL . '/admin/setup.php',
];
require_once __DIR__ . '/../header.php';
?>

<div class="auth-wrap">
  <div class="card auth-card">
    <h1 class="card-title">初始化管理员账号</h1>
    <p class="page-sub">系统中还没有管理员，请创建首个管理员账号。</p>

    <?php if ($errors): ?>
      <div class="alert alert-error"><?php echo e(implode(' ', $errors)); ?></div>
    <?php endif; ?>

    <form method="post" action="/admin/setup.php" novalidate>
      <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

      <div class="form-item">
        <label for="username">管理员登录名</label>
        <input type="text" id="username" name="username" required maxlength="20"
               placeholder="字母开头，可含数字、下划线" value="<?php echo e($old['username']); ?>">
      </div>
      <div class="form-item">
        <label for="nickname">昵称</label>
        <input type="text" id="nickname" name="nickname" required maxlength="20"
               placeholder="后台展示名称" value="<?php echo e($old['nickname']); ?>">
      </div>
      <div class="form-item">
        <label for="password">密码</label>
        <input type="password" id="password" name="password" required minlength="6" maxlength="32" placeholder="6-32 位密码">
      </div>
      <div class="form-item">
        <label for="password_confirm">确认密码</label>
        <input type="password" id="password_confirm" name="password_confirm" required minlength="6" maxlength="32" placeholder="再次输入密码">
      </div>

      <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <button class="btn btn-primary" type="submit">创建并进入后台</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../footer.php'; ?>
