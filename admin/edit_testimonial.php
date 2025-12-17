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

    if (empty($rev['id'])) {
        $stmt = $pdo->prepare("INSERT INTO testimonials (name, rating, review) VALUES (?, ?, ?)");
        $stmt->execute([$name, $rating, $review]);
    } else {
        $stmt = $pdo->prepare("UPDATE testimonials SET name=?, rating=?, review=? WHERE id=?");
        $stmt->execute([$name, $rating, $review, $rev['id']]);
    }
    header('Location: testimonials.php');
    exit;
}

include 'includes/header.php';
?>

<div class="container-fluid">
    <h1><?php echo $title; ?></h1>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Client Name</label>
            <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($rev['name']); ?>"
                required>
        </div>
        <div class="mb-3">
            <label class="form-label">Rating (1-5)</label>
            <select class="form-select" name="rating">
                <option value="5" <?php echo $rev['rating'] == 5 ? 'selected' : ''; ?>>5 Stars</option>
                <option value="4" <?php echo $rev['rating'] == 4 ? 'selected' : ''; ?>>4 Stars</option>
                <option value="3" <?php echo $rev['rating'] == 3 ? 'selected' : ''; ?>>3 Stars</option>
                <option value="2" <?php echo $rev['rating'] == 2 ? 'selected' : ''; ?>>2 Stars</option>
                <option value="1" <?php echo $rev['rating'] == 1 ? 'selected' : ''; ?>>1 Star</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Review</label>
            <textarea class="form-control" name="review" rows="4"
                required><?php echo htmlspecialchars($rev['review']); ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Save Testimonial</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>