<?php
/**
 * rapport_honoraires.php
 * كشف وتقرير ملخص الأتعاب والمصاريف (Rapport & Récapitulatif des Honoraires)
 * مطابق للشريحة 6 في دليل E-Expertise
 */

require_once __DIR__ . '/config/db.php';

$page_title = "Rapport des Honoraires | E-EXPERTISE";

$total_general_honoraires = 0.00;
$total_dossiers_honores   = 0;
$recap_list = [];

try {
    if (isset($pdo)) {
        // استعلام تجميعي لجلب الأتعاب لكل محضر خبرة
        $sql = "
            SELECT 
                pv.id AS pv_id,
                pv.numero_pv,
                pv.statut AS pv_statut,
                pv.date_expertise,
                pv.code_validation,
                o.numero_ods,
                o.numero_dossier,
                o.assure,
                o.matricule,
                o.marque,
                o.modele,
                COUNT(h.id) AS nb_lignes_frais,
                COALESCE(SUM(h.montant_total), 0) AS total_honoraire
            FROM pv_expertises pv
            JOIN ods o ON pv.ods_id = o.id
            LEFT JOIN pv_honoraires h ON pv.id = h.pv_id
            GROUP BY pv.id
            ORDER BY pv.id DESC
        ";
        $stmt = $pdo->query($sql);
        $recap_list = $stmt->fetchAll();

        foreach ($recap_list as $row) {
            $total_general_honoraires += (float)$row['total_honoraire'];
            if ((float)$row['total_honoraire'] > 0) {
                $total_dossiers_honores++;
            }
        }
    }
} catch (Exception $e) {
    $db_error = $e->getMessage();
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<main id="main" class="main">

  <div class="pagetitle d-flex justify-content-between align-items-center">
    <div>
      <h1>Rapport des Honoraires</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Accueil</a></li>
          <li class="breadcrumb-item">Honoraires</li>
          <li class="breadcrumb-item active">Récapitulatif Général</li>
        </ol>
      </nav>
    </div>
    <div>
      <a href="index.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Dashboard
      </a>
    </div>
  </div><!-- End Page Title -->

  <section class="section">

    <!-- بطاقات الإحصائيات المالية للأتعاب -->
    <div class="row mb-4">
      <div class="col-md-6 col-lg-4">
        <div class="card info-card shadow-sm border-0">
          <div class="card-body py-3">
            <h5 class="card-title text-muted fs-7 mb-2">Total des Honoraires <span>| Cumul HT</span></h5>
            <div class="d-flex align-items-center">
              <div class="p-3 bg-warning-subtle text-warning-emphasis rounded-circle me-3 fs-3">
                <i class="bi bi-cash-stack"></i>
              </div>
              <div>
                <h4 class="m-0 fw-bold text-dark"><?= number_format($total_general_honoraires, 2) ?> DA</h4>
                <small class="text-muted">Total des frais et vacations saisis</small>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-lg-4">
        <div class="card info-card shadow-sm border-0">
          <div class="card-body py-3">
            <h5 class="card-title text-muted fs-7 mb-2">Dossiers Chiffrés <span>| Avec Honoraires</span></h5>
            <div class="d-flex align-items-center">
              <div class="p-3 bg-success-subtle text-success rounded-circle me-3 fs-3">
                <i class="bi bi-check2-circle"></i>
              </div>
              <div>
                <h4 class="m-0 fw-bold text-dark"><?= $total_dossiers_honores ?> / <?= count($recap_list) ?></h4>
                <small class="text-muted">Procès-verbaux avec état de frais</small>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-12 col-lg-4">
        <div class="card info-card shadow-sm border-0">
          <div class="card-body py-3">
            <h5 class="card-title text-muted fs-7 mb-2">Actions d'Édition <span>| Export</span></h5>
            <div class="d-flex align-items-center">
              <div class="p-3 bg-primary-subtle text-primary rounded-circle me-3 fs-3">
                <i class="bi bi-printer"></i>
              </div>
              <div>
                <button onclick="window.print()" class="btn btn-outline-primary btn-sm fw-bold">
                  <i class="bi bi-printer-fill me-1"></i> Imprimer l'état global
                </button>
                <div class="small text-muted mt-1">Conforme aux bordereaux Alliance</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- جدول الكشف التفصيلي للأتعاب حسب المحضر -->
    <div class="card shadow-sm border-0 mb-4">
      <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
        <h5 class="card-title m-0 p-0 fs-6 fw-bold text-dark">
          <i class="bi bi-file-earmark-spreadsheet me-2 text-primary"></i> Récapitulatif des Honoraires par Expertise (PV)
        </h5>
        <span class="badge bg-secondary"><?= count($recap_list) ?> dossier(s)</span>
      </div>

      <div class="card-body pt-3 overflow-auto">
        <table class="table table-hover table-striped align-middle mb-0 datatable">
          <thead class="table-light">
            <tr>
              <th scope="col">N° PV</th>
              <th scope="col">N° ODS & Dossier</th>
              <th scope="col">Assuré / Tiers</th>
              <th scope="col">Véhicule</th>
              <th scope="col">Date PV</th>
              <th scope="col" class="text-center">Lignes de Frais</th>
              <th scope="col" class="text-end">Montant Total</th>
              <th scope="col" class="text-center">Statut</th>
              <th scope="col" class="text-center">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($recap_list)): ?>
              <?php foreach ($recap_list as $row): ?>
                <tr>
                  <td>
                    <a href="pv_details.php?id=<?= $row['pv_id'] ?>" class="fw-bold text-primary">
                      <?= htmlspecialchars($row['numero_pv']) ?>
                    </a>
                  </td>
                  <td>
                    <strong><?= htmlspecialchars($row['numero_ods']) ?></strong>
                    <div class="small text-muted"><code><?= htmlspecialchars($row['numero_dossier']) ?></code></div>
                  </td>
                  <td><?= htmlspecialchars($row['assure']) ?></td>
                  <td>
                    <span class="fw-semibold"><?= htmlspecialchars($row['marque'] . ' ' . $row['modele']) ?></span>
                    <div class="small text-muted"><?= htmlspecialchars($row['matricule']) ?></div>
                  </td>
                  <td><?= htmlspecialchars($row['date_expertise']) ?></td>
                  <td class="text-center">
                    <span class="badge bg-light text-dark border"><?= (int)$row['nb_lignes_frais'] ?> frais</span>
                  </td>
                  <td class="text-end fw-bold fs-7 text-dark pe-3">
                    <?= number_format($row['total_honoraire'], 2) ?> DA
                  </td>
                  <td class="text-center">
                    <?php if ($row['pv_statut'] === 'Validé'): ?>
                      <span class="badge bg-success">Validé</span>
                    <?php else: ?>
                      <span class="badge bg-secondary">Brouillon</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center">
                    <div class="btn-group btn-group-sm">
                      <a href="honoraire_manage.php?pv_id=<?= $row['pv_id'] ?>" class="btn btn-outline-warning text-dark" title="Gérer les frais">
                        <i class="bi bi-gear-fill"></i>
                      </a>
                      <a href="honoraire_print.php?pv_id=<?= $row['pv_id'] ?>" target="_blank" class="btn btn-outline-info" title="Imprimer la note">
                        <i class="bi bi-printer"></i>
                      </a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="9" class="text-center py-4 text-muted">
                  Aucun dossier d'expertise trouvé.
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </section>

</main><!-- End #main -->

<?php
require_once __DIR__ . '/includes/footer.php';
?>
