<?php
require_once '../includes/config.php';
require_once '../classes/Auth.php';

$auth = new Auth($pdo);
$auth->requireLogin();

// --- DATA AGGREGATION FOR CHARTS ---

// 0. Basic Counts
$pageCount = $pdo->query("SELECT COUNT(*) FROM pages")->fetchColumn();
$packageCount = $pdo->query("SELECT COUNT(*) FROM packages")->fetchColumn();
$serviceCount = $pdo->query("SELECT COUNT(*) FROM services")->fetchColumn();
$inquiryCount = $pdo->query("SELECT COUNT(*) FROM inquiries")->fetchColumn();

// 1. Booking Trends (Last 6 Months)
$months = [];
$bookingCounts = [];
$inquiryCounts = [];

for ($i = 5; $i >= 0; $i--) {
    $date = date('Y-m', strtotime("-$i months"));
    $months[] = date('M Y', strtotime("-$i months"));

    // Count Bookings
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM inquiries WHERE type = 'booking' AND date_format(created_at, '%Y-%m') = ?");
    $stmt->execute([$date]);
    $bookingCounts[] = $stmt->fetchColumn();

    // Count Inquiries
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM inquiries WHERE type = 'inquiry' AND date_format(created_at, '%Y-%m') = ?");
    $stmt->execute([$date]);
    $inquiryCounts[] = $stmt->fetchColumn();
}

// 2. Inquiry Type Distribution
$stmt = $pdo->query("SELECT type, COUNT(*) as count FROM inquiries GROUP BY type");
$typesData = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // ['booking' => 10, 'inquiry' => 5]
$typeLabels = array_map('ucfirst', array_keys($typesData));
$typeValues = array_values($typesData);

// 3. Top Packages (PHP Parse approach for safety)
// Fetch last 50 bookings to analyze trends
$stmt = $pdo->query("SELECT message FROM inquiries WHERE type = 'booking' ORDER BY created_at DESC LIMIT 50");
$pkgStats = [];
while ($row = $stmt->fetch()) {
    if (preg_match('/Package: (.*?)(\n|$)/', $row['message'], $m)) {
        $pkgName = trim($m[1]);
        if (!isset($pkgStats[$pkgName]))
            $pkgStats[$pkgName] = 0;
        $pkgStats[$pkgName]++;
    }
}
arsort($pkgStats); // Sort high to low
$topPackages = array_slice($pkgStats, 0, 5); // Take top 5
$pkgLabels = array_keys($topPackages);
$pkgValues = array_values($topPackages);


include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Dashboard Overview</h1>
    </div>

    <!-- Summary Cards -->
    <div class="row">
        <!-- Total Bookings -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Inquiries</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $inquiryCount; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-envelope-fill fs-2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Packages -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Active Packages</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $packageCount; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-backpack2-fill fs-2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Services -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Services</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $serviceCount; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-tree-fill fs-2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Warning / Pending -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Requests
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $typesData['booking'] ?? 0; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-exclamation-circle-fill fs-2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row">
        <!-- Area Chart -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Booking Trends (Last 6 Months)</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area" style="height: 320px;">
                        <canvas id="myAreaChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pie Chart -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Inquiry Sources</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2" style="height: 250px;">
                        <canvas id="myPieChart"></canvas>
                    </div>
                    <div class="mt-4 text-center small text-muted">
                        <i class="bi bi-circle-fill text-primary"></i> Bookings
                        <span class="mx-2">|</span>
                        <i class="bi bi-circle-fill text-success"></i> General Inquiries
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Row: Top Packages -->
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Top Performing Packages</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($topPackages)): ?>
                        <p class="text-muted text-center">No sufficient booking data yet.</p>
                    <?php else: ?>
                        <?php foreach ($topPackages as $name => $count):
                            $pct = ($count / max($topPackages)) * 100;
                            ?>
                            <h4 class="small font-weight-bold"><?php echo htmlspecialchars($name); ?> <span
                                    class="float-end"><?php echo $count; ?> Bookings</span></h4>
                            <div class="progress mb-4">
                                <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $pct; ?>%"></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <a href="edit_package.php" class="btn btn-success btn-lg w-100 mb-3"><i
                            class="bi bi-plus-circle"></i> Add New Package</a>
                    <a href="floating_widgets.php" class="btn btn-secondary w-100 mb-3"><i class="bi bi-chat-dots"></i>
                        Manage Chat Widgets</a>
                    <a href="bookings.php" class="btn btn-light w-100 border"><i class="bi bi-calendar-check"></i>
                        Manage Bookings</a>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- CHART.JS Loaded from CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // Pass PHP data to JS
    const monthLabels = <?php echo json_encode($months); ?>;
    const bookingData = <?php echo json_encode($bookingCounts); ?>;
    const inquiryData = <?php echo json_encode($inquiryCounts); ?>;

    const typeLabels = <?php echo json_encode($typeLabels); ?>;
    const typeValues = <?php echo json_encode($typeValues); ?>;

    // 1. AREA CHART (Trends)
    const ctxArea = document.getElementById("myAreaChart");
    new Chart(ctxArea, {
        type: 'line',
        data: {
            labels: monthLabels,
            datasets: [{
                label: "Bookings",
                lineTension: 0.3,
                backgroundColor: "rgba(78, 115, 223, 0.05)",
                borderColor: "rgba(78, 115, 223, 1)",
                pointRadius: 3,
                pointBackgroundColor: "rgba(78, 115, 223, 1)",
                pointBorderColor: "rgba(78, 115, 223, 1)",
                pointHoverRadius: 3,
                pointHoverBackgroundColor: "rgba(78, 115, 223, 1)",
                pointHoverBorderColor: "rgba(78, 115, 223, 1)",
                pointHitRadius: 10,
                pointBorderWidth: 2,
                data: bookingData,
            }, {
                label: "Inquiries",
                lineTension: 0.3,
                borderColor: "rgba(28, 200, 138, 1)",
                pointRadius: 3,
                pointBackgroundColor: "rgba(28, 200, 138, 1)",
                data: inquiryData,
            }],
        },
        options: {
            maintainAspectRatio: false,
            layout: { padding: { left: 10, right: 25, top: 25, bottom: 0 } },
            scales: {
                xAxes: [{ gridLines: { display: false, drawBorder: false } }],
                yAxes: [{ ticks: { maxTicksLimit: 5, padding: 10, beginAtZero: true } }],
            },
            legend: { display: true },
            tooltips: { backgroundColor: "rgb(255,255,255)", bodyFontColor: "#858796", titleFontColor: '#6e707e', borderColor: '#dddfeb', borderWidth: 1, xPadding: 15, yPadding: 15 }
        }
    });

    // 2. PIE CHART (Distribution)
    const ctxPie = document.getElementById("myPieChart");
    new Chart(ctxPie, {
        type: 'doughnut',
        data: {
            labels: typeLabels,
            datasets: [{
                data: typeValues,
                backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc'],
                hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf'],
                hoverBorderColor: "rgba(234, 236, 244, 1)",
            }],
        },
        options: {
            maintainAspectRatio: false,
            tooltips: { backgroundColor: "rgb(255,255,255)", bodyFontColor: "#858796", borderColor: '#dddfeb', borderWidth: 1, xPadding: 15, yPadding: 15, displayColors: false },
            legend: { display: true, position: 'bottom' },
            cutoutPercentage: 80,
        },
    });
</script>

<?php include 'includes/footer.php'; ?>