<?php
// Footer Renderer Engine
if (file_exists(__DIR__ . '/../classes/FooterRenderer.php')) {
    require_once __DIR__ . '/../classes/FooterRenderer.php';
    $footerBuilder = new FooterRenderer($pdo);
    $footerBuilder->render();
} else {
    // Fallback or Error
    echo "<footer class='bg-dark text-white py-4 text-center'>Footer Engine Missing</footer>";
}
?>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>
<script src="js/custom.js"></script>

<!-- FLOATING WIDGETS ENGINE -->
<?php
try {
    $fStmt = $pdo->query("SELECT settings FROM footer_settings WHERE id = 1");
    $fSet = $fStmt->fetch();
    $fData = $fSet ? json_decode($fSet['settings'], true) : [];
    $chat = $fData['widgets']['live_chat'] ?? [];
} catch (Exception $e) {
    $chat = [];
}
?>

<style>
    /* Social Dock (Bottom Left) */
    .social-dock {
        position: fixed;
        bottom: 30px;
        left: 30px;
        z-index: 9999;
        display: flex;
        flex-direction: column-reverse;
        /* Stack bottom-up */
        gap: 15px;
    }

    .social-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: #fff;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        transition: all 0.3s ease;
        text-decoration: none;
        position: relative;
    }

    .social-icon:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
    }

    /* Brand Colors */
    .social-icon.whatsapp {
        background: #25D366;
        color: white;
    }

    .social-icon.messenger {
        background: #0084FF;
        color: white;
    }

    .social-icon.email {
        background: #EA4335;
        color: white;
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .social-dock {
            bottom: 20px;
            left: 15px;
            gap: 10px;
        }

        .social-icon {
            width:
                <?php echo intval($iconSize) * 0.8; ?>
                px;
            height:
                <?php echo intval($iconSize) * 0.8; ?>
                px;
            font-size:
                <?php echo intval($iconSize) * 0.35; ?>
                px;
        }
    }
</style>

<!-- 1. Social Dock -->
<div class="social-dock">
    <?php if (!empty($chat['whatsapp_number'])): ?>
        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $chat['whatsapp_number']); ?>?text=I'm%20interested%20in%20a%20tour"
            class="social-icon whatsapp" target="_blank" title="Chat on WhatsApp">
            <i class="bi bi-whatsapp"></i>
        </a>
    <?php endif; ?>

    <?php if (!empty($chat['messenger_id'])): ?>
        <a href="http://m.me/<?php echo htmlspecialchars($chat['messenger_id']); ?>" class="social-icon messenger"
            target="_blank" title="Chat on Messenger">
            <i class="bi bi-messenger"></i>
        </a>
    <?php endif; ?>

    <?php if (!empty($chat['contact_email'])): ?>
        <a href="mailto:<?php echo htmlspecialchars($chat['contact_email']); ?>?subject=Tour Inquiry"
            class="social-icon email" title="Send Email">
            <i class="bi bi-envelope-fill"></i>
        </a>
    <?php endif; ?>
</div>

<!-- 2. Tawk.to Chat -->
<?php if (!empty($chat['tawk_to_code'])): ?>
    <!-- Start of Tawk.to Script -->
    <?php echo $chat['tawk_to_code']; ?>
    <!-- End of Tawk.to Script -->
<?php endif; ?>

</body>

</html>