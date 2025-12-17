<?php
require_once '../includes/config.php';
require_once '../classes/Auth.php';

$auth = new Auth($pdo);
$auth->requireLogin();

$stmt = $pdo->query("SELECT * FROM gallery ORDER BY display_order ASC, id DESC");
$gallery = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Photo Gallery</h1>
        <a href="edit_gallery.php" class="btn btn-primary btn-sm"><i class="bi bi-cloud-upload"></i> Upload Image</a>
    </div>

    <div class="row">
        <?php foreach ($gallery as $img): ?>
            <div class="col-md-3 mb-4">
                <div class="card h-100">
                    <img src="../uploads/<?php echo $img['image']; ?>" class="card-img-top"
                        style="height: 200px; object-fit: cover;">
                    <div class="card-body">
                        <p class="card-text text-center"><?php echo htmlspecialchars($img['caption'] ?: 'No Caption'); ?>
                        </p>
                    </div>
                    <div class="card-footer d-flex justify-content-between">
                        <a href="edit_gallery.php?id=<?php echo $img['id']; ?>" class="btn btn-sm btn-info">Edit</a>
                        <a href="delete_gallery.php?id=<?php echo $img['id']; ?>" class="btn btn-sm btn-danger"
                            onclick="return confirm('Delete image?');">Delete</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>