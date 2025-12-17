<?php
require_once '../includes/config.php';
require_once '../classes/Auth.php';

$auth = new Auth($pdo);
$auth->requireLogin();

$stmt = $pdo->query("SELECT * FROM packages ORDER BY id DESC");
$packages = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Packages</h1>
        <a href="edit_package.php" class="btn btn-primary btn-sm"><i class="bi bi-plus"></i> Add New Package</a>
    </div>

    <div class="row">
        <?php foreach ($packages as $pkg): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <?php if ($pkg['image']): ?>
                        <img src="../uploads/<?php echo $pkg['image']; ?>" class="card-img-top"
                            alt="<?php echo htmlspecialchars($pkg['title']); ?>" style="height: 200px; object-fit: cover;">
                    <?php else: ?>
                        <div class="bg-secondary text-white d-flex align-items-center justify-content-center"
                            style="height: 200px;">No Image</div>
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($pkg['title']); ?></h5>
                        <p class="card-text text-truncate"><?php echo strip_tags($pkg['description']); ?></p>
                        <div class="d-flex justify-content-between">
                            <span class="badge bg-success">$<?php echo $pkg['price']; ?></span>
                            <span class="badge bg-info text-dark"><?php echo $pkg['duration']; ?></span>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top-0 d-flex justify-content-between">
                        <a href="edit_package.php?id=<?php echo $pkg['id']; ?>" class="btn btn-sm btn-info">Edit</a>
                        <a href="delete_package.php?id=<?php echo $pkg['id']; ?>" class="btn btn-sm btn-danger"
                            onclick="return confirm('Delete this package?');">Delete</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>