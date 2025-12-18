<?php
require_once '../includes/config.php';
require_once '../classes/Auth.php';

$auth = new Auth($pdo);
$auth->requireLogin();

if (!isset($_GET['page_id'])) {
    die("Page ID required.");
}

$page_id = $_GET['page_id'];
// Fetch Page Info
$pageStmt = $pdo->prepare("SELECT title FROM pages WHERE id = ?");
$pageStmt->execute([$page_id]);
$page = $pageStmt->fetch();

// Fetch Sections
$stmt = $pdo->prepare("SELECT * FROM sections WHERE page_id = ? ORDER BY display_order ASC");
$stmt->execute([$page_id]);
$sections = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Sections for: <?php echo htmlspecialchars($page['title']); ?></h1>
        <a href="edit_section.php?page_id=<?php echo $page_id; ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i>
            Add New Section</a>
    </div>

    <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i> Use sections to build your page. You can stack multiple sections with
        different background colors or images.
    </div>

    <div class="row">
        <?php if (count($sections) == 0): ?>
            <div class="col-12 text-center py-5 text-muted">
                <h4>No sections yet.</h4>
                <p>Click "Add New Section" to start building this page.</p>
            </div>
        <?php endif; ?>

        <?php foreach ($sections as $sec): ?>
            <div class="col-12 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header d-flex justify-content-between align-items-center bg-white">
                        <div class="d-flex align-items-center gap-3">
                            <span class="badge bg-secondary"><?php echo $sec['display_order']; ?></span>
                            <h5 class="mb-0"><?php echo htmlspecialchars($sec['title'] ?: 'Section ' . $sec['id']); ?></h5>
                        </div>
                        <div>
                            <a href="builder.php?section_id=<?php echo $sec['id']; ?>"
                                class="btn btn-sm btn-primary text-white"><i class="bi bi-palette"></i> Design</a>
                            <a href="edit_section.php?id=<?php echo $sec['id']; ?>&page_id=<?php echo $page_id; ?>"
                                class="btn btn-sm btn-info text-white"><i class="bi bi-gear"></i> Props</a>
                            <a href="delete_section.php?id=<?php echo $sec['id']; ?>&page_id=<?php echo $page_id; ?>"
                                class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></a>
                        </div>
                    </div>
                    <div class="card-body"
                        style="background-color: <?php echo $sec['bg_color']; ?>; color: <?php echo $sec['text_color']; ?>;">
                        <div class="row align-items-center">
                            <?php if ($sec['image']): ?>
                                <div class="col-md-3">
                                    <img src="../uploads/<?php echo $sec['image']; ?>" class="img-fluid rounded shadow-sm"
                                        style="max-height: 150px; width: 100%; object-fit: cover;">
                                </div>
                            <?php endif; ?>
                            <div class="<?php echo $sec['image'] ? 'col-md-9' : 'col-md-12'; ?>">
                                <?php if ($sec['subtitle']): ?>
                                    <h6 class="text-uppercase opacity-75"><?php echo htmlspecialchars($sec['subtitle']); ?></h6>
                                <?php endif; ?>
                                <div><?php echo htmlspecialchars(substr(strip_tags($sec['content']), 0, 200)); ?>...</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="mt-3">
        <a href="pages.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back to Pages</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>