<?php
require_once '../includes/config.php';
require_once '../classes/Auth.php';

$auth = new Auth($pdo);
$auth->requireLogin();

$stmt = $pdo->query("SELECT * FROM testimonials ORDER BY created_at DESC");
$reviews = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Testimonials</h1>
        <a href="edit_testimonial.php" class="btn btn-primary btn-sm"><i class="bi bi-plus"></i> Add Testimonial</a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Name</th>
                            <th>Rating</th>
                            <th>Review</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reviews as $rev): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($rev['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($rev['name']); ?></td>
                                <td>
                                    <?php for ($i = 0; $i < $rev['rating']; $i++)
                                        echo '⭐'; ?>
                                </td>
                                <td><?php echo htmlspecialchars(substr($rev['review'], 0, 80)); ?>...</td>
                                <td>
                                    <a href="edit_testimonial.php?id=<?php echo $rev['id']; ?>"
                                        class="btn btn-info btn-sm">Edit</a>
                                    <a href="delete_testimonial.php?id=<?php echo $rev['id']; ?>"
                                        class="btn btn-danger btn-sm" onclick="return confirm('Delete?');">Delete</a>
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