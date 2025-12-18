<?php
require_once '../includes/config.php';
require_once '../classes/Auth.php';

$auth = new Auth($pdo);
$auth->requireLogin();

if (!isset($_GET['section_id'])) {
    die("Section ID required.");
}

$section_id = $_GET['section_id'];

// Fetch Section
$stmt = $pdo->prepare("SELECT * FROM sections WHERE id = ?");
$stmt->execute([$section_id]);
$section = $stmt->fetch();

if (!$section) die("Section not found.");

$page_id = $section['page_id'];

// Handle Element Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_element'])) {
        $type = $_POST['type'];
        $stmt = $pdo->prepare("INSERT INTO section_elements (section_id, type, content, settings, display_order) VALUES (?, ?, ?, ?, ?)");
        
        // Defaults
        $content = 'New Element';
        $settings = '{}';
        if ($type == 'heading') { $content = 'New Heading'; $settings = json_encode(['tag'=>'h2', 'align'=>'center']); }
        if ($type == 'text') { $content = '<p>Start typing here...</p>'; }
        if ($type == 'button') { $content = 'Click Me'; $settings = json_encode(['style'=>'btn-primary', 'url'=>'#']); }
        
        $stmt->execute([$section_id, $type, $content, $settings, 99]);
    }
    
    if (isset($_POST['update_element'])) {
        $id = $_POST['element_id'];
        $content = $_POST['content'];
        $settings = $_POST['settings']; // Array
        
        // Handle Image Upload for Element
        if (!empty($_FILES['element_image']['name'])) {
            $targetDir = "../uploads/";
            if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
            $fileName = time() . '_el_' . basename($_FILES['element_image']['name']);
            if (move_uploaded_file($_FILES['element_image']['tmp_name'], $targetDir . $fileName)) {
                $content = $fileName;
            }
        }

        $stmt = $pdo->prepare("UPDATE section_elements SET content = ?, settings = ? WHERE id = ?");
        $stmt->execute([$content, json_encode($settings), $id]);
    }

    if (isset($_POST['delete_element'])) {
        $stmt = $pdo->prepare("DELETE FROM section_elements WHERE id = ?");
        $stmt->execute([$_POST['element_id']]);
    }
    
    // Update Section Styles
    if (isset($_POST['update_section_styles'])) {
        $sql = "UPDATE sections SET bg_color=?, text_color=?, padding_top=?, padding_bottom=?, min_height=?, bg_type=?, bg_gradient=? WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['bg_color'], $_POST['text_color'], $_POST['padding_top'], $_POST['padding_bottom'], 
            $_POST['min_height'], $_POST['bg_type'], $_POST['bg_gradient'], $section_id
        ]);
        
        // Handle Background Image
        if (!empty($_FILES['bg_image']['name'])) {
            $targetDir = "../uploads/";
            if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
            $fileName = time() . '_bg_' . basename($_FILES['bg_image']['name']);
            move_uploaded_file($_FILES['bg_image']['tmp_name'], $targetDir . $fileName);
            $pdo->prepare("UPDATE sections SET image = ? WHERE id = ?")->execute([$fileName, $section_id]);
        }
    }

    header("Location: builder.php?section_id=$section_id");
    exit;
}

