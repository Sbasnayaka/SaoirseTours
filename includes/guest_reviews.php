<?php
// Fetch Reviews
$stmt = $pdo->query("SELECT * FROM testimonials ORDER BY review_date DESC, created_at DESC LIMIT 10");
$reviews = $stmt->fetchAll();

if ($reviews):
    ?>
    <section class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Guest Reviews</h2>
                <p class="text-muted">What our travelers say about us</p>
            </div>

            <div class="owl-carousel owl-theme" id="reviews-slider">
                <?php foreach ($reviews as $rev):
                    // Safe Fallbacks (In case DB column missing)
                    $source = $rev['source'] ?? 'direct';
                    $image = $rev['image'] ?? '';
                    $link = $rev['link'] ?? '';
                    $reviewDate = $rev['review_date'] ?? $rev['created_at'] ?? date('Y-m-d');

                    $date = date('M d, Y', strtotime($reviewDate));
                    $imgSrc = !empty($image) ? 'uploads/' . $image : 'https://ui-avatars.com/api/?name=' . urlencode($rev['name']);

                    // Link Wrapper Logic
                    $hasLink = !empty($link);
                    $tag = $hasLink ? 'a' : 'div';
                    $href = $hasLink ? 'href="' . htmlspecialchars($link) . '" target="_blank"' : '';
                    $cursorClass = $hasLink ? 'cursor-pointer' : '';

                    // RENDER CARD BASED ON SOURCE
                    if ($source == 'google') {
                        // --- GOOGLE STYLE ---
                        ?>
                        <div class="item">
                            <<?php echo $tag; ?>             <?php echo $href; ?> class="card border-0 shadow-sm h-100 p-3
                                text-decoration-none review-card <?php echo $cursorClass; ?>" style="border-radius: 15px;
                    font-family: 'Roboto', sans-serif;">
                                <div class="d-flex align-items-center mb-2">
                                    <img src="<?php echo $imgSrc; ?>" class="rounded-circle me-3"
                                        style="width: 40px; height: 40px;">
                                    <div>
                                        <h6 class="mb-0 fw-bold text-dark" style="font-size: 15px;">
                                            <?php echo htmlspecialchars($rev['name']); ?>
                                        </h6>
                                        <small class="text-muted" style="font-size: 12px;"><?php echo $date; ?></small>
                                    </div>
                                    <div class="ms-auto">
                                        <img src="https://upload.wikimedia.org/wikipedia/commons/5/53/Google_%22G%22_Logo.svg"
                                            width="24" alt="Google">
                                    </div>
                                </div>
                                <div class="mb-2 text-warning d-flex">
                                    <?php for ($i = 0; $i < 5; $i++)
                                        echo '<i class="bi bi-star-fill me-1" style="color: #fb8c00; font-size: 14px;"></i>'; ?>
                                </div>
                                <p class="small text-dark mb-0" style="line-height: 1.5;">
                                    <?php echo htmlspecialchars(substr($rev['review'], 0, 140)); ?>...
                                </p>
                            </<?php echo $tag; ?>>
                        </div>
                        <?php
                    } elseif ($source == 'tripadvisor') {
                        // --- TRIPADVISOR STYLE ---
                        ?>
                        <div class="item">
                            <<?php echo $tag; ?>             <?php echo $href; ?> class="card border h-100 p-3 bg-white text-decoration-none
                                review-card <?php echo $cursorClass; ?>" style="border-radius: 0; border-color: #e0e0e0;">
                                <div class="d-flex justify-content-between mb-2">
                                    <div class="d-flex">
                                        <?php for ($i = 0; $i < 5; $i++)
                                            echo '<i class="bi bi-circle-fill me-1" style="color: #00aa6c; font-size: 14px; border: 1px solid #00aa6c; border-radius: 50%;"></i>'; ?>
                                    </div>
                                    <span class="badge bg-white text-dark border rounded-pill"><i
                                            class="bi bi-eye-fill text-success"></i> TripAdvisor</span>
                                </div>
                                <h6 class="fw-bold mb-1 text-dark" style="font-family: 'Arial', sans-serif; font-size: 16px;">
                                    "Excellent Experience"</h6>
                                <p class="small text-dark mb-3" style="font-family: 'Arial', sans-serif;">
                                    <?php echo htmlspecialchars(substr($rev['review'], 0, 140)); ?>
                                </p>

                                <div class="d-flex align-items-center mt-auto border-top pt-2">
                                    <img src="<?php echo $imgSrc; ?>" class="rounded-circle me-2"
                                        style="width: 30px; height: 30px;">
                                    <div style="line-height: 1.2;">
                                        <div class="small fw-bold text-dark"><?php echo htmlspecialchars($rev['name']); ?></div>
                                        <div class="text-muted" style="font-size: 10px;">Reviewed <?php echo $date; ?></div>
                                    </div>
                                </div>
                            </<?php echo $tag; ?>>
                        </div>
                        <?php
                    } else {
                        // --- GENERIC / FACEBOOK STYLE ---
                        $isFb = $source == 'facebook';
                        $icon = $isFb ? 'bi-facebook' : 'bi-chat-quote';
                        $color = $isFb ? '#1877f2' : '#dc3545';
                        ?>
                        <div class="item">
                            <<?php echo $tag; ?>             <?php echo $href; ?> class="card border-0 shadow-sm h-100 review-card
                                text-decoration-none <?php echo $cursorClass; ?>">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <img src="<?php echo $imgSrc; ?>" class="rounded-circle me-3"
                                            style="width: 50px; height: 50px; object-fit: cover;">
                                        <div>
                                            <h6 class="mb-0 fw-bold text-dark"><?php echo htmlspecialchars($rev['name']); ?>
                                            </h6>
                                            <?php if ($isFb): ?>
                                                <small class="text-primary"><i class="bi bi-hand-thumbs-up-fill"></i>
                                                    Recommended</small>
                                            <?php else: ?>
                                                <small class="text-muted"><?php echo $date; ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="ms-auto display-6" style="color: <?php echo $color; ?>;">
                                            <i class="bi <?php echo $icon; ?>"></i>
                                        </div>
                                    </div>

                                    <p class="card-text text-muted fst-italic">
                                        "<?php echo htmlspecialchars(substr($rev['review'], 0, 120)); ?>..."</p>
                                </div>
                            </<?php echo $tag; ?>>
                        </div>
                        <?php
                    }
                endforeach; ?>
            </div>
        </div>
    </section>

    <script>
        $(document).ready(function () {
            $("#reviews-slider").owlCarousel({
                loop: true,
                margin: 20,
                nav: false,
                dots: true,
                autoplay: true,
                autoplayTimeout: 6000,
                smartSpeed: 800,
                responsive: {
                    0: { items: 1 },
                    768: { items: 2 },
                    1000: { items: 3 }
                }
            });
        });
    </script>

    <style>
        .owl-dots {
            margin-top: 20px !important;
        }

        .owl-theme .owl-dots .owl-dot span {
            background: #ccc;
        }

        .owl-theme .owl-dots .owl-dot.active span {
            background: var(--color-primary);
        }
    </style>
<?php endif; ?>