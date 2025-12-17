<?php
require_once '../includes/config.php';
require_once '../classes/Auth.php';

$auth = new Auth($pdo);
$auth->requireLogin();

$stmt = $pdo->query("SELECT * FROM services ORDER BY id DESC");
$services = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Services</h1>
        <a href="edit_service.php" class="btn btn-primary btn-sm"><i class="bi bi-plus"></i> Add Service</a>
    </div>

    <div class="row">
        <?php foreach ($services as $svc): ?>
            <div class="col-md-3 mb-4">
                <div class="card h-100">
                    <?php if ($svc['image']): ?>
                        <img src="../uploads/<?php echo $svc['image']; ?>" class="card-img-top"
                            style="height: 150px; object-fit: cover;">
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi <?php echo $svc['icon']; ?>"></i>
                            <?php echo htmlspecialchars($svc['title']); ?></h5>
                        <p class="card-text small">
                            <?php echo htmlspecialchars(substr(strip_tags($svc['description']), 0, 100)) . '...'; ?></p>
                    </div>
                    <div class="card-footer d-flex justify-content-between">
                        <a href="edit_service.php?id=<?php echo $svc['id']; ?>" class="btn btn-sm btn-info">Edit</a>
                        <a href="delete_service.php?id=<?php echo $svc['id']; ?>" class="btn btn-sm btn-danger"
                            onclick="return confirm('Delete this service?');">Delete</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>