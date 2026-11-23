# 梦境收集器（PHP + MySQL 自适应站点）

## 一、项目信息

| 项目 | 值 |
| --- | --- |
| 运行环境 | Apache + PHP 7.4 + MySQL 5.7（宝塔面板亦可） |
| 站点目录示例 | `/var/www/dream` |
| 数据库名 | `dream_collector` |
| 数据库账号 | `dream`（仅授权本库） |
| 证书方案 | Let's Encrypt + certbot 自动续期 |

> 数据库与站点配置集中在 `config.php`。仓库只提供 `config.example.php`，部署时复制为 `config.php` 并填入自己的值，该文件已被 `.gitignore` 排除，不会提交到仓库。

## 二、功能清单

### 梦境记录与浏览
- 梦境记录：标题、做梦日期、心情（开心/平淡/奇妙/迷茫/惊悚/悲伤）、标签、详细内容
- 列表浏览：按做梦日期倒序，分页展示，浏览次数统计
- 检索：关键词搜索（标题/正文/标签）、按心情筛选
- 详情查看：完整内容、标签、相关梦境推荐、面包屑导航
- 编辑与删除：带 CSRF 校验，删除需页面确认

### 账号体系
- 注册：登录名（字母开头，字母数字下划线）、昵称、密码（`password_hash` 哈希存储）
- 登录/退出：会话固定防护（登录后重新生成会话 ID），HttpOnly + SameSite Cookie
- 个人中心：查看本人全部记录，统计公开/隐私数量，可切换可见性、编辑、删除
- 权限隔离：编辑、删除仅作者本人；隐私记录仅作者本人或管理员可见

### 互动评论
- 评论范围：仅公开梦境开放评论；隐私梦境详情不显示评论区，提交接口同样拒绝
- 两级楼中楼：登录用户可发表顶级评论，可回复顶级评论；回复展示被回复者昵称，不允许回复回复
- 内容校验：1–500 字，输出经 `e()` 转义防 XSS，CSRF 校验
- 删除权限：评论本人、梦境作者、管理员可删除；删除顶级评论时级联删除其下全部回复，删除单条回复仅删自身
- 后台管理：管理员可查看全站评论与回复，按关键词/状态筛选，可隐藏、恢复或删除

### 公开 / 隐私
- 公开梦境：进入首页公开列表、搜索、相关推荐与站点地图，可被搜索引擎收录
- 隐私梦境：不进入公开列表、站点地图与搜索引擎；访客访问详情按 404 处理
- 发布与编辑时可选择公开/隐私，个人中心可随时切换

### 管理后台（/admin/，仅管理员）
- 仪表盘：梦境总数、公开/隐私数量、注册用户、近 7 天新增、评论总数，最近梦境与最近注册用户
- 梦境管理：按关键词/心情/可见性/作者筛选，管理员可切换可见性、删除记录
- 评论管理：按关键词/状态筛选全站评论与回复，可隐藏、恢复、删除；顶级评论删除级联清除回复
- 用户管理：查看账号状态与角色，可禁用/启用、提升/降权；保护当前账号与最后一个管理员
- 初始化：无管理员时通过 `/admin/setup.php` 创建首个管理员，已有管理员后该页自动跳转后台

### 界面
- 自适应布局：宽屏双栏、平板/手机单栏堆叠，触控热区与聚焦环齐备

## 三、目录结构

