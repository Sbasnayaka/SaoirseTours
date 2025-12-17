<?php
require_once '../includes/config.php';
require_once '../classes/Auth.php';

$auth = new Auth($pdo);
$auth->requireLogin();

$page = ['id' => '', 'title' => '', 'slug' => '', 'content' => '', 'meta_title' => '', 'meta_desc' => ''];
$title = "Add New Page";

if (isset($_GET['id'])) {
    $title = "Edit Page";
    $stmt = $pdo->prepare("SELECT * FROM pages WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $page = $stmt->fetch();
    if (!$page)
        die("Page not found");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title_in = $_POST['title'];
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $_POST['slug'])));
    $content = $_POST['content'];
    $meta_title = $_POST['meta_title'];
    $meta_desc = $_POST['meta_desc'];

    if (empty($page['id'])) {
        // Insert
        $stmt = $pdo->prepare("INSERT INTO pages (title, slug, content, meta_title, meta_desc) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$title_in, $slug, $content, $meta_title, $meta_desc]);
    } else {
        // Update
        $stmt = $pdo->prepare("UPDATE pages SET title=?, slug=?, content=?, meta_title=?, meta_desc=? WHERE id=?");
        $stmt->execute([$title_in, $slug, $content, $meta_title, $meta_desc, $page['id']]);
    }
    header('Location: pages.php');
    exit;
}

include 'includes/header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?php echo $title; ?></h1>
    <form method="POST">
        <div class="row">
            <div class="col-md-8">
                <div class="mb-3">
                    <label class="form-label">Page Title</label>
                    <input type="text" class="form-control" name="title"
                        value="<?php echo htmlspecialchars($page['title']); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Slug (URL)</label>
                    <input type="text" class="form-control" name="slug"
                        value="<?php echo htmlspecialchars($page['slug']); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Content</label>
                    <textarea class="form-control" name="content" id="editor"
                        rows="10"><?php echo htmlspecialchars($page['content']); ?></textarea>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">SEO Settings</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Meta Title</label>
                            <input type="text" class="form-control" name="meta_title"
                                value="<?php echo htmlspecialchars($page['meta_title']); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Meta Description</label>
                            <textarea class="form-control" name="meta_desc"
                                rows="4"><?php echo htmlspecialchars($page['meta_desc']); ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-success w-100">Save Page</button>
                    <a href="pages.php" class="btn btn-secondary w-100 mt-2">Cancel</a>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    CKEDITOR.replace('editor');
</script>

<?php include 'includes/footer.php'; ?>