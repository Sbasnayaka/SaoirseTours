<?php
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $type = $_POST['type'] ?? 'inquiry';
    $message = trim($_POST['message']);

    // Capture Extra booking details
    $extras = [];
    if (!empty($_POST['package_interest']))
        $extras[] = "Package: " . $_POST['package_interest'];
    if (!empty($_POST['country']))
        $extras[] = "Country: " . $_POST['country'];
    if (!empty($_POST['phone']))
        $extras[] = "Phone: " . $_POST['phone'];
    if (!empty($_POST['arrival_date']))
        $extras[] = "Arrival: " . $_POST['arrival_date'];
    if (!empty($_POST['departure_date']))
        $extras[] = "Departure: " . $_POST['departure_date'];
    if (isset($_POST['adults']))
        $extras[] = "Adults: " . $_POST['adults'];
    if (isset($_POST['children']))
        $extras[] = "Children: " . $_POST['children'];

    // Append extras to message
    if (!empty($extras)) {
        $message .= "\n\n--- Booking Details ---\n" . implode("\n", $extras);
    }

    // Basic Validation
    if ($name && $email && $message) {
        $stmt = $pdo->prepare("INSERT INTO inquiries (name, email, type, message) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$name, $email, $type, $message])) {
            $msg = '<div class="alert alert-success">Thank you! Your inquiry has been sent. We will contact you soon.</div>';
        } else {
            $msg = '<div class="alert alert-danger">Something went wrong. Please try again.</div>';
        }
    } else {
        $msg = '<div class="alert alert-warning">Please fill in all fields.</div>';
    }
}

$prefillPackage = isset($_GET['package']) ? "I am interested in booking: " . htmlspecialchars($_GET['package']) : '';
?>

<!-- Builder Sections (Top) -->
<?php
if (isset($page['id'])) {
    require_once 'classes/PageBuilder.php';
    $stmt = $pdo->prepare("SELECT * FROM sections WHERE page_id = ? AND display_order < 0 ORDER BY display_order ASC");
    $stmt->execute([$page['id']]);
    while ($sect = $stmt->fetch()) {
        PageBuilder::renderSection($sect, $pdo);
    }
}
?>

<div class="container py-5">
    <h1 class="text-center section-title">Contact Us</h1>

    <div class="row justify-content-center mt-5">
        <div class="col-md-5 mb-4">
            <div class="p-4 bg-light rounded shadow-sm h-100">
                <h3>Get in Touch</h3>
                <p>We are here to help you plan your perfect trip.</p>
                <ul class="list-unstyled mt-4">
                    <li class="mb-3"><i class="bi bi-geo-alt text-primary me-2"></i> Habarana, Sri Lanka</li>
                    <li class="mb-3"><i class="bi bi-envelope text-primary me-2"></i>
                        <?php echo $settings['contact_email'] ?: 'info@saoirsetours.com'; ?></li>
                    <li class="mb-3"><i class="bi bi-telephone text-primary me-2"></i>
                        <?php echo $settings['contact_phone'] ?: '+94 77 123 4567'; ?></li>
                </ul>
            </div>
        </div>

        <div class="col-md-7">
            <?php echo $msg; ?>
            <form method="POST" class="p-4 border rounded shadow-sm bg-white">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Your Name</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Inquiry Type</label>
                    <select class="form-select" name="type">
                        <option value="inquiry">General Inquiry</option>
                        <option value="booking" <?php echo $prefillPackage ? 'selected' : ''; ?>>Booking Request
                        </option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Message</label>
                    <textarea class="form-control" name="message" rows="5"
                        required><?php echo $prefillPackage; ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary w-100">Send Message</button>
            </form>
        </div>
    </div>
</div>

<!-- Builder Sections (Bottom) -->
<?php
if (isset($page['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM sections WHERE page_id = ? AND display_order >= 0 ORDER BY display_order ASC");
    $stmt->execute([$page['id']]);
    while ($sect = $stmt->fetch()) {
        PageBuilder::renderSection($sect, $pdo);
    }
}
?>