// Fetch Elements
$elStmt = $pdo->prepare("SELECT * FROM section_elements WHERE section_id = ? ORDER BY display_order ASC");
$elStmt->execute([$section_id]);
$elements = $elStmt->fetchAll();

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="page_sections.php?page_id=<?php echo $page_id; ?>" class="btn btn-outline-secondary btn-sm mb-2"><i class="bi bi-arrow-left"></i> Back to Sections</a>
            <h1 class="h3">Visual Builder: <?php echo htmlspecialchars($section['title']); ?></h1>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#sectionSettingsModal"><i class="bi bi-gear"></i> Section Settings</button>
            <a href="../index.php" target="_blank" class="btn btn-primary"><i class="bi bi-eye"></i> View Live</a>
        </div>
    </div>

    <div class="row">
        <!-- Elements List (Canvas) -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 min-vh-100 bg-light">
                <div class="card-body p-4">
                    <h5 class="text-muted mb-4 uppercase ls-1 fz-12">Page Content</h5>
                    
                    <?php if (count($elements) == 0): ?>
                        <div class="text-center py-5 text-muted border rounded border-dashed">
                            <i class="bi bi-bricks fs-1 mb-3 d-block"></i>
                            <p>This section is empty.</p>
                            <p>Add an element from the sidebar to start building.</p>
                        </div>
                    <?php endif; ?>

                    <div id="elements-container">
                        <?php foreach ($elements as $el): 
                            $settings = json_decode($el['settings'], true) ?? [];
                        ?>
                        <div class="card mb-3 shadow-sm border-0 element-card">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                                <span class="badge bg-light text-dark border"><i class="bi bi-<?php echo $el['type'] == 'text' ? 'file-text' : ($el['type']=='image'?'image':'type'); ?>"></i> <?php echo ucfirst($el['type']); ?></span>
                                <div>
                                    <button class="btn btn-sm btn-light text-primary" data-bs-toggle="collapse" data-bs-target="#edit-el-<?php echo $el['id']; ?>"><i class="bi bi-pencil"></i></button>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Delete?');">
                                        <input type="hidden" name="delete_element" value="1">
                                        <input type="hidden" name="element_id" value="<?php echo $el['id']; ?>">
                                        <button class="btn btn-sm btn-light text-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </div>
                            <!-- Edit Form -->
                            <div class="collapse" id="edit-el-<?php echo $el['id']; ?>">
                                <div class="card-body bg-light border-bottom">
                                    <form method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="update_element" value="1">
                                        <input type="hidden" name="element_id" value="<?php echo $el['id']; ?>">
                                        
                                        <!-- Content Fields based on Type -->
                                        <?php if ($el['type'] == 'heading'): ?>
                                            <div class="mb-2">
                                                <label>Text</label>
                                                <input type="text" class="form-control" name="content" value="<?php echo htmlspecialchars($el['content']); ?>">
                                            </div>
                                            <div class="row g-2">
                                                <div class="col-6">
                                                    <label>Tag</label>
                                                    <select class="form-select" name="settings[tag]">
                                                        <option value="h1" <?php echo ($settings['tag']??'')=='h1'?'selected':''; ?>>H1</option>
                                                        <option value="h2" <?php echo ($settings['tag']??'')=='h2'?'selected':''; ?>>H2</option>
                                                        <option value="h3" <?php echo ($settings['tag']??'')=='h3'?'selected':''; ?>>H3</option>
                                                    </select>
                                                </div>
                                                <div class="col-6">
                                                     <label>Color</label>
                                                     <input type="color" class="form-control form-control-color w-100" name="settings[color]" value="<?php echo $settings['color'] ?? '#000000'; ?>">
                                                </div>
                                            </div>
                                        <?php elseif ($el['type'] == 'text'): ?>
                                            <div class="mb-2">
                                                <textarea class="form-control ckeditor-lite" name="content"><?php echo htmlspecialchars($el['content']); ?></textarea>
                                            </div>
                                        <?php elseif ($el['type'] == 'image'): ?>
                                            <div class="mb-2">
                                                <label>Change Image</label>
                                                <input type="file" class="form-control" name="element_image">
                                                <input type="hidden" name="content" value="<?php echo $el['content']; ?>">
                                                <div class="mt-2 text-center">
                                                    <img src="../uploads/<?php echo $el['content']; ?>" style="max-height: 100px;">
                                                </div>
                                            </div>
                                        <?php elseif ($el['type'] == 'button'): ?>
                                            <div class="mb-2">
                                                <label>Button Text</label>
                                                <input type="text" class="form-control" name="content" value="<?php echo htmlspecialchars($el['content']); ?>">
                                            </div>
                                            <div class="mb-2">
                                                <label>Link URL</label>
                                                <input type="text" class="form-control" name="settings[url]" value="<?php echo htmlspecialchars($settings['url']??'#'); ?>">
                                            </div>
                                            <div class="mb-2">
                                                <label>Style</label>
                                                <select class="form-select" name="settings[style]">
                                                    <option value="btn-primary" <?php echo ($settings['style']??'')=='btn-primary'?'selected':''; ?>>Primary Color</option>
                                                    <option value="btn-secondary" <?php echo ($settings['style']??'')=='btn-secondary'?'selected':''; ?>>Secondary Color</option>
                                                    <option value="btn-outline-dark" <?php echo ($settings['style']??'')=='btn-outline-dark'?'selected':''; ?>>Outline Dark</option>
                                                </select>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <!-- Common Spacer/Margin Settings -->
                                        <div class="row g-2 mt-2 border-top pt-2">
                                            <div class="col-6">
                                                <label class="small text-muted">Margin Top</label>
                                                <input type="text" class="form-control form-control-sm" name="settings[margin_top]" value="<?php echo $settings['margin_top'] ?? '0px'; ?>" placeholder="0px">
                                            </div>
                                            <div class="col-6">
                                                <label class="small text-muted">Margin Bottom</label>
                                                <input type="text" class="form-control form-control-sm" name="settings[margin_bottom]" value="<?php echo $settings['margin_bottom'] ?? '20px'; ?>" placeholder="20px">
                                            </div>
                                        </div>

                                        <div class="mt-3 text-end">
                                            <button type="submit" class="btn btn-success btn-sm">Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <!-- Preview Content -->
                            <div class="card-body p-3">
                                <?php 
                                    if ($el['type'] == 'image') {
                                        echo '<img src="../uploads/'.$el['content'].'" style="max-height: 80px;" class="rounded">';
                                    } elseif ($el['type'] == 'text') {
                                        echo strip_tags(substr($el['content'], 0, 100)) . '...';
                                    } else {
                                        echo htmlspecialchars($el['content']);
                                    }
                                ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar (Tools) -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 sticky-top" style="top: 20px;">
                <div class="card-header bg-white font-weight-bold">Add Element</div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-6">
                            <form method="POST"><input type="hidden" name="add_element" value="1"><input type="hidden" name="type" value="heading"><button class="btn btn-outline-dark w-100 py-3"><i class="bi bi-type-h1 d-block fs-3 mb-1"></i> Heading</button></form>
                        </div>
                        <div class="col-6">
                            <form method="POST"><input type="hidden" name="add_element" value="1"><input type="hidden" name="type" value="text"><button class="btn btn-outline-dark w-100 py-3"><i class="bi bi-file-text d-block fs-3 mb-1"></i> Text</button></form>
                        </div>
                        <div class="col-6">
                            <form method="POST"><input type="hidden" name="add_element" value="1"><input type="hidden" name="type" value="image"><button class="btn btn-outline-dark w-100 py-3"><i class="bi bi-image d-block fs-3 mb-1"></i> Image</button></form>
                        </div>
                        <div class="col-6">
                            <form method="POST"><input type="hidden" name="add_element" value="1"><input type="hidden" name="type" value="button"><button class="btn btn-outline-dark w-100 py-3"><i class="bi bi-hand-index-thumb d-block fs-3 mb-1"></i> Button</button></form>
                        </div>
                        <div class="col-6">
                            <form method="POST"><input type="hidden" name="add_element" value="1"><input type="hidden" name="type" value="spacer"><button class="btn btn-outline-dark w-100 py-3"><i class="bi bi-arrows-expand d-block fs-3 mb-1"></i> Spacer</button></form>
                        </div>
                        <div class="col-6">
                            <form method="POST"><input type="hidden" name="add_element" value="1"><input type="hidden" name="type" value="divider"><button class="btn btn-outline-dark w-100 py-3"><i class="bi bi-dash-lg d-block fs-3 mb-1"></i> Divider</button></form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Section Settings Modal -->
