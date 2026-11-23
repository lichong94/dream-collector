<?php
/**
 * 公共页头：在页面设置 $page_meta 与 $active_nav 后引入。
 * $page_meta = ['title'=>..., 'description'=>..., 'keywords'=>..., 'canonical'=>..., 'type'=>...]
 */
$active_nav = $active_nav ?? 'index';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#3559c7">
  <?php render_seo_head($page_meta ?? []); ?>
  <link rel="stylesheet" href="/assets/style.css?v=20261120e">
</head>
<body>
<header class="topbar">
  <div class="topbar-inner">
    <a class="brand" href="/">
      <span class="brand-mark">梦</span>
      <span><?php echo e(SITE_NAME); ?></span>
    </a>
    <nav class="nav">
      <a href="/" class="<?php echo $active_nav === 'index' ? 'active' : ''; ?>"><span class="nav-full">首页</span><span class="nav-short">首页</span></a>
      <a href="/edit.php" class="<?php echo $active_nav === 'edit' ? 'active' : ''; ?>"><span class="nav-full">记录梦境</span><span class="nav-short">记梦</span></a>
      <a href="/faq.php" class="<?php echo $active_nav === 'faq' ? 'active' : ''; ?>"><span class="nav-full">常见问题</span><span class="nav-short">问答</span></a>
      <a href="/about.php" class="<?php echo $active_nav === 'about' ? 'active' : ''; ?>"><span class="nav-full">关于</span><span class="nav-short">关于</span></a>
      <?php if (is_logged_in()): ?>
        <a href="/profile.php" class="<?php echo $active_nav === 'profile' ? 'active' : ''; ?>"><span class="nav-full">我的梦境</span><span class="nav-short">我的</span></a>
        <?php if (is_admin()): ?>
          <a href="/admin/" class="<?php echo $active_nav === 'admin' ? 'active' : ''; ?>"><span class="nav-full">管理后台</span><span class="nav-short">后台</span></a>
        <?php endif; ?>
        <form method="post" action="/logout.php" class="nav-logout-form">
          <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
          <button type="submit" class="nav-logout-btn">退出</button>
        </form>
      <?php else: ?>
        <a href="/login.php" class="<?php echo $active_nav === 'login' ? 'active' : ''; ?>"><span class="nav-full">登录</span><span class="nav-short">登录</span></a>
        <a href="/register.php" class="<?php echo $active_nav === 'register' ? 'active' : ''; ?>"><span class="nav-full">注册</span><span class="nav-short">注册</span></a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main class="page">
  <div class="container">
