</div> <!-- End Content -->
</div> <!-- End Wrapper -->

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    $(document).ready(function () {
        $('#sidebarCollapse').on('click', function () {
            $('#sidebar').toggleClass('active');
            if ($('#sidebar').css('margin-left') == '0px') {
                $('#sidebar').css('margin-left', '-250px');
            } else {
                $('#sidebar').css('margin-left', '0px');
            }
        });

        // Initialize CKEditor for all textareas with class 'editor'
        // We use a small timeout or just init individually if needed
    });
</script>
</body>

</html>