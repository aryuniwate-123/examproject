            </div> <!-- End main-content -->
        </div> <!-- End #content -->
    </div> <!-- End .wrapper -->

    <!-- JS dependencies (Bootstrap 5 Bundle includes Popper.js) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo $base_path; ?>assets/js/app.js"></script>
    
    <!-- Sidebar Toggle Script -->
    <script>
        $(document).ready(function () {
            $('#sidebarCollapse').on('click', function () {
                $('#sidebar').toggleClass('active');
            });
            
            // Client-side search implementation for tables with data-search-table attribute
            $('input[data-search-table]').on('keyup', function() {
                var value = $(this).val().toLowerCase();
                var tableId = $(this).data('search-table');
                $("#" + tableId + " tbody tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });
        });
    </script>
</body>
</html>
