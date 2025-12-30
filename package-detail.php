<?php
require_once 'includes/config.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM packages WHERE id = ?");
$stmt->execute([$id]);
$pkg = $stmt->fetch();

if (!$pkg) {
    header("HTTP/1.0 404 Not Found");
    include 'includes/header.php';
    echo '<div class="container py-5 text-center"><h1>Package Not Found</h1><a href="index.php" class="btn btn-primary">Return Home</a></div>';
    include 'includes/footer.php';
    exit;
}

// Decode JSON Fields
$itinerary = json_decode($pkg['itinerary'] ?? '[]', true) ?: [];
$inclusions = json_decode($pkg['inclusions'] ?? '[]', true) ?: [];
$exclusions = json_decode($pkg['exclusions'] ?? '[]', true) ?: [];
$sidebar = json_decode($pkg['sidebar_settings'] ?? '{}', true) ?: [];

// Page Meta
$page_title = $pkg['title'];
include 'includes/header.php';
?>

<!-- Package Header -->
<div class="bg-light py-5 mb-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="display-5 fw-bold"><?php echo htmlspecialchars($pkg['title']); ?></h1>
                <div class="d-flex align-items-center gap-3 mt-3">
                    <span class="badge bg-success fs-5 px-3 py-2">$<?php echo htmlspecialchars($pkg['price']); ?></span>
                    <span class="badge bg-secondary fs-5 px-3 py-2"><i class="bi bi-clock"></i>
                        <?php echo htmlspecialchars($pkg['duration']); ?></span>
                </div>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <a href="#booking-form" class="btn btn-primary btn-lg px-4 shadow-sm"><i
                        class="bi bi-envelope-check"></i> Send Inquiry</a>
            </div>
        </div>
    </div>
</div>

