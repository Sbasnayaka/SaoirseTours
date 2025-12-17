<?php
$gallery = $pdo->query("SELECT * FROM gallery ORDER BY display_order ASC, id DESC")->fetchAll();
?>

<div class="container py-5">
    <h1 class="text-center section-title mb-5">Photo Gallery</h1>

    <div class="row g-3">
        <?php foreach ($gallery as $img): ?>
            <div class="col-md-4 col-sm-6">
                <div class="gallery-item position-relative rounded shadow-sm">
                    <img src="uploads/<?php echo $img['image']; ?>" alt="<?php echo htmlspecialchars($img['caption']); ?>"
                        class="img-fluid w-100 rounded">
                    <?php if ($img['caption']): ?>
                        <div
                            class="position-absolute bottom-0 start-0 w-100 bg-dark bg-opacity-75 text-white p-2 text-center small rounded-bottom">
                            <?php echo htmlspecialchars($img['caption']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>