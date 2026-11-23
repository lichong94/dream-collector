-- =====================================================================
-- 线上增量迁移脚本：在已有 dream_collector 库上补充账号体系与可见性字段
-- 适用：已存在旧版 dream_log 表、本次升级注册登录/公开隐私/管理后台
-- 说明：MySQL 5.7 的 ALTER TABLE 不支持 IF NOT EXISTS，本脚本仅需执行一次，
--       若列/表已存在会报重复错误，忽略即可。
-- =====================================================================

USE `dream_collector`;

-- 1. 用户表（如不存在则创建）
CREATE TABLE IF NOT EXISTS `users` (
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

-- 2. 梦境表补充归属用户与可见性字段（已存在则跳过报错）
ALTER TABLE `dream_log`
  ADD COLUMN `user_id`    INT UNSIGNED NULL DEFAULT NULL COMMENT '归属用户ID（作者）' AFTER `id`,
  ADD COLUMN `visibility` ENUM('public','private') NOT NULL DEFAULT 'public' COMMENT '可见性：公开/隐私' AFTER `tags`;

-- 3. 补充索引
ALTER TABLE `dream_log` ADD KEY `idx_user_id` (`user_id`);
ALTER TABLE `dream_log` ADD KEY `idx_visibility` (`visibility`);

-- 4. 评论表（顶级评论 + 两级回复）
CREATE TABLE IF NOT EXISTS `comments` (
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
