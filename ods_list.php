<?php
/**
 * ods_list.php
 * صفحة قائمة أوامر المهمة المسندة للخبير (Liste des ODS affectés)
 * مطابقة تماماً للشاشات الواردة في Slide 6 و 7 من الدليل
 */

require_once __DIR__ . '/config/db.php';

$page_title = "Liste des ODS | E-EXPERTISE";

$search = trim($_GET['search'] ?? '');
$liste_ods = [];
$liste_pvs = [];

try {
    if (isset($pdo)) {
        // استعلام جلب أوامر المهمة مع فلترة البحث إن وُجدت
        if (!empty($search)) {
            $stmt = $pdo->prepare("SELECT * FROM ods WHERE numero_ods LIKE :s OR numero_dossier LIKE :s OR matricule LIKE :s OR assure LIKE :s ORDER BY id DESC");
            $stmt->execute([':s' => "%$search%"]);
            $liste_ods = $stmt->fetchAll();
        } else {
            $stmt = $pdo->query("SELECT * FROM ods ORDER BY id DESC");
            $liste_ods = $stmt->fetchAll();
        }

        // جلب قائمة المعالجات والمحاضر (Traitements PVs) كما في أسفل الشاشة في Slide 7
        $stmt_pv = $pdo->query("
            SELECT pv.*, o.numero_ods, o.numero_dossier, u.nom, u.prenom 
            FROM pv_expertises pv
            JOIN ods o ON pv.ods_id = o.id
            JOIN utilisateurs u ON pv.expert_id = u.id
            ORDER BY pv.id DESC
        ");
        $liste_pvs = $stmt_pv->fetchAll();
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
      <h1>Expertise</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Accueil</a></li>
          <li class="breadcrumb-item">Expertise</li>
          <li class="breadcrumb-item active">Liste des ODS</li>
        </ol>
      </nav>
    </div>
    <div class="d-flex gap-2">
      <a href="ods_create.php" class="btn btn-success btn-sm">
        <i class="bi bi-file-earmark-plus me-1"></i> Nouveau ODS
      </a>
      <a href="pv_create.php" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i> Créer une expertise
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

    <div class="row">

      <!-- الجدول 1: Liste des ODS (Slide 7) -->
      <div class="col-12 mb-4">
        <div class="card shadow-sm border-0">
          <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="card-title m-0 p-0 fs-6 text-primary">
              <i class="bi bi-folder-symlink me-2"></i> Liste des ODS Affectés
            </h5>
            <span class="badge bg-secondary"><?= count($liste_ods) ?> enregistrement(s)</span>
          </div>
          <div class="card-body pt-3 overflow-auto">

            <table class="table table-hover table-striped datatable align-middle">
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
                      <td><span class="fw-bold text-primary"><?= htmlspecialchars($row['numero_ods']) ?></span></td>
                      <td><code><?= htmlspecialchars($row['numero_dossier']) ?></code></td>
                      <td><?= htmlspecialchars($row['date_sinistre']) ?></td>
                      <td><?= htmlspecialchars($row['date_ods']) ?></td>
                      <td><?= htmlspecialchars($row['assure']) ?></td>
                      <td>
                        <?= htmlspecialchars($row['marque'] . ' ' . $row['modele']) ?>
                        <div class="small text-muted"><?= htmlspecialchars($row['matricule']) ?></div>
                      </td>
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
                        <a href="pv_create.php?ods_id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary" title="Créer / Traiter l'expertise pour cet ODS">
                          <i class="bi bi-pencil-square me-1"></i> Expertise
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="8" class="text-center py-4 text-muted">
                      Aucun ODS trouvé.
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>

          </div>
        </div>
      </div>

      <!-- الجدول 2: Traitements (PVs) - كما في أسفل شاشة Slide 7 و 8 -->
      <div class="col-12">
        <div class="card shadow-sm border-0">
          <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="card-title m-0 p-0 fs-6 text-success">
              <i class="bi bi-file-earmark-check me-2"></i> Traitements (PVs enregistrés)
            </h5>
            <span class="badge bg-success"><?= count($liste_pvs) ?> PV(s)</span>
          </div>
          <div class="card-body pt-3 overflow-auto">

            <table class="table table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th scope="col">N° PV</th>
                  <th scope="col">Type</th>
                  <th scope="col">N° ODS</th>
                  <th scope="col">Expert</th>
                  <th scope="col">Date</th>
                  <th scope="col">Statut PV</th>
                  <th scope="col" class="text-end">Montant TTC</th>
                  <th scope="col" class="text-center">Action</th>
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
                      </td>
                      <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($pv['type_pv']) ?></span></td>
                      <td><?= htmlspecialchars($pv['numero_ods']) ?></td>
                      <td><?= htmlspecialchars($pv['nom'] . ' ' . $pv['prenom']) ?></td>
                      <td><?= htmlspecialchars($pv['date_expertise']) ?></td>
                      <td>
                        <?php if ($pv['statut'] === 'Brouillon'): ?>
                          <span class="badge bg-secondary">Brouillon</span>
                        <?php elseif ($pv['statut'] === 'Validé' || $pv['statut'] === 'Validé Expert' || $pv['statut'] === 'Validé Backoffice'): ?>
                          <span class="badge bg-success">Validé</span>
                        <?php elseif ($pv['statut'] === 'Clôturé'): ?>
                          <span class="badge bg-primary">Clôturé</span>
                        <?php else: ?>
                          <span class="badge bg-warning text-dark"><?= htmlspecialchars($pv['statut']) ?></span>
                        <?php endif; ?>
                      </td>
                      <td class="fw-bold text-end pe-3"><?= number_format($pv['montant_total_ttc'], 2) ?> DA</td>
                      <td class="text-center">
                        <a href="pv_details.php?id=<?= $pv['id'] ?>" class="btn btn-sm btn-outline-primary" title="Consulter et gérer l'expertise">
                          <i class="bi bi-folder2-open me-1"></i> Ouvrir
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="8" class="text-center py-4 text-muted">
                      Pas de résultat pour le moment.
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>

          </div>
        </div>
      </div>

    </div>
  </section>

</main><!-- End #main -->

<?php
require_once __DIR__ . '/includes/footer.php';
?>
