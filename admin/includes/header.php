<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Saoirse Tours</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .wrapper {
            display: flex;
            width: 100%;
            flex: 1;
        }

        #sidebar {
            min-width: 250px;
            max-width: 250px;
            background: #343a40;
            color: #fff;
            transition: all 0.3s;
        }

        #sidebar .sidebar-header {
            padding: 20px;
            background: #212529;
        }

        #sidebar ul.components {
            padding: 20px 0;
            border-bottom: 1px solid #4b545c;
        }

        #sidebar ul li a {
            padding: 10px;
            font-size: 1.1em;
            display: block;
            color: #adb5bd;
            text-decoration: none;
        }

        #sidebar ul li a:hover {
            color: #fff;
            background: #495057;
        }

        #sidebar ul li.active>a {
            color: #fff;
            background: #0d6efd;
        }

        .content {
            width: 100%;
            padding: 20px;
            background-color: #f8f9fa;
        }
    </style>
    <!-- CKEditor -->
    <script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>
</head>

<body>

    <div class="wrapper">
        <!-- Sidebar -->
        <nav id="sidebar">
            <div class="sidebar-header">
                <h3>CMS Admin</h3>
            </div>

            <ul class="list-unstyled components">
                <li><a href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a></li>
                <li><a href="settings.php"><i class="bi bi-gear me-2"></i> Settings</a></li>
                <li><a href="pages.php"><i class="bi bi-file-earmark-text me-2"></i> Pages</a></li>
                <li><a href="packages.php"><i class="bi bi-backpack2 me-2"></i> Packages</a></li>
                <li><a href="services.php"><i class="bi bi-tree me-2"></i> Services</a></li>
                <li><a href="gallery.php"><i class="bi bi-images me-2"></i> Gallery</a></li>
                <li><a href="inquiries.php"><i class="bi bi-envelope me-2"></i> Inquiries</a></li>
                <li><a href="testimonials.php"><i class="bi bi-chat-quote me-2"></i> Testimonials</a></li>
                <li><a href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
            </ul>
        </nav>

        <!-- Page Content -->
        <div class="content">
            <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4 shadow-sm rounded">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-dark">
                        <i class="bi bi-list"></i>
                    </button>
                    <div class="ms-auto">
                        <span class="navbar-text me-3">Welcome,
                            <?php echo $_SESSION['admin_username'] ?? 'Admin'; ?></span>
                        <a href="../index.php" target="_blank" class="btn btn-outline-primary btn-sm">View Site</a>
                    </div>
                </div>
            </nav>