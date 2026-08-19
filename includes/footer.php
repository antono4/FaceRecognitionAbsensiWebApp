<?php
/**
 * Footer layout AdminLTE 4.
 * Halaman dapat mengisi:
 *   $page_scripts  - array URL JS tambahan (mis. face-api.js)
 *   $page_inline_js - string JS inline yang dijalankan setelah library
 */
?>
      </div><!-- /.container-fluid -->
    </div><!-- /.app-content -->
  </main>

  <footer class="app-footer">
    <div class="float-end d-none d-sm-inline">Face Recognition &mdash; face-api.js</div>
    <strong>&copy; <?= date('Y') ?> Absensi Online.</strong>
  </footer>

</div><!-- /.app-wrapper -->

<!-- Library inti: Bootstrap 5 bundle + AdminLTE 4 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@4.4.1/dist/js/adminlte.min.js"></script>

<?php foreach (($page_scripts ?? []) as $src): ?>
  <script src="<?= htmlspecialchars($src) ?>"></script>
<?php endforeach; ?>

<?php if (!empty($page_inline_js)): ?>
<script>
<?= $page_inline_js ?>
</script>
<?php endif; ?>

</body>
</html>
