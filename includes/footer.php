<?php
/**
 * includes/footer.php
 * التذييل وروابط الجافاسكريبت (Footer & JS Scripts)
 * موجهة إلى ملفات المكتبات والسكربتات في مجلد Tamplate-frontend/assets/
 */

if (!isset($assets_path)) {
    $assets_path = 'Tamplate-frontend/assets/';
}
?>

  <!-- ======= تذييل الصفحة (Footer) ======= -->
  <footer id="footer" class="footer">
    <div class="copyright">
      &copy; <?= date('Y') ?> <strong><span>E-EXPERTISE</span></strong> - Zahra Assurances. Tous droits réservés.
    </div>
  </footer><!-- End Footer -->

  <!-- زر الصعود للأعلى -->
  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Vendor JS Files (من مجلد Tamplate-frontend) -->
  <script src="<?= $assets_path ?>vendor/apexcharts/apexcharts.min.js"></script>
  <script src="<?= $assets_path ?>vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="<?= $assets_path ?>vendor/chart.js/chart.umd.js"></script>
  <script src="<?= $assets_path ?>vendor/echarts/echarts.min.js"></script>
  <script src="<?= $assets_path ?>vendor/quill/quill.min.js"></script>
  <script src="<?= $assets_path ?>vendor/simple-datatables/simple-datatables.js"></script>
  <script src="<?= $assets_path ?>vendor/tinymce/tinymce.min.js"></script>
  <script src="<?= $assets_path ?>vendor/php-email-form/validate.js"></script>

  <!-- Template Main JS File -->
  <script src="<?= $assets_path ?>js/main.js"></script>

</body>

</html>
