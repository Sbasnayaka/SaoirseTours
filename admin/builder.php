<?php
require_once '../includes/config.php';
require_once '../classes/Auth.php';

$auth = new Auth($pdo);
$auth->requireLogin();

if (!isset($_GET['section_id']))
    die("Section ID required.");
$section_id = $_GET['section_id'];

// Fetch Section
$stmt = $pdo->prepare("SELECT * FROM sections WHERE id = ?");
$stmt->execute([$section_id]);
$section = $stmt->fetch();
if (!$section)
    die("Section not found.");

// Decode Settings
$adv = json_decode($section['section_settings'] ?? '{}', true);

// --- HANDLE POST ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Add/Update/Delete Elements... (Same as before)
    if (isset($_POST['add_element'])) {
        $type = $_POST['type'];
        $content = 'New Item';
        $s = ['margin_bottom' => '20px'];
        if ($type == 'heading') {
            $content = 'New Heading';
            $s['tag'] = 'h2';
        }
        if ($type == 'text') {
            $content = '<p>Start writing...</p>';
        }
        if ($type == 'button') {
            $content = 'Click Here';
            $s['style'] = 'btn-primary';
        }
        if ($type == 'spacer') {
            $content = '';
            $s['height'] = '50px';
        }
        $pdo->prepare("INSERT INTO section_elements (section_id, type, content, settings, display_order) VALUES (?, ?, ?, ?, 99)")->execute([$section_id, $type, $content, json_encode($s)]);
    }

    if (isset($_POST['update_element'])) {
        $id = $_POST['element_id'];
        $content = $_POST['content'];
        $s = $_POST['settings'] ?? [];
        if (!empty($_FILES['el_image']['name'])) {
            $fn = time() . '_el_' . basename($_FILES['el_image']['name']);
            move_uploaded_file($_FILES['el_image']['tmp_name'], "../uploads/" . $fn);
            $content = $fn;
        }
        if (!empty($_FILES['card_image']['name'])) {
            $fn = time() . '_card_' . basename($_FILES['card_image']['name']);
            move_uploaded_file($_FILES['card_image']['tmp_name'], "../uploads/" . $fn);
            $s['card_image'] = $fn;
        } elseif (isset($_POST['existing_card_image']))
            $s['card_image'] = $_POST['existing_card_image'];

        $pdo->prepare("UPDATE section_elements SET content = ?, settings = ? WHERE id = ?")->execute([$content, json_encode($s), $id]);
    }

    if (isset($_POST['delete_element'])) {
        $pdo->prepare("DELETE FROM section_elements WHERE id = ?")->execute([$_POST['element_id']]);
    }

    // 4. Update ADVANCED Section Styling
    if (isset($_POST['update_section_advanced'])) {
        // Collect existing or upload new BG
        $bgImg = $section['image'];
        if (!empty($_FILES['bg_image']['name'])) {
            $bgImg = time() . '_bg_' . basename($_FILES['bg_image']['name']);
            move_uploaded_file($_FILES['bg_image']['tmp_name'], "../uploads/" . $bgImg);
            // Update legacy column for backward compat
            $pdo->prepare("UPDATE sections SET image = ? WHERE id = ?")->execute([$bgImg, $section_id]);
        }

        // Construct JSON Blob
        $newAdv = [
            'layout' => $_POST['layout'], // width, min_height
            'background' => array_merge($_POST['background'], ['image' => $bgImg]), // type, color, blur, overlay
            'spacing' => $_POST['spacing'], // padding, margin
            'border' => $_POST['border'], // style, radius
            'typography' => $_POST['typography'] ?? [], // color
            'dividers' => $_POST['dividers'] ?? []
        ];

        // Update with robust error handling for legacy columns
        // We only primarily update section_settings now.
        // Legacy columns are kept for minor backward compatibility but not critical if missing.
        $sql = "UPDATE sections SET section_settings=?, bg_color=?, bg_type=? WHERE id=?";
        $pdo->prepare($sql)->execute([
            json_encode($newAdv),
            $newAdv['background']['color'],
            $newAdv['background']['type'],
            $section_id
        ]);

        header("Location: builder.php?section_id=$section_id");
        exit;
    }

    header("Location: builder.php?section_id=$section_id");
    exit;
}

$elements = $pdo->prepare("SELECT * FROM section_elements WHERE section_id = ? ORDER BY display_order ASC");
$elements->execute([$section_id]);
$elements = $elements->fetchAll();

include 'includes/header.php';
?>

