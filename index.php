<?php
require_once 'includes/config.php';

// Get Page Slug
$slug = isset($_GET['page']) ? $_GET['page'] : 'home';

// Fix singular 'package' url (should be packages)
if ($slug === 'package') {
    header("Location: packages");
    exit;
}

if ($slug == '')
    $slug = 'home';

// Sanitize Slug
$slug = preg_replace('/[^a-zA-Z0-9-]/', '', $slug);

// Fetch Page Data from DB
$stmt = $pdo->prepare("SELECT * FROM pages WHERE slug = :slug LIMIT 1");
$stmt->execute([':slug' => $slug]);
$page = $stmt->fetch();

// If page doesn't exist in DB, and it's not a special template, show 404 or default
if (!$page) {
    // Special core pages might not have DB entries yet but should exist (like 'packages' listing)
    // We create a dummy page object for them to avoid errors in header
    if (in_array($slug, ['packages', 'gallery', 'services'])) {
        $page = ['title' => ucfirst($slug), 'content' => '', 'meta_title' => ucfirst($slug) . ' - Saoirse Tours'];
    } else {
        // Fallback 404
        $page = ['title' => 'Page Not Found', 'content' => 'The page you are looking for does not exist.', 'meta_title' => '404 Not Found'];
    }
}

include 'includes/header.php';

// Determine Template
$templateFile = "templates/{$slug}.php";

// Check if specific template exists, otherwise use generic page one
if (file_exists($templateFile)) {
    include $templateFile;
} else {
    // Fallback: If it's a generic content page from DB
    if (isset($page['id'])) {
        include 'templates/page.php';
    } else {
        echo '<div class="container py-5"><h1>404 Not Found</h1></div>';
    }
}

include 'includes/footer.php';
?>