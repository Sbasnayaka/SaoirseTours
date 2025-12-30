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
                <table class="table table-hover align-middle" width="100%">
                    <thead class="table-light">
                        <tr>
                            <th>User</th>
                            <th>Source</th>
                            <th>Rating</th>
                            <th>Review</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reviews as $rev):
                            $imgSrc = !empty($rev['image']) ? '../uploads/' . $rev['image'] : 'https://ui-avatars.com/api/?name=' . urlencode($rev['name']);

                            // Source Badge Logic
                            switch ($rev['source']) {
                                case 'tripadvisor':
                                    $badge = '<span class="badge bg-success rounded-pill"><i class="bi bi-eye-fill"></i> TripAdvisor</span>';
                                    break;
                                case 'google':
                                    $badge = '<span class="badge bg-white text-dark border rounded-pill"><span class="text-primary">G</span><span class="text-danger">o</span><span class="text-warning">o</span><span class="text-primary">g</span><span class="text-success">l</span><span class="text-danger">e</span></span>';
                                    break;
                                case 'facebook':
                                    $badge = '<span class="badge bg-primary rounded-pill"><i class="bi bi-facebook"></i> Facebook</span>';
                                    break;
                                default:
                                    $badge = '<span class="badge bg-secondary rounded-pill">Direct</span>';
                            }
                            ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo $imgSrc; ?>" class="rounded-circle me-2"
                                            style="width: 40px; height: 40px; object-fit: cover;">
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($rev['name']); ?></div>
                                    </div>
                                </td>
                                <td><?php echo $badge; ?></td>
                                <td>
                                    <div class="text-warning small" style="width: 80px;">
                                        <?php for ($i = 0; $i < $rev['rating']; $i++)
                                            echo '⭐'; ?>
                                    </div>
                                </td>
                                <td>
                                    <small
                                        class="text-muted fst-italic">"<?php echo htmlspecialchars(substr($rev['review'], 0, 50)); ?>..."</small>
                                    <?php if (!empty($rev['link'])): ?>
                                        <a href="<?php echo htmlspecialchars($rev['link']); ?>" target="_blank"
                                            class="text-decoration-none ms-1" title="Verify Link"><i
                                                class="bi bi-link-45deg"></i></a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?php echo !empty($rev['review_date']) ? date('M d, Y', strtotime($rev['review_date'])) : date('M d, Y', strtotime($rev['created_at'])); ?></small>
                                </td>
                                <td>
                                    <a href="edit_testimonial.php?id=<?php echo $rev['id']; ?>"
                                        class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil-square"></i></a>
                                    <a href="delete_testimonial.php?id=<?php echo $rev['id']; ?>"
                                        class="btn btn-outline-danger btn-sm"
                                        onclick="return confirm('Delete this review?');"><i class="bi bi-trash"></i></a>
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