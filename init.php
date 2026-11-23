<?php
/**
 * 公共引导文件：数据库连接、安全函数、SEO 公共输出。
 * 所有业务页面在开头引入本文件。
 */
require_once __DIR__ . '/config.php';

// 错误显示控制
if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// ===== 数据库连接 =====
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_errno) {
    http_response_code(500);
    exit('数据库连接失败，请检查 config.php 中的连接配置。');
}
$db->set_charset(DB_CHARSET);

// 开启会话（用于 CSRF Token 与登录态）
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

/**
 * 文本转义输出，防止 XSS
 */
function e($str)
{
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

/**
 * 预处理查询封装
 * @param string $sql    SQL 语句，占位符用 ?
 * @param array  $params 绑定参数
 * @return mysqli_stmt|false
 */
function db_query($sql, array $params = [])
{
    global $db;
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        return false;
    }
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt;
}

/**
 * 取出多行关联数组
 */
function db_all($sql, array $params = [])
{
    $stmt = db_query($sql, $params);
    if (!$stmt) return [];
    $result = $stmt->get_result();
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $rows;
}

/**
 * 取出单行关联数组
 */
function db_one($sql, array $params = [])
{
    $rows = db_all($sql, $params);
    return $rows[0] ?? null;
}

/**
 * 页面跳转
 */
function redirect($url)
{
    header('Location: ' . $url);
    exit;
}

/**
 * 生成并返回 CSRF Token
 */
function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * 校验 CSRF Token（POST 表单必须调用）
 */
function csrf_check()
{
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        exit('表单校验失败，请返回重试。');
    }
}

/**
 * 允许的心情选项
 */
function mood_options()
{
    return ['开心', '平淡', '奇妙', '迷茫', '惊悚', '悲伤'];
}

/**
 * 格式化日期时间
 */
function fmt_datetime($dt)
{
    return $dt ? date('Y-m-d H:i', strtotime($dt)) : '';
}

/**
 * 生成页面 SEO 头部（meta、OG、Twitter Card、Canonical）
 * 传入 $meta 数组：title / description / keywords / canonical / type
 */
function render_seo_head(array $meta)
{
    $title = e($meta['title'] ?? SITE_NAME);
    $description = e($meta['description'] ?? SITE_DESC);
    $keywords = e($meta['keywords'] ?? '梦境记录,梦境收集,记梦,梦境日记,清醒梦');
    $canonical = e($meta['canonical'] ?? (SITE_URL . ($_SERVER['REQUEST_URI'] ?? '/')));
    $type = e($meta['type'] ?? 'website');
    $robots = e($meta['robots'] ?? 'index, follow');
    echo '<title>' . $title . '</title>' . "\n";
    echo '<meta name="description" content="' . $description . '">' . "\n";
    echo '<meta name="keywords" content="' . $keywords . '">' . "\n";
    echo '<meta name="robots" content="' . $robots . '">' . "\n";
    echo '<link rel="canonical" href="' . $canonical . '">' . "\n";
    echo '<meta property="og:type" content="' . $type . '">' . "\n";
    echo '<meta property="og:site_name" content="' . e(SITE_NAME) . '">' . "\n";
    echo '<meta property="og:title" content="' . $title . '">' . "\n";
    echo '<meta property="og:description" content="' . $description . '">' . "\n";
    echo '<meta property="og:url" content="' . $canonical . '">' . "\n";
    echo '<meta name="twitter:card" content="summary">' . "\n";
    echo '<meta name="twitter:title" content="' . $title . '">' . "\n";
    echo '<meta name="twitter:description" content="' . $description . '">' . "\n";
}

/**
 * 输出 JSON-LD 结构化数据
 */
function render_jsonld(array $data)
{
    echo '<script type="application/ld+json">'
        . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        . '</script>' . "\n";
}

/* =====================================================================
   账号与权限体系
   ===================================================================== */

/**
 * 当前登录用户（每行缓存）；未登录或已被禁用返回 null
 */
function current_user()
{
    static $user = null;
    static $loaded = false;
    if ($loaded) {
        return $user;
    }
    $loaded = true;
    $uid = (int)($_SESSION['user_id'] ?? 0);
    if ($uid <= 0) {
        $user = null;
        return null;
    }
    $row = db_one(
        "SELECT id, username, nickname, role, status, created_at, last_login_at
         FROM users WHERE id = ?",
        [$uid]
    );
    if (!$row || $row['status'] !== 'active') {
        $user = null;
        return null;
    }
    $user = $row;
    return $user;
}

/** 当前登录用户 ID，未登录返回 0 */
function current_user_id()
{
    $u = current_user();
    return $u ? (int)$u['id'] : 0;
}

/** 是否已登录 */
function is_logged_in()
{
    return current_user() !== null;
}

/** 当前用户是否为管理员 */
function is_admin()
{
    $u = current_user();
    return $u && $u['role'] === 'admin';
}

/** 要求登录，否则跳登录页并记录回跳地址 */
function require_login()
{
    if (!is_logged_in()) {
        $next = urlencode($_SERVER['REQUEST_URI'] ?? '/');
        redirect('/login.php?next=' . $next);
    }
}

/** 要求管理员身份，否则 403 */
function require_admin()
{
    if (!is_logged_in()) {
        $next = urlencode($_SERVER['REQUEST_URI'] ?? '/admin/');
        redirect('/login.php?next=' . $next);
    }
    if (!is_admin()) {
        http_response_code(403);
        exit('无权访问后台，请使用管理员账号登录。');
    }
}

/**
 * 判断当前用户是否可见某条梦境：
 * 公开内容所有人可见；隐私内容仅作者本人或管理员可见。
 */
function can_view_dream(array $record)
{
    $visibility = $record['visibility'] ?? 'public';
    if ($visibility === 'public') {
        return true;
    }
    $u = current_user();
    if (!$u) {
        return false;
    }
    if ((int)$u['id'] === (int)($record['user_id'] ?? 0) || $u['role'] === 'admin') {
        return true;
    }
    return false;
}

/** 退出登录：清空会话并销毁 */
function logout_user()
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

/**
 * 校验站内回跳地址，防止开放重定向；仅允许以 / 开头的相对路径
 */
function safe_next_url($next)
{
    if (is_string($next) && $next !== '' && $next[0] === '/' && strpos($next, '//') !== 0) {
        return $next;
    }
    return '/';
}