<div class="modal fade" id="sectionSettingsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Section Styles</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="update_section_styles" value="1">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Background Type</label>
                        <select class="form-select" name="bg_type" onchange="toggleBgFields(this.value)">
                            <option value="color" <?php echo ($section['bg_type']??'')=='color'?'selected':''; ?>>Solid Color</option>
                            <option value="image" <?php echo ($section['bg_type']??'')=='image'?'selected':''; ?>>Image</option>
                            <option value="gradient" <?php echo ($section['bg_type']??'')=='gradient'?'selected':''; ?>>CSS Gradient</option>
                        </select>
                    </div>
                    
                    <div class="mb-3 bg-field" id="bg-color-field">
                        <label>Background Color</label>
                        <input type="color" class="form-control form-control-color w-100" name="bg_color" value="<?php echo $section['bg_color'] ?? '#ffffff'; ?>">
                    </div>

                    <div class="mb-3 bg-field d-none" id="bg-image-field">
                        <label>Background Image</label>
                        <?php if($section['image']): ?><div class="mb-2"><img src="../uploads/<?php echo $section['image']; ?>" height="50"></div><?php endif; ?>
                        <input type="file" class="form-control" name="bg_image">
                    </div>
                    
                    <div class="mb-3 bg-field d-none" id="bg-gradient-field">
                        <label>Gradient CSS (e.g., linear-gradient(...))</label>
                        <input type="text" class="form-control" name="bg_gradient" value="<?php echo htmlspecialchars($section['bg_gradient']??''); ?>" placeholder="linear-gradient(to right, #ff0000, #0000ff)">
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label>Text Color</label>
                            <input type="color" class="form-control form-control-color w-100" name="text_color" value="<?php echo $section['text_color'] ?? '#000000'; ?>">
                        </div>
                        <div class="col-6 mb-3">
                            <label>Min Height</label>
                            <input type="text" class="form-control" name="min_height" value="<?php echo $section['min_height'] ?? 'auto'; ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label>Padding Top</label>
                            <input type="text" class="form-control" name="padding_top" value="<?php echo $section['padding_top'] ?? '50px'; ?>">
                        </div>
                        <div class="col-6 mb-3">
                            <label>Padding Bottom</label>
                            <input type="text" class="form-control" name="padding_bottom" value="<?php echo $section['padding_bottom'] ?? '50px'; ?>">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    CKEDITOR.replace('.ckeditor-lite');
    
    function toggleBgFields(val) {
        document.querySelectorAll('.bg-field').forEach(el => el.classList.add('d-none'));
        if(val === 'color') document.getElementById('bg-color-field').classList.remove('d-none');
        if(val === 'image') document.getElementById('bg-image-field').classList.remove('d-none');
        if(val === 'gradient') document.getElementById('bg-gradient-field').classList.remove('d-none');
    }
    // Run on load
    toggleBgFields('<?php echo $section['bg_type'] ?? 'color'; ?>');
</script>

<?php include 'includes/footer.php'; ?>
