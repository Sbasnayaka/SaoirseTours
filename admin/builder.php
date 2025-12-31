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
        
        // 1. Calculate Next Order
        $stmtOrder = $pdo->prepare("SELECT MAX(display_order) FROM section_elements WHERE section_id = ?");
        $stmtOrder->execute([$section_id]);
        $maxOrder = $stmtOrder->fetchColumn();
        $newOrder = ($maxOrder !== false) ? $maxOrder + 10 : 10;

        // 2. Define Defaults
        $content = 'New ' . ucfirst($type);
        $s = ['margin_bottom' => '20px'];
        
        switch ($type) {
            case 'heading':
                $content = 'New Heading';
                $s['tag'] = 'h2'; 
                $s['color'] = ''; // Inherit by default
                break;
            case 'text':
                $content = '<p>Start writing your content here...</p>'; 
                break;
            case 'button':
                $content = 'Click Here';
                $s['style'] = 'btn-primary';
                break;
            case 'card':
                $content = 'Card content goes here.';
                $s['card_title'] = 'Card Title';
                $s['shadow'] = '1';
                $s['bg_color'] = '#ffffff';
                break;
            case 'spacer':
                $content = '';
                $s['height'] = '50px';
                break;
            case 'map':
                $content = '';
                $s['height'] = '400px';
                break;
            case 'image':
                $content = ''; // Placeholder or require upload
                break;
            case 'divider':
                $content = '';
                $s['color'] = '#cccccc';
                break;
        }

        $pdo->prepare("INSERT INTO section_elements (section_id, type, content, settings, display_order) VALUES (?, ?, ?, ?, ?)")
            ->execute([$section_id, $type, $content, json_encode($s), $newOrder]);
            
        // Add success param to URL for toast (optional, but good for UX)
    }

    if (isset($_POST['update_element'])) {
        $id = $_POST['element_id'];
        $content = $_POST['content'] ?? ''; // Default empty if not sent
        $s = $_POST['settings'] ?? [];
        $order = $_POST['display_order'] ?? 0;

        // Handle Image Upload (Element)
        if (!empty($_FILES['el_image']['name'])) {
            $fn = time() . '_el_' . basename($_FILES['el_image']['name']);
            move_uploaded_file($_FILES['el_image']['tmp_name'], "../uploads/" . $fn);
            $content = $fn;
        }

        // Handle Card Image (Legacy support or Card element)
        if (!empty($_FILES['card_image']['name'])) {
            $fn = time() . '_card_' . basename($_FILES['card_image']['name']);
            move_uploaded_file($_FILES['card_image']['tmp_name'], "../uploads/" . $fn);
            $s['card_image'] = $fn;
        } elseif (isset($_POST['existing_card_image']))
            $s['card_image'] = $_POST['existing_card_image'];

        $pdo->prepare("UPDATE section_elements SET content = ?, settings = ?, display_order = ? WHERE id = ?")->execute([$content, json_encode($s), $order, $id]);
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
        <div class="d-flex align-items-center gap-3">
            <a href="page_sections.php?page_id=<?php echo $section['page_id']; ?>"
                class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
            <h4 class="mb-0">Builder: <?php echo htmlspecialchars($section['title']); ?></h4>
        </div>
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

                                    <!-- Common: Order -->
                                    <div class="row g-2 mb-2 align-items-center bg-white p-2 border rounded">
                                        <div class="col-auto"><label class="small fw-bold mb-0">Order:</label></div>
                                        <div class="col-auto"><input type="number" class="form-control form-control-sm"
                                                style="width: 70px;" name="display_order"
                                                value="<?php echo $el['display_order']; ?>"></div>
                                        <?php if (in_array($el['type'], ['heading', 'text', 'button'])): ?>
                                            <div class="col-auto ms-auto"><label class="small fw-bold mb-0">Align:</label></div>
                                            <div class="col-auto">
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <input type="radio" class="btn-check" name="settings[align]" value="left"
                                                        id="aL<?php echo $el['id']; ?>" <?php echo ($s['align'] ?? 'left') == 'left' ? 'checked' : ''; ?>>
                                                    <label class="btn btn-outline-secondary" for="aL<?php echo $el['id']; ?>"><i
                                                            class="bi bi-text-left"></i></label>

                                                    <input type="radio" class="btn-check" name="settings[align]" value="center"
                                                        id="aC<?php echo $el['id']; ?>" <?php echo ($s['align'] ?? '') == 'center' ? 'checked' : ''; ?>>
                                                    <label class="btn btn-outline-secondary" for="aC<?php echo $el['id']; ?>"><i
                                                            class="bi bi-text-center"></i></label>

                                                    <input type="radio" class="btn-check" name="settings[align]" value="right"
                                                        id="aR<?php echo $el['id']; ?>" <?php echo ($s['align'] ?? '') == 'right' ? 'checked' : ''; ?>>
                                                    <label class="btn btn-outline-secondary" for="aR<?php echo $el['id']; ?>"><i
                                                            class="bi bi-text-right"></i></label>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Type Specific -->
                                    <?php if ($el['type'] == 'text'): ?>
                                        <textarea class="form-control ckeditor-lite"
                                            name="content"><?php echo htmlspecialchars($el['content']); ?></textarea>

                                    <?php elseif ($el['type'] == 'heading'): ?>
                                        <label class="small">Heading Text</label>
                                        <input type="text" class="form-control mb-2 fw-bold" name="content"
                                            value="<?php echo htmlspecialchars($el['content']); ?>">
                                        <label class="small">Tag</label>
                                        <select class="form-select form-select-sm w-auto d-inline-block" name="settings[tag]">
                                            <option value="h1" <?php echo ($s['tag'] ?? '') == 'h1' ? 'selected' : ''; ?>>H1
                                            </option>
                                            <option value="h2" <?php echo ($s['tag'] ?? 'h2') == 'h2' ? 'selected' : ''; ?>>H2
                                            </option>
                                            <option value="h3" <?php echo ($s['tag'] ?? '') == 'h3' ? 'selected' : ''; ?>>H3
                                            </option>
                                        </select>

                                    <?php elseif ($el['type'] == 'image'): ?>
                                        <div class="d-flex align-items-start gap-3 mb-2">
                                            <?php if ($el['content']): ?>
                                                <div class="text-center">
                                                    <img src="../uploads/<?php echo $el['content']; ?>"
                                                        style="height: 80px; width: 80px; object-fit: cover;"
                                                        class="rounded border">
                                                    <input type="hidden" name="content" value="<?php echo $el['content']; ?>">
                                                </div>
                                            <?php endif; ?>
                                            <div class="flex-grow-1">
                                                <label class="small">Upload Image</label>
                                                <input type="file" class="form-control form-control-sm mb-2" name="el_image">
                                                <label class="small">Link URL (Optional)</label>
                                                <input type="text" class="form-control form-control-sm" name="settings[url]"
                                                    value="<?php echo $s['url'] ?? ''; ?>" placeholder="https://...">
                                            </div>
                                        </div>

                                    <?php elseif ($el['type'] == 'button'): ?>
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <label class="small">Label</label>
                                                <input type="text" class="form-control form-control-sm" name="content"
                                                    value="<?php echo htmlspecialchars($el['content']); ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="small">Link URL</label>
                                                <input type="text" class="form-control form-control-sm" name="settings[url]"
                                                    value="<?php echo $s['url'] ?? ''; ?>">
                                            </div>
                                            <div class="col-md-12">
                                                <label class="small">Style</label>
                                                <select class="form-select form-select-sm" name="settings[style]">
                                                    <option value="btn-primary" <?php echo ($s['style'] ?? '') == 'btn-primary' ? 'selected' : ''; ?>>Primary Green
                                                    </option>
                                                    <option value="btn-dark" <?php echo ($s['style'] ?? '') == 'btn-dark' ? 'selected' : ''; ?>>Dark</option>
                                                    <option value="btn-outline-primary" <?php echo ($s['style'] ?? '') == 'btn-outline-primary' ? 'selected' : ''; ?>>Outline Green
                                                    </option>
                                                    <option value="btn-outline-dark" <?php echo ($s['style'] ?? '') == 'btn-outline-dark' ? 'selected' : ''; ?>>Outline Dark
                                                    </option>
                                                </select>
                                            </div>
                                        </div>

                                    <?php elseif ($el['type'] == 'spacer'): ?>
                                        <label class="small">Height</label>
                                        <input type="text" class="form-control form-control-sm" name="settings[height]"
                                            value="<?php echo $s['height'] ?? '50px'; ?>">

                                    <?php elseif ($el['type'] == 'card'): ?>
                                        <div class="mb-3">
                                            <label class="small fw-bold">Card Title</label>
                                            <input type="text" class="form-control" name="settings[card_title]"
                                                value="<?php echo htmlspecialchars($s['card_title'] ?? ''); ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label class="small fw-bold">content</label>
                                            <textarea class="form-control" name="content"
                                                rows="3"><?php echo htmlspecialchars($el['content']); ?></textarea>
                                        </div>
                                        <div class="mb-3 border p-2 rounded bg-light">
                                            <label class="small fw-bold">Card Image</label>
                                            <?php if (!empty($s['card_image'])): ?>
                                                <div class="mb-2">
                                                    <img src="../uploads/<?php echo $s['card_image']; ?>"
                                                        style="height: 100px; object-fit: cover;" class="rounded d-block mb-1">
                                                    <input type="hidden" name="existing_card_image"
                                                        value="<?php echo $s['card_image']; ?>">
                                                    <small class="text-success"><i class="bi bi-check"></i> Image Saved</small>
                                                </div>
                                            <?php endif; ?>
                                            <input type="file" class="form-control form-control-sm" name="card_image">
                                        </div>
                                        <div class="row g-2">
                                            <div class="col-4">
                                                <label class="small">Bg Color</label>
                                                <input type="color" class="form-control form-control-color w-100"
                                                    name="settings[bg_color]"
                                                    value="<?php echo $s['bg_color'] ?? '#ffffff'; ?>">
                                            </div>
                                            <div class="col-4">
                                                <label class="small">Text Color</label>
                                                <input type="color" class="form-control form-control-color w-100"
                                                    name="settings[color]" value="<?php echo $s['color'] ?? '#000000'; ?>">
                                            </div>
                                            <div class="col-4">
                                                <label class="small">Shadow</label>
                                                <select class="form-select form-select-sm" name="settings[shadow]">
                                                    <option value="0" <?php echo ($s['shadow'] ?? '0') == '0' ? 'selected' : ''; ?>>Flat
                                                    </option>
                                                    <option value="1" <?php echo ($s['shadow'] ?? '0') == '1' ? 'selected' : ''; ?>>
                                                        Shadow</option>
                                                </select>
                                            </div>
                                        </div>

                                    <?php elseif ($el['type'] == 'map'): ?>
                                        <div class="mb-2">
                                            <label class="small fw-bold">Embed Code (iframe)</label>
                                            <textarea class="form-control form-control-sm font-monospace" name="content"
                                                rows="3"
                                                placeholder="<iframe src='...'></iframe>"><?php echo htmlspecialchars($el['content']); ?></textarea>
                                        </div>
                                        <label class="small">Height</label>
                                        <input type="text" class="form-control form-control-sm" name="settings[height]"
                                            value="<?php echo $s['height'] ?? '400px'; ?>">

                                    <?php else: ?>
                                        <input type="text" class="form-control" name="content"
                                            value="<?php echo htmlspecialchars($el['content']); ?>">
                                    <?php endif; ?>

                                    <button class="btn btn-primary btn-sm mt-3 w-100">Save Changes</button>
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
                        <?php foreach (['heading', 'text', 'image', 'button', 'card', 'video', 'map', 'spacer', 'divider'] as $t): ?>
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

<!-- ADVANCED SETTINGS MODAL (Redesigned) -->
<div class="modal fade" id="advancedSettingsModalV2" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow-lg" enctype="multipart/form-data">
            <input type="hidden" name="update_section_advanced" value="1">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title font-weight-bold"><i class="bi bi-sliders me-2"></i> Advanced Section Design</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <!-- HORIZONTAL TABS WITH ICONS -->
                <ul class="nav nav-tabs nav-justified bg-light border-bottom" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active py-3" data-bs-toggle="tab" data-bs-target="#tab-layout"
                            type="button">
                            <i class="bi bi-aspect-ratio fs-5 d-block mb-1"></i> Layout
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link py-3" data-bs-toggle="tab" data-bs-target="#tab-bg" type="button">
                            <i class="bi bi-image fs-5 d-block mb-1"></i> Background
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link py-3" data-bs-toggle="tab" data-bs-target="#tab-spacing" type="button">
                            <i class="bi bi-arrows-expand fs-5 d-block mb-1"></i> Spacing
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link py-3" data-bs-toggle="tab" data-bs-target="#tab-border" type="button">
                            <i class="bi bi-border-outer fs-5 d-block mb-1"></i> Borders
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link py-3" data-bs-toggle="tab" data-bs-target="#tab-typography"
                            type="button">
                            <i class="bi bi-fonts fs-5 d-block mb-1"></i> Typography
                        </button>
                    </li>
                </ul>

                <div class="tab-content p-4">

                    <!-- TAB 1: LAYOUT -->
                    <div class="tab-pane fade show active" id="tab-layout">
                        <h6 class="text-uppercase text-muted small fw-bold mb-3">Container & Sizing</h6>
                        <div class="row align-items-center mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Container Width</label>
                                <select class="form-select" name="layout[width]">
                                    <option value="boxed" <?php echo ($adv['layout']['width'] ?? '') == 'boxed' ? 'selected' : ''; ?>>Boxed (Center content)</option>
                                    <option value="full" <?php echo ($adv['layout']['width'] ?? '') == 'full' ? 'selected' : ''; ?>>Full Width (Fluid)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Minimum Height</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="layout[min_height]"
                                        value="<?php echo $adv['layout']['min_height'] ?? 'auto'; ?>"
                                        placeholder="auto">
                                    <span class="input-group-text text-muted">px / vh</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: BACKGROUND -->
                    <div class="tab-pane fade" id="tab-bg">
                        <h6 class="text-uppercase text-muted small fw-bold mb-3">Background Type</h6>

                        <!-- Visual Selectors -->
                        <div class="row g-3 mb-4">
                            <div class="col-4">
                                <label
                                    class="card h-100 cursor-pointer border-0 bg-light bg-hover-select text-center p-3">
                                    <input type="radio" name="background[type]" value="color" class="d-none"
                                        onchange="toggleBgType(this.value)" <?php echo ($adv['background']['type'] ?? '') == 'color' ? 'checked' : ''; ?>>
                                    <i class="bi bi-palette fs-2 text-primary mb-2"></i>
                                    <div class="small fw-bold">Solid Color</div>
                                </label>
                            </div>
                            <div class="col-4">
                                <label
                                    class="card h-100 cursor-pointer border-0 bg-light bg-hover-select text-center p-3">
                                    <input type="radio" name="background[type]" value="image" class="d-none"
                                        onchange="toggleBgType(this.value)" <?php echo ($adv['background']['type'] ?? '') == 'image' ? 'checked' : ''; ?>>
                                    <i class="bi bi-image fs-2 text-success mb-2"></i>
                                    <div class="small fw-bold">Image</div>
                                </label>
                            </div>
                            <div class="col-4">
                                <label
                                    class="card h-100 cursor-pointer border-0 bg-light bg-hover-select text-center p-3">
                                    <input type="radio" name="background[type]" value="gradient" class="d-none"
                                        onchange="toggleBgType(this.value)" <?php echo ($adv['background']['type'] ?? '') == 'gradient' ? 'checked' : ''; ?>>
                                    <i class="bi bi-rainbow fs-2 text-danger mb-2"></i>
                                    <div class="small fw-bold">Gradient</div>
                                </label>
                            </div>
                        </div>

                        <!-- BG Fields -->
                        <div class="card bg-light border-0 p-3">
                            <div class="bg-section-field" id="bg-color-field">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">Background Color</label>
                                        <input type="color" class="form-control form-control-color w-100"
                                            name="background[color]"
                                            value="<?php echo $adv['background']['color'] ?? '#ffffff'; ?>">
                                    </div>
                                    <div class="col-md-6 pt-4">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox"
                                                name="background[transparent]" value="1" <?php echo ($adv['background']['transparent'] ?? 0) == 1 ? 'checked' : ''; ?>>
                                            <label class="form-check-label">Transparent Background</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-section-field d-none" id="bg-image-field">
                                <label class="form-label">Background Image</label>
                                <div class="input-group mb-2">
                                    <input type="file" class="form-control" name="bg_image">
                                    <?php if ($section['image']): ?>
                                        <span class="input-group-text bg-white"><i
                                                class="bi bi-check-circle-fill text-success"></i> Saved</span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($section['image']): ?>
                                    <div class="mb-2"><img src="../uploads/<?php echo $section['image']; ?>" height="60"
                                            class="rounded"></div>
                                <?php endif; ?>
                            </div>

                            <div class="bg-section-field d-none" id="bg-gradient-field">
                                <label class="form-label">CSS Gradient</label>
                                <input type="text" class="form-control font-monospace" name="background[gradient]"
                                    value="<?php echo $adv['background']['gradient'] ?? ''; ?>"
                                    placeholder="linear-gradient(to right, #ff0099, #493240)">
                            </div>
                        </div>

                        <hr class="my-4">
                        <div class="row">
                            <div class="col-6">
                                <label class="form-label small text-muted text-uppercase">Overlay Color</label>
                                <input type="color" class="form-control form-control-color w-100"
                                    name="background[overlay_color]"
                                    value="<?php echo $adv['background']['overlay_color'] ?? '#000000'; ?>">
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-muted text-uppercase">Overlay Opacity</label>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="range" class="form-range" min="0" max="1" step="0.1"
                                        name="background[overlay_opacity]"
                                        value="<?php echo $adv['background']['overlay_opacity'] ?? '0'; ?>"
                                        oninput="this.nextElementSibling.value = this.value">
                                    <output><?php echo $adv['background']['overlay_opacity'] ?? '0'; ?></output>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: SPACING (Visual Box Model) -->
                    <div class="tab-pane fade" id="tab-spacing">
                        <h6 class="text-center text-uppercase text-muted small fw-bold mb-3">Box Model Spacing</h6>

                        <div class="d-flex justify-content-center">
                            <div
                                style="width: 300px; position: relative; padding: 40px; border: 1px dashed #ccc; background: #f8f9fa; border-radius: 8px;">
                                <div class="text-center text-muted small mb-2 fw-bold">MARGIN (Outside)</div>

                                <!-- Margin Inputs -->
                                <input type="text" name="spacing[margin_top]"
                                    class="form-control form-control-sm text-center position-absolute"
                                    style="top: 5px; left: 50%; transform: translateX(-50%); width: 60px;"
                                    placeholder="Top" value="<?php echo $adv['spacing']['margin_top'] ?? '0px'; ?>">
                                <input type="text" name="spacing[margin_bottom]"
                                    class="form-control form-control-sm text-center position-absolute"
                                    style="bottom: 5px; left: 50%; transform: translateX(-50%); width: 60px;"
                                    placeholder="Btm" value="<?php echo $adv['spacing']['margin_bottom'] ?? '0px'; ?>">

                                <!-- PADDING BOX -->
                                <div class="bg-white border text-center p-4 rounded position-relative"
                                    style="min-height: 120px;">
                                    <div class="text-center text-primary small mb-2 fw-bold">PADDING (Inside)</div>

                                    <input type="text" name="spacing[padding_top]"
                                        class="form-control form-control-sm text-center position-absolute border-primary"
                                        style="top: 5px; left: 50%; transform: translateX(-50%); width: 60px;"
                                        placeholder="Top"
                                        value="<?php echo $adv['spacing']['padding_top'] ?? '50px'; ?>">
                                    <input type="text" name="spacing[padding_bottom]"
                                        class="form-control form-control-sm text-center position-absolute border-primary"
                                        style="bottom: 5px; left: 50%; transform: translateX(-50%); width: 60px;"
                                        placeholder="Btm"
                                        value="<?php echo $adv['spacing']['padding_bottom'] ?? '50px'; ?>">
                                    <input type="text" name="spacing[padding_left]"
                                        class="form-control form-control-sm text-center position-absolute border-primary"
                                        style="left: 5px; top: 50%; transform: translateY(-50%); width: 50px;"
                                        placeholder="L" value="<?php echo $adv['spacing']['padding_left'] ?? '0px'; ?>">
                                    <input type="text" name="spacing[padding_right]"
                                        class="form-control form-control-sm text-center position-absolute border-primary"
                                        style="right: 5px; top: 50%; transform: translateY(-50%); width: 50px;"
                                        placeholder="R"
                                        value="<?php echo $adv['spacing']['padding_right'] ?? '0px'; ?>">

                                    <div class="mt-4 text-muted small">CONTENT</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 4: BORDERS -->
                    <div class="tab-pane fade" id="tab-border">
                        <div class="row align-items-end mb-4">
                            <div class="col-md-3">
                                <label class="form-label">Width</label>
                                <input type="text" class="form-control" name="border[width]"
                                    value="<?php echo $adv['border']['width'] ?? ''; ?>" placeholder="0px">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Style</label>
                                <select class="form-select" name="border[style]">
                                    <option value="solid">Solid</option>
                                    <option value="dashed">Dashed</option>
                                    <option value="dotted">Dotted</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Color</label>
                                <input type="color" class="form-control form-control-color w-100" name="border[color]"
                                    value="<?php echo $adv['border']['color'] ?? '#000000'; ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Radius</label>
                                <input type="text" class="form-control" name="border[radius]"
                                    value="<?php echo $adv['border']['radius'] ?? '0px'; ?>" placeholder="e.g. 8px">
                            </div>
                        </div>
                        <h6 class="text-uppercase text-muted small fw-bold mb-3">Shape Dividers (SVG)</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Top Shape</label>
                                <select class="form-select" name="dividers[top]">
                                    <option value="none">None</option>
                                    <option value="wave" <?php echo ($adv['dividers']['top'] ?? '') == 'wave' ? 'selected' : ''; ?>>Wave</option>
                                    <option value="slant" <?php echo ($adv['dividers']['top'] ?? '') == 'slant' ? 'selected' : ''; ?>>Slant</option>
                                    <option value="curve" <?php echo ($adv['dividers']['top'] ?? '') == 'curve' ? 'selected' : ''; ?>>Curve</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Bottom Shape</label>
                                <select class="form-select" name="dividers[bottom]">
                                    <option value="none">None</option>
                                    <option value="wave" <?php echo ($adv['dividers']['bottom'] ?? '') == 'wave' ? 'selected' : ''; ?>>Wave</option>
                                    <option value="slant" <?php echo ($adv['dividers']['bottom'] ?? '') == 'slant' ? 'selected' : ''; ?>>Slant</option>
                                    <option value="curve" <?php echo ($adv['dividers']['bottom'] ?? '') == 'curve' ? 'selected' : ''; ?>>Curve</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Shape Color</label>
                                <input type="color" class="form-control form-control-color w-100" name="dividers[color]"
                                    value="<?php echo $adv['dividers']['color'] ?? '#ffffff'; ?>">
                            </div>
                        </div>
                    </div>

                    <!-- TAB 5: TYPOGRAPHY -->
                    <div class="tab-pane fade" id="tab-typography">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Text Color</label>
                                <input type="color" class="form-control form-control-color w-100"
                                    name="typography[color]"
                                    value="<?php echo $adv['typography']['color'] ?? '#000000'; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Alignment</label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="typography[align]" value="left"
                                        id="tAlignL" <?php echo ($adv['typography']['align'] ?? '') == 'left' ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-secondary" for="tAlignL"><i
                                            class="bi bi-text-left"></i></label>
                                    <input type="radio" class="btn-check" name="typography[align]" value="center"
                                        id="tAlignC" <?php echo ($adv['typography']['align'] ?? '') == 'center' ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-secondary" for="tAlignC"><i
                                            class="bi bi-text-center"></i></label>
                                    <input type="radio" class="btn-check" name="typography[align]" value="right"
                                        id="tAlignR" <?php echo ($adv['typography']['align'] ?? '') == 'right' ? 'checked' : ''; ?>>
                                    <label class="btn btn-outline-secondary" for="tAlignR"><i
                                            class="bi bi-text-right"></i></label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Font Family</label>
                                <select class="form-select" name="typography[font_family]">
                                    <option value="inherit">Inherit Global Font</option>
                                    <option value="'Inter', sans-serif" <?php echo ($adv['typography']['font_family'] ?? '') == "'Inter', sans-serif" ? 'selected' : ''; ?>>Inter</option>
                                    <option value="'Playfair Display', serif" <?php echo ($adv['typography']['font_family'] ?? '') == "'Playfair Display', serif" ? 'selected' : ''; ?>>Playfair Display</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Font Weight</label>
                                <select class="form-select" name="typography[font_weight]">
                                    <option value="">Default</option>
                                    <option value="300" <?php echo ($adv['typography']['font_weight'] ?? '') == '300' ? 'selected' : ''; ?>>Light</option>
                                    <option value="400" <?php echo ($adv['typography']['font_weight'] ?? '') == '400' ? 'selected' : ''; ?>>Normal</option>
                                    <option value="700" <?php echo ($adv['typography']['font_weight'] ?? '') == '700' ? 'selected' : ''; ?>>Bold</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary px-4">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    if (typeof CKEDITOR != 'undefined') CKEDITOR.replace('.ckeditor-lite');

    function toggleBgType(val) {
        // Toggle Active State visuals
        document.querySelectorAll('input[name="background[type]"]').forEach(input => {
            // Reset container style
            input.parentElement.classList.remove('border-primary', 'bg-white', 'shadow-sm');
            input.parentElement.classList.add('bg-light', 'border-0');
        });

        // Highlight active
        const activeInput = document.querySelector(`input[name="background[type]"][value="${val}"]`);
        if (activeInput) {
            activeInput.parentElement.classList.remove('bg-light', 'border-0');
            activeInput.parentElement.classList.add('border', 'border-primary', 'bg-white', 'shadow-sm');
        }

        // Show/Hide Fields
        document.querySelectorAll('.bg-section-field').forEach(el => el.classList.add('d-none'));
        if (val === 'color') document.getElementById('bg-color-field').classList.remove('d-none');
        if (val === 'image') document.getElementById('bg-image-field').classList.remove('d-none');
        if (val === 'gradient') document.getElementById('bg-gradient-field').classList.remove('d-none');
    }

    // Init state
    document.addEventListener('DOMContentLoaded', function () {
        toggleBgType('<?php echo $adv['background']['type'] ?? ($section['bg_type'] ?? 'color'); ?>');
    });
</script>

<?php include 'includes/footer.php'; ?>