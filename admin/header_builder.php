<?php
// Enable Error Reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start Session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// ---------------------------------------------------------
// DATABASE CONNECTION SETUP
// ---------------------------------------------------------
// Attempt to load standard config first
if (file_exists(__DIR__ . '/../includes/config.php')) {
    require_once __DIR__ . '/../includes/config.php';
}

// Global Validation Helper (defined early)
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

// Check Connection (Fallback to manual if needed/config broke)
if (!isset($pdo)) {
    $host = 'localhost';
    $dbname = 'tourism_cms';
    $username = 'root';
    $password = '';
    $port = 3307;

    try {
        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8";
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // Define BASE_URL if missing
        if (!defined('BASE_URL')) {
            define('BASE_URL', 'http://localhost/SaoirseTours/');
        }
    } catch (PDOException $e) {
        die("<h3>Database Connection Error</h3><p>" . $e->getMessage() . "</p>");
    }
}

// Auth Check (Standardized)
// If Auth class is available, use it. Otherwise manual check.
if (file_exists(__DIR__ . '/../classes/Auth.php')) {
    require_once __DIR__ . '/../classes/Auth.php';
    $auth = new Auth($pdo);
    $auth->requireLogin();
} else {
    // Fallback Auth
    if (!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}

// Initialize Success Message
$success = "";

// Handle Save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'general' => [
            'layout' => $_POST['layout'] ?? 'logo_left',
            'type' => $_POST['type'] ?? 'standard',
            'container' => $_POST['container'] ?? 'container',
            'sticky' => isset($_POST['sticky']) ? 1 : 0
        ],
        'rows' => [
            'top_bar' => [
                'visible' => isset($_POST['top_bar_visible']) ? 1 : 0,
                'bg_color' => $_POST['top_bar_bg'] ?? '#3a4c40',
                'text_color' => $_POST['top_bar_text_color'] ?? '#ffffff',
                'text' => $_POST['top_bar_content'] ?? ''
            ],
            'main_header' => [
                'height' => $_POST['header_height'] ?? '80px',
                'bg_color' => $_POST['header_bg'] ?? '#ffffff'
            ]
        ],
        'design' => [
            // User requested to NOT mix logo styles here as they are in theme_customizer.
            // Keeping menu colors as they are specific to the header instance.
            'typography' => [
                'menu_color' => $_POST['menu_color'] ?? '#333333',
                'menu_hover' => $_POST['menu_hover'] ?? '#000000'
            ],
            'borders' => [
                'bottom_width' => $_POST['border_bottom'] ?? '1px',
                'bottom_color' => $_POST['border_color'] ?? '#e0e0e0'
            ]
        ],
        // Tab C: Advanced Menu Styler
        'navigation' => [
            'typography' => [
                'font_family' => $_POST['nav_font_family'] ?? 'inherit',
                'font_weight' => $_POST['nav_font_weight'] ?? '500',
                'text_transform' => $_POST['nav_text_transform'] ?? 'none',
                'font_size' => $_POST['nav_font_size'] ?? '16',
                'item_spacing' => $_POST['nav_item_spacing'] ?? '15'
            ],
            'colors' => [
                'link_color' => $_POST['nav_link_color'] ?? '#333333',
                'link_hover_color' => $_POST['nav_hover_color'] ?? '#486856',
                'link_active_color' => $_POST['nav_active_color'] ?? '#486856',
                'dropdown_bg' => $_POST['nav_dd_bg'] ?? '#ffffff'
            ],
            'hover_effect' => [
                'style' => $_POST['nav_hover_style'] ?? 'none'
            ],
            'dropdown' => [
                'width' => $_POST['nav_dd_width'] ?? '220',
                'dividers' => isset($_POST['nav_dd_dividers']) ? 1 : 0
            ],
            'mobile' => [
                'toggle_icon' => $_POST['nav_mobile_icon'] ?? 'bi-list',
                'link_color' => $_POST['nav_mobile_link_color'] ?? '#333333'
            ]
        ]
    ];

    $json = json_encode($settings);

    // Self-Healing Table Check
    try {
        $check = $pdo->query("SELECT id FROM header_settings LIMIT 1");
    } catch (PDOException $e) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `header_settings` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `is_active` tinyint(1) DEFAULT 1,
          `settings` longtext, 
          `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        $pdo->exec("INSERT INTO header_settings (id, settings) VALUES (1, '$json')");
    }

    // Upsert
    $existing = $pdo->query("SELECT id FROM header_settings WHERE id = 1")->fetch();
    if ($existing) {
        $stmt = $pdo->prepare("UPDATE header_settings SET settings = ? WHERE id = 1");
        $stmt->execute([$json]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO header_settings (id, settings) VALUES (1, ?)");
        $stmt->execute([$json]);
    }
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
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-menu-style"
                                    type="button"><i class="bi bi-star"></i> Menu Styler</button>
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
                                    <div class="col-md-6"><label>Header BG</label><input type="color"
                                            class="form-control w-100" name="header_bg"
                                            value="<?php echo val($s, ['rows', 'main_header', 'bg_color'], '#ffffff'); ?>">
                                    </div>
                                    <div class="col-md-3"><label>Menu Color</label><input type="color"
                                            class="form-control w-100" name="menu_color"
                                            value="<?php echo val($s, ['design', 'typography', 'menu_color'], '#333333'); ?>">
                                    </div>
                                    <div class="col-md-3"><label>Menu Hover</label><input type="color"
                                            class="form-control w-100" name="menu_hover"
                                            value="<?php echo val($s, ['design', 'typography', 'menu_hover'], '#000000'); ?>">
                                    </div>
                                </div>
                                <h5 class="mt-4 mb-3">Layout & Borders</h5>
                                <div class="row g-3">
                                    <div class="col-md-6"><label>Height</label><input type="text" class="form-control"
                                            name="header_height"
                                            value="<?php echo val($s, ['rows', 'main_header', 'height'], '80px'); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Bottom Border</label>
                                        <select class="form-select" name="border_bottom">
                                            <option value="0px" <?php echo val($s, ['design', 'borders', 'bottom_width']) == '0px' ? 'selected' : ''; ?>>None
                                            </option>
                                            <option value="1px" <?php echo val($s, ['design', 'borders', 'bottom_width']) == '1px' ? 'selected' : ''; ?>>Thin
                                                (1px)</option>
                                            <option value="2px" <?php echo val($s, ['design', 'borders', 'bottom_width']) == '2px' ? 'selected' : ''; ?>>Thick
                                                (2px)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Border Color</label>
                                        <input type="color" class="form-control form-control-color w-100"
                                            name="border_color"
                                            value="<?php echo val($s, ['design', 'borders', 'bottom_color'], '#eeeeee'); ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- TAB C: MENU STYLER -->
                            <div class="tab-pane fade" id="tab-menu-style">
                                <!-- A. Typography & Spacing -->
                                <h5 class="mb-3 text-primary"><i class="bi bi-fonts"></i> Typography & Spacing</h5>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-4">
                                        <label class="form-label">Font Family</label>
                                        <select class="form-select" name="nav_font_family">
                                            <option value="inherit" <?php echo val($s, ['navigation', 'typography', 'font_family']) == 'inherit' ? 'selected' : ''; ?>>Inherit (Theme)
                                            </option>
                                            <option value="'Inter', sans-serif" <?php echo val($s, ['navigation', 'typography', 'font_family']) == "'Inter', sans-serif" ? 'selected' : ''; ?>>Inter (Modern)</option>
                                            <option value="'Roboto', sans-serif" <?php echo val($s, ['navigation', 'typography', 'font_family']) == "'Roboto', sans-serif" ? 'selected' : ''; ?>>Roboto</option>
                                            <option value="'Playfair Display', serif" <?php echo val($s, ['navigation', 'typography', 'font_family']) == "'Playfair Display', serif" ? 'selected' : ''; ?>>Playfair Display (Serif)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Weight</label>
                                        <select class="form-select" name="nav_font_weight">
                                            <option value="400" <?php echo val($s, ['navigation', 'typography', 'font_weight']) == '400' ? 'selected' : ''; ?>>Normal (400)</option>
                                            <option value="500" <?php echo val($s, ['navigation', 'typography', 'font_weight']) == '500' ? 'selected' : ''; ?>>Medium (500)</option>
                                            <option value="600" <?php echo val($s, ['navigation', 'typography', 'font_weight']) == '600' ? 'selected' : ''; ?>>SemiBold (600)</option>
                                            <option value="700" <?php echo val($s, ['navigation', 'typography', 'font_weight']) == '700' ? 'selected' : ''; ?>>Bold (700)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Transform</label>
                                        <select class="form-select" name="nav_text_transform">
                                            <option value="none" <?php echo val($s, ['navigation', 'typography', 'text_transform']) == 'none' ? 'selected' : ''; ?>>None</option>
                                            <option value="uppercase" <?php echo val($s, ['navigation', 'typography', 'text_transform']) == 'uppercase' ? 'selected' : ''; ?>>UPPERCASE</option>
                                            <option value="capitalize" <?php echo val($s, ['navigation', 'typography', 'text_transform']) == 'capitalize' ? 'selected' : ''; ?>>Capitalize
                                            </option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Size (px)</label>
                                        <input type="number" class="form-control" name="nav_font_size"
                                            value="<?php echo val($s, ['navigation', 'typography', 'font_size'], '16'); ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Spacing (px)</label>
                                        <input type="number" class="form-control" name="nav_item_spacing"
                                            value="<?php echo val($s, ['navigation', 'typography', 'item_spacing'], '15'); ?>"
                                            title="Left/Right Padding">
                                    </div>
                                </div>

                                <hr>

                                <!-- B. Interaction & Colors -->
                                <h5 class="mb-3 text-primary"><i class="bi bi-mouse"></i> Interaction & Colors</h5>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-3">
                                        <label class="form-label">Link Color</label>
                                        <input type="color" class="form-control form-control-color w-100"
                                            name="nav_link_color"
                                            value="<?php echo val($s, ['navigation', 'colors', 'link_color'], '#333333'); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Hover Color</label>
                                        <input type="color" class="form-control form-control-color w-100"
                                            name="nav_hover_color"
                                            value="<?php echo val($s, ['navigation', 'colors', 'link_hover_color'], '#486856'); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Active Color</label>
                                        <input type="color" class="form-control form-control-color w-100"
                                            name="nav_active_color"
                                            value="<?php echo val($s, ['navigation', 'colors', 'link_active_color'], '#486856'); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Hover Effect</label>
                                        <select class="form-select" name="nav_hover_style">
                                            <option value="none" <?php echo val($s, ['navigation', 'hover_effect', 'style']) == 'none' ? 'selected' : ''; ?>>None (Color Only)</option>
                                            <option value="underline" <?php echo val($s, ['navigation', 'hover_effect', 'style']) == 'underline' ? 'selected' : ''; ?>>Underline</option>
                                            <option value="overline" <?php echo val($s, ['navigation', 'hover_effect', 'style']) == 'overline' ? 'selected' : ''; ?>>Overline</option>
                                            <option value="framed" <?php echo val($s, ['navigation', 'hover_effect', 'style']) == 'framed' ? 'selected' : ''; ?>>Framed Box</option>
                                        </select>
                                    </div>
                                </div>

                                <hr>

                                <div class="row">
                                    <!-- C. Dropdown Styling -->
                                    <div class="col-md-6 border-end">
                                        <h5 class="mb-3 text-primary"><i class="bi bi-menu-button"></i> Dropdown Menu
                                        </h5>
                                        <div class="mb-3">
                                            <label class="form-label">Background Color</label>
                                            <input type="color" class="form-control form-control-color w-100"
                                                name="nav_dd_bg"
                                                value="<?php echo val($s, ['navigation', 'colors', 'dropdown_bg'], '#ffffff'); ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Dropdown Width (px)</label>
                                            <input type="number" class="form-control" name="nav_dd_width"
                                                value="<?php echo val($s, ['navigation', 'dropdown', 'width'], '220'); ?>">
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="nav_dd_dividers" <?php echo val($s, ['navigation', 'dropdown', 'dividers']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label">Show Divider Lines</label>
                                        </div>
                                    </div>

                                    <!-- D. Mobile Menu -->
                                    <div class="col-md-6 ps-4">
                                        <h5 class="mb-3 text-primary"><i class="bi bi-phone"></i> Mobile Menu</h5>
                                        <div class="mb-3">
                                            <label class="form-label">Toggle Icon</label>
                                            <select class="form-select" name="nav_mobile_icon">
                                                <option value="bi-list" <?php echo val($s, ['navigation', 'mobile', 'toggle_icon']) == 'bi-list' ? 'selected' : ''; ?>>Bars (Standard)
                                                </option>
                                                <option value="bi-grid" <?php echo val($s, ['navigation', 'mobile', 'toggle_icon']) == 'bi-grid' ? 'selected' : ''; ?>>Grid</option>
                                                <option value="bi-three-dots" <?php echo val($s, ['navigation', 'mobile', 'toggle_icon']) == 'bi-three-dots' ? 'selected' : ''; ?>>
                                                    Dots</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Mobile Link Color</label>
                                            <input type="color" class="form-control form-control-color w-100"
                                                name="nav_mobile_link_color"
                                                value="<?php echo val($s, ['navigation', 'mobile', 'link_color'], '#333333'); ?>">
                                        </div>
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
                        <hr>
                        <div class="alert alert-info small">
                            <strong>Note:</strong> Logo and Font details are managed in <a
                                href="theme_customizer.php">Global Theme Customizer</a>.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
<?php include 'includes/footer.php'; ?>