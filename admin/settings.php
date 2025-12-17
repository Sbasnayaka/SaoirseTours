<?php
require_once '../includes/config.php';
require_once '../classes/Auth.php';

$auth = new Auth($pdo);
$auth->requireLogin();

$message = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $site_title = $_POST['site_title'];
    $tagline = $_POST['tagline'];
    $bg_color = $_POST['bg_color'];
    $primary_color = $_POST['primary_color'];
    $font_family = $_POST['font_family'];
    $contact_email = $_POST['contact_email'];

    // Handle Logo Upload
    $logoPath = null;
    if (!empty($_FILES['logo']['name'])) {
        $targetDir = "../uploads/";
        $fileName = time() . '_' . basename($_FILES['logo']['name']);
        $targetFile = $targetDir . $fileName;
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

        // Validate
        $check = getimagesize($_FILES['logo']['tmp_name']);
        if ($check !== false) {
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $targetFile)) {
                $logoPath = $fileName;
            }
        }
    }

    // Update Query
    $sql = "UPDATE settings SET 
            site_title = :site_title, 
            tagline = :tagline,
            bg_color = :bg_color,
            primary_color = :primary_color,
            font_family = :font_family,
            contact_email = :contact_email";

    $params = [
        ':site_title' => $site_title,
        ':tagline' => $tagline,
        ':bg_color' => $bg_color,
        ':primary_color' => $primary_color,
        ':font_family' => $font_family,
        ':contact_email' => $contact_email
    ];

    if ($logoPath) {
        $sql .= ", logo = :logo";
        $params[':logo'] = $logoPath;
    }

    $sql .= " WHERE id = 1";
    $stmt = $pdo->prepare($sql);

    if ($stmt->execute($params)) {
        $message = '<div class="alert alert-success">Settings updated successfully!</div>';
    } else {
        $message = '<div class="alert alert-danger">Error updating settings.</div>';
    }
}

// Fetch Current Settings
$stmt = $pdo->query("SELECT * FROM settings WHERE id = 1");
$settings = $stmt->fetch();
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <h2>Site Settings</h2>
    <?php echo $message; ?>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Site Title</label>
                        <input type="text" class="form-control" name="site_title"
                            value="<?php echo htmlspecialchars($settings['site_title'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tagline</label>
                        <input type="text" class="form-control" name="tagline"
                            value="<?php echo htmlspecialchars($settings['tagline'] ?? ''); ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Background Color</label>
                        <input type="color" class="form-control form-control-color" name="bg_color"
                            value="<?php echo htmlspecialchars($settings['bg_color'] ?? '#ffffff'); ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Primary Theme Color</label>
                        <input type="color" class="form-control form-control-color" name="primary_color"
                            value="<?php echo htmlspecialchars($settings['primary_color'] ?? '#0d6efd'); ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Font Family</label>
                        <select class="form-select" name="font_family">
                            <option value="Arial, sans-serif" <?php echo ($settings['font_family'] == 'Arial, sans-serif') ? 'selected' : ''; ?>>Arial</option>
                            <option value="'Roboto', sans-serif" <?php echo ($settings['font_family'] == "'Roboto', sans-serif") ? 'selected' : ''; ?>>Roboto</option>
                            <option value="'Open Sans', sans-serif" <?php echo ($settings['font_family'] == "'Open Sans', sans-serif") ? 'selected' : ''; ?>>Open Sans</option>
                            <option value="'Lato', sans-serif" <?php echo ($settings['font_family'] == "'Lato', sans-serif") ? 'selected' : ''; ?>>Lato</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Contact Email (for form submissions)</label>
                    <input type="email" class="form-control" name="contact_email"
                        value="<?php echo htmlspecialchars($settings['contact_email'] ?? ''); ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Website Logo</label>
                    <?php if (!empty($settings['logo'])): ?>
                        <div class="mb-2">
                            <img src="../uploads/<?php echo htmlspecialchars($settings['logo']); ?>" alt="Current Logo"
                                style="max-height: 100px;">
                        </div>
                    <?php endif; ?>
                    <input type="file" class="form-control" name="logo" accept="image/*">
                </div>

                <button type="submit" class="btn btn-primary">Save Settings</button>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>