<div class="container pb-5">
    <div class="row">
        <!-- LEFT COLUMN (70%) -->
        <div class="col-lg-8">

            <!-- Cover Image -->
            <?php if ($pkg['image']): ?>
                <div class="mb-5 rounded overflow-hidden shadow-sm">
                    <img src="uploads/<?php echo $pkg['image']; ?>" class="w-100 object-fit-cover"
                        style="max-height: 500px;" alt="<?php echo htmlspecialchars($pkg['title']); ?>">
                </div>
            <?php endif; ?>

            <!-- TABS -->
            <ul class="nav nav-tabs nav-justified mb-4" id="pkgTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold py-3" id="info-tab" data-bs-toggle="tab"
                        data-bs-target="#info" type="button" role="tab">Information</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold py-3" id="plan-tab" data-bs-toggle="tab" data-bs-target="#plan"
                        type="button" role="tab">Travel Plan</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold py-3" id="inex-tab" data-bs-toggle="tab" data-bs-target="#inex"
                        type="button" role="tab">Include | Exclude</button>
                </li>
            </ul>

            <div class="tab-content" id="pkgTabContent">

                <!-- 1. INFORMATION -->
                <div class="tab-pane fade show active" id="info" role="tabpanel">
                    <div class="fs-5 lh-lg text-secondary">
                        <?php echo $pkg['description']; // CKEditor content is HTML safe ?>
                    </div>
                </div>

                <!-- 2. TRAVEL PLAN -->
                <div class="tab-pane fade" id="plan" role="tabpanel">
                    <div class="accordion" id="itineraryAccordion">
                        <?php if (empty($itinerary)): ?>
                            <p class="text-muted">Detailed itinerary coming soon.</p>
                        <?php else: ?>
                            <?php foreach ($itinerary as $k => $day): ?>
                                <div class="accordion-item mb-3 border rounded overflow-hidden">
                                    <h2 class="accordion-header" id="heading<?php echo $k; ?>">
                                        <button
                                            class="accordion-button <?php echo $k === 0 ? '' : 'collapsed'; ?> fw-bold bg-light"
                                            type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $k; ?>">
                                            <span class="text-primary me-2">Day <?php echo $k + 1; ?>:</span>
                                            <?php echo htmlspecialchars($day['title']); ?>
                                        </button>
                                    </h2>
                                    <div id="collapse<?php echo $k; ?>"
                                        class="accordion-collapse collapse <?php echo $k === 0 ? 'show' : ''; ?>"
                                        data-bs-parent="#itineraryAccordion">
                                        <div class="accordion-body text-secondary lh-lg">
                                            <?php echo nl2br(htmlspecialchars($day['desc'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 3. INCLUDE / EXCLUDE -->
                <div class="tab-pane fade" id="inex" role="tabpanel">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <h5 class="fw-bold mb-3 text-success"><i class="bi bi-check-circle-fill"></i> Includes</h5>
                            <ul class="list-group list-group-flush">
                                <?php if (empty($inclusions))
                                    echo '<li class="list-group-item text-muted">No specific inclusions listed.</li>'; ?>
                                <?php foreach ($inclusions as $inc): ?>
                                    <li class="list-group-item bg-transparent border-0 ps-0"><i
                                            class="bi bi-check2 text-success me-2 fs-5"></i>
                                        <?php echo htmlspecialchars($inc); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="col-md-6 mb-4">
                            <h5 class="fw-bold mb-3 text-danger"><i class="bi bi-x-circle-fill"></i> Excludes</h5>
                            <ul class="list-group list-group-flush">
                                <?php if (empty($exclusions))
                                    echo '<li class="list-group-item text-muted">No specific exclusions listed.</li>'; ?>
                                <?php foreach ($exclusions as $exc): ?>
                                    <li class="list-group-item bg-transparent border-0 ps-0"><i
                                            class="bi bi-x text-danger me-2 fs-5"></i> <?php echo htmlspecialchars($exc); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- RIGHT COLUMN (30%) - STICKY SIDEBAR -->
        <div class="col-lg-4">
            <div id="booking-form" class="position-sticky" style="top: 100px; z-index: 10;">
                <div class="card shadow border-0 rounded-3">
                    <div class="card-header bg-primary text-white p-4 text-center">
                        <h4 class="mb-0">Book This Tour</h4>
                        <small>Get a custom quote today</small>
                    </div>
                    <div class="card-body p-4">
                        <form action="contact.php" method="POST"> <!-- Directing to contact handler -->
                            <input type="hidden" name="package_interest"
                                value="<?php echo htmlspecialchars($pkg['title']); ?>">

                            <?php if ($sidebar['show_name'] ?? 1): ?>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Full Name</label>
                                    <input type="text" name="name" class="form-control" placeholder="John Doe" required>
                                </div>
                            <?php endif; ?>

                            <?php if ($sidebar['show_email'] ?? 1): ?>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Email Address</label>
                                    <input type="email" name="email" class="form-control" placeholder="john@example.com"
                                        required>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($sidebar['show_phone'])): ?>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Phone Number</label>
                                    <input type="tel" name="phone" class="form-control" placeholder="+1 234 567 890">
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($sidebar['show_arrival'])): ?>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Arrival Date</label>
                                    <input type="date" name="arrival_date" class="form-control">
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($sidebar['show_pax'])): ?>
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="form-label small fw-bold">Adults</label>
                                        <input type="number" name="adults" class="form-control" value="2" min="1">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-bold">Children</label>
                                        <input type="number" name="children" class="form-control" value="0" min="0">
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="mb-3">
                                <label class="form-label small fw-bold">Message / Questions</label>
                                <textarea name="message" class="form-control" rows="3"
                                    placeholder="Tell us your preferences..."></textarea>
                            </div>

                            <button type="submit"
                                class="btn btn-dark w-100 py-3 fw-bold"><?php echo htmlspecialchars($sidebar['button_text'] ?? 'Request Quote'); ?></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include 'includes/footer.php'; ?>