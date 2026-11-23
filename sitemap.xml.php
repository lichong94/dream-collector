<?php
/**
 * 动态站点地图：输出 XML，包含静态页面与全部梦境详情页。
 * 访问地址：/sitemap.xml
 */
require_once __DIR__ . '/init.php';

header('Content-Type: application/xml; charset=UTF-8');

$staticPages = [
    ['loc' => SITE_URL . '/', 'priority' => '1.0'],
    ['loc' => SITE_URL . '/faq.php', 'priority' => '0.8'],
    ['loc' => SITE_URL . '/about.php', 'priority' => '0.7'],
];

$records = db_all("SELECT id, updated_at FROM dream_log WHERE visibility = 'public' ORDER BY id DESC");

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($staticPages as $page) {
    echo "  <url>\n";
    echo "    <loc>" . e($page['loc']) . "</loc>\n";
    echo "    <changefreq>weekly</changefreq>\n";
    echo "    <priority>" . $page['priority'] . "</priority>\n";
    echo "  </url>\n";
}

foreach ($records as $r) {
    echo "  <url>\n";
    echo "    <loc>" . SITE_URL . '/dream/' . (int)$r['id'] . '.html</loc>' . "\n";
    echo "    <lastmod>" . date('Y-m-d', strtotime($r['updated_at'])) . "</lastmod>\n";
    echo "    <changefreq>monthly</changefreq>\n";
    echo "    <priority>0.6</priority>\n";
    echo "  </url>\n";
}

echo '</urlset>';
