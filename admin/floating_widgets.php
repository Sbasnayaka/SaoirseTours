<?php
require_once '../includes/config.php';
require_once '../classes/Auth.php';

$auth = new Auth($pdo);
$auth->requireLogin();

$success = "";

// Initialize Settings in DB if missing (Self-Healing)
try {
    $pdo->query("SELECT 1 FROM footer_settings LIMIT 1");
} catch (PDOException $e) {
    // Should operate on existing table, but safety check
    die("Database Error: Table footer_settings missing. Please run Footer Builder first.");
}

// HANDLE SAVE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Fetch current to merge
    $curr = $pdo->query("SELECT settings FROM footer_settings WHERE id = 1")->fetch();
    $s = $curr ? json_decode($curr['settings'], true) : [];

    // Update Live Chat Settings
    $s['widgets']['live_chat'] = [
        'tawk_to_code' => $_POST['tawk_to_code'] ?? '',
        'whatsapp_number' => $_POST['whatsapp_number'] ?? '',
        'messenger_id' => $_POST['messenger_id'] ?? '',
        'contact_email' => $_POST['contact_email'] ?? '',
        'styles' => [
            'icon_size' => $_POST['icon_size'] ?? '60',
            'bottom_offset' => $_POST['bottom_offset'] ?? '30',
            'side_offset' => $_POST['side_offset'] ?? '30',
            'z_index' => $_POST['z_index'] ?? '9999'
        ]
    ];

    $json = json_encode($s);
    $stmt = $pdo->prepare("UPDATE footer_settings SET settings = ? WHERE id = 1");
    if ($stmt->execute([$json])) {
        $success = "Floating Widgets configuration saved successfully!";
    } else {
        $success = "Error saving configuration.";
    }
}

// FETCH SETTINGS
$curr = $pdo->query("SELECT settings FROM footer_settings WHERE id = 1")->fetch();
$s = $curr ? json_decode($curr['settings'], true) : [];
$chat = $s['widgets']['live_chat'] ?? [];
$styles = $chat['styles'] ?? ['icon_size' => '60', 'bottom_offset' => '30', 'side_offset' => '30', 'z_index' => '9999'];

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Floating Widgets Manager</h1>
        <a href="../index.php" target="_blank" class="btn btn-outline-primary"><i class="bi bi-eye"></i> View Site</a>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="row">
            <!-- LEFT COLUMN: CONTENT -->
            <div class="col-lg-8">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 bg-primary text-white">
                        <h6 class="m-0 font-weight-bold"><i class="bi bi-chat-dots"></i> Widget Content</h6>
                    </div>
                    <div class="card-body">

                        <!-- SOCIAL SECTION -->
                        <h5 class="text-primary mb-3"><i class="bi bi-person-lines-fill"></i> Social Dock (Bottom Left)
                        </h5>
                        <p class="text-muted small">These icons will float on the bottom-left of the screen for quick
                            contact.</p>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">WhatsApp Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-whatsapp"></i></span>
                                    <input type="text" class="form-control" name="whatsapp_number"
                                        placeholder="e.g. 94771234567"
                                        value="<?php echo htmlspecialchars($chat['whatsapp_number'] ?? ''); ?>">
                                </div>
                                <small class="text-muted">Format: CountryCode + Number (No + or spaces)</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Messenger Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-messenger"></i></span>
                                    <input type="text" class="form-control" name="messenger_id"
                                        placeholder="e.g. FabulousAsiaTours"
                                        value="<?php echo htmlspecialchars($chat['messenger_id'] ?? ''); ?>">
                                </div>
                                <small class="text-muted">Your Facebook Page Username/ID.</small>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Emergency Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control" name="contact_email"
                                        placeholder="e.g. info@saoirsetours.com"
                                        value="<?php echo htmlspecialchars($chat['contact_email'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- CHATBOT SECTION -->
                        <h5 class="text-primary mb-3"><i class="bi bi-robot"></i> Live Chat (Bottom Right)</h5>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tawk.to Widget Code</label>
                            <textarea class="form-control font-monospace bg-light" name="tawk_to_code" rows="5"
                                placeholder="Paste the <script>...</script> code from Tawk.to here..."><?php echo htmlspecialchars($chat['tawk_to_code'] ?? ''); ?></textarea>
                            <small class="text-muted">Sign up at tawk.to and paste the widget code here.</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: STYLES -->
            <div class="col-lg-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 bg-secondary text-white">
                        <h6 class="m-0 font-weight-bold"><i class="bi bi-palette"></i> Style & Position</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Icon Size (px)</label>
                            <input type="number" class="form-control" name="icon_size"
                                value="<?php echo htmlspecialchars($styles['icon_size']); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Bottom Offset (px)</label>
                            <input type="number" class="form-control" name="bottom_offset"
                                value="<?php echo htmlspecialchars($styles['bottom_offset']); ?>">
                            <small class="text-muted">Distance from bottom of screen.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Side Offset (px)</label>
                            <input type="number" class="form-control" name="side_offset"
                                value="<?php echo htmlspecialchars($styles['side_offset']); ?>">
                            <small class="text-muted">Distance from left/right edges.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Z-Index (Layer Priority)</label>
                            <input type="number" class="form-control" name="z_index"
                                value="<?php echo htmlspecialchars($styles['z_index']); ?>">
                            <small class="text-muted">Increase this if icons are hidden behind other elements.</small>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">Save Configuration</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>