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
$page_id = $section['page_id'];

// --- HANDLE POST ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Add Element
    if (isset($_POST['add_element'])) {
        $type = $_POST['type'];
        $content = 'New Item';
        $settings = ['margin_bottom' => '20px'];

        switch ($type) {
            case 'heading':
                $content = 'New Heading';
                $settings['tag'] = 'h2';
                break;
            case 'text':
                $content = '<p>Start writing...</p>';
                break;
            case 'button':
                $content = 'Click Here';
                $settings['style'] = 'btn-primary';
                break;
            case 'icon':
                $content = 'bi-star-fill';
                $settings['font_size'] = '2rem';
                $settings['color'] = '#ffc107';
                break;
            case 'video':
                $content = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';
                break;
            case 'card':
                $content = 'Card Description text.';
                $settings['card_title'] = 'Card Title';
                break;
            case 'list':
                $content = "Item 1\nItem 2\nItem 3";
                break;
            case 'spacer':
                $content = '';
                $settings['height'] = '50px';
                break;
        }

        $stmt = $pdo->prepare("INSERT INTO section_elements (section_id, type, content, settings, display_order) VALUES (?, ?, ?, ?, 99)");
        $stmt->execute([$section_id, $type, $content, json_encode($settings)]);
    }

    // 2. Update Element (Content + Settings)
    if (isset($_POST['update_element'])) {
        $id = $_POST['element_id'];
        $content = $_POST['content'];
        $s = $_POST['settings'] ?? [];

        // Handle File Uploads (General Element Images)
        if (!empty($_FILES['el_image']['name'])) {
            $targetDir = "../uploads/";
            if (!is_dir($targetDir))
                mkdir($targetDir, 0755, true);
            $fileName = time() . '_el_' . basename($_FILES['el_image']['name']);
            move_uploaded_file($_FILES['el_image']['tmp_name'], $targetDir . $fileName);
            $content = $fileName;
        }
        // Handle Card Image Upload
        if (!empty($_FILES['card_image']['name'])) {
            $targetDir = "../uploads/";
            if (!is_dir($targetDir))
                mkdir($targetDir, 0755, true);
            $fileName = time() . '_card_' . basename($_FILES['card_image']['name']);
            move_uploaded_file($_FILES['card_image']['tmp_name'], $targetDir . $fileName);
            $s['card_image'] = $fileName;
        } else {
            // Keep existing if not uploaded (hidden field trick or just merge)
            // For simplicity, we assume we fetch existing first or use hidden input for old value.
            if (isset($_POST['existing_card_image']))
                $s['card_image'] = $_POST['existing_card_image'];
        }

        $stmt = $pdo->prepare("UPDATE section_elements SET content = ?, settings = ? WHERE id = ?");
        $stmt->execute([$content, json_encode($s), $id]);
    }

    // 3. Delete Element
    if (isset($_POST['delete_element'])) {
        $pdo->prepare("DELETE FROM section_elements WHERE id = ?")->execute([$_POST['element_id']]);
    }

    // 4. Update Section Styling
    if (isset($_POST['update_section'])) {
        $sql = "UPDATE sections SET bg_color=?, text_color=?, padding_top=?, padding_bottom=?, min_height=?, bg_type=?, bg_gradient=? WHERE id=?";
        $pdo->prepare($sql)->execute([
            $_POST['bg_color'],
            $_POST['text_color'],
            $_POST['padding_top'],
            $_POST['padding_bottom'],
            $_POST['min_height'],
            $_POST['bg_type'],
            $_POST['bg_gradient'],
            $section_id
        ]);
        if (!empty($_FILES['bg_image']['name'])) {
            $fileName = time() . '_bg_' . basename($_FILES['bg_image']['name']);
            move_uploaded_file($_FILES['bg_image']['tmp_name'], "../uploads/" . $fileName);
            $pdo->prepare("UPDATE sections SET image = ? WHERE id = ?")->execute([$fileName, $section_id]);
        }
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
    <!-- Builder Header -->
    <div class="d-flex justify-content-between align-items-center mb-3 bg-white p-3 rounded shadow-sm">
        <div class="d-flex align-items-center gap-3">
            <a href="page_sections.php?page_id=<?php echo $page_id; ?>" class="btn btn-outline-secondary btn-sm"><i
                    class="bi bi-arrow-left"></i> Back</a>
            <h4 class="mb-0">Builder: <?php echo htmlspecialchars($section['title']); ?></h4>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#sectionModal"><i
                    class="bi bi-gear"></i> Section Styles</button>
            <a href="../index.php" target="_blank" class="btn btn-success btn-sm">Preview</a>
        </div>
    </div>

    <div class="row">
        <!-- Canvas -->
        <div class="col-lg-9">
            <div class="builder-canvas bg-light p-4 min-vh-100 rounded border">
                <?php if (!$elements): ?>
                    <div class="text-center py-5 opacity-50">
                        <h3>Start Building</h3>
                        <p>Select an element from the right sidebar.</p>
                    </div>
                <?php endif; ?>

                <?php foreach ($elements as $el):
                    $s = json_decode($el['settings'], true) ?? [];
                    ?>
                    <div class="card mb-3 shadow-sm element-card border-0">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <span class="badge bg-primary rounded-pill text-uppercase"><?php echo $el['type']; ?></span>
                            <div>
                                <button class="btn btn-sm btn-light text-primary" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#edit-<?php echo $el['id']; ?>"><i class="bi bi-pencil-fill"></i>
                                    Edit</button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete?');">
                                    <input type="hidden" name="delete_element" value="1"><input type="hidden"
                                        name="element_id" value="<?php echo $el['id']; ?>">
                                    <button class="btn btn-sm btn-light text-danger"><i
                                            class="bi bi-trash-fill"></i></button>
                                </form>
                            </div>
                        </div>

                        <!-- Editor Form -->
                        <div class="collapse" id="edit-<?php echo $el['id']; ?>">
                            <div class="card-body bg-secondary bg-opacity-10 border-bottom">
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="update_element" value="1">
                                    <input type="hidden" name="element_id" value="<?php echo $el['id']; ?>">

                                    <ul class="nav nav-tabs mb-3 bg-white rounded" role="tablist">
                                        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab"
                                                data-bs-target="#content-<?php echo $el['id']; ?>"
                                                type="button">Content</button></li>
                                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab"
                                                data-bs-target="#style-<?php echo $el['id']; ?>"
                                                type="button">Styling</button></li>
                                    </ul>

                                    <div class="tab-content">
                                        <!-- Content Tab -->
                                        <div class="tab-pane fade show active" id="content-<?php echo $el['id']; ?>">
                                            <!-- Dynamic Fields based on Type -->
                                            <?php if ($el['type'] == 'heading'): ?>
                                                <div class="mb-2"><label>Text</label><input type="text" class="form-control"
                                                        name="content" value="<?php echo htmlspecialchars($el['content']); ?>">
                                                </div>
                                                <div class="mb-2"><label>Tag</label>
                                                    <select class="form-select" name="settings[tag]">
                                                        <option value="h1" <?php echo ($s['tag'] ?? '') == 'h1' ? 'selected' : ''; ?>>H1
                                                        </option>
                                                        <option value="h2" <?php echo ($s['tag'] ?? '') == 'h2' ? 'selected' : ''; ?>>H2
                                                        </option>
                                                        <option value="h3" <?php echo ($s['tag'] ?? '') == 'h3' ? 'selected' : ''; ?>>H3
                                                        </option>
                                                    </select>
                                                </div>
                                            <?php elseif ($el['type'] == 'text'): ?>
                                                <textarea class="form-control ckeditor-lite"
                                                    name="content"><?php echo htmlspecialchars($el['content']); ?></textarea>
                                            <?php elseif ($el['type'] == 'image'): ?>
                                                <input type="file" class="form-control mb-2" name="el_image">
                                                <input type="hidden" name="content" value="<?php echo $el['content']; ?>">
                                                <?php if ($el['content'])
                                                    echo "<img src='../uploads/{$el['content']}' height='50'>"; ?>
                                            <?php elseif ($el['type'] == 'button'): ?>
                                                <div class="row">
                                                    <div class="col-6"><label>Label</label><input type="text"
                                                            class="form-control" name="content"
                                                            value="<?php echo htmlspecialchars($el['content']); ?>"></div>
                                                    <div class="col-6"><label>URL</label><input type="text" class="form-control"
                                                            name="settings[url]" value="<?php echo $s['url'] ?? '#'; ?>"></div>
                                                </div>
                                                <div class="mt-2"><label>Style</label>
                                                    <select class="form-select" name="settings[style]">
                                                        <option value="btn-primary" <?php echo ($s['style'] ?? '') == 'btn-primary' ? 'selected' : ''; ?>>Primary</option>
                                                        <option value="btn-outline-primary" <?php echo ($s['style'] ?? '') == 'btn-outline-primary' ? 'selected' : ''; ?>>Outline
                                                            Primary</option>
                                                        <option value="btn-light text-dark" <?php echo ($s['style'] ?? '') == 'btn-light text-dark' ? 'selected' : ''; ?>>
                                                            White/Light</option>
                                                    </select>
                                                </div>
                                            <?php elseif ($el['type'] == 'icon'): ?>
                                                <label>Icon Class (Bootstrap)</label><input type="text" class="form-control"
                                                    name="content" value="<?php echo htmlspecialchars($el['content']); ?>"
                                                    placeholder="bi-star">
                                            <?php elseif ($el['type'] == 'video'): ?>
                                                <label>Video URL (Youtube/MP4)</label><input type="text" class="form-control"
                                                    name="content" value="<?php echo htmlspecialchars($el['content']); ?>">
                                            <?php elseif ($el['type'] == 'card'): ?>
                                                <label>Card Title</label><input type="text" class="form-control mb-2"
                                                    name="settings[card_title]"
                                                    value="<?php echo htmlspecialchars($s['card_title'] ?? ''); ?>">
                                                <label>Body Text</label><textarea class="form-control mb-2"
                                                    name="content"><?php echo htmlspecialchars($el['content']); ?></textarea>
                                                <label>Card Image</label><input type="file" class="form-control mb-2"
                                                    name="card_image">
                                                <?php if (!empty($s['card_image'])): ?><input type="hidden"
                                                        name="existing_card_image"
                                                        value="<?php echo $s['card_image']; ?>"><?php endif; ?>
                                            <?php elseif ($el['type'] == 'list'): ?>
                                                <label>Items (One per line)</label><textarea class="form-control" rows="4"
                                                    name="content"><?php echo htmlspecialchars($el['content']); ?></textarea>
                                                <label>Type</label><select name="settings[list_type]" class="form-select">
                                                    <option value="ul">Bulleted</option>
                                                    <option value="ol">Numbered</option>
                                                </select>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Styling Tab (Granular Controls) -->
                                        <div class="tab-pane fade" id="style-<?php echo $el['id']; ?>">
                                            <div class="row g-2">
                                                <div class="col-6"><label class="small text-muted">Text Color</label><input
                                                        type="color" class="form-control form-control-color w-100"
                                                        name="settings[color]"
                                                        value="<?php echo $s['color'] ?? '#000000'; ?>"></div>
                                                <div class="col-6"><label class="small text-muted">Bg Color</label><input
                                                        type="color" class="form-control form-control-color w-100"
                                                        name="settings[background_color]"
                                                        value="<?php echo $s['background_color'] ?? '#ffffff'; ?>"></div>
                                            
                                            <div class="col-6">
                                                <label class="small text-muted">Opacity (0-100%)</label>
                                                <input type="range" class="form-range" name="settings[opacity]" min="0" max="1" step="0.1" value="<?php echo $s['opacity']??'1'; ?>" oninput="this.nextElementSibling.value = Math.round(this.value * 100) + '%'">
                                                <output class="small text-muted"><?php echo isset($s['opacity']) ? round($s['opacity']*100).'%' : '100%'; ?></output>
                                            </div>
                                            <div class="col-6 d-flex align-items-center pt-3">
                                                 <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="settings[background_color_transparent]" value="transparent" <?php echo ($s['background_color_transparent']??'')=='transparent'?'checked':''; ?>>
                                                    <label class="form-check-label small">Transparent BG</label>
                                                </div>
                                            </div>

                                                <div class="col-6"><label class="small text-muted">Font Size</label><input
                                                        type="text" class="form-control form-control-sm"
                                                        name="settings[font_size]"
                                                        value="<?php echo $s['font_size'] ?? ''; ?>" placeholder="16px"></div>
                                                <div class="col-6"><label class="small text-muted">Font Weight</label><input
                                                        type="text" class="form-control form-control-sm"
                                                        name="settings[font_weight]"
                                                        value="<?php echo $s['font_weight'] ?? ''; ?>" placeholder="400">
                                                </div>

                                                <div class="col-md-12 border-top my-2"></div>
                                                <div class="col-3"><label class="small text-muted">Mg Top</label><input
                                                        type="text" class="form-control form-control-sm"
                                                        name="settings[margin_top]"
                                                        value="<?php echo $s['margin_top'] ?? ''; ?>"></div>
                                                <div class="col-3"><label class="small text-muted">Mg Bot</label><input
                                                        type="text" class="form-control form-control-sm"
                                                        name="settings[margin_bottom]"
                                                        value="<?php echo $s['margin_bottom'] ?? '20px'; ?>"></div>
                                                <div class="col-3"><label class="small text-muted">Pd Top</label><input
                                                        type="text" class="form-control form-control-sm"
                                                        name="settings[padding_top]"
                                                        value="<?php echo $s['padding_top'] ?? ''; ?>"></div>
                                                <div class="col-3"><label class="small text-muted">Pd Bot</label><input
                                                        type="text" class="form-control form-control-sm"
                                                        name="settings[padding_bottom]"
                                                        value="<?php echo $s['padding_bottom'] ?? ''; ?>"></div>

                                                <div class="col-12"><label class="small text-muted">Alignment</label>
                                                    <select class="form-select form-select-sm" name="settings[text_align]">
                                                        <option value="left" <?php echo ($s['text_align'] ?? '') == 'left' ? 'selected' : ''; ?>>Left</option>
                                                        <option value="center" <?php echo ($s['text_align'] ?? '') == 'center' ? 'selected' : ''; ?>>Center
                                                        </option>
                                                        <option value="right" <?php echo ($s['text_align'] ?? '') == 'right' ? 'selected' : ''; ?>>Right</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-3 text-end"><button class="btn btn-primary btn-sm">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Preview -->
                        <div class="card-body p-3">
                            <div class="text-muted small mb-1">Preview:</div>
                            <div style="zoom: 0.8; border: 1px dashed #ccc; padding: 10px;">
                                <?php
                                // Simple HTML strip preview
                                echo substr(strip_tags($el['content']), 0, 150);
                                if ($el['type'] == 'image')
                                    echo "[Image: {$el['content']}]";
                                if ($el['type'] == 'icon')
                                    echo "[Icon: {$el['content']}]";
                                ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Sidebar Tools -->
        <div class="col-lg-3">
            <div class="sticky-top" style="top: 20px;">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-dark text-white fw-bold">Elements</div>
                    <div class="card-body p-2">
                        <div class="row g-2">
                            <?php
                            $tools = [
                                'heading' => 'type-h1',
                                'text' => 'file-text',
                                'image' => 'image',
                                'button' => 'hand-index',
                                'list' => 'list-ul',
                                'card' => 'card-heading',
                                'video' => 'play-btn',
                                'icon' => 'star',
                                'spacer' => 'arrows-expand',
                                'divider' => 'dash-lg'
                            ];
                            foreach ($tools as $t => $i):
                                ?>
                                <div class="col-6">
                                    <form method="POST">
                                        <input type="hidden" name="add_element" value="1">
                                        <input type="hidden" name="type" value="<?php echo $t; ?>">
                                        <button
                                            class="btn btn-outline-secondary w-100 py-2 d-flex flex-column align-items-center">
                                            <i class="bi bi-<?php echo $i; ?> fs-4"></i>
                                            <span class="small mt-1"><?php echo ucfirst($t); ?></span>
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Section Settings Modal -->
<div class="modal fade" id="sectionModal">
    <div class="modal-dialog">
        <form method="POST" class="modal-content" enctype="multipart/form-data">
            <input type="hidden" name="update_section" value="1">
            <div class="modal-header">
                <h5 class="modal-title">Section Settings</h5><button type="button" class="btn-close"
                    data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label>Background</label>
                    <select class="form-select mb-2" name="bg_type">
                        <option value="color" <?php echo ($section['bg_type'] == 'color') ? 'selected' : ''; ?>>Color</option>
                        <option value="image" <?php echo ($section['bg_type'] == 'image') ? 'selected' : ''; ?>>Image</option>
                        <option value="gradient" <?php echo ($section['bg_type'] == 'gradient') ? 'selected' : ''; ?>>Gradient
                        </option>
                    </select>
                    <input type="color" class="form-control form-control-color w-100 mb-2" name="bg_color"
                        value="<?php echo $section['bg_color'] ?? '#ffffff'; ?>" title="Bg Color">
                    <input type="text" class="form-control mb-2" name="bg_gradient"
                        value="<?php echo htmlspecialchars($section['bg_gradient'] ?? ''); ?>"
                        placeholder="linear-gradient(...)">
                    <input type="file" class="form-control" name="bg_image">
                </div>
                <div class="row g-2">
                    <div class="col-6"><label>Min Height</label><input type="text" class="form-control"
                            name="min_height" value="<?php echo $section['min_height'] ?? 'auto'; ?>"></div>
                    <div class="col-6"><label>Text Color</label><input type="color"
                            class="form-control form-control-color w-100" name="text_color"
                            value="<?php echo $section['text_color'] ?? '#000000'; ?>"></div>
                    <div class="col-6"><label>Pad Top</label><input type="text" class="form-control" name="padding_top"
                            value="<?php echo $section['padding_top'] ?? '50px'; ?>"></div>
                    <div class="col-6"><label>Pad Bot</label><input type="text" class="form-control"
                            name="padding_bottom" value="<?php echo $section['padding_bottom'] ?? '50px'; ?>"></div>
                </div>
            </div>
            <div class="modal-footer"><button class="btn btn-primary">Save Styles</button></div>
        </form>
    </div>
</div>

<script>
    if (typeof CKEDITOR != 'undefined') CKEDITOR.replace('.ckeditor-lite');
</script>

<?php include 'includes/footer.php'; ?>