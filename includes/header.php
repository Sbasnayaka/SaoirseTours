<?php
// Fetch Settings
$stmt = $pdo->query("SELECT * FROM settings WHERE id = 1");
$settings = $stmt->fetch();

// Default Meta defaults
$meta_title = $page['meta_title'] ?? $settings['site_title'];
$meta_desc = $page['meta_desc'] ?? $settings['tagline'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($meta_title); ?> | <?php echo htmlspecialchars($settings['site_title']); ?>
    </title>
    <meta name="description" content="<?php echo htmlspecialchars($meta_desc); ?>">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=<?php echo urlencode(str_replace(['"', "'"], '', $settings['font_family'])); ?>:wght@300;400;700&display=swap"
        rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/custom.css">

    <style>
        :root {
            --primary-color:
                <?php echo $settings['primary_color']; ?>
            ;
            --secondary-color:
                <?php echo $settings['secondary_color'] ?? '#6c757d'; ?>
            ;
            --bg-color:
                <?php echo $settings['bg_color']; ?>
            ;
            --font-family:
                <?php echo $settings['font_family']; ?>
            ;
        }

        body {
            font-family: var(--font-family);
            background-color: var(--bg-color);
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <?php if (!empty($settings['logo'])): ?>
                    <img src="uploads/<?php echo $settings['logo']; ?>" alt="Logo" height="40" class="me-2">
                <?php endif; ?>
                <span><?php echo htmlspecialchars($settings['site_title']); ?></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="home">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="about">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="services">Services</a></li>
                    <li class="nav-item"><a class="nav-link" href="packages">Packages</a></li>
                    <li class="nav-item"><a class="nav-link" href="gallery">Gallery</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact">Contact</a></li>
                </ul>
            </div>
        </div>
    </nav>