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

    // Decode JSON fields
    $itinerary = json_decode($pkg['itinerary'] ?? '[]', true) ?: [];
    $inclusions = json_decode($pkg['inclusions'] ?? '[]', true) ?: [];
    $exclusions = json_decode($pkg['exclusions'] ?? '[]', true) ?: [];
    $sidebar = json_decode($pkg['sidebar_settings'] ?? '{}', true) ?: [];
} else {
    // Defaults for new package
    $itinerary = [];
    $inclusions = [];
    $exclusions = [];
    $sidebar = ['show_name' => 1, 'show_email' => 1];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title_in = $_POST['title'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $duration = $_POST['duration'];

    // Process JSON Fields
    $itinerary = json_encode($_POST['itinerary'] ?? []);
    $inclusions = json_encode(array_filter($_POST['inclusions'] ?? [])); // Filter empty
    $exclusions = json_encode(array_filter($_POST['exclusions'] ?? []));
    $sidebar_settings = json_encode($_POST['sidebar_settings'] ?? []);

    $imagePath = $pkg['image'];
    if (!empty($_FILES['image']['name'])) {
        $targetDir = "../uploads/";
        if (!is_dir($targetDir))
            mkdir($targetDir, 0755, true);
        $fileName = time() . '_' . basename($_FILES['image']['name']);
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetDir . $fileName)) {
            $imagePath = $fileName;
        }
    }

    if (empty($pkg['id'])) {
        $stmt = $pdo->prepare("INSERT INTO packages (title, description, price, duration, image, itinerary, inclusions, exclusions, sidebar_settings) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title_in, $description, $price, $duration, $imagePath, $itinerary, $inclusions, $exclusions, $sidebar_settings]);
    } else {
        $stmt = $pdo->prepare("UPDATE packages SET title=?, description=?, price=?, duration=?, image=?, itinerary=?, inclusions=?, exclusions=?, sidebar_settings=? WHERE id=?");
        $stmt->execute([$title_in, $description, $price, $duration, $imagePath, $itinerary, $inclusions, $exclusions, $sidebar_settings, $pkg['id']]);
    }
    header('Location: packages.php');
    exit;
}

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><?php echo $title; ?></h1>
    </div>

    <form method="POST" enctype="multipart/form-data" id="packageForm">

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" id="packageTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general"
                    type="button" role="tab">General Info</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="itinerary-tab" data-bs-toggle="tab" data-bs-target="#itinerary"
                    type="button" role="tab">Travel Plan (Itinerary)</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="inex-tab" data-bs-toggle="tab" data-bs-target="#inex" type="button"
                    role="tab">Include / Exclude</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="sidebar-tab" data-bs-toggle="tab" data-bs-target="#sidebar" type="button"
                    role="tab">Booking Form</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="styling-tab" data-bs-toggle="tab" data-bs-target="#styling" type="button"
                    role="tab">Styling</button>
            </li>
        </ul>

        <div class="tab-content" id="packageTabContent">

            <!-- 1. GENERAL INFO -->
            <div class="tab-pane fade show active" id="general" role="tabpanel">
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
                                    <label class="form-label">Duration</label>
                                    <input type="text" class="form-control" name="duration"
                                        value="<?php echo htmlspecialchars($pkg['duration']); ?>"
                                        placeholder="e.g. 5 Days" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Cover Image</label>
                                    <?php if ($pkg['image']): ?>
                                        <div class="mb-2"><img src="../uploads/<?php echo $pkg['image']; ?>" width="100%"
                                                class="rounded"></div>
                                    <?php endif; ?>
                                    <input type="file" class="form-control" name="image">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. TRAVEL PLAN (ITINERARY) -->
            <div class="tab-pane fade" id="itinerary" role="tabpanel">
                <div class="alert alert-info py-2"><i class="bi bi-info-circle"></i> Build your day-by-day itinerary
                    here.</div>
                <div id="itinerary-list">
                    <?php
                    foreach ($itinerary as $index => $day):
                        ?>
                        <div class="card mb-3 itinerary-item">
                            <div class="card-header d-flex justify-content-between align-items-center bg-light">
                                <span class="fw-bold">Day <?php echo $index + 1; ?></span>
                                <button type="button" class="btn btn-sm btn-outline-danger remove-day"><i
                                        class="bi bi-trash"></i></button>
                            </div>
                            <div class="card-body p-3">
                                <div class="mb-2">
                                    <label class="small text-muted">Day Title (e.g. Arrival in Kandy)</label>
                                    <input type="text" class="form-control mb-2"
                                        name="itinerary[<?php echo $index; ?>][title]"
                                        value="<?php echo htmlspecialchars($day['title'] ?? ''); ?>"
                                        placeholder="Enter Title">
                                </div>
                                <div>
                                    <label class="small text-muted">Activities / Description</label>
                                    <textarea class="form-control" name="itinerary[<?php echo $index; ?>][desc]"
                                        rows="2"><?php echo htmlspecialchars($day['desc'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn btn-outline-primary" onclick="addDay()"><i class="bi bi-plus-lg"></i>
                    Add Day</button>
            </div>

            <!-- 3. INCLUDE / EXCLUDE -->
            <div class="tab-pane fade" id="inex" role="tabpanel">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-success"><i class="bi bi-check-circle-fill"></i> Inclusions (What's included)
                        </h6>
                        <div id="inclusions-list">
                            <?php foreach ($inclusions as $inc): ?>
                                <div class="input-group mb-2">
                                    <input type="text" class="form-control" name="inclusions[]"
                                        value="<?php echo htmlspecialchars($inc); ?>">
                                    <button type="button" class="btn btn-outline-danger"
                                        onclick="this.parentElement.remove()">X</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-success mt-2"
                            onclick="addItem('inclusions-list', 'inclusions[]')">+ Add Inclusion</button>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-danger"><i class="bi bi-x-circle-fill"></i> Exclusions (What's NOT included)
                        </h6>
                        <div id="exclusions-list">
                            <?php foreach ($exclusions as $exc): ?>
                                <div class="input-group mb-2">
                                    <input type="text" class="form-control" name="exclusions[]"
                                        value="<?php echo htmlspecialchars($exc); ?>">
                                    <button type="button" class="btn btn-outline-danger"
                                        onclick="this.parentElement.remove()">X</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger mt-2"
                            onclick="addItem('exclusions-list', 'exclusions[]')">+ Add Exclusion</button>
                    </div>
                </div>
            </div>

            <!-- 4. BOOKING FORM SETTINGS -->
            <!-- 4. BOOKING FORM SETTINGS -->
            <div class="tab-pane fade" id="sidebar" role="tabpanel">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-light"><strong>Sidebar Form Settings</strong></div>
                            <div class="card-body">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="sidebar_settings[show_name]" value="1"
                                        <?php echo ($sidebar['show_name'] ?? 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label">Show Name Field</label>
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="sidebar_settings[show_email]"
                                        value="1" <?php echo ($sidebar['show_email'] ?? 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label">Show Email Field</label>
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="sidebar_settings[show_phone]"
                                        value="1" <?php echo ($sidebar['show_phone'] ?? 0) ? 'checked' : ''; ?>>
                                    <label class="form-check-label">Show Phone Number</label>
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="sidebar_settings[show_arrival]"
                                        value="1" <?php echo ($sidebar['show_arrival'] ?? 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label">Show Arrival Date</label>
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="sidebar_settings[show_country]"
                                        value="1" <?php echo ($sidebar['show_country'] ?? 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label">Show Country Field</label>
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="sidebar_settings[show_pax]" value="1"
                                        <?php echo ($sidebar['show_pax'] ?? 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label">Show Pax (Adults/Kids) Count</label>
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="sidebar_settings[show_departure]"
                                        value="1" <?php echo ($sidebar['show_departure'] ?? 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label">Show Departure Date</label>
                                </div>
                                <div class="mb-3 border-top pt-3">
                                    <label class="form-label">Custom Button Label</label>
                                    <input type="text" class="form-control" name="sidebar_settings[button_text]"
                                        value="<?php echo htmlspecialchars($sidebar['button_text'] ?? 'Request Quote'); ?>"
                                        placeholder="e.g. Request Quote">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. STYLING SETTINGS -->
            <div class="tab-pane fade" id="styling" role="tabpanel">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header bg-light"><strong>Hero Section (Top)</strong></div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <label class="form-label small">Background Color</label>
                                        <input type="color" class="form-control form-control-color w-100"
                                            name="sidebar_settings[design][hero_bg]"
                                            value="<?php echo $sidebar['design']['hero_bg'] ?? '#f8f9fa'; ?>">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small">Text Color</label>
                                        <input type="color" class="form-control form-control-color w-100"
                                            name="sidebar_settings[design][hero_text]"
                                            value="<?php echo $sidebar['design']['hero_text'] ?? '#212529'; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card mb-3">
                            <div class="card-header bg-light"><strong>Sidebar & Tabs</strong></div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <label class="form-label small">Sidebar Header BG</label>
                                        <input type="color" class="form-control form-control-color w-100"
                                            name="sidebar_settings[design][sidebar_bg]"
                                            value="<?php echo $sidebar['design']['sidebar_bg'] ?? '#0d6efd'; ?>">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small">Active Tab Color</label>
                                        <input type="color" class="form-control form-control-color w-100"
                                            name="sidebar_settings[design][tab_color]"
                                            value="<?php echo $sidebar['design']['tab_color'] ?? '#0d6efd'; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header bg-light"><strong>Buttons & Accents</strong></div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <label class="form-label small">Primary Button BG</label>
                                        <input type="color" class="form-control form-control-color w-100"
                                            name="sidebar_settings[design][btn_bg]"
                                            value="<?php echo $sidebar['design']['btn_bg'] ?? '#0d6efd'; ?>">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small">Button Text Color</label>
                                        <input type="color" class="form-control form-control-color w-100"
                                            name="sidebar_settings[design][btn_text]"
                                            value="<?php echo $sidebar['design']['btn_text'] ?? '#ffffff'; ?>">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small">Custom CSS (Advanced)</label>
                                    <textarea class="form-control" name="sidebar_settings[design][custom_css]" rows="4"
                                        placeholder=".element { color: red; }"><?php echo htmlspecialchars($sidebar['design']['custom_css'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="mt-4 border-top pt-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-save"></i> Save All Changes</button>
            <a href="packages.php" class="btn btn-secondary btn-lg">Cancel</a>
        </div>
    </form>
</div>

<script>
    if (typeof CKEDITOR != 'undefined') CKEDITOR.replace('editor');

    // Add Inclusions/Exclusions
    function addItem(containerId, fieldName) {
        const div = document.createElement('div');
        div.className = 'input-group mb-2';
        div.innerHTML = `
            <input type="text" class="form-control" name="${fieldName}" placeholder="Enter item...">
            <button type="button" class="btn btn-outline-danger" onclick="this.parentElement.remove()">X</button>
        `;
        document.getElementById(containerId).appendChild(div);
    }

    // Add Itinerary Day
    function addDay() {
        const container = document.getElementById('itinerary-list');
        const count = container.children.length;
        const div = document.createElement('div');
        div.className = 'card mb-3 itinerary-item';
        div.innerHTML = `
            <div class="card-header d-flex justify-content-between align-items-center bg-light">
                <span class="fw-bold">Day ${count + 1}</span>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.itinerary-item').remove()"><i class="bi bi-trash"></i></button>
            </div>
            <div class="card-body p-3">
                <div class="mb-2">
                    <label class="small text-muted">Day Title (e.g. Arrival in Kandy)</label>
                    <input type="text" class="form-control mb-2" name="itinerary[${count}][title]" placeholder="Enter Title">
                </div>
                <div>
                    <label class="small text-muted">Activities / Description</label>
                    <textarea class="form-control" name="itinerary[${count}][desc]" rows="2"></textarea>
                </div>
            </div>
        `;
        container.appendChild(div);
    }
</script>

<?php include 'includes/footer.php'; ?>