<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3 bg-white p-3 rounded shadow-sm">
        <h4 class="mb-0">Builder: <?php echo htmlspecialchars($section['title']); ?></h4>
        <div class="d-flex gap-2">
            <button class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#advancedSettingsModalV2"><i
                    class="bi bi-sliders"></i> Advanced Settings</button>
            <a href="../index.php" target="_blank" class="btn btn-success btn-sm">Preview</a>
        </div>
    </div>

    <!-- Canvas (Same as before) -->
    <div class="row">
        <div class="col-lg-9">
            <div class="builder-canvas bg-light p-4 min-vh-100 rounded border">
                <?php foreach ($elements as $el):
                    $s = json_decode($el['settings'], true) ?? []; ?>
                    <div class="card mb-3 shadow-sm element-card border-0">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <span class="badge bg-primary rounded-pill"><?php echo ucfirst($el['type']); ?></span>
                            <div>
                                <button class="btn btn-sm btn-light text-primary" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#edit-<?php echo $el['id']; ?>"><i
                                        class="bi bi-pencil-fill"></i></button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete?');">
                                    <input type="hidden" name="delete_element" value="1"><input type="hidden"
                                        name="element_id" value="<?php echo $el['id']; ?>"><button
                                        class="btn btn-sm btn-light text-danger"><i class="bi bi-trash-fill"></i></button>
                                </form>
                            </div>
                        </div>
                        <div class="collapse" id="edit-<?php echo $el['id']; ?>">
                            <!-- Element Editor Content (Keeping previous logic simpler here for brevity, assume populated) -->
                            <div class="card-body bg-secondary bg-opacity-10 border-bottom">
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="update_element" value="1"><input type="hidden"
                                        name="element_id" value="<?php echo $el['id']; ?>">
                                    <?php if ($el['type'] == 'text'): ?><textarea class="form-control ckeditor-lite"
                                            name="content"><?php echo htmlspecialchars($el['content']); ?></textarea>
                                    <?php else: ?><input type="text" class="form-control" name="content"
                                            value="<?php echo htmlspecialchars($el['content']); ?>"><?php endif; ?>
                                    <button class="btn btn-primary btn-sm mt-2">Save</button>
                                </form>
                            </div>
                        </div>
                        <div class="card-body p-3 small text-muted">
                            <?php echo strip_tags(substr($el['content'], 0, 100)); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <!-- Sidebar -->
        <div class="col-lg-3">
            <!-- Tools List (Same as before) -->
            <div class="card shadow-sm">
                <div class="card-body p-2">
                    <div class="row g-2">
                        <?php foreach (['heading', 'text', 'image', 'button', 'card', 'video', 'spacer', 'divider'] as $t): ?>
                            <div class="col-6">
                                <form method="POST"><input type="hidden" name="add_element" value="1"><input type="hidden"
                                        name="type" value="<?php echo $t; ?>"><button
                                        class="btn btn-outline-secondary w-100 py-2"><?php echo ucfirst($t); ?></button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ADVANCED SETTINGS MODAL -->
