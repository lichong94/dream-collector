  </div>
</main>
<footer class="footer">
  <div class="container">
    <p><?php echo e(SITE_NAME); ?> · 记录每一个梦境，回顾自己的内心轨迹</p>
    <p style="margin-top:6px;">
      <a href="/faq.php">常见问题</a> · <a href="/about.php">关于本站</a> · <a href="/sitemap.xml">站点地图</a>
    </p>
  </div>
</footer>
<?php
// 全站结构化数据：网站实体
render_jsonld([
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'name' => SITE_NAME,
    'url' => SITE_URL . '/',
    'description' => SITE_DESC,
    'inLanguage' => 'zh-CN',
    'potentialAction' => [
        '@type' => 'SearchAction',
        'target' => SITE_URL . '/?kw={search_term_string}',
        'query-input' => 'required name=search_term_string',
    ],
]);
?>
</body>
</html>
