<?php
require_once __DIR__ . '/init.php';

$page_meta = [
    'title' => '关于梦境收集器 - 在线记梦与梦境回顾工具',
    'description' => '梦境收集器用于记录梦境标题、情节、心情与标签，支持关键词检索、心情筛选和历史回顾。本站面向个人记梦场景，提供清晰的字段说明与使用步骤。',
    'keywords' => '关于梦境收集器,在线记梦工具,梦境日记,梦境字段说明',
    'canonical' => SITE_URL . '/about.php',
];
$active_nav = 'about';
require_once __DIR__ . '/header.php';
?>

<h1 class="page-title">关于梦境收集器</h1>
<p class="page-sub">一个专注于梦境记录、整理与回顾的在线工具。</p>

<div class="card prose">
  <h2 class="card-title">工具定位</h2>
  <p>
    梦境收集器面向有记梦习惯或希望观察自己梦境变化的用户。用户在醒来后将记得的梦境内容录入系统，
    系统按做梦日期保存，并提供关键词搜索、心情筛选、详情回顾和相关梦境推荐，帮助用户在日后翻查自己的梦境轨迹。
  </p>

  <h2 class="card-title" style="margin-top:24px;">使用步骤</h2>
  <ol>
    <li>浏览公开梦境无需登录；要记录内容，先注册并登录账号；</li>
    <li>点击「记录梦境」，填写梦境标题、做梦日期、心情和标签；</li>
    <li>在正文区域写下记得的情节、人物、场景和感受，并选择公开或隐私；</li>
    <li>保存后可在个人中心查看和管理自己的全部记录，公开梦境会出现在首页列表。</li>
  </ol>

  <h2 class="card-title" style="margin-top:24px;">字段说明</h2>
  <ul>
    <li><strong>梦境标题</strong>：用一句话概括梦境，便于列表快速识别。</li>
    <li><strong>做梦日期</strong>：梦境发生的日期，列表按该日期倒序排列。</li>
    <li><strong>醒来心情</strong>：可选开心、平淡、奇妙、迷茫、惊悚、悲伤六类。</li>
    <li><strong>标签</strong>：多个标签用逗号分隔，用于按主题检索。</li>
    <li><strong>详细内容</strong>：梦境的完整文字记录，支持换行保存。</li>
    <li><strong>可见性</strong>：公开则展示给所有访客；隐私则仅作者本人可见，他人无法查看。</li>
  </ul>

  <h2 class="card-title" style="margin-top:24px;">适用场景</h2>
  <ul>
    <li>个人日常记梦，留存醒来后容易遗忘的梦境片段；</li>
    <li>按心情和主题回顾一段时间内的梦境变化；</li>
    <li>整理重复出现的梦境意象，作为自我观察的记录素材。</li>
  </ul>

  <h2 class="card-title" style="margin-top:24px;">数据说明</h2>
  <p>
    所有记录保存在本站数据库中；标题、正文等内容在页面展示前经过转义处理，避免页面脚本注入。
    隐私梦境仅对作者本人开放，不进入公开列表、站点地图与搜索引擎收录。
    本工具仅提供记录与回顾功能，不替代任何心理或医疗诊断。
  </p>
</div>

<?php
render_jsonld([
    '@context' => 'https://schema.org',
    '@type' => 'AboutPage',
    'name' => '关于梦境收集器',
    'url' => SITE_URL . '/about.php',
    'description' => SITE_DESC,
    'inLanguage' => 'zh-CN',
]);
require_once __DIR__ . '/footer.php';
