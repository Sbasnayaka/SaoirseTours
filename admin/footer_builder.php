<?php
// Standard Admin Page Setup
if (file_exists(__DIR__ . '/../includes/config.php')) {
    require_once __DIR__ . '/../includes/config.php';
}
if (file_exists(__DIR__ . '/../classes/Auth.php')) {
    require_once __DIR__ . '/../classes/Auth.php';
    if (isset($pdo)) {
        $auth = new Auth($pdo);
        $auth->requireLogin();
    }
} else {
    session_start();
    if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_logged_in'])) {
        header("Location: login.php");
        exit;
    }
}

// Fallback manual connection if config failed (similar to header builder)
if (!isset($pdo)) {
    $host = 'localhost';
    $dbname = 'tourism_cms';
    $username = 'root';
    $password = '';
    $port = 3307;
    try {
        $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("DB Error: " . $e->getMessage());
    }
}

// Global Validation Helper
if (!function_exists('val')) {
    function val($arr, $keys, $default = '')
    {
        foreach ($keys as $k) {
            if (!isset($arr[$k]))
                return $default;
            $arr = $arr[$k];
        }
        return $arr;
    }
}

$success = "";

// SELF-HEALING: Create Table if Missing
try {
    $pdo->query("SELECT 1 FROM footer_settings LIMIT 1");
} catch (PDOException $e) {
    $sql = "CREATE TABLE IF NOT EXISTS `footer_settings` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `settings` longtext,
      `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sql);

    // Insert Defaults
    $def = json_encode([
        'widgets' => [
            'tripadvisor_html' => '',
            'copyright_text' => 'Copyright 2024 Saoirse Tours',
            'quick_links' => [
                ['text' => 'Home', 'url' => 'home'],
                ['text' => 'About', 'url' => 'about'],
                ['text' => 'Contact', 'url' => 'contact']
            ]
        ],
        'design' => ['bg_color' => '#1e1e2d', 'text_color' => '#ffffff']
    ]);
    $pdo->exec("INSERT INTO footer_settings (id, settings) VALUES (1, '$def')");
}

// HANDLE SAVE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process Quick Links (Array)
    $qLinks = [];
    if (isset($_POST['qlink_text']) && is_array($_POST['qlink_text'])) {
        for ($i = 0; $i < count($_POST['qlink_text']); $i++) {
            if (!empty($_POST['qlink_text'][$i])) {
                $qLinks[] = [
                    'text' => $_POST['qlink_text'][$i],
                    'url' => $_POST['qlink_url'][$i]
                ];
            }
        }
    }

    $settings = [
        'widgets' => [
            'tripadvisor_html' => $_POST['tripadvisor_html'] ?? '',
            'socials' => [
                'facebook' => $_POST['social_fb'] ?? '',
                'instagram' => $_POST['social_ig'] ?? '',
                'whatsapp' => $_POST['social_wa'] ?? '',
                'tripadvisor_url' => $_POST['social_ta'] ?? ''
            ],
            'copyright_text' => $_POST['copyright_text'] ?? '',
            'trust_badges_html' => $_POST['trust_badges_html'] ?? '',
            'quick_links' => $qLinks
        ],
        'design' => [
            'bg_color' => $_POST['bg_color'] ?? '#1e1e2d',
            'text_color' => $_POST['text_color'] ?? '#ffffff',
            'heading_color' => $_POST['heading_color'] ?? '#ffffff'
        ]
    ];

    $json = json_encode($settings);

    // Upsert
    $existing = $pdo->query("SELECT id FROM footer_settings WHERE id = 1")->fetch();
    if ($existing) {
        $stmt = $pdo->prepare("UPDATE footer_settings SET settings = ? WHERE id = 1");
        $stmt->execute([$json]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO footer_settings (id, settings) VALUES (1, ?)");
        $stmt->execute([$json]);
    }
    $success = "Footer configuration saved!";
}

// FETCH SETTINGS
$curr = $pdo->query("SELECT settings FROM footer_settings WHERE id = 1")->fetch();
$s = $curr ? json_decode($curr['settings'], true) : [];
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center gap-3">
            <a href="dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
            <h1 class="h3 mb-0 text-gray-800">Footer Builder Engine</h1>
        </div>
        <a href="../home" target="_blank" class="btn btn-outline-primary"><i class="bi bi-eye"></i> View Site</a>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <ul class="nav nav-tabs card-header-tabs" id="footerTabs" role="tablist">
                            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab"
                                    data-bs-target="#tab-widgets" type="button">Widget Content</button></li>
                            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab"
                                    data-bs-target="#tab-design" type="button">Design & Style</button></li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content">
                            <!-- WIDGETS TAB -->
                            <div class="tab-pane fade show active" id="tab-widgets">

                                <h5 class="mb-3">Column 1: TripAdvisor</h5>
                                <div class="mb-3">
                                    <label class="form-label">TripAdvisor Widget HTML Code</label>
                                    <textarea class="form-control font-monospace" name="tripadvisor_html" rows="4"
                                        placeholder="Paste <div id='TA_...'> code here"><?php echo htmlspecialchars(val($s, ['widgets', 'tripadvisor_html'])); ?></textarea>
                                    <small class="text-muted">Get this code from your TripAdvisor Partner
                                        Dashboard.</small>
                                </div>

                                <h5 class="mb-3 mt-4">Column 1: Social Links</h5>
                                <div class="row g-2">
                                    <div class="col-md-6"><input type="text" class="form-control" name="social_fb"
                                            placeholder="Facebook URL"
                                            value="<?php echo val($s, ['widgets', 'socials', 'facebook']); ?>"></div>
                                    <div class="col-md-6"><input type="text" class="form-control" name="social_ig"
                                            placeholder="Instagram URL"
                                            value="<?php echo val($s, ['widgets', 'socials', 'instagram']); ?>"></div>
                                    <div class="col-md-6"><input type="text" class="form-control" name="social_wa"
                                            placeholder="WhatsApp URL"
                                            value="<?php echo val($s, ['widgets', 'socials', 'whatsapp']); ?>"></div>
                                    <div class="col-md-6"><input type="text" class="form-control" name="social_ta"
                                            placeholder="TripAdvisor Profile URL"
                                            value="<?php echo val($s, ['widgets', 'socials', 'tripadvisor_url']); ?>">
                                    </div>
                                </div>

                                <h5 class="mb-3 mt-4">Column 2: Tour Packages</h5>
                                <div class="alert alert-info py-2">
                                    <i class="bi bi-info-circle"></i> This content is dynamic. It will automatically
                                    show the latest 5 Published Packages.
                                </div>

                                <h5 class="mb-3 mt-4">Column 3: Quick Links</h5>
                                <div id="quick-links-wrapper">
                                    <?php
                                    $qlinks = val($s, ['widgets', 'quick_links'], []);
                                    if (empty($qlinks))
                                        $qlinks = [['text' => '', 'url' => '']];
                                    foreach ($qlinks as $idx => $lnk): ?>
                                        <div class="input-group mb-2 link-row">
                                            <input type="text" class="form-control" name="qlink_text[]"
                                                placeholder="Link Text (e.g. Home)"
                                                value="<?php echo htmlspecialchars($lnk['text']); ?>">
                                            <input type="text" class="form-control" name="qlink_url[]"
                                                placeholder="URL (e.g. home)"
                                                value="<?php echo htmlspecialchars($lnk['url']); ?>">
                                            <button type="button" class="btn btn-outline-danger remove-link"><i
                                                    class="bi bi-trash"></i></button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" class="btn btn-sm btn-secondary mt-2" id="add-link-btn"><i
                                        class="bi bi-plus-circle"></i> Add Link</button>

                                <h5 class="mb-3 mt-4">Column 4: Badges & Icons</h5>
                                <div class="mb-3">
                                    <label class="form-label">Trust Badges HTML / Icons</label>
                                    <textarea class="form-control font-monospace" name="trust_badges_html" rows="3"
                                        placeholder="<img src='...'> or HTML for icons"><?php echo htmlspecialchars(val($s, ['widgets', 'trust_badges_html'])); ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Copyright Text (Bottom Bar)</label>
                                    <input type="text" class="form-control" name="copyright_text"
                                        value="<?php echo htmlspecialchars(val($s, ['widgets', 'copyright_text'], 'Copyright ' . date('Y') . ' Saoirse Tours')); ?>">
                                </div>
                            </div>

                            <!-- DESIGN TAB -->
                            <div class="tab-pane fade" id="tab-design">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Background Color</label>
                                        <input type="color" class="form-control form-control-color w-100"
                                            name="bg_color"
                                            value="<?php echo val($s, ['design', 'bg_color'], '#1e1e2d'); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Body Text Color</label>
                                        <input type="color" class="form-control form-control-color w-100"
                                            name="text_color"
                                            value="<?php echo val($s, ['design', 'text_color'], '#ffffff'); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Heading Color</label>
                                        <input type="color" class="form-control form-control-color w-100"
                                            name="heading_color"
                                            value="<?php echo val($s, ['design', 'heading_color'], '#ffffff'); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-primary px-4">Save Footer</button>
                    </div>
                </div>
            </div>

            <!-- Instructions Side Panel -->
            <div class="col-lg-4">
                <div class="card shadow mb-4">
                    <div class="card-header font-weight-bold "><i class="bi bi-lightbulb"></i> Tips</div>
                    <div class="card-body small">
                        <p><strong>TripAdvisor:</strong> Go to your TA Listing > Partner Dashboard > Widgets to generate
                            the code.</p>
                        <p><strong>Images:</strong> For Badges, upload images in "Media Gallery" and paste the full
                            Image URL here like <code>&lt;img src='http...' width='100'&gt;</code>.</p>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Quick Links Repeater
        const wrapper = document.getElementById('quick-links-wrapper');
        const addBtn = document.getElementById('add-link-btn');

        addBtn.addEventListener('click', function () {
            const row = document.createElement('div');
            row.className = 'input-group mb-2 link-row';
            row.innerHTML = `
            <input type="text" class="form-control" name="qlink_text[]" placeholder="Link Text" value="">
            <input type="text" class="form-control" name="qlink_url[]" placeholder="URL" value="">
            <button type="button" class="btn btn-outline-danger remove-link"><i class="bi bi-trash"></i></button>
        `;
            wrapper.appendChild(row);
        });

        wrapper.addEventListener('click', function (e) {
            if (e.target.closest('.remove-link')) {
                e.target.closest('.link-row').remove();
            }
        });
    });
</script>

<?php include 'includes/footer.php'; ?>