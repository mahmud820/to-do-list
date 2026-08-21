<!-- Footer -->
<footer class="bg-primary text-white text-center py-3 mt-auto">
  <small>&copy; <?= date('Y'); ?> To Do List App - Mahmud Arifin</small>
</footer>

<!-- Deklarasi -->
<script>
  const BASEURL = '<?= BASEURL; ?>';
</script>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<script src="<?= BASEURL; ?>/js/tasks.js"></script>
<script src="<?= BASEURL; ?>/js/agenda.js"></script>

</body>

</html>