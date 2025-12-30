<?php
// Fetch Home Data
$featuredPackages = $pdo->query("SELECT * FROM packages ORDER BY id DESC LIMIT 3")->fetchAll();
$services = $pdo->query("SELECT * FROM services LIMIT 4")->fetchAll();
$testimonials = $pdo->query("SELECT * FROM testimonials ORDER BY created_at DESC LIMIT 3")->fetchAll();
?>

<!-- Dynamic Content from Page Builder -->
<?php
require_once __DIR__ . '/../classes/PageBuilder.php';

// Fetch Sections for Home Page (ID=1)
// Note: We use $page['id'] directly as it's fetched in index.php
if (isset($page['id'])) {
    $secStmt = $pdo->prepare("SELECT * FROM sections WHERE page_id = ? ORDER BY display_order ASC");
    $secStmt->execute([$page['id']]);
    $sections = $secStmt->fetchAll();

    foreach ($sections as $sec) {
        PageBuilder::renderSection($sec, $pdo);
    }
}
?>

<!-- Auto-Generated Package Listings (Optional Bonus Content) -->

<!-- Featured Packages -->
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="section-title">Popular Packages</h2>
        <div class="row">
            <?php foreach ($featuredPackages as $pkg): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <?php if ($pkg['image']): ?>
                            <img src="uploads/<?php echo $pkg['image']; ?>" class="card-img-top"
                                alt="<?php echo htmlspecialchars($pkg['title']); ?>" style="height: 250px; object-fit: cover;">
                        <?php else: ?>
                            <div class="card-img-top bg-secondary" style="height: 250px;"></div>
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($pkg['title']); ?></h5>
                            <p class="card-text text-muted">
                                <?php echo strip_tags(substr($pkg['description'], 0, 100)); ?>...
                            </p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-primary fw-bold">$<?php echo $pkg['price']; ?></span>
                                <span class="badge bg-info text-dark"><?php echo $pkg['duration']; ?></span>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-top-0">
                            <a href="contact?package=<?php echo urlencode($pkg['title']); ?>"
                                class="btn btn-outline-primary w-100">Book Now</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="packages" class="btn btn-secondary">View All Packages</a>
        </div>
    </div>
</section>

<!-- Services Preview -->
<section class="py-5">
    <div class="container">
        <h2 class="section-title">Our Services</h2>
        <div class="row text-center">
            <?php foreach ($services as $svc): ?>
                <div class="col-md-3 mb-4">
                    <div class="p-4 border rounded h-100">
                        <i class="bi <?php echo $svc['icon']; ?> service-icon"></i>
                        <h4 class="h5 mt-3"><?php echo htmlspecialchars($svc['title']); ?></h4>
                        <p class="small text-muted">
                            <?php echo htmlspecialchars(substr(strip_tags($svc['description']), 0, 80)); ?>...
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Guest Reviews Slider (Authentic Platform Style) -->
<?php include __DIR__ . '/../includes/guest_reviews.php'; ?>