<?php
require_once '../includes/config.php';
require_once '../classes/Auth.php';

$auth = new Auth($pdo);
$auth->requireLogin();

$stmt = $pdo->query("SELECT * FROM pages ORDER BY id ASC");
$pages = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Pages</h1>
        <a href="edit_page.php" class="btn btn-primary btn-sm"><i class="bi bi-plus"></i> Add New Page</a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Slug</th>
                            <th>Last Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pages as $page): ?>
                            <tr>
                                <td><?php echo $page['id']; ?></td>
                                <td><?php echo htmlspecialchars($page['title']); ?></td>
                                <td><?php echo htmlspecialchars($page['slug']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($page['created_at'])); ?></td>
                                <td>
                                    <a href="page_sections.php?page_id=<?php echo $page['id']; ?>"
                                        class="btn btn-warning btn-sm"><i class="bi bi-layers"></i> Manage Sections</a>
                                    <a href="edit_page.php?id=<?php echo $page['id']; ?>"
                                        class="btn btn-info btn-sm">SEO/Title</a>
                                    <a href="delete_page.php?id=<?php echo $page['id']; ?>" class="btn btn-danger btn-sm"
                                        onclick="return confirm('Delete this page?');">Delete</a>
                                    <a href="../<?php echo $page['slug']; ?>" target="_blank"
                                        class="btn btn-secondary btn-sm">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>