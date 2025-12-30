<?php
// Fetch Reviews
$stmt = $pdo->query("SELECT * FROM testimonials ORDER BY created_at DESC LIMIT 10");
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
                    // Determine Source Icon & Color
                    $icon = 'bi-chat-quote-fill';
                    $badgeColor = 'bg-secondary';
                    $sourceName = 'Review';

                    switch ($rev['source']) {
                        case 'tripadvisor':
                            $icon = 'bi-tripadvisor'; // Requires bootstrap-icons > 1.8 or use custom image if needed. BI has no owl, using generic or text.
                            // Actually BI DOES NOT have tripadvisor. Using a substitute or text badge.
                            // Let's use a nice badge logic.
                            $brandHtml = '<span class="badge bg-success"><i class="bi bi-circle-fill"></i> TripAdvisor</span>';
                            break;
                        case 'google':
                            $brandHtml = '<span class="badge bg-white text-dark border"><span class="text-primary">G</span><span class="text-danger">o</span><span class="text-warning">o</span><span class="text-primary">g</span><span class="text-success">l</span><span class="text-danger">e</span></span>';
                            break;
                        case 'facebook':
                            $brandHtml = '<span class="badge bg-primary"><i class="bi bi-facebook"></i> Facebook</span>';
                            break;
                        default:
                            $brandHtml = '<span class="badge bg-info text-dark"><i class="bi bi-star-fill"></i> Direct</span>';
                    }

                    $imgSrc = !empty($rev['image']) ? 'uploads/' . $rev['image'] : 'https://ui-avatars.com/api/?name=' . urlencode($rev['name']);
                    ?>
                    <div class="item">
                        <div class="card border-0 shadow-sm h-100 review-card">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center mb-3">
                                    <img src="<?php echo $imgSrc; ?>" class="rounded-circle me-3"
                                        style="width: 50px; height: 50px; object-fit: cover;">
                                    <div>
                                        <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($rev['name']); ?></h6>
                                        <small
                                            class="text-muted"><?php echo date('M Y', strtotime($rev['created_at'])); ?></small>
                                    </div>
                                    <div class="ms-auto">
                                        <?php echo $brandHtml; ?>
                                    </div>
                                </div>

                                <div class="mb-3 text-warning small">
                                    <?php for ($i = 0; $i < $rev['rating']; $i++)
                                        echo '<i class="bi bi-star-fill"></i>'; ?>
                                </div>

                                <p class="card-text text-muted fst-italic">
                                    "<?php echo htmlspecialchars(substr($rev['review'], 0, 120)); ?><?php echo strlen($rev['review']) > 120 ? '...' : ''; ?>"
                                </p>

                                <?php if (!empty($rev['link'])): ?>
                                    <a href="<?php echo htmlspecialchars($rev['link']); ?>" target="_blank"
                                        class="small text-decoration-none fw-bold">Verify Review <i
                                            class="bi bi-box-arrow-up-right"></i></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <script>
        $(document).ready(function () {
            $("#reviews-slider").owlCarousel({
                loop: true,
                margin: 20,
                nav: true,
                dots: true,
                autoplay: true,
                autoplayTimeout: 5000,
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
        /* Custom Tweaks for Slider */
        .owl-nav button {
            font-size: 40px !important;
            color: var(--color-primary) !important;
        }

        .review-card {
            transition: transform 0.3s;
        }

        .review-card:hover {
            transform: translateY(-5px);
        }
    </style>
<?php endif; ?>