<div class="page-container">
    <?php
    require_once 'classes/PageBuilder.php';

    // Fetch Sections
    $secStmt = $pdo->prepare("SELECT * FROM sections WHERE page_id = ? ORDER BY display_order ASC");
    $secStmt->execute([$page['id']]);
    $sections = $secStmt->fetchAll();
    ?>

    <?php if (count($sections) > 0): ?>
        <!-- Render Sections via PageBuilder -->
        <?php foreach ($sections as $sec): ?>
            <?php PageBuilder::renderSection($sec, $pdo); ?>
        <?php endforeach; ?>
    <?php else: ?>
        <!-- Fallback to simple content if no sections -->
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <h1 class="mb-4 text-center section-title"><?php echo htmlspecialchars($page['title']); ?></h1>
                    <div class="content">
                        <?php echo $page['content']; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>