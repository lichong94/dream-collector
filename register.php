<?php
/**
 * 用户注册：填写登录名、昵称、密码，注册成功后自动登录。
 */
require_once __DIR__ . '/init.php';

// 已登录则直接进入个人中心
if (is_logged_in()) {
    redirect('/profile.php');
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

    // 校验登录名：3-20 位，字母开头，字母数字下划线
    if (!preg_match('/^[A-Za-z][A-Za-z0-9_]{2,19}$/', $username)) {
        $errors[] = '登录名需 3-20 位，以字母开头，可含字母、数字、下划线。';
    }
    // 校验昵称：2-20 个字符
    if ($nickname === '' || mb_strlen($nickname) > 20) {
        $errors[] = '昵称需填写，且不超过 20 个字符。';
    }
    // 校验密码
    if (mb_strlen($password) < 6 || mb_strlen($password) > 32) {
        $errors[] = '密码长度需 6-32 位。';
    }
    if ($password !== $passwordConfirm) {
        $errors[] = '两次输入的密码不一致。';
    }

    // 登录名查重
    if (!$errors) {
        $exists = db_one("SELECT id FROM users WHERE username = ?", [$username]);
        if ($exists) {
            $errors[] = '该登录名已被使用，请更换。';
        }
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = db_query(
            "INSERT INTO users (username, password_hash, nickname, role, status)
             VALUES (?, ?, ?, 'user', 'active')",
            [$username, $hash, $nickname]
        );
        $newId = $db->insert_id;

        // 注册成功后自动登录
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$newId;
        $_SESSION['flash'] = '注册成功，欢迎来到' . SITE_NAME . '。';
        redirect('/profile.php');
    }
}

$page_meta = [
    'title' => '注册账号 - ' . SITE_NAME,
    'description' => '注册梦境收集器账号，记录属于自己的梦境，并可选择公开分享或仅自己可见。',
    'canonical' => SITE_URL . '/register.php',
    'type' => 'website',
    'robots' => 'noindex',
];
$active_nav = '';
require_once __DIR__ . '/header.php';
?>

<div class="auth-wrap">
  <div class="card auth-card">
    <h1 class="card-title">注册账号</h1>

    <?php if ($errors): ?>
      <div class="alert alert-error"><?php echo e(implode(' ', $errors)); ?></div>
    <?php endif; ?>

    <form method="post" action="/register.php" novalidate>
      <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

      <div class="form-item">
        <label for="username">登录名</label>
        <input type="text" id="username" name="username" required maxlength="20"
               placeholder="字母开头，可含数字、下划线" value="<?php echo e($old['username']); ?>">
      </div>

      <div class="form-item">
        <label for="nickname">昵称</label>
        <input type="text" id="nickname" name="nickname" required maxlength="20"
               placeholder="展示在梦境记录旁的名字" value="<?php echo e($old['nickname']); ?>">
      </div>

      <div class="form-item">
        <label for="password">密码</label>
        <input type="password" id="password" name="password" required minlength="6" maxlength="32"
               placeholder="6-32 位密码">
      </div>

      <div class="form-item">
        <label for="password_confirm">确认密码</label>
        <input type="password" id="password_confirm" name="password_confirm" required minlength="6" maxlength="32"
               placeholder="再次输入密码">
      </div>

      <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <button class="btn btn-primary" type="submit">注册并登录</button>
        <a class="btn btn-ghost" href="/login.php">已有账号，去登录</a>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
