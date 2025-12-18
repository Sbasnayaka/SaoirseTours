<?php
require_once '../includes/config.php';
require_once '../classes/Auth.php';

$auth = new Auth($pdo);
$auth->requireLogin();

$svc = ['id' => '', 'title' => '', 'description' => '', 'icon' => 'bi-tree', 'image' => ''];
$title = "Add Service";

if (isset($_GET['id'])) {
    $title = "Edit Service";
    $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $svc = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title_in = $_POST['title'];
    $description = $_POST['description'];
    $icon = $_POST['icon'];

    $imagePath = $svc['image'];
    if (!empty($_FILES['image']['name'])) {
        $targetDir = "../uploads/";
        if (!is_dir($targetDir))
            mkdir($targetDir, 0755, true);
        $fileName = time() . '_' . basename($_FILES['image']['name']);
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetDir . $fileName)) {
            $imagePath = $fileName;
        }
    }

    if (empty($svc['id'])) {
        $stmt = $pdo->prepare("INSERT INTO services (title, description, icon, image) VALUES (?, ?, ?, ?)");
        $stmt->execute([$title_in, $description, $icon, $imagePath]);
    } else {
        $stmt = $pdo->prepare("UPDATE services SET title=?, description=?, icon=?, image=? WHERE id=?");
        $stmt->execute([$title_in, $description, $icon, $imagePath, $svc['id']]);
    }
    header('Location: services.php');
    exit;
}

include 'includes/header.php';
?>

<div class="container-fluid">
    <h1><?php echo $title; ?></h1>
    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars($svc['title']); ?>"
                required>
        </div>
        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea class="form-control" name="description"
                rows="3"><?php echo htmlspecialchars($svc['description']); ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Bootstrap Icon Class (e.g., bi-car-front, bi-tree, bi-camera)</label>
            <input type="text" class="form-control" name="icon" value="<?php echo htmlspecialchars($svc['icon']); ?>">
            <small><a href="https://icons.getbootstrap.com/" target="_blank">Browse Icons</a></small>
        </div>
        <div class="mb-3">
            <label class="form-label">Image (Optional)</label>
            <input type="file" class="form-control" name="image">
        </div>
        <button type="submit" class="btn btn-primary">Save</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>