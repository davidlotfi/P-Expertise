<?php
/**
 * index.php
 * لوحة التحكم الرئيسية (Tableau de bord - E-Expertise)
 */
require_once __DIR__ . '/config/db.php';

$page_title = "Tableau de Bord | E-EXPERTISE";

// جلب الإحصائيات وقائمة الـ ODS الحديثة إن كانت قاعدة البيانات مهيأة
$total_ods = 0;
$total_pvs = 0;
$total_valides = 0;
$liste_ods = [];

try {
    if (isset($pdo)) {
        // إجمالي أوامر المهمة
        $total_ods = $pdo->query("SELECT COUNT(*) FROM ods")->fetchColumn();
        
        // إجمالي المحاضر
        $total_pvs = $pdo->query("SELECT COUNT(*) FROM pv_expertises")->fetchColumn();
        
        // المحاضر المعتمدة
        $total_valides = $pdo->query("SELECT COUNT(*) FROM pv_expertises WHERE statut LIKE '%Validé%'")->fetchColumn();
        
        // جلب آخر أوامر مهمة ODS
        $stmt = $pdo->query("SELECT * FROM ods ORDER BY id DESC LIMIT 10");
        $liste_ods = $stmt->fetchAll();
    }
} catch (Exception $e) {
    // في حال لم يتم استيراد قاعدة البيانات بعد، يتم تجاوز الخطأ ليعمل العرض بدون تعطل
}

