<?php
$services = $pdo->query("SELECT * FROM services ORDER BY id ASC")->fetchAll();
?>

<div class="container py-5">
    <h1 class="text-center section-title mb-5">Our Services</h1>

    <div class="row">
        <?php foreach ($services as $svc): ?>
            <div class="col-md-6 mb-4">
                <div class="card h-100 shadow-sm border-0 flex-row">
                    <?php if ($svc['image']): ?>
                        <img src="uploads/<?php echo $svc['image']; ?>" class="card-img-left d-none d-md-block"
                            style="width: 200px; object-fit: cover;" alt="<?php echo htmlspecialchars($svc['title']); ?>">
                    <?php endif; ?>
                    <div class="card-body">
                        <div class="mb-2">
                            <i class="bi <?php echo $svc['icon']; ?> fs-2 text-primary"></i>
                        </div>
                        <h3 class="card-title h5"><?php echo htmlspecialchars($svc['title']); ?></h3>
                        <p class="card-text"><?php echo nl2br(htmlspecialchars($svc['description'])); ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>