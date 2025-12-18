<?php
require_once '../includes/config.php';
require_once '../classes/Auth.php';

$auth = new Auth($pdo);
$auth->requireLogin();

$sec = [
    'id' => '',
    'title' => '',
    'subtitle' => '',
    'content' => '',
    'image' => '',
    'bg_color' => '#ffffff',
    'text_color' => '#000000',
    'display_order' => 0,
    'layout_type' => 'default'
];
$title_page = "Add Section";

// Get Page ID 
$page_id = $_GET['page_id'] ?? null;
if (!$page_id && isset($_POST['page_id']))
    $page_id = $_POST['page_id'];
if (!$page_id)
    die("Page ID missing.");

// Edit Mode
if (isset($_GET['id'])) {
    $title_page = "Edit Section";
    $stmt = $pdo->prepare("SELECT * FROM sections WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $sec = $stmt->fetch();
    $page_id = $sec['page_id'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $subtitle = $_POST['subtitle'];
    $content = $_POST['content'];
    $bg_color = $_POST['bg_color'];
    $text_color = $_POST['text_color'];
    $display_order = $_POST['display_order'];
    $layout_type = $_POST['layout_type'];

    // Handle Image
    $imagePath = $sec['image'];
    if (!empty($_FILES['image']['name'])) {
        $targetDir = "../uploads/";
        if (!is_dir($targetDir))
            mkdir($targetDir, 0755, true);
        $fileName = time() . '_' . basename($_FILES['image']['name']);
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetDir . $fileName)) {
            $imagePath = $fileName;
        }
    }

    if (empty($sec['id'])) {
        // Insert
        $stmt = $pdo->prepare("INSERT INTO sections (page_id, title, subtitle, content, image, bg_color, text_color, display_order, layout_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$page_id, $title, $subtitle, $content, $imagePath, $bg_color, $text_color, $display_order, $layout_type]);
    } else {
        // Update
        $stmt = $pdo->prepare("UPDATE sections SET title=?, subtitle=?, content=?, image=?, bg_color=?, text_color=?, display_order=?, layout_type=? WHERE id=?");
        $stmt->execute([$title, $subtitle, $content, $imagePath, $bg_color, $text_color, $display_order, $layout_type, $sec['id']]);
    }

    header("Location: page_sections.php?page_id=$page_id");
    exit;
}

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="page_sections.php?page_id=<?php echo $page_id; ?>" class="btn btn-outline-secondary btn-sm"><i
                class="bi bi-arrow-left"></i> Back</a>
        <h1 class="h3 mb-0"><?php echo $title_page; ?></h1>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="page_id" value="<?php echo $page_id; ?>">

        <div class="row">
            <!-- Left Column: Content -->
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">Main Content</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Section Title (Heading)</label>
                            <input type="text" class="form-control" name="title"
                                value="<?php echo htmlspecialchars($sec['title']); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Subtitle (Small text above heading)</label>
                            <input type="text" class="form-control" name="subtitle"
                                value="<?php echo htmlspecialchars($sec['subtitle']); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Body Content</label>
                            <textarea class="form-control" name="content" id="editor"
                                rows="10"><?php echo htmlspecialchars($sec['content']); ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">Media & Image</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Section Image</label>
                            <?php if ($sec['image']): ?>
                                <div class="mb-2 p-2 border rounded bg-light">
                                    <img src="../uploads/<?php echo $sec['image']; ?>" height="100" class="rounded">
                                    <small class="d-block text-muted mt-1">Current Image</small>
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" name="image">
                            <small class="text-muted">Used as background or side image depending on layout.</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Settings -->
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">Display Settings</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Layout Type</label>
                            <select class="form-select" name="layout_type">
                                <option value="default" <?php echo $sec['layout_type'] == 'default' ? 'selected' : ''; ?>>
                                    Standard (Text + Image Side-by-Side)</option>
                                <option value="full-width" <?php echo $sec['layout_type'] == 'full-width' ? 'selected' : ''; ?>>Centered Content (No Image Split)</option>
                                <option value="bg-image" <?php echo $sec['layout_type'] == 'bg-image' ? 'selected' : ''; ?>>Full Background Image (Hero Style)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Display Order</label>
                            <input type="number" class="form-control" name="display_order"
                                value="<?php echo $sec['display_order']; ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Background Color</label>
                            <input type="color" class="form-control form-control-color w-100" name="bg_color"
                                value="<?php echo htmlspecialchars($sec['bg_color']); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Text Color</label>
                            <input type="color" class="form-control form-control-color w-100" name="text_color"
                                value="<?php echo htmlspecialchars($sec['text_color']); ?>">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 btn-lg shadow">Save Section</button>
            </div>
        </div>
    </form>
</div>

<script>
    CKEDITOR.replace('editor');
</script>

<?php include 'includes/footer.php'; ?>