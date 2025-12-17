<?php
// Fetch Home Data
$featuredPackages = $pdo->query("SELECT * FROM packages ORDER BY id DESC LIMIT 3")->fetchAll();
$services = $pdo->query("SELECT * FROM services LIMIT 4")->fetchAll();
$testimonials = $pdo->query("SELECT * FROM testimonials ORDER BY created_at DESC LIMIT 3")->fetchAll();

// Hero Image (Use a default or one from gallery?)
// For now, simple background color or placeholder if no image setting in DB for hero.
// We can use the first gallery image as hero if available.
$heroImgStmt = $pdo->query("SELECT image FROM gallery ORDER BY display_order ASC LIMIT 1");
$heroImg = $heroImgStmt->fetchColumn();
$bgStyle = $heroImg ? "background-image: url('uploads/{$heroImg}');" : "background-color: var(--primary-color);";
?>

<!-- Hero Section -->
<header class="hero-section text-center text-white"
    style="<?php echo $bgStyle; ?> box-shadow: inset 0 0 0 2000px rgba(0,0,0,0.4);">
    <div class="container">
        <h1 class="display-3 fw-bold"><?php echo htmlspecialchars($settings['site_title']); ?></h1>
        <p class="lead mb-4"><?php echo htmlspecialchars($settings['tagline']); ?></p>
        <a href="packages" class="btn btn-primary btn-lg">Explore Packages</a>
    </div>
</header>

<!-- Intro Section (Page Content) -->
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <h2 class="section-title"><?php echo htmlspecialchars($page['title']); ?></h2>
                <div class="lead">
                    <?php echo $page['content']; ?>
                </div>
            </div>
        </div>
    </div>
</section>

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
                                <?php echo strip_tags(substr($pkg['description'], 0, 100)); ?>...</p>
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
                            <?php echo htmlspecialchars(substr(strip_tags($svc['description']), 0, 80)); ?>...</p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Testimonials -->
<section class="py-5 bg-dark text-white">
    <div class="container">
        <h2 class="section-title text-white">Guest Reviews</h2>
        <div class="row justify-content-center">
            <?php foreach ($testimonials as $rev): ?>
                <div class="col-md-4 mb-3">
                    <div class="card bg-secondary text-white border-0 h-100">
                        <div class="card-body text-center">
                            <div class="mb-2 text-warning">
                                <?php for ($i = 0; $i < $rev['rating']; $i++)
                                    echo '⭐'; ?>
                            </div>
                            <p class="fst-italic">"<?php echo htmlspecialchars(substr($rev['review'], 0, 150)); ?>..."</p>
                            <h6 class="fw-bold">- <?php echo htmlspecialchars($rev['name']); ?></h6>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>