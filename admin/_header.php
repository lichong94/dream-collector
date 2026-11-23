<?php
/**
 * 管理后台公共页头：要求管理员登录，输出后台框架与导航。
 * 业务页先设置 $page_title 与 $active_admin，再引入本文件。
 */
require_once __DIR__ . '/../init.php';
require_admin();

$page_title = $page_title ?? '管理后台';
$active_admin = $active_admin ?? 'index';
$me = current_user();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title><?php echo e($page_title); ?> - 后台</title>
  <link rel="stylesheet" href="/assets/style.css?v=20261120e">
</head>
<body>
<header class="admin-topbar">
  <div class="admin-topbar-inner">
    <div class="admin-brand"><?php echo e(SITE_NAME); ?> · 管理后台</div>
    <nav class="admin-nav">
      <a href="/admin/" class="<?php echo $active_admin === 'index' ? 'active' : ''; ?>">仪表盘</a>
      <a href="/admin/dreams.php" class="<?php echo $active_admin === 'dreams' ? 'active' : ''; ?>">梦境管理</a>
      <a href="/admin/comments.php" class="<?php echo $active_admin === 'comments' ? 'active' : ''; ?>">评论管理</a>
      <a href="/admin/users.php" class="<?php echo $active_admin === 'users' ? 'active' : ''; ?>">用户管理</a>
      <a href="/">返回前台</a>
      <form method="post" action="/logout.php" class="nav-logout-form">
        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
        <button type="submit" class="nav-logout-btn">退出</button>
      </form>
    </nav>
  </div>
</header>
<main class="admin-main">
  <div class="container">
