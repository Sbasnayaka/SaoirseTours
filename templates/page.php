<div class="page-container">
    <?php
    // Fetch Sections
    $secStmt = $pdo->prepare("SELECT * FROM sections WHERE page_id = ? ORDER BY display_order ASC");
    $secStmt->execute([$page['id']]);
    $sections = $secStmt->fetchAll();
    ?>

    <?php if (count($sections) > 0): ?>
        <!-- Render Sections -->
        <?php foreach ($sections as $sec): ?>
            <section class="py-5"
                style="background-color: <?php echo $sec['bg_color']; ?>; color: <?php echo $sec['text_color']; ?>; <?php if ($sec['layout_type'] == 'bg-image')
                          echo "background: url('uploads/{$sec['image']}') center/cover no-repeat;"; ?>">
                <div class="container">
                    <?php if ($sec['layout_type'] == 'bg-image'): ?>
                        <div class="text-center py-5">
                            <div class="bg-dark bg-opacity-50 p-4 d-inline-block rounded text-white">
                                <?php if ($sec['subtitle']): ?>
                                    <h6 class="text-uppercase ls-2 mb-2"><?php echo htmlspecialchars($sec['subtitle']); ?></h6>
                                <?php endif; ?>
                                <?php if ($sec['title']): ?>
                                    <h2 class="display-4 fw-bold mb-4"><?php echo htmlspecialchars($sec['title']); ?></h2>
                                <?php endif; ?>
                                <div class="lead"><?php echo $sec['content']; ?></div>
                            </div>
                        </div>
                    <?php elseif ($sec['layout_type'] == 'full-width'): ?>
                        <div class="text-center max-w-800 mx-auto">
                            <?php if ($sec['subtitle']): ?>
                                <h6 class="text-uppercase text-primary mb-2 ls-2"><?php echo htmlspecialchars($sec['subtitle']); ?></h6>
                            <?php endif; ?>
                            <?php if ($sec['title']): ?>
                                <h2 class="section-title mb-4"><?php echo htmlspecialchars($sec['title']); ?></h2><?php endif; ?>
                            <div><?php echo $sec['content']; ?></div>
                        </div>
                    <?php else: ?>
                        <!-- Default Side by Side -->
                        <div class="row align-items-center">
                            <div class="<?php echo $sec['image'] ? 'col-lg-6' : 'col-lg-12'; ?>">
                                <?php if ($sec['subtitle']): ?>
                                    <h6 class="text-uppercase text-primary mb-2 ls-2"><?php echo htmlspecialchars($sec['subtitle']); ?>
                                    </h6><?php endif; ?>
                                <?php if ($sec['title']): ?>
                                    <h2 class="fw-bold mb-4"><?php echo htmlspecialchars($sec['title']); ?></h2><?php endif; ?>
                                <div><?php echo $sec['content']; ?></div>
                            </div>
                            <?php if ($sec['image']): ?>
                                <div class="col-lg-6">
                                    <img src="uploads/<?php echo $sec['image']; ?>" class="img-fluid rounded shadow-lg"
                                        alt="<?php echo htmlspecialchars($sec['title']); ?>">
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
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