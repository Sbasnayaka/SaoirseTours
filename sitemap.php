<?php
require_once 'includes/config.php';
header("Content-Type: application/xml; charset=utf-8");

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

// Base URL
$base = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . BASE_URL;

// Static Pages
$pages = $pdo->query("SELECT slug, created_at FROM pages")->fetchAll();
foreach ($pages as $p) {
    echo '<url>';
    echo '<loc>' . $base . $p['slug'] . '</loc>';
    echo '<lastmod>' . date('Y-m-d', strtotime($p['created_at'])) . '</lastmod>';
    echo '<changefreq>monthly</changefreq>';
    echo '<priority>0.8</priority>';
    echo '</url>';
}

// Packages (if we had detailed view URLs like /package/id, but we use contact?package=name currently)
// We still list the main /packages page via the Static Pages loop if it exists in DB, 
// otherwise we can add manual entries here.

$manual_pages = ['packages', 'services', 'gallery', 'contact'];
foreach ($manual_pages as $mp) {
    // Check if not already in DB pages
    $exists = false;
    foreach ($pages as $p) {
        if ($p['slug'] == $mp)
            $exists = true;
    }

    if (!$exists) {
        echo '<url>';
        echo '<loc>' . $base . $mp . '</loc>';
        echo '<changefreq>monthly</changefreq>';
        echo '<priority>0.8</priority>';
        echo '</url>';
    }
}

echo '</urlset>';
?>