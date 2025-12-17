<footer class="bg-dark text-white py-5 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-3">
                <h5><?php echo htmlspecialchars($settings['site_title']); ?></h5>
                <p><?php echo htmlspecialchars($settings['tagline']); ?></p>
            </div>
            <div class="col-md-4 mb-3">
                <h5>Quick Links</h5>
                <ul class="list-unstyled">
                    <li><a href="home" class="text-white-50 text-decoration-none">Home</a></li>
                    <li><a href="packages" class="text-white-50 text-decoration-none">Packages</a></li>
                    <li><a href="contact" class="text-white-50 text-decoration-none">Contact Us</a></li>
                    <li><a href="admin/login.php" class="text-white-50 text-decoration-none" target="_blank">Admin
                            Login</a></li>
                </ul>
            </div>
            <div class="col-md-4 mb-3">
                <h5>Contact Us</h5>
                <p>
                    <?php echo $settings['contact_email'] ?? 'info@saoirsetours.com'; ?><br>
                    <?php echo $settings['contact_phone'] ?? '+94 123 456 789'; ?>
                </p>
                <!-- Social Icons Placeholder -->
                <div class="d-flex gap-3">
                    <a href="#" class="text-white"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="text-white"><i class="bi bi-instagram"></i></a>
                </div>
            </div>
        </div>
        <hr>
        <div class="text-center text-white-50">
            <small>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($settings['site_title']); ?>. All rights
                reserved.</small>
        </div>
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/custom.js"></script>
</body>

</html>