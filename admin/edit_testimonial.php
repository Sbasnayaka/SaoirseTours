<?php
require_once '../includes/config.php';
require_once '../classes/Auth.php';

$auth = new Auth($pdo);
$auth->requireLogin();

$rev = ['id' => '', 'name' => '', 'review' => '', 'rating' => 5];
$title = "Add Testimonial";

if (isset($_GET['id'])) {
    $title = "Edit Testimonial";
    $stmt = $pdo->prepare("SELECT * FROM testimonials WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $rev = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $rating = $_POST['rating'];
    $review = $_POST['review'];
    $source = $_POST['source'] ?? 'direct';
    $link = $_POST['link'] ?? '';
    $review_date = $_POST['review_date'] ?? date('Y-m-d');

    // Image Upload
    $image = $rev['image'] ?? '';
    if (!empty($_FILES['image']['name'])) {
        $targetDir = "../uploads/reviews/";
        if (!is_dir($targetDir))
            mkdir($targetDir, 0777, true);

        $fileName = time() . '_' . basename($_FILES['image']['name']);
        $targetFilePath = $targetDir . $fileName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFilePath)) {
            $image = "reviews/" . $fileName;
        }
    }

    if (empty($rev['id'])) {
        $stmt = $pdo->prepare("INSERT INTO testimonials (name, rating, review, source, link, image, review_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $rating, $review, $source, $link, $image, $review_date]);
    } else {
        $stmt = $pdo->prepare("UPDATE testimonials SET name=?, rating=?, review=?, source=?, link=?, image=?, review_date=? WHERE id=?");
        $stmt->execute([$name, $rating, $review, $source, $link, $image, $review_date, $rev['id']]);
    }
    header('Location: testimonials.php');
    exit;
}

include 'includes/header.php';
?>

<div class="container-fluid">
    <h1><?php echo $title; ?></h1>
    <form method="POST" enctype="multipart/form-data">
        <div class="row">
            <div class="col-md-8">
                <div class="mb-3">
                    <label class="form-label">Client Name</label>
                    <input type="text" class="form-control" name="name"
                        value="<?php echo htmlspecialchars($rev['name']); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Review Source</label>
                    <select class="form-select" name="source">
                        <option value="direct" <?php echo ($rev['source'] ?? '') == 'direct' ? 'selected' : ''; ?>>Direct
                            / Social</option>
                        <option value="tripadvisor" <?php echo ($rev['source'] ?? '') == 'tripadvisor' ? 'selected' : ''; ?>>TripAdvisor</option>
                        <option value="google" <?php echo ($rev['source'] ?? '') == 'google' ? 'selected' : ''; ?>>Google
                            Reviews</option>
                        <option value="facebook" <?php echo ($rev['source'] ?? '') == 'facebook' ? 'selected' : ''; ?>>
                            Facebook</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Verification Link (Optional)</label>
                    <input type="url" class="form-control" name="link" placeholder="https://..."
                        value="<?php echo htmlspecialchars($rev['link'] ?? ''); ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Review Date</label>
                    <input type="date" class="form-control" name="review_date"
                        value="<?php echo htmlspecialchars($rev['review_date'] ?? date('Y-m-d')); ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Review Text</label>
                    <textarea class="form-control" name="review" rows="4"
                        required><?php echo htmlspecialchars($rev['review']); ?></textarea>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">Reviewer Photo</div>
                    <div class="card-body text-center">
                        <?php if (!empty($rev['image'])): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($rev['image']); ?>"
                                class="img-fluid rounded-circle mb-3"
                                style="width: 100px; height: 100px; object-fit: cover;">
                        <?php else: ?>
                            <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3"
                                style="width: 100px; height: 100px;">
                                <i class="bi bi-person fs-1"></i>
                            </div>
                        <?php endif; ?>

                        <input type="file" class="form-control" name="image" accept="image/*">
                        <small class="text-muted d-block mt-2">Upload Profile Pic</small>
                    </div>
                </div>

                <div class="mb-3 mt-3">
                    <label class="form-label">Rating (1-5)</label>
                    <select class="form-select" name="rating">
                        <option value="5" <?php echo $rev['rating'] == 5 ? 'selected' : ''; ?>>5 Stars</option>
                        <option value="4" <?php echo $rev['rating'] == 4 ? 'selected' : ''; ?>>4 Stars</option>
                        <option value="3" <?php echo $rev['rating'] == 3 ? 'selected' : ''; ?>>3 Stars</option>
                        <option value="2" <?php echo $rev['rating'] == 2 ? 'selected' : ''; ?>>2 Stars</option>
                        <option value="1" <?php echo $rev['rating'] == 1 ? 'selected' : ''; ?>>1 Star</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2"><i class="bi bi-save"></i> Save Review</button>
            </div>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>