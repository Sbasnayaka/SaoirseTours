<?php
require_once '../includes/config.php';
require_once '../classes/Auth.php';
require_once '../classes/ThemeHelper.php';

$auth = new Auth($pdo);
$auth->requireLogin();

$message = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Gather Basic Data
    $site_title = $_POST['site_title'];
    $tagline = $_POST['tagline'];
    $contact_email = $_POST['contact_email'];

    // 2. Gather Theme Data (Deep Merge)
    $stmt = $pdo->query("SELECT * FROM settings WHERE id = 1");
    $currentSettings = $stmt->fetch();
    $themeData = json_decode($currentSettings['theme_json'] ?? '{}', true);

    // Merge Posted Theme Data
    $themeData['colors'] = $_POST['colors'];
    $themeData['typography'] = $_POST['typography'];
    $themeData['nav'] = $_POST['nav'];
    $themeData['footer'] = $_POST['footer'];

    // NEW: General Section in JSON
    $themeData['general']['branding_type'] = $_POST['branding_type'] ?? 'image';

    // 3. Handle Files
    // Logo
    $logoPath = $currentSettings['logo'] ?? null;
    if (!empty($_FILES['logo']['name'])) {
        $targetDir = "../uploads/";
        if (!is_dir($targetDir))
            mkdir($targetDir, 0755, true);
        $fileName = time() . '_' . basename($_FILES['logo']['name']);
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $targetDir . $fileName)) {
            $logoPath = $fileName;
        }
    }

    // Favicon
    $faviconPath = $currentSettings['favicon'] ?? null;
    if (!empty($_FILES['favicon']['name'])) {
        $targetDir = "../uploads/";
        if (!is_dir($targetDir))
            mkdir($targetDir, 0755, true);
        $fileName = 'favicon_' . time() . '_' . basename($_FILES['favicon']['name']);
        if (move_uploaded_file($_FILES['favicon']['tmp_name'], $targetDir . $fileName)) {
            $faviconPath = $fileName;
        }
    }

    // 4. Update Database
    // We update both legacy columns (for simple access) and JSON (for engine)
    $sql = "UPDATE settings SET 
            site_title = :site_title, 
            tagline = :tagline,
            contact_email = :contact_email,
            bg_color = :bg_color,
            primary_color = :primary_color,
            font_family = :font_family,
            logo = :logo,
            favicon = :favicon,
            theme_json = :theme_json
            WHERE id = 1";

    $params = [
        ':site_title' => $site_title,
        ':tagline' => $tagline,
        ':contact_email' => $contact_email,
        ':bg_color' => $themeData['colors']['bg_body'],        // Sync Legacy
        ':primary_color' => $themeData['colors']['primary'],    // Sync Legacy
        ':font_family' => $themeData['typography']['font_primary'], // Sync Legacy
        ':logo' => $logoPath,
        ':favicon' => $faviconPath,
        ':theme_json' => json_encode($themeData)
    ];

    $stmt = $pdo->prepare($sql);

    if ($stmt->execute($params)) {
        $message = '<div class="alert alert-success">Settings & Design updated successfully!</div>';
    } else {
        $message = '<div class="alert alert-danger">Error updating settings.</div>';
    }
}

