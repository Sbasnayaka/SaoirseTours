<?php
// Fetch Settings
$stmt = $pdo->query("SELECT * FROM settings WHERE id = 1");
$settings = $stmt->fetch();
if (!$settings) {
    $settings = [
        'site_title' => 'Saoirse Tours',
        'tagline' => 'Experience Sri Lanka',
        'contact_email' => 'admin@example.com',
        'logo' => '',
        'favicon' => ''
    ];
}

// Default Meta defaults
$meta_title = $page['meta_title'] ?? $settings['site_title'];
$meta_desc = $page['meta_desc'] ?? $settings['tagline'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title ?? 'Saoirse Tours'); ?></title>

    <!-- Meta Tags -->
    <meta name="description" content="<?php echo htmlspecialchars($meta_desc ?? ''); ?>">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Playfair+Display:wght@700&family=Roboto:wght@300;400;700&family=Lato:wght@300;400;700&family=Open+Sans:wght@300;400;600&display=swap"
        rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/custom.css">

    <?php
    // Dynamic Theme Engine
    require_once __DIR__ . '/../classes/ThemeHelper.php';
    $globalTheme = ThemeHelper::getTheme($pdo);
    ?>

    <?php if (!empty($settingsRow['favicon'])): ?>
        <link rel="icon" href="<?php echo BASE_URL; ?>uploads/<?php echo $settingsRow['favicon']; ?>" type="image/x-icon">
    <?php endif; ?>

    <style>
        /* Dynamic Global Variables */
        <?php echo ThemeHelper::renderCssVariables($globalTheme); ?>

        /* Navbar Overrides */
        .navbar {
            background-color: var(--nav-bg) !important;
            padding: var(--nav-padding) !important;
            <?php if (!empty($globalTheme['nav']['sticky'])): ?>
                position: sticky;
                top: 0;
                z-index: 1000;
                width: 100%;
            <?php endif; ?>
        }

        .navbar-brand,
        .nav-link {
            color: var(--nav-text) !important;
            font-weight: 500;
        }

        .navbar-brand img {
            max-height: calc(var(--nav-height) - 20px);
        }

        /* Footer Overrides */
        footer {
            background-color: var(--footer-bg) !important;
            color: var(--footer-text) !important;
            padding: var(--footer-padding) !important;
        }

        /* Link Overrides */
        a:not(.btn):not(.nav-link) {
            color: var(--color-primary);
        }
    </style>
</head>

<body class="d-flex flex-column min-vh-100">
    <nav class="navbar navbar-expand-lg border-bottom shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="<?php echo BASE_URL; ?>">
                <?php if (!empty($settings['logo'])): ?>
                    <img src="<?php echo BASE_URL; ?>uploads/<?php echo $settings['logo']; ?>"
                        alt="<?php echo htmlspecialchars($settings['site_title']); ?>" class="img-fluid">
                <?php else: ?>
                    <span class="fw-bold fs-3 text-primary"><?php echo htmlspecialchars($settings['site_title']); ?></span>
                <?php endif; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>home">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>about">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>packages">Packages</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>services">Services</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>gallery">Gallery</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>contact">Contact</a></li>
                </ul>
                <a href="<?php echo BASE_URL; ?>contact" class="btn btn-primary ms-lg-3">Book Now</a>
            </div>
        </div>
    </nav>
    <main class="flex-grow-1">