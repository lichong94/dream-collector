<?php
/**
 * 退出登录：POST 表单提交，销毁会话后返回首页。
 */
require_once __DIR__ . '/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/');
}
csrf_check();
logout_user();
$_SESSION['flash'] = '已退出登录。';
redirect('/');