// Fetch Current Settings
$stmt = $pdo->query("SELECT * FROM settings WHERE id = 1");
$settings = $stmt->fetch();
$theme = ThemeHelper::getTheme($pdo); // Uses Helper to get merged structure
$savedTheme = json_decode($settings['theme_json'] ?? '{}', true); // Raw saved data to access 'branding_type' which might not be in helper default
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid mb-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>System Settings & Design</h2>
        <a href="../index.php" target="_blank" class="btn btn-outline-primary"><i class="bi bi-eye"></i> View Site</a>
    </div>

    <?php echo $message; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="card shadow">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" id="settingsTab" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general"
                            type="button">General</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="branding-tab" data-bs-toggle="tab" data-bs-target="#branding"
                            type="button">Header & Branding</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="design-tab" data-bs-toggle="tab" data-bs-target="#design"
                            type="button">Colors & Fonts</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="layout-tab" data-bs-toggle="tab" data-bs-target="#layout"
                            type="button">Layout & Footer</button>
                    </li>
                </ul>
            </div>

            <div class="card-body">
                <div class="tab-content" id="settingsTabContent">

                    <!-- TAB 1: GENERAL -->
                    <div class="tab-pane fade show active" id="general" role="tabpanel">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Site Title</label>
                                <input type="text" class="form-control" name="site_title"
                                    value="<?php echo htmlspecialchars($settings['site_title']); ?>" required>
                                <small class="text-muted">Used for SEO and if no Logo is uploaded.</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tagline</label>
                                <input type="text" class="form-control" name="tagline"
                                    value="<?php echo htmlspecialchars($settings['tagline']); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Admin/Contact Email</label>
                                <input type="email" class="form-control" name="contact_email"
                                    value="<?php echo htmlspecialchars($settings['contact_email']); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: HEADER & BRANDING -->
                    <div class="tab-pane fade" id="branding" role="tabpanel">
                        <div class="row">
                            <div class="col-md-12 mb-4">
                                <label class="form-label fw-bold">Branding Mode</label>
                                <div class="d-flex gap-4 p-3 border rounded bg-light">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="branding_type" value="image"
                                            <?php echo ($savedTheme['general']['branding_type'] ?? 'image') == 'image' ? 'checked' : ''; ?>>
                                        <label class="form-check-label">Use Image Logo</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="branding_type" value="text"
                                            <?php echo ($savedTheme['general']['branding_type'] ?? '') == 'text' ? 'checked' : ''; ?>>
                                        <label class="form-check-label">Use Site Title (Text Logo)</label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Website Logo (for Image Mode)</label>
                                <?php if (!empty($settings['logo'])): ?>
                                    <div class="mb-2"><img
                                            src="../uploads/<?php echo htmlspecialchars($settings['logo']); ?>"
                                            style="max-height: 60px;"></div>
                                <?php endif; ?>
                                <input type="file" class="form-control" name="logo" accept="image/*">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Favicon (Browser Tab Icon)</label>
                                <?php if (!empty($settings['favicon'])): ?>
                                    <div class="mb-2"><img
                                            src="../uploads/<?php echo htmlspecialchars($settings['favicon']); ?>"
                                            style="width: 32px;"></div>
                                <?php endif; ?>
                                <input type="file" class="form-control" name="favicon" accept="image/*">
                            </div>

                            <hr>
                            <h5 class="mt-3">Navigation Style</h5>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Background Color</label>
                                <input type="color" class="form-control form-control-color w-100" name="nav[bg]"
                                    value="<?php echo $theme['nav']['bg']; ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Link Color</label>
                                <input type="color" class="form-control form-control-color w-100" name="nav[text_color]"
                                    value="<?php echo $theme['nav']['text_color']; ?>">
                            </div>
                            <div class="col-md-4 mb-3 pt-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="nav[sticky]" value="1" <?php echo !empty($theme['nav']['sticky']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label">Sticky Header</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: COLORS & FONTS -->
                    <div class="tab-pane fade" id="design" role="tabpanel">
                        <h5 class="mb-3">Global Color Palette</h5>
                        <div class="row text-center mb-4">
                            <div class="col-md-2 col-4 mb-3">
                                <label class="small text-muted">Primary</label>
                                <input type="color" class="form-control form-control-color w-100 mb-2"
                                    name="colors[primary]" value="<?php echo $theme['colors']['primary']; ?>">
                            </div>
                            <div class="col-md-2 col-4 mb-3">
                                <label class="small text-muted">Secondary</label>
                                <input type="color" class="form-control form-control-color w-100 mb-2"
                                    name="colors[secondary]" value="<?php echo $theme['colors']['secondary']; ?>">
                            </div>
                            <div class="col-md-2 col-4 mb-3">
                                <label class="small text-muted">Page BG</label>
                                <input type="color" class="form-control form-control-color w-100 mb-2"
                                    name="colors[bg_body]" value="<?php echo $theme['colors']['bg_body']; ?>">
                            </div>
                        </div>

                        <h5 class="mb-3">Typography</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Primary Font (Headings)</label>
                                <select class="form-select" name="typography[font_primary]">
                                    <option value="'Inter', sans-serif" <?php echo strpos($theme['typography']['font_primary'], 'Inter') !== false ? 'selected' : ''; ?>>Inter</option>
                                    <option value="'Roboto', sans-serif" <?php echo strpos($theme['typography']['font_primary'], 'Roboto') !== false ? 'selected' : ''; ?>>Roboto</option>
                                    <option value="'Playfair Display', serif" <?php echo strpos($theme['typography']['font_primary'], 'Playfair') !== false ? 'selected' : ''; ?>>Playfair (Elegant)</option>
                                    <option value="'Lato', sans-serif" <?php echo strpos($theme['typography']['font_primary'], 'Lato') !== false ? 'selected' : ''; ?>>Lato</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Body Font</label>
                                <select class="form-select" name="typography[font_secondary]">
                                    <option value="'Inter', sans-serif" <?php echo strpos($theme['typography']['font_secondary'], 'Inter') !== false ? 'selected' : ''; ?>>Inter</option>
                                    <option value="'Roboto', sans-serif" <?php echo strpos($theme['typography']['font_secondary'], 'Roboto') !== false ? 'selected' : ''; ?>>Roboto</option>
                                    <option value="'Open Sans', sans-serif" <?php echo strpos($theme['typography']['font_secondary'], 'Open Sans') !== false ? 'selected' : ''; ?>>Open Sans</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 4: LAYOUT & FOOTER -->
                    <div class="tab-pane fade" id="layout" role="tabpanel">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Footer Design</h5>
                                <div class="mb-3">
                                    <label class="form-label">Background Color</label>
                                    <input type="color" class="form-control form-control-color w-100" name="footer[bg]"
                                        value="<?php echo $theme['footer']['bg']; ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Text Color</label>
                                    <input type="color" class="form-control form-control-color w-100"
                                        name="footer[text_color]" value="<?php echo $theme['footer']['text_color']; ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Padding (Vertical)</label>
                                    <input type="text" class="form-control" name="footer[padding]"
                                        value="<?php echo htmlspecialchars($theme['footer']['padding']); ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                </div> <!-- End Tab Content -->
            </div>

            <div class="card-footer text-end">
                <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-save"></i> Save System
                    Changes</button>
            </div>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>