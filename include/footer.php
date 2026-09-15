    
    
    <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; Keynet System Solutions 2026</span>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->
  
        <!-- ===== FLASH MESSAGE (works on every page) ===== -->


  </body>
</html>
<script>
(function waitForDeps() {
    if (typeof jQuery === 'undefined' || typeof Swal === 'undefined') {
        if (!window.__dashRetries) window.__dashRetries = 0;
        window.__dashRetries++;
        if (window.__dashRetries < 30) return setTimeout(waitForDeps, 100);
        console.error('jQuery or SweetAlert2 failed to load.');
        return;
    }

    jQuery(function ($) {
        <?php if (!empty($__flash['text'])): ?>
        Swal.fire({
            icon: <?php echo json_encode($__flash['icon'] ?: 'success'); ?>,
            title: <?php echo json_encode(
                $__flash['type'] === 'success' ? 'Success!' :
                ($__flash['type'] === 'error' ? 'Oops!' : 'Notice')
            ); ?>,
            text: <?php echo json_encode($__flash['text']); ?>,
            confirmButtonColor: <?php echo json_encode(
                $__flash['type'] === 'success' ? '#1cc88a' :
                ($__flash['type'] === 'error' ? '#e74a3b' : '#4e73df')
            ); ?>,
            timer: 3200,
            timerProgressBar: true,
            toast: true,
            position: 'top-end',
            showConfirmButton: false
        });
        <?php endif; ?>
    });
})();
</script>