<div class="modal fade" id="advancedSettingsModalV2" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <form method="POST" class="modal-content" enctype="multipart/form-data">
            <input type="hidden" name="update_section_advanced" value="1">
            <div class="modal-header">
                <h5 class="modal-title">Advanced Section Settings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="d-flex align-items-start">
                    <div class="nav flex-column nav-pills me-3 bg-light h-100 p-3" style="min-width: 200px;"
                        role="tablist">
                        <button class="nav-link active text-start" data-bs-toggle="pill" data-bs-target="#tab-layout"
                            type="button">Layout</button>
                        <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#tab-bg"
                            type="button">Background & Overlay</button>
                        <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#tab-spacing"
                            type="button">Spacing</button>
                        <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#tab-border"
                            type="button">Borders & Dividers</button>
                        <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#tab-typography"
                            type="button">Typography</button>
                    </div>
                    <div class="tab-content w-100 p-3">

                        <!-- LAYOUT -->
                        <div class="tab-pane fade show active" id="tab-layout">
                            <h6>Layout & Dimensions</h6>
                            <div class="mb-3">
                                <label>Container Width</label>
                                <select class="form-select" name="layout[width]">
                                    <option value="boxed" <?php echo ($adv['layout']['width'] ?? '') == 'boxed' ? 'selected' : ''; ?>>Boxed (Default)
                                    </option>
                                    <option value="full" <?php echo ($adv['layout']['width'] ?? '') == 'full' ? 'selected' : ''; ?>>Full Width (Fluid)</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label>Min Height</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="layout[min_height]"
                                        value="<?php echo $adv['layout']['min_height'] ?? 'auto'; ?>">
                                    <span class="input-group-text text-muted">e.g., 500px, 100vh</span>
                                </div>
                            </div>
                        </div>

                        <!-- BACKGROUND -->
                        <div class="tab-pane fade" id="tab-bg">
                            <h6>Background Source</h6>
                            <div class="mb-3">
                                <select class="form-select" name="background[type]" onchange="toggleBgType(this.value)">
                                    <option value="color" <?php echo ($adv['background']['type'] ?? '') == 'color' ? 'selected' : ''; ?>>Solid Color</option>
                                    <option value="image" <?php echo ($adv['background']['type'] ?? '') == 'image' ? 'selected' : ''; ?>>Image</option>
                                    <option value="gradient" <?php echo ($adv['background']['type'] ?? '') == 'gradient' ? 'selected' : ''; ?>>Gradient</option>
                                </select>
                            </div>
                            <!-- Color Fields -->
                            <div class="bg-section-field row g-2" id="bg-color-field">
                                <div class="col-6">
                                    <label>Bg Color</label>
                                    <input type="color" class="form-control form-control-color w-100"
                                        name="background[color]"
                                        value="<?php echo $adv['background']['color'] ?? '#ffffff'; ?>">
                                </div>
                                <div class="col-6 d-flex align-items-end">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="background[transparent]"
                                            value="1" <?php echo ($adv['background']['transparent'] ?? 0) == 1 ? 'checked' : ''; ?>>
                                        <label class="form-check-label">Transparent</label>
                                    </div>
                                </div>
                            </div>
                            <!-- Image Fields -->
                            <div class="bg-section-field d-none" id="bg-image-field">
                                <label>Bg Image</label>
                                <input type="file" class="form-control mb-2" name="bg_image">
                                <?php if ($section['image']): ?>
                                    <div class="small">Current: <?php echo $section['image']; ?></div><?php endif; ?>
                            </div>
                            <!-- Gradient Fields -->
                            <div class="bg-section-field d-none" id="bg-gradient-field">
                                <label>CSS Gradient</label>
                                <input type="text" class="form-control" name="background[gradient]"
                                    value="<?php echo $adv['background']['gradient'] ?? ''; ?>"
                                    placeholder="linear-gradient(...)">
                            </div>

                            <hr>
                            <h6>Effects & Overlay</h6>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label>Overlay Color</label>
                                    <input type="color" class="form-control form-control-color w-100"
                                        name="background[overlay_color]"
                                        value="<?php echo $adv['background']['overlay_color'] ?? '#000000'; ?>">
                                </div>
                                <div class="col-6">
                                    <label>Overlay Opacity (0-1) </label>
                                    <input type="number" step="0.1" min="0" max="1" class="form-control"
                                        name="background[overlay_opacity]"
                                        value="<?php echo $adv['background']['overlay_opacity'] ?? '0'; ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label>Blur Effect</label>
                                <div class="input-group">
                                    <select class="form-select" name="background[blur_mode]">
                                        <option value="none" <?php echo ($adv['background']['blur_mode'] ?? '') == 'none' ? 'selected' : ''; ?>>No Blur
                                        </option>
                                        <option value="backdrop" <?php echo ($adv['background']['blur_mode'] ?? '') == 'backdrop' ? 'selected' : ''; ?>>Backdrop
                                            Blur (Glassmorphism)</option>
                                        <option value="full" <?php echo ($adv['background']['blur_mode'] ?? '') == 'full' ? 'selected' : ''; ?>>Blur Image
                                        </option>
                                    </select>
                                    <input type="text" class="form-control" name="background[blur_px]"
                                        value="<?php echo $adv['background']['blur_px'] ?? '10px'; ?>"
                                        placeholder="10px">
                                </div>
                            </div>
                        </div>

                        <!-- SPACING -->
                        <div class="tab-pane fade" id="tab-spacing">
                            <h6>Padding (Inside)</h6>
                            <div class="row g-2 mb-3">
                                <div class="col-3"><label class="small">Top</label><input type="text"
                                        class="form-control" name="spacing[padding_top]"
                                        value="<?php echo $adv['spacing']['padding_top'] ?? '50px'; ?>"></div>
                                <div class="col-3"><label class="small">Bottom</label><input type="text"
                                        class="form-control" name="spacing[padding_bottom]"
                                        value="<?php echo $adv['spacing']['padding_bottom'] ?? '50px'; ?>"></div>
                                <div class="col-3"><label class="small">Left</label><input type="text"
                                        class="form-control" name="spacing[padding_left]"
                                        value="<?php echo $adv['spacing']['padding_left'] ?? '0px'; ?>"></div>
                                <div class="col-3"><label class="small">Right</label><input type="text"
                                        class="form-control" name="spacing[padding_right]"
                                        value="<?php echo $adv['spacing']['padding_right'] ?? '0px'; ?>"></div>
                            </div>
                            <h6>Margin (Outside)</h6>
                            <div class="row g-2">
                                <div class="col-6"><label class="small">Top</label><input type="text"
                                        class="form-control" name="spacing[margin_top]"
                                        value="<?php echo $adv['spacing']['margin_top'] ?? '0px'; ?>"></div>
                                <div class="col-6"><label class="small">Bottom</label><input type="text"
                                        class="form-control" name="spacing[margin_bottom]"
                                        value="<?php echo $adv['spacing']['margin_bottom'] ?? '0px'; ?>"></div>
                            </div>
                        </div>

                        <!-- BORDER & DIVIDERS -->
                        <div class="tab-pane fade" id="tab-border">
                            <h6>Borders</h6>
                            <div class="row g-2 mb-3">
                                <div class="col-4"><input type="text" class="form-control" name="border[width]"
                                        value="<?php echo $adv['border']['width'] ?? ''; ?>" placeholder="1px"></div>
                                <div class="col-4"><select class="form-select" name="border[style]">
                                        <option value="solid">Solid</option>
                                        <option value="dashed">Dashed</option>
                                    </select></div>
                                <div class="col-4"><input type="color" class="form-control form-control-color w-100"
                                        name="border[color]"
                                        value="<?php echo $adv['border']['color'] ?? '#000000'; ?>"></div>
                            </div>
                            <div class="mb-3"><label>Radius</label><input type="text" class="form-control"
                                    name="border[radius]" value="<?php echo $adv['border']['radius'] ?? '0px'; ?>">
                            </div>

                            <hr>
                            <h6>Dividers (Scalable Vector Graphics)</h6>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label>Top Divider</label>
                                    <select class="form-select" name="dividers[top]">
                                        <option value="none" <?php echo ($adv['dividers']['top'] ?? '') == 'none' ? 'selected' : ''; ?>>None</option>
                                        <option value="wave" <?php echo ($adv['dividers']['top'] ?? '') == 'wave' ? 'selected' : ''; ?>>Wave</option>
                                        <option value="slant" <?php echo ($adv['dividers']['top'] ?? '') == 'slant' ? 'selected' : ''; ?>>Slant</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label>Bottom Divider</label>
                                    <select class="form-select" name="dividers[bottom]">
                                        <option value="none" <?php echo ($adv['dividers']['bottom'] ?? '') == 'none' ? 'selected' : ''; ?>>None</option>
                                        <option value="wave" <?php echo ($adv['dividers']['bottom'] ?? '') == 'wave' ? 'selected' : ''; ?>>Wave</option>
                                        <option value="slant" <?php echo ($adv['dividers']['bottom'] ?? '') == 'slant' ? 'selected' : ''; ?>>Slant</option>
                                    </select>
                                </div>
                                <div class="col-12 mt-2">
                                    <label>Divider Color</label>
                                    <input type="color" class="form-control form-control-color w-100"
                                        name="dividers[color]"
                                        value="<?php echo $adv['dividers']['color'] ?? '#ffffff'; ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TYPOGRAPHY -->
                    <div class="tab-pane fade" id="tab-typography">
                        <h6>Section Typography</h6>
                        <div class="mb-3">
                            <label>Text Color</label>
                            <input type="color" class="form-control form-control-color w-100" name="typography[color]"
                                value="<?php echo $adv['typography']['color'] ?? ''; ?>">
                            <div class="form-text">Leave empty/black to inherit global theme.</div>
                        </div>
                        <div class="mb-3">
                            <label>Text Align</label>
                            <select class="form-select" name="typography[align]">
                                <option value="left" <?php echo ($adv['typography']['align'] ?? '') == 'left' ? 'selected' : ''; ?>>Left</option>
                                <option value="center" <?php echo ($adv['typography']['align'] ?? '') == 'center' ? 'selected' : ''; ?>>Center</option>
                                <option value="right" <?php echo ($adv['typography']['align'] ?? '') == 'right' ? 'selected' : ''; ?>>Right</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Advanced Settings</button>
                </div>
        </form>
    </div>
</div>

<script>
    if (typeof CKEDITOR != 'undefined') CKEDITOR.replace('.ckeditor-lite');
    function toggleBgType(val) {
        document.querySelectorAll('.bg-section-field').forEach(el => el.classList.add('d-non e'));
        if (val === 'color') document.getElementById('bg-color-field').classList.remove('d -non e');
        if (val === 'image') document.getElementById('bg-image-field').classList.remove('d- non e');
        if (val === 'gradient') document.getElementById('bg-gradient-field').classList.remove('d-none');
    }
    // Init
    toggleBgType('<?php echo $adv['background']['type'] ?? ($section['bg_type'] ?? 'color'); ?>');
</script>

<?php include 'includes/footer.php'; ?>