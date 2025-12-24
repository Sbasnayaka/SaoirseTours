<?php
// Standard Admin Page Setup
require_once '../includes/config.php';
require_once '../classes/Auth.php';

// Auth Check (Standardized)
$auth = new Auth($pdo);
$auth->requireLogin();

// ---------------------------------------------------------
// HEADER BUILDER LOGIC
// ---------------------------------------------------------

// Helper for nested array access
function val($arr, $keys, $default = '')
{
    foreach ($keys as $k) {
        if (!isset($arr[$k]))
            return $default;
        $arr = $arr[$k];
    }
    return $arr;
}

// Global Variables
$success = "";

// SELF-HEALING: Auto-Create Table if Missing
try {
    $stmt = $pdo->query("SELECT 1 FROM header_settings LIMIT 1");
} catch (PDOException $e) {
    try {
        $sql = "CREATE TABLE IF NOT EXISTS `header_settings` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `is_active` tinyint(1) DEFAULT 1,
          `settings` longtext, 
          `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $pdo->exec($sql);

        // Insert Default
        $defaultJson = json_encode([
            'general' => ['layout' => 'logo_left', 'type' => 'standard', 'container' => 'container', 'sticky' => false],
            'rows' => [
                'top_bar' => ['visible' => false, 'bg_color' => '#3a4c40', 'text_color' => '#ffffff', 'text' => 'Call us: +94 123 456 789'],
                'main_header' => ['height' => '80px', 'bg_color' => '#ffffff']
            ],
            'design' => [
                'typography' => ['logo_color' => '#486856', 'menu_color' => '#3a4c40', 'menu_hover' => '#486856'],
                'borders' => ['bottom_width' => '1px', 'bottom_color' => '#e0e0e0']
            ]
        ]);

        $stmt = $pdo->prepare("INSERT INTO header_settings (id, settings) VALUES (1, ?)");
        $stmt->execute([$defaultJson]);

    } catch (PDOException $ex) {
        die("<h3>Critical Error: Could not create header_settings table.</h3><br>" . $ex->getMessage());
    }
}

// Handle Save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'general' => [
            'layout' => $_POST['layout'],
            'type' => $_POST['type'],
            'container' => $_POST['container'],
            'sticky' => isset($_POST['sticky']) ? 1 : 0
        ],
        'rows' => [
            'top_bar' => [
                'visible' => isset($_POST['top_bar_visible']) ? 1 : 0,
                'bg_color' => $_POST['top_bar_bg'],
                'text_color' => $_POST['top_bar_text_color'],
                'text' => $_POST['top_bar_content']
            ],
            'main_header' => [
                'height' => $_POST['header_height'],
                'bg_color' => $_POST['header_bg']
            ]
        ],
        'design' => [
            'typography' => [
                'logo_color' => $_POST['logo_color'],
                'menu_color' => $_POST['menu_color'],
                'menu_hover' => $_POST['menu_hover']
            ],
            'borders' => [
                'bottom_width' => $_POST['border_bottom'],
                'bottom_color' => $_POST['border_color']
            ]
        ]
    ];

    $json = json_encode($settings);

    // Update
    $stmt = $pdo->prepare("UPDATE header_settings SET settings = ? WHERE id = 1");
    $stmt->execute([$json]);
    $success = "Header settings saved successfully!";
}

// Fetch Current Settings
try {
    $curr = $pdo->query("SELECT settings FROM header_settings WHERE id = 1")->fetch();
    $s = $curr && !empty($curr['settings']) ? json_decode($curr['settings'], true) : [];
} catch (PDOException $e) {
    $s = [];
}

?>
<?php include 'includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Header Builder Engine</h1>
        <a href="../home" target="_blank" class="btn btn-outline-primary"><i class="bi bi-eye"></i> View Site</a>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="row">
            <!-- LEFT: Controls -->
            <div class="col-lg-8">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <ul class="nav nav-tabs card-header-tabs" id="builderTabs" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-general"
                                    type="button">General Layout</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-design"
                                    type="button">Design & Style</button>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content">
                            <!-- TAB A: GENERAL -->
                            <div class="tab-pane fade show active" id="tab-general">
                                <h5 class="mb-3">Header Layout</h5>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label">Presets</label>
                                        <div class="border p-3 rounded">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="radio" name="layout"
                                                    value="logo_left" <?php echo val($s, ['general', 'layout']) == 'logo_left' ? 'checked' : ''; ?>>
                                                <label class="form-check-label">Logo Left / Menu Right</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="layout"
                                                    value="logo_center" <?php echo val($s, ['general', 'layout']) == 'logo_center' ? 'checked' : ''; ?>>
                                                <label class="form-check-label">Logo Center / Menu Below</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Header Type</label>
                                        <select class="form-select" name="type">
                                            <option value="standard" <?php echo val($s, ['general', 'type']) == 'standard' ? 'selected' : ''; ?>>Standard</option>
                                            <option value="transparent" <?php echo val($s, ['general', 'type']) == 'transparent' ? 'selected' : ''; ?>>Transparent
                                            </option>
                                        </select>
                                        <div class="form-check mt-3">
                                            <input class="form-check-input" type="checkbox" name="sticky" <?php echo val($s, ['general', 'sticky']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label">Sticky Header</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Container</label>
                                        <select class="form-select" name="container">
                                            <option value="container" <?php echo val($s, ['general', 'container']) == 'container' ? 'selected' : ''; ?>>Boxed
                                            </option>
                                            <option value="container-fluid" <?php echo val($s, ['general', 'container']) == 'container-fluid' ? 'selected' : ''; ?>>Full
                                                Width</option>
                                        </select>
                                    </div>
                                </div>
                                <hr>
                                <h5 class="mb-3">Top Bar</h5>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="top_bar_visible" <?php echo val($s, ['rows', 'top_bar', 'visible']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label">Enable Top Bar</label>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Content</label>
                                    <input type="text" class="form-control" name="top_bar_content"
                                        value="<?php echo htmlspecialchars(val($s, ['rows', 'top_bar', 'text'], 'Call us: +123 456 789')); ?>">
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">BG Color</label>
                                        <input type="color" class="form-control form-control-color w-100"
                                            name="top_bar_bg"
                                            value="<?php echo val($s, ['rows', 'top_bar', 'bg_color'], '#333333'); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Text Color</label>
                                        <input type="color" class="form-control form-control-color w-100"
                                            name="top_bar_text_color"
                                            value="<?php echo val($s, ['rows', 'top_bar', 'text_color'], '#ffffff'); ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- TAB B: DESIGN -->
                            <div class="tab-pane fade" id="tab-design">
                                <h5 class="mb-3">Colors</h5>
                                <div class="row g-3">
                                    <div class="col-md-4"><label>Header BG</label><input type="color"
                                            class="form-control w-100" name="header_bg"
                                            value="<?php echo val($s, ['rows', 'main_header', 'bg_color'], '#ffffff'); ?>">
                                    </div>
                                    <div class="col-md-4"><label>Logo Color</label><input type="color"
                                            class="form-control w-100" name="logo_color"
                                            value="<?php echo val($s, ['design', 'typography', 'logo_color'], '#000000'); ?>">
                                    </div>
                                    <div class="col-md-4"><label>Menu Color</label><input type="color"
                                            class="form-control w-100" name="menu_color"
                                            value="<?php echo val($s, ['design', 'typography', 'menu_color'], '#333333'); ?>">
                                    </div>
                                </div>
                                <h5 class="mt-4 mb-3">Layout</h5>
                                <div class="row g-3">
                                    <div class="col-md-6"><label>Height</label><input type="text" class="form-control"
                                            name="header_height"
                                            value="<?php echo val($s, ['rows', 'main_header', 'height'], '80px'); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-primary px-4">Save Changes</button>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card shadow">
                    <div class="card-body">
                        <p class="text-muted">Changes apply immediately.</p>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
<?php include 'includes/footer.php'; ?>