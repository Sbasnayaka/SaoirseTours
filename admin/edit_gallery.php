<?php
require_once '../includes/config.php';
require_once '../classes/Auth.php';

$auth = new Auth($pdo);
$auth->requireLogin();

$img = ['id' => '', 'image' => '', 'caption' => '', 'display_order' => 0];
$title = "Upload Image";

if (isset($_GET['id'])) {
    $title = "Edit Image Details";
    $stmt = $pdo->prepare("SELECT * FROM gallery WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $img = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $caption = $_POST['caption'];
    $order = $_POST['display_order'];

    $imagePath = $img['image'];
    if (!empty($_FILES['image']['name'])) {
        $targetDir = "../uploads/";
        $fileName = time() . '_' . basename($_FILES['image']['name']);
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetDir . $fileName)) {
            $imagePath = $fileName;
        }
    }

    if (empty($img['id'])) {
        if (!empty($imagePath)) {
            $stmt = $pdo->prepare("INSERT INTO gallery (image, caption, display_order) VALUES (?, ?, ?)");
            $stmt->execute([$imagePath, $caption, $order]);
        }
    } else {
        $stmt = $pdo->prepare("UPDATE gallery SET caption=?, display_order=?, image=? WHERE id=?");
        $stmt->execute([$caption, $order, $imagePath, $img['id']]);
    }
    header('Location: gallery.php');
    exit;
}

include 'includes/header.php';
?>

<div class="container-fluid">
    <h1><?php echo $title; ?></h1>
    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label class="form-label">Image</label>
            <?php if ($img['image']): ?>
                <div class="mb-2"><img src="../uploads/<?php echo $img['image']; ?>" height="100"></div>
            <?php endif; ?>
            <input type="file" class="form-control" name="image" <?php echo empty($img['id']) ? 'required' : ''; ?>>
        </div>
        <div class="mb-3">
            <label class="form-label">Caption</label>
            <input type="text" class="form-control" name="caption"
                value="<?php echo htmlspecialchars($img['caption']); ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Display Order</label>
            <input type="number" class="form-control" name="display_order"
                value="<?php echo htmlspecialchars($img['display_order']); ?>">
        </div>
        <button type="submit" class="btn btn-primary">Save</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>