<?php
require_once '../includes/config.php';
require_once '../classes/Auth.php';

$auth = new Auth($pdo);
$auth->requireLogin();

// Fetch counts for dashboard
$pageCount = $pdo->query("SELECT COUNT(*) FROM pages")->fetchColumn();
$packageCount = $pdo->query("SELECT COUNT(*) FROM packages")->fetchColumn();
$serviceCount = $pdo->query("SELECT COUNT(*) FROM services")->fetchColumn();
$inquiryCount = $pdo->query("SELECT COUNT(*) FROM inquiries")->fetchColumn();

include 'includes/header.php';
?>

<div class="container-fluid">
    <h1 class="mt-4">Dashboard</h1>
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Pages</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pageCount; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Packages</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $packageCount; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Services</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $serviceCount; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Inquiries</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $inquiryCount; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>