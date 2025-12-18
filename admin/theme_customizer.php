<?php
require_once '../includes/config.php';
require_once '../classes/Auth.php';
require_once '../classes/ThemeHelper.php';

$auth = new Auth($pdo);
$auth->requireLogin();

// Fetch Settings
$stmt = $pdo->query("SELECT * FROM settings WHERE id = 1");
$settingsRow = $stmt->fetch();
$currentTheme = json_decode($settingsRow['theme_json'] ?? '{}', true);

// Handle Save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $themeData = [
        'colors' => $_POST['colors'],
        'typography' => $_POST['typography'],
        'nav' => $_POST['nav'],
        'footer' => $_POST['footer']
    ];

    // Upload Favicon
    if (!empty($_FILES['favicon']['name'])) {
        $targetDir = "../uploads/";
        if (!is_dir($targetDir))
            mkdir($targetDir, 0755, true);
        $fileName = 'favicon_' . time() . '_' . basename($_FILES['favicon']['name']);
        if (move_uploaded_file($_FILES['favicon']['tmp_name'], $targetDir . $fileName)) {
            $pdo->prepare("UPDATE settings SET favicon = ? WHERE id = 1")->execute([$fileName]);
        }
    }

    // Save JSON
    $pdo->prepare("UPDATE settings SET theme_json = ? WHERE id = 1")->execute([json_encode($themeData)]);

    // Standard Logo Upload logic (existing compatible)
    if (!empty($_FILES['logo']['name'])) {
        $targetDir = "../uploads/";
        if (!is_dir($targetDir))
            mkdir($targetDir, 0755, true);
        $fileName = time() . '_' . basename($_FILES['logo']['name']);
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $targetDir . $fileName)) {
            $pdo->prepare("UPDATE settings SET logo = ? WHERE id = 1")->execute([$fileName]);
        }
    }

    // Refresh
    header("Location: theme_customizer.php?saved=1");
    exit;
}

