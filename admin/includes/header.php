<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMS Admin - Saoirse Tours</title>
    <!-- Modern Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --sidebar-bg: #1e1e2d;
            --sidebar-color: #9899ac;
            --sidebar-active: #ffffff;
            --sidebar-active-bg: #1b1b28;
            --primary: #5664d2;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f6fa;
            min-height: 100vh;
            display: flex;
        }

        .wrapper {
            display: flex;
            width: 100%;
        }

        /* Sidebar Styling */
        #sidebar {
            min-width: 260px;
            max-width: 260px;
            background: var(--sidebar-bg);
            color: var(--sidebar-color);
            transition: all 0.3s;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
        }

        #sidebar .sidebar-header {
            padding: 25px;
            background: #1a1a27;
            color: #fff;
            font-weight: 700;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        #sidebar ul.components {
            padding: 10px 0;
        }

        #sidebar ul li {
            margin: 2px 10px;
        }

        #sidebar ul li a {
            padding: 12px 15px;
            font-size: 0.95em;
            display: flex;
            align-items: center;
            color: var(--sidebar-color);
            text-decoration: none;
            border-radius: 8px;
            transition: 0.3s;
        }

        #sidebar ul li a:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.05);
        }

        #sidebar ul li a.active {
            color: var(--sidebar-active);
            background: var(--primary);
            box-shadow: 0 4px 15px rgba(86, 100, 210, 0.3);
        }

        #sidebar ul li a i {
            font-size: 1.1rem;
            margin-right: 12px;
            opacity: 0.8;
        }

        /* Content Styling */
        .content {
            width: 100%;
            padding: 25px;
            overflow-x: hidden;
        }

        .navbar {
            background: white;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.03);
            border-radius: 12px;
            padding: 15px 25px;
            border: none;
        }

        /* Cards */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 3px 20px rgba(0, 0, 0, 0.03);
            margin-bottom: 25px;
            background: white;
            transition: transform 0.2s;
        }

        .card:hover {
            transform: translateY(-2px);
        }

        .card-header {
            background: white;
            border-bottom: 1px solid #f0f0f0;
            padding: 20px;
            font-weight: 600;
            font-size: 1.05rem;
            border-radius: 12px 12px 0 0 !important;
        }

        /* Buttons */
        .btn {
            border-radius: 8px;
            padding: 8px 16px;
            font-weight: 500;
            font-size: 0.9rem;
            transition: 0.3s;
        }

        .btn-primary {
            background: var(--primary);
            border-color: var(--primary);
            box-shadow: 0 3px 10px rgba(86, 100, 210, 0.2);
        }

        .btn-primary:hover {
            background: #4b58c5;
            border-color: #4b58c5;
            transform: translateY(-1px);
        }

        /* Tables */
        .table thead th {
            border-top: none;
            border-bottom: 1px solid #f0f0f0;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            color: #a1a5b7;
            padding: 15px;
        }

        .table td {
            padding: 15px;
            vertical-align: middle;
            color: #5e6278;
            border-bottom: 1px solid #f8f9fa;
        }

        .badge {
            padding: 6px 10px;
            border-radius: 6px;
            font-weight: 500;
        }

        /* Forms */
        .form-control {
            border-radius: 8px;
            padding: 10px 15px;
            border: 1px solid #e4e6ef;
            background: #f5f8fa;
        }

        .form-control:focus {
            background: #fff;
            border-color: var(--primary);
            box-shadow: none;
        }

        .form-label {
            font-weight: 500;
            color: #3f4254;
            margin-bottom: 8px;
        }
    </style>
    <!-- CKEditor -->
    <!-- CKEditor -->
    <script src="https://cdn.ckeditor.com/4.24.0-lts/standard/ckeditor.js"></script>
</head>

<body>

    <div class="wrapper">
        <!-- Sidebar -->
        <nav id="sidebar">
            <div class="sidebar-header">
                <i class="bi bi-grid-fill"></i> Saoirse Admin
            </div>

            <ul class="list-unstyled components">
                <li><a href="dashboard.php"
                        class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>"><i
                            class="bi bi-speedometer2"></i> Dashboard</a></li>
                <li><a href="theme_customizer.php"
                        class="<?php echo basename($_SERVER['PHP_SELF']) == 'theme_customizer.php' ? 'active' : ''; ?>"><i
                            class="bi bi-palette-fill"></i> Global Theme</a></li>
                <li><a href="header_builder.php"
                        class="<?php echo basename($_SERVER['PHP_SELF']) == 'header_builder.php' ? 'active' : ''; ?>"><i
                            class="bi bi-window-desktop"></i> Header Builder</a></li>
                <li><a href="footer_builder.php"
                        class="<?php echo basename($_SERVER['PHP_SELF']) == 'footer_builder.php' ? 'active' : ''; ?>"><i
                            class="bi bi-layout-three-columns"></i> Footer Builder</a></li>
                <li><a href="settings.php"
                        class="<?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>"><i
                            class="bi bi-sliders"></i> Site Identity</a></li>
                <li><a href="pages.php"
                        class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['pages.php', 'edit_page.php', 'page_sections.php', 'edit_section.php']) ? 'active' : ''; ?>"><i
                            class="bi bi-file-earmark-text"></i> Pages & Sections</a></li>
                <li><a href="packages.php"
                        class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['packages.php', 'edit_package.php']) ? 'active' : ''; ?>"><i
                            class="bi bi-backpack2"></i> Tour Packages</a></li>
                <li><a href="services.php"
                        class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['services.php', 'edit_service.php']) ? 'active' : ''; ?>"><i
                            class="bi bi-tree"></i> Services</a></li>
                <li><a href="gallery.php"
                        class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['gallery.php', 'edit_gallery.php']) ? 'active' : ''; ?>"><i
                            class="bi bi-images"></i> Gallery</a></li>
                <li><a href="inquiries.php"
                        class="<?php echo basename($_SERVER['PHP_SELF']) == 'inquiries.php' ? 'active' : ''; ?>"><i
                            class="bi bi-envelope"></i> Inquiries</a></li>
                <li><a href="testimonials.php"
                        class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['testimonials.php', 'edit_testimonial.php']) ? 'active' : ''; ?>"><i
                            class="bi bi-chat-quote"></i> Testimonials</a></li>
                <li><a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
            </ul>
        </nav>

        <!-- Page Content -->
        <div class="content">
            <nav class="navbar navbar-expand-lg">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-light me-3">
                        <i class="bi bi-list fs-5"></i>
                    </button>
                    <div class="ms-auto d-flex align-items-center">
                        <span class="text-muted me-3">Logged in as
                            <b><?php echo $_SESSION['admin_username'] ?? 'Admin'; ?></b></span>
                        <a href="../index.php" target="_blank" class="btn btn-primary btn-sm"><i class="bi bi-eye"></i>
                            View Website</a>
                    </div>
                </div>
            </nav>