```
dream-collector/
├── config.example.php    # 配置模板（复制为 config.php 后填写真实值）
├── init.php              # 公共引导：连接、安全函数、账号鉴权、SEO 输出
├── header.php / footer.php
├── index.php             # 首页：统计、搜索筛选、公开梦境列表
├── edit.php              # 新增/编辑表单（登录后可用，含可见性选项）
├── save.php              # 保存接口（新增/更新）
├── detail.php            # 梦境详情 + 评论区 + 相关推荐（隐私鉴权）
├── delete.php            # 删除接口（作者本人/管理员）
├── comment.php           # 评论/回复提交接口（登录、CSRF、两级校验）
├── comment_delete.php    # 评论删除接口（本人/作者/管理员，顶级级联删回复）
├── login.php             # 登录
├── register.php          # 注册
├── logout.php            # 退出登录
├── profile.php           # 个人中心：本人记录与可见性管理
├── toggle_visibility.php # 本人切换公开/隐私
├── faq.php               # 常见问题（GEO 问答 + FAQPage 结构化数据）
├── about.php             # 关于本站（字段说明、使用步骤）
├── sitemap.xml.php       # 动态站点地图（/sitemap.xml，仅公开记录）
├── robots.txt
├── .htaccess             # 伪静态 /dream/编号.html、敏感文件保护
├── assets/style.css      # 自适应样式（设计系统 Token）
├── admin/                # 管理后台（仅管理员）
│   ├── _header.php / _footer.php   # 后台框架公共文件
│   ├── index.php         # 仪表盘
│   ├── dreams.php        # 梦境管理
│   ├── comments.php      # 评论管理（筛选、隐藏/恢复、删除）
│   ├── users.php         # 用户管理
│   ├── setup.php         # 初始化首个管理员（无管理员时可用）
│   └── .htaccess         # 后台目录保护
├── sql/
│   ├── install.sql       # 全新安装：建库、建账号、users + dream_log + comments 建表
│   └── migrate.sql       # 旧库增量迁移：补 users 表、dream_log 新字段与 comments 表
├── deploy/
│   ├── apache-vhost.conf     # Apache 站点配置模板（80 跳转 443 + SSL）
│   └── certbot-renew.cron    # 证书自动续期定时任务
└── tools/aliyun_dns.py   # 阿里云云解析 API 脚本（增删查解析记录）
```

## 四、SEO / GEO 优化清单

### 搜索引擎优化（SEO）
- 每页独立 title、description、keywords、canonical 规范链接
- Open Graph 与 Twitter Card 标签
- 语义化 HTML5：h1/h2 层级、面包屑、article 标签
- 结构化数据（JSON-LD）：WebSite（含站内搜索 Action）、WebApplication、BlogPosting、BreadcrumbList、FAQPage
- 动态 `sitemap.xml` 与 `robots.txt`，后台处理页已禁止抓取
- 伪静态详情地址：`/dream/编号.html`
- 站点语言标记 `zh-CN`，移动端视口适配

### 生成式引擎优化（GEO）
- FAQ 页提供事实型问答：工具定义、记梦原因、忘记梦境处理、保存字段、清醒梦概念、回顾方式
- 问答采用明确陈述句与清单结构，便于大模型抽取引用
- About 页提供工具定位、使用步骤、字段定义等稳定可引用内容
- FAQPage / AboutPage 结构化数据便于引擎理解内容实体

## 五、重装 / 迁移步骤

1. 上传全部站点文件到目标目录，目录归属 Web 运行用户（如 `www:www`）。
2. 复制 `config.example.php` 为 `config.php`，填写数据库与站点信息。
3. 执行 `sql/install.sql`（root 账号）创建库、账号与表；已有旧版库升级时改执行 `sql/migrate.sql` 增量补充 `users` 表与 `dream_log` 的归属、可见性字段。
4. 参考 `deploy/apache-vhost.conf` 配置 Apache 虚拟主机，`httpd -t` 校验后优雅重载。
5. DNS 添加二级域名 A 记录指向服务器公网 IP。
6. 申请证书：`certbot certonly --webroot -w <站点目录> -d <域名>`，再在 vhost 配置 443。
7. 部署 `deploy/certbot-renew.cron` 到 `/etc/cron.d/`，属主 root、权限 644。
8. 浏览器访问 `/admin/setup.php` 创建首个管理员账号。

## 六、日常运维

- **日志**：访问/错误日志按 Apache 配置路径查看
- **备份**：定期备份 `dream_collector` 数据库与站点目录
- **PHP 错误**：DEBUG 常量在 `config.php`，生产保持 `false`
- **新增梦境后**：详情页自动进入 `sitemap.xml`，无需手动维护
- **后台入口**：`/admin/`，仅管理员可访问；首个管理员通过 `/admin/setup.php` 创建
- **账号管理**：后台「用户管理」可禁用账号、调整角色；系统会保留至少一个启用的管理员

## 七、开源说明

- 许可证：MIT，可自由使用、修改与商用，保留版权声明即可。
- 部署前请复制 `config.example.php` 为 `config.php` 并填写自己的数据库信息；`config.php` 已被 `.gitignore` 排除。
- 首次部署建议修改 `sql/install.sql` 中的数据库账号口令占位 `CHANGE_ME_STRONG_PASSWORD`。
- 欢迎提交 Issue 与 Pull Request。