// Merge defaults for display
$theme = ThemeHelper::getTheme($pdo);

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Global Theme Customizer</h1>
        <a href="../index.php" target="_blank" class="btn btn-primary"><i class="bi bi-eye"></i> View Site</a>
    </div>

    <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-success">Theme settings saved successfully!</div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="row">
            <!-- Branding -->
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header font-weight-bold">Branding & Identity</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Website Logo</label>
                            <?php if (!empty($settingsRow['logo'])): ?>
                                <div class="mb-2"><img src="../uploads/<?php echo $settingsRow['logo']; ?>"
                                        style="height: 50px;"></div>
                            <?php endif; ?>
                            <input type="file" class="form-control" name="logo">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Favicon (Browser Tab Icon)</label>
                            <?php if (!empty($settingsRow['favicon'])): ?>
                                <div class="mb-2"><img src="../uploads/<?php echo $settingsRow['favicon']; ?>"
                                        style="width: 32px;"></div>
                            <?php endif; ?>
                            <input type="file" class="form-control" name="favicon">
                            <small class="text-muted">Recommended: 32x32px PNG or ICO</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Typography -->
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header font-weight-bold">Typography</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Primary Font (Headings)</label>
                                <select class="form-select" name="typography[font_primary]">
                                    <option value="'Inter', sans-serif" <?php echo strpos($theme['typography']['font_primary'], 'Inter') !== false ? 'selected' : ''; ?>>
                                        Inter</option>
                                    <option value="'Roboto', sans-serif" <?php echo strpos($theme['typography']['font_primary'], 'Roboto') !== false ? 'selected' : ''; ?>>Roboto</option>
                                    <option value="'Playfair Display', serif" <?php echo strpos($theme['typography']['font_primary'], 'Playfair') !== false ? 'selected' : ''; ?>>Playfair Display (Serif)</option>
                                    <option value="'Lato', sans-serif" <?php echo strpos($theme['typography']['font_primary'], 'Lato') !== false ? 'selected' : ''; ?>>
                                        Lato</option>
                                </select>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Secondary Font (Body)</label>
                                <select class="form-select" name="typography[font_secondary]">
                                    <option value="'Inter', sans-serif" <?php echo strpos($theme['typography']['font_secondary'], 'Inter') !== false ? 'selected' : ''; ?>>Inter</option>
                                    <option value="'Roboto', sans-serif" <?php echo strpos($theme['typography']['font_secondary'], 'Roboto') !== false ? 'selected' : ''; ?>>Roboto</option>
                                    <option value="'Open Sans', sans-serif" <?php echo strpos($theme['typography']['font_secondary'], 'Open Sans') !== false ? 'selected' : ''; ?>>Open Sans</option>
                                </select>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Base Font Size</label>
                                <input type="text" class="form-control" name="typography[base_size]"
                                    value="<?php echo htmlspecialchars($theme['typography']['base_size']); ?>">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Line Height</label>
                                <input type="text" class="form-control" name="typography[line_height]"
                                    value="<?php echo htmlspecialchars($theme['typography']['line_height']); ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Global Colors -->
            <div class="col-md-12 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header font-weight-bold">Global Color Palette</div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-2 col-4 mb-3">
                                <label class="d-block small text-muted mb-1">Primary</label>
                                <input type="color" class="form-control form-control-color w-100" name="colors[primary]"
                                    value="<?php echo $theme['colors']['primary']; ?>">
                            </div>
                            <div class="col-md-2 col-4 mb-3">
                                <label class="d-block small text-muted mb-1">Secondary</label>
                                <input type="color" class="form-control form-control-color w-100"
                                    name="colors[secondary]" value="<?php echo $theme['colors']['secondary']; ?>">
                            </div>
                            <div class="col-md-2 col-4 mb-3">
                                <label class="d-block small text-muted mb-1">Background</label>
                                <input type="color" class="form-control form-control-color w-100" name="colors[bg_body]"
                                    value="<?php echo $theme['colors']['bg_body']; ?>">
                            </div>
                            <div class="col-md-2 col-4 mb-3">
                                <label class="d-block small text-muted mb-1">Text Color</label>
                                <input type="color" class="form-control form-control-color w-100"
                                    name="colors[text_main]" value="<?php echo $theme['colors']['text_main']; ?>">
                            </div>
                            <div class="col-md-2 col-4 mb-3">
                                <label class="d-block small text-muted mb-1">Surface (Cards)</label>
                                <input type="color" class="form-control form-control-color w-100" name="colors[surface]"
                                    value="<?php echo $theme['colors']['surface']; ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation & Footer -->
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header font-weight-bold">Navigation Bar</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Background Color</label>
                            <input type="color" class="form-control form-control-color w-100" name="nav[bg]"
                                value="<?php echo $theme['nav']['bg']; ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Text Color</label>
                            <input type="color" class="form-control form-control-color w-100" name="nav[text_color]"
                                value="<?php echo $theme['nav']['text_color']; ?>">
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Height</label>
                                <input type="text" class="form-control" name="nav[height]"
                                    value="<?php echo htmlspecialchars($theme['nav']['height']); ?>">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Padding</label>
                                <input type="text" class="form-control" name="nav[padding]"
                                    value="<?php echo htmlspecialchars($theme['nav']['padding']); ?>">
                            </div>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="nav[sticky]" value="1" <?php echo !empty($theme['nav']['sticky']) ? 'checked' : ''; ?>>
                            <label class="form-check-label">Sticky Navigation</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header font-weight-bold">Global Footer</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Background Color</label>
                            <input type="color" class="form-control form-control-color w-100" name="footer[bg]"
                                value="<?php echo $theme['footer']['bg']; ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Text Color</label>
                            <input type="color" class="form-control form-control-color w-100" name="footer[text_color]"
                                value="<?php echo $theme['footer']['text_color']; ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Padding</label>
                            <input type="text" class="form-control" name="footer[padding]"
                                value="<?php echo htmlspecialchars($theme['footer']['padding']); ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="fixed-bottom bg-white border-top p-3 text-center shadow-lg" style="margin-left: 260px;">
            <!-- Adjust margin for sidebar -->
            <button type="submit" class="btn btn-success btn-lg px-5">Publish Global Changes</button>
        </div>
        <div style="height: 80px;"></div> <!-- Spacer for fixed bottom -->
    </form>
</div>

<?php include 'includes/footer.php'; ?>