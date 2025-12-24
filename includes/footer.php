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
<script src="js/custom.js"></script>
</body>

</html>