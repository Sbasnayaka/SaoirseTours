<?php
require_once '../includes/config.php';
require_once '../classes/Auth.php';

$auth = new Auth($pdo);
$auth->requireLogin();

$pkg = ['id' => '', 'title' => '', 'description' => '', 'price' => '', 'duration' => '', 'image' => ''];
$title = "Add New Package";

if (isset($_GET['id'])) {
    $title = "Edit Package";
    $stmt = $pdo->prepare("SELECT * FROM packages WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $pkg = $stmt->fetch();
    if (!$pkg)
        die("Package not found");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title_in = $_POST['title'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $duration = $_POST['duration'];

    $imagePath = $pkg['image'];
    if (!empty($_FILES['image']['name'])) {
        $targetDir = "../uploads/";
        $fileName = time() . '_' . basename($_FILES['image']['name']);
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetDir . $fileName)) {
            $imagePath = $fileName;
        }
    }

    if (empty($pkg['id'])) {
        $stmt = $pdo->prepare("INSERT INTO packages (title, description, price, duration, image) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$title_in, $description, $price, $duration, $imagePath]);
    } else {
        $stmt = $pdo->prepare("UPDATE packages SET title=?, description=?, price=?, duration=?, image=? WHERE id=?");
        $stmt->execute([$title_in, $description, $price, $duration, $imagePath, $pkg['id']]);
    }
    header('Location: packages.php');
    exit;
}

include 'includes/header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-4"><?php echo $title; ?></h1>
    <form method="POST" enctype="multipart/form-data">
        <div class="row">
            <div class="col-md-8">
                <div class="mb-3">
                    <label class="form-label">Package Title</label>
                    <input type="text" class="form-control" name="title"
                        value="<?php echo htmlspecialchars($pkg['title']); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" id="editor"
                        rows="5"><?php echo htmlspecialchars($pkg['description']); ?></textarea>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Price ($)</label>
                            <input type="number" step="0.01" class="form-control" name="price"
                                value="<?php echo htmlspecialchars($pkg['price']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Duration (e.g. 3 Days)</label>
                            <input type="text" class="form-control" name="duration"
                                value="<?php echo htmlspecialchars($pkg['duration']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Image</label>
                            <?php if ($pkg['image']): ?>
                                <div class="mb-2"><img src="../uploads/<?php echo $pkg['image']; ?>" width="100%"></div>
                            <?php endif; ?>
                            <input type="file" class="form-control" name="image">
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100">Save Package</button>
                <a href="packages.php" class="btn btn-secondary w-100 mt-2">Cancel</a>
            </div>
        </div>
    </form>
</div>

<script>
    CKEDITOR.replace('editor');
</script>

<?php include 'includes/footer.php'; ?>