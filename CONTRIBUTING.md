# 参与贡献

感谢你关注本项目。提交 Issue、Pull Request 前请先阅读本文件。

## 环境要求

| 组件 | 版本 |
| --- | --- |
| PHP | 7.4 及以上（推荐 8.0–8.2） |
| MySQL / MariaDB | 5.7 及以上 |
| Web 服务器 | Apache 2.4（启用 `mod_rewrite`）或 Nginx |

依赖的 PHP 扩展：`pdo_mysql`、`mbstring`、`json`（默认安装通常已包含）。

## 本地运行

1. 复制配置模板并填写数据库信息：
   ```
   cp config.example.php config.php
   ```
2. 创建数据库并导入结构：
   ```
   mysql -u root -p -e "CREATE DATABASE dream_collector DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   mysql -u root -p dream_collector < sql/install.sql
   ```
3. 将项目根目录指向 Web 站点根目录，浏览器访问 `http://localhost/`。
4. 首次进入 `/admin/setup.php` 创建管理员账号，随后登录 `/admin/` 管理。

`config.php` 已被 `.gitignore` 排除，**不要**把它提交到仓库；如需调整配置项，请修改 `config.example.php` 中的键名或补充注释。

## 代码约定

- 缩进 4 个空格，编码 UTF-8（无 BOM），换行 LF。
- 所有 SQL 使用 PDO 预处理，禁止字符串拼接外部输入。
- 所有输出到 HTML 的变量必须经 `e()` 转义；富文本仅允许白名单标签。
- 所有写操作（POST/GET 变更状态）必须校验 CSRF token。
- 新增页面须复用 `header.php` / `footer.php`，并保持 `init.php` 中的权限与数据库初始化逻辑一致。
- 后台页面须引入 `admin/_header.php` 并设置对应的 `active_admin` 标识，保证侧边导航高亮正确。
- 涉及表结构变更时，同步更新 `sql/install.sql` 并在 `sql/migrate.sql` 追加可重复执行的迁移语句。

## 提交信息

采用 `类型: 中文说明` 的格式，类型取值：

- `新增`：新功能
- `修复`：缺陷修复
- `优化`：性能、体验、可读性改进
- `文档`：README、注释等
- `重构`：不改变行为的结构调整
- `构建`：配置、部署脚本、依赖

示例：`修复: 登录页密码输入框宽度异常`

## Pull Request 流程

1. 从 `main` 拉出特性分支，命名如 `feature/export-dreams`、`fix/comment-cascade`。
2. 提交前自查：是否误提交 `config.php`、日志、备份文件；是否包含真实域名、IP、口令。
3. PR 描述中说明变更目的、影响页面、是否修改表结构。
4. 若涉及数据库结构或配置项，须在 PR 中给出迁移与升级步骤。
