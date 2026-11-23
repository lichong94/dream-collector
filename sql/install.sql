-- =====================================================================
-- 梦境收集器 数据库安装脚本（MySQL 5.7 / 8.0，utf8mb4）
-- 部署时由 root 账号执行：建库、建专用账号、授权、建表
-- 注意：MySQL 5.7 的 CREATE USER 不支持 IF NOT EXISTS，如账号已存在请先手动删除
-- =====================================================================

-- 1. 创建数据库（如不存在）
CREATE DATABASE IF NOT EXISTS `dream_collector`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_general_ci;

-- 2. 创建专用账号（仅授予本库权限；密码请按实际部署修改）
--    若提示账号已存在，先执行：DROP USER 'dream'@'localhost';
CREATE USER 'dream'@'localhost' IDENTIFIED BY 'CHANGE_ME_STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON `dream_collector`.* TO 'dream'@'localhost';
FLUSH PRIVILEGES;

USE `dream_collector`;

-- 3. 用户表（注册账号、角色、状态）
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `username`      VARCHAR(32)  NOT NULL COMMENT '登录名（字母开头，字母数字下划线）',
  `password_hash` VARCHAR(255) NOT NULL COMMENT '密码哈希（password_hash）',
  `nickname`      VARCHAR(32)  NOT NULL COMMENT '展示昵称',
  `role`          ENUM('user','admin') NOT NULL DEFAULT 'user' COMMENT '角色：普通用户/管理员',
  `status`        ENUM('active','disabled') NOT NULL DEFAULT 'active' COMMENT '账号状态：正常/禁用',
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '注册时间',
  `last_login_at` DATETIME     NULL DEFAULT NULL COMMENT '最后登录时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户账号表';

-- 4. 梦境记录表
DROP TABLE IF EXISTS `dream_log`;
CREATE TABLE `dream_log` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `user_id`     INT UNSIGNED NULL DEFAULT NULL COMMENT '归属用户ID（作者）',
  `title`       VARCHAR(120) NOT NULL DEFAULT '' COMMENT '梦境标题',
  `content`     TEXT         NOT NULL COMMENT '梦境详细内容',
  `mood`        VARCHAR(20)  NOT NULL DEFAULT '平淡' COMMENT '醒来心情：开心/平淡/惊悚/迷茫/奇妙/悲伤',
  `tags`        VARCHAR(255) NOT NULL DEFAULT '' COMMENT '标签，逗号分隔',
  `visibility`  ENUM('public','private') NOT NULL DEFAULT 'public' COMMENT '可见性：公开/隐私',
  `dream_date`  DATE         NOT NULL COMMENT '做梦日期',
  `view_count`  INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '浏览次数',
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '录入时间',
  `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_visibility` (`visibility`),
  KEY `idx_dream_date` (`dream_date`),
  KEY `idx_mood` (`mood`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='梦境收集记录表';

-- 5. 评论表（顶级评论 + 两级回复）
DROP TABLE IF EXISTS `comments`;
CREATE TABLE `comments` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `dream_id`   INT UNSIGNED NOT NULL COMMENT '所属梦境ID',
  `user_id`    INT UNSIGNED NOT NULL COMMENT '评论者ID',
  `parent_id`  INT UNSIGNED NULL DEFAULT NULL COMMENT '回复的上级评论ID（顶级为 NULL）',
  `content`    VARCHAR(500) NOT NULL COMMENT '评论内容',
  `status`     ENUM('normal','hidden') NOT NULL DEFAULT 'normal' COMMENT '状态：正常/隐藏',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '评论时间',
  PRIMARY KEY (`id`),
  KEY `idx_dream_id` (`dream_id`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='梦境评论表';
