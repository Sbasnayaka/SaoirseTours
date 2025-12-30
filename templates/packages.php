<?php
// 1. Fetch Packages
$packages = $pdo->query("SELECT * FROM packages ORDER BY id DESC")->fetchAll();
?>

<?php
// 2. Render Custom Sections (Intro)
require_once __DIR__ . '/../classes/PageBuilder.php';
if (isset($page['id'])) {
    $secStmt = $pdo->prepare("SELECT * FROM sections WHERE page_id = ? ORDER BY display_order ASC");
    $secStmt->execute([$page['id']]);
    $sections = $secStmt->fetchAll();
    foreach ($sections as $sec) {
        PageBuilder::renderSection($sec, $pdo);
    }
}
?>

<!-- 3. Grid -->

<div class="container py-5">
    <h1 class="text-center section-title mb-5">Our Tour Packages</h1>

    <div class="row">
        <?php foreach ($packages as $pkg): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <?php if ($pkg['image']): ?>
                        <img src="uploads/<?php echo $pkg['image']; ?>" class="card-img-top"
                            alt="<?php echo htmlspecialchars($pkg['title']); ?>" style="height: 250px; object-fit: cover;">
                    <?php else: ?>
                        <div class="card-img-top bg-secondary" style="height: 250px;"></div>
                    <?php endif; ?>
                    <div class="card-body">
                        <h3 class="h5 card-title"><a href="package-detail.php?id=<?php echo $pkg['id']; ?>"
                                class="text-decoration-none text-dark"><?php echo htmlspecialchars($pkg['title']); ?></a>
                        </h3>
                        <p class="card-text text-muted"><?php echo strip_tags(substr($pkg['description'], 0, 120)); ?>...
                        </p>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="text-primary fs-5 fw-bold">$<?php echo $pkg['price']; ?></span>
                            <span class="badge bg-light text-dark border"><?php echo $pkg['duration']; ?></span>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top-0 d-grid">
                        <a href="package-detail.php?id=<?php echo $pkg['id']; ?>" class="btn btn-outline-primary">View
                            Details</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>