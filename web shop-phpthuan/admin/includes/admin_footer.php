    </div> <!-- End Main Content Wrapper -->
</div> <!-- End d-flex -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/public/js/main.js"></script>
<!-- Mobile responsive disabled -->
<!-- <script src="/public/js/admin-mobile.js"></script> -->

<script>
// Admin specific JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Confirm delete actions
    document.querySelectorAll('[data-confirm]').forEach(function(element) {
        element.addEventListener('click', function(e) {
            if (!confirm(this.dataset.confirm || 'Bạn có chắc chắn muốn xóa?')) {
                e.preventDefault();
            }
        });
    });

    // Alerts will NOT auto-hide - user must close manually
});
</script>
</body>
</html>
