<?php
/**
 * pv_list.php
 * صفحة قائمة المعالجات ومحاضر الخبرة المسجلة (Traitements PVs)
 * تعرض جميع المحاضر مع إمكانية الفلترة، فتح التفاصيل، إضافة الصدمات والأتعاب
 */

require_once __DIR__ . '/config/db.php';

$page_title = "Traitements (PVs d'Expertise) | E-EXPERTISE";

$liste_pvs = [];
$search = trim($_GET['search'] ?? '');

try {
    if (isset($pdo)) {
        $sql = "
            SELECT 
                pv.*, 
                o.numero_ods, o.numero_dossier, o.assure, o.matricule, o.marque, o.modele,
                u.nom AS expert_nom, u.prenom AS expert_prenom,
                (SELECT COUNT(*) FROM chocs WHERE pv_id = pv.id) AS nb_chocs,
                (SELECT COUNT(*) FROM pv_honoraires WHERE pv_id = pv.id) AS nb_honoraires
            FROM pv_expertises pv
            JOIN ods o ON pv.ods_id = o.id
            JOIN utilisateurs u ON pv.expert_id = u.id
        ";

        if (!empty($search)) {
            $sql .= " WHERE pv.numero_pv LIKE :s OR o.numero_ods LIKE :s OR o.numero_dossier LIKE :s OR o.matricule LIKE :s OR o.assure LIKE :s";
            $sql .= " ORDER BY pv.id DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':s' => "%$search%"]);
        } else {
            $sql .= " ORDER BY pv.id DESC";
            $stmt = $pdo->query($sql);
        }

        $liste_pvs = $stmt->fetchAll();
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<main id="main" class="main">

  <div class="pagetitle d-flex justify-content-between align-items-center">
    <div>
      <h1>Traitements (PVs d'Expertise)</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Accueil</a></li>
          <li class="breadcrumb-item">Expertise</li>
          <li class="breadcrumb-item active">Traitements (PVs)</li>
        </ol>
      </nav>
    </div>
    <div>
      <a href="pv_create.php" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i> Nouveau PV
      </a>
    </div>
  </div><!-- End Page Title -->

  <section class="section">

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

    <div class="card shadow-sm border-0 mb-4">
      <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
        <h5 class="card-title m-0 p-0 fs-6 fw-bold text-dark">
          <i class="bi bi-clipboard2-data me-2 text-primary"></i> Liste de Tous les Procès-Verbaux
        </h5>
        <span class="badge bg-secondary"><?= count($liste_pvs) ?> dossier(s)</span>
      </div>

      <div class="card-body pt-3 overflow-auto">
        <table class="table table-hover table-striped align-middle mb-0 datatable">
          <thead class="table-light">
            <tr>
              <th scope="col">N° PV</th>
              <th scope="col">Type de PV</th>
              <th scope="col">N° ODS & Dossier</th>
              <th scope="col">Assuré & Véhicule</th>
              <th scope="col">Date Expertise</th>
              <th scope="col" class="text-center">Chocs</th>
              <th scope="col" class="text-end">Montant TTC</th>
              <th scope="col" class="text-center">Statut</th>
              <th scope="col" class="text-center" style="min-width: 140px;">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($liste_pvs)): ?>
              <?php foreach ($liste_pvs as $pv): ?>
                <tr>
                  <td>
                    <a href="pv_details.php?id=<?= $pv['id'] ?>" class="fw-bold text-primary">
                      <?= htmlspecialchars($pv['numero_pv']) ?>
                    </a>
                    <?php if (!empty($pv['est_additif'])): ?>
                      <span class="badge bg-warning text-dark ms-1 small">Additif</span>
                    <?php endif; ?>
                  </td>
                  <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($pv['type_pv']) ?></span></td>
                  <td>
                    <strong><?= htmlspecialchars($pv['numero_ods']) ?></strong>
                    <div class="small text-muted"><code><?= htmlspecialchars($pv['numero_dossier']) ?></code></div>
                  </td>
                  <td>
                    <div class="fw-semibold text-dark"><?= htmlspecialchars($pv['assure']) ?></div>
                    <div class="small text-muted">
                      <?= htmlspecialchars($pv['marque'] . ' ' . $pv['modele']) ?> (<?= htmlspecialchars($pv['matricule']) ?>)
                    </div>
                  </td>
                  <td><?= htmlspecialchars($pv['date_expertise']) ?></td>
                  <td class="text-center">
                    <?php if ($pv['nb_chocs'] > 0): ?>
                      <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold">
                        <?= (int)$pv['nb_chocs'] ?> choc(s)
                      </span>
                    <?php else: ?>
                      <span class="badge bg-light text-muted border">0</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-end fw-bold fs-7 text-dark pe-3">
                    <?= number_format($pv['montant_total_ttc'], 2) ?> DA
                  </td>
                  <td class="text-center">
                    <?php if ($pv['statut'] === 'Validé'): ?>
                      <span class="badge bg-success">Validé</span>
                    <?php elseif ($pv['statut'] === 'Clôturé'): ?>
                      <span class="badge bg-primary">Clôturé</span>
                    <?php else: ?>
                      <span class="badge bg-secondary">Brouillon</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center">
                    <div class="btn-group btn-group-sm">
                      <a href="pv_details.php?id=<?= $pv['id'] ?>" class="btn btn-outline-primary" title="Ouvrir les détails et chocs">
                        <i class="bi bi-folder2-open me-1"></i> Ouvrir
                      </a>
                      <a href="honoraire_manage.php?pv_id=<?= $pv['id'] ?>" class="btn btn-outline-warning text-dark" title="Gérer Honoraires">
                        <i class="bi bi-cash-stack"></i>
                      </a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="9" class="text-center py-4 text-muted">
                  Aucun procès-verbal enregistré pour le moment.
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