// تضمين الهيدر والقائمة الجانبية
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<main id="main" class="main">

  <div class="pagetitle">
    <h1>Tableau de bord</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Accueil</a></li>
        <li class="breadcrumb-item active">Dashboard</li>
      </ol>
    </nav>
  </div><!-- End Page Title -->

  <section class="section dashboard">

    <!-- رسائل التنبيه والنجاح (Flash Alerts) -->
    <?php if (!empty($_SESSION['alert'])): ?>
      <div class="alert alert-<?= htmlspecialchars($_SESSION['alert']['type']) ?> alert-dismissible fade show shadow-sm" role="alert">
        <div class="d-flex align-items-center">
          <i class="bi bi-<?= $_SESSION['alert']['type'] === 'success' ? 'check-circle-fill text-success' : 'exclamation-triangle-fill text-danger' ?> fs-4 me-2"></i>
          <div>
            <strong><?= htmlspecialchars($_SESSION['alert']['title']) ?></strong>
            <div class="mt-1"><?= $_SESSION['alert']['message'] ?></div>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
      </div>
      <?php unset($_SESSION['alert']); ?>
    <?php endif; ?>

    <div class="row">

      <!-- العمود الأيسر: الإحصائيات والجداول الرئيسية -->
      <div class="col-lg-12">
        <div class="row">

          <!-- بطاقة: أوامر المهمة ODS -->
          <div class="col-xxl-3 col-md-6">
            <div class="card info-card sales-card">
              <div class="card-body">
                <h5 class="card-title">ODS <span>| Total</span></h5>
                <div class="d-flex align-items-center">
                  <div class="card-icon rounded-circle d-flex align-items-center justify-content-center bg-primary-subtle text-primary">
                    <i class="bi bi-file-earmark-text"></i>
                  </div>
                  <div class="ps-3">
                    <h6><?= $total_ods ?></h6>
                    <span class="text-muted small pt-2">Ordres de Service</span>
                  </div>
                </div>
              </div>
            </div>
          </div><!-- End ODS Card -->

          <!-- بطاقة: المحاضر المنجزة -->
          <div class="col-xxl-3 col-md-6">
            <div class="card info-card revenue-card">
              <div class="card-body">
                <h5 class="card-title">Expertises <span>| PVs</span></h5>
                <div class="d-flex align-items-center">
                  <div class="card-icon rounded-circle d-flex align-items-center justify-content-center bg-info-subtle text-info">
                    <i class="bi bi-clipboard-check"></i>
                  </div>
                  <div class="ps-3">
                    <h6><?= $total_pvs ?></h6>
                    <span class="text-muted small pt-2">Procès-Verbaux</span>
                  </div>
                </div>
              </div>
            </div>
          </div><!-- End Revenue Card -->

          <!-- بطاقة: المحاضر المعتمدة -->
          <div class="col-xxl-3 col-md-6">
            <div class="card info-card customers-card">
              <div class="card-body">
                <h5 class="card-title">Validations <span>| Clôturées</span></h5>
                <div class="d-flex align-items-center">
                  <div class="card-icon rounded-circle d-flex align-items-center justify-content-center bg-success-subtle text-success">
                    <i class="bi bi-check2-all"></i>
                  </div>
                  <div class="ps-3">
                    <h6><?= $total_valides ?></h6>
                    <span class="text-muted small pt-2">Dossiers Validés</span>
                  </div>
                </div>
              </div>
            </div>
          </div><!-- End Customers Card -->

          <!-- بطاقة: رابط سريع لتقرير الأتعاب -->
          <div class="col-xxl-3 col-md-6">
            <div class="card info-card">
              <div class="card-body">
                <h5 class="card-title">Honoraires <span>| État</span></h5>
                <div class="d-flex align-items-center">
                  <div class="card-icon rounded-circle d-flex align-items-center justify-content-center bg-warning-subtle text-warning">
                    <i class="bi bi-cash-coin"></i>
                  </div>
                  <div class="ps-3">
                    <h6><a href="rapport_honoraires.php" class="text-decoration-none">Consulter</a></h6>
                    <span class="text-muted small pt-2">Rapport Honoraires</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- جدول: Liste des ODS (مطابق لصفحة 7 في دليل الاستخدام) -->
          <div class="col-12">
            <div class="card recent-sales overflow-auto">

              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                  <h5 class="card-title">Liste des ODS <span>| Affectés pour traitement</span></h5>
                  <div class="d-flex gap-2">
                    <a href="ods_create.php" class="btn btn-sm btn-success"><i class="bi bi-file-earmark-plus me-1"></i> Nouveau ODS</a>
                    <a href="ods_list.php" class="btn btn-sm btn-outline-primary"><i class="bi bi-list-ul me-1"></i> Voir tout</a>
                  </div>
                </div>

                <table class="table table-hover datatable">
                  <thead class="table-light">
                    <tr>
                      <th scope="col">N° ODS</th>
                      <th scope="col">N° Dossier</th>
                      <th scope="col">Date Sinistre</th>
                      <th scope="col">Date ODS</th>
                      <th scope="col">Assuré</th>
                      <th scope="col">Véhicule</th>
                      <th scope="col">Statut</th>
                      <th scope="col" class="text-center">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (!empty($liste_ods)): ?>
                      <?php foreach ($liste_ods as $row): ?>
                        <tr>
                          <td><strong><?= htmlspecialchars($row['numero_ods']) ?></strong></td>
                          <td><?= htmlspecialchars($row['numero_dossier']) ?></td>
                          <td><?= htmlspecialchars($row['date_sinistre']) ?></td>
                          <td><?= htmlspecialchars($row['date_ods']) ?></td>
                          <td><?= htmlspecialchars($row['assure']) ?></td>
                          <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['marque'] . ' ' . $row['modele']) ?></span></td>
                          <td>
                            <?php if ($row['statut'] === 'Nouveau'): ?>
                              <span class="badge bg-info text-dark">Nouveau</span>
                            <?php elseif ($row['statut'] === 'Validé'): ?>
                              <span class="badge bg-success">Validé</span>
                            <?php else: ?>
                              <span class="badge bg-warning text-dark"><?= htmlspecialchars($row['statut']) ?></span>
                            <?php endif; ?>
                          </td>
                          <td class="text-center">
                            <a href="pv_create.php?ods_id=<?= $row['id'] ?>" class="btn btn-primary btn-sm" title="Créer / Traiter Expertise">
                              <i class="bi bi-pencil-square"></i> Expertise
                            </a>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                          <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                          Aucun ODS chargé pour le moment. Vous pouvez importer la base de données via <code>database.sql</code>.
                        </td>
                      </tr>
                    <?php endif; ?>
                  </tbody>
                </table>

              </div>

            </div>
          </div><!-- End Liste ODS -->

        </div>
      </div><!-- End Left side columns -->

    </div>
  </section>

</main><!-- End #main -->

<?php
// تضمين الفوتر وروابط الجافاسكريبت
require_once __DIR__ . '/includes/footer.php';
?>
