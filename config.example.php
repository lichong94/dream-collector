<?php
/**
 * 数据库与站点基础配置（示例文件）
 * 使用方法：复制本文件为 config.php，再按你的部署环境填写真实值。
 * config.php 已被 .gitignore 排除，不要提交到仓库。
 */

// ===== 数据库连接配置 =====
define('DB_HOST', 'localhost');
define('DB_NAME', 'dream_collector');
define('DB_USER', 'dream');
define('DB_PASS', '请填写你的数据库密码');
define('DB_CHARSET', 'utf8mb4');

// ===== 站点基础信息（SEO 与结构化数据使用）=====
define('SITE_NAME', '梦境收集器');
define('SITE_DOMAIN', 'dream.example.com');
define('SITE_URL', 'https://' . SITE_DOMAIN);
define('SITE_DESC', '梦境收集器是一个记录、整理与回顾梦境的在线工具，支持记录梦境标题、内容、心情与标签，按日期检索并回顾自己的梦境轨迹。');

// 报错开关：生产环境置 false
define('DEBUG', false);
