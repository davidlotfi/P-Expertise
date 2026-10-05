<?php
/**
 * pv_details.php
 * صفحة العرض والتفاصيل الشاملة لمحضر الخبرة والمعاينة (Détails & Suivi de l'Expertise)
 * تجمع بطاقات الـ ODS، معلومات الخبرة، قائمة الصدمات (Liste Des chocs)، قائمة قطع الغيار (Fournitures)، والأتعاب (Honoraires)
 * مطابقة 100% للشاشات 10، 11، 12، 15، 18، 19 من دليل E-Expertise
 */

require_once __DIR__ . '/config/db.php';

$page_title = "Détails de l'Expertise | E-EXPERTISE";

$pv_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($pv_id <= 0) {
    header("Location: ods_list.php");
    exit;
}

$pv = null;
$chocs = [];
$all_fournitures = [];
$honoraires = [];
$total_honoraires = 0.00;

try {
    // 1. جلب تفاصيل المحضر والـ ODS والخبير
    $stmt = $pdo->prepare("
        SELECT pv.*, 
               o.numero_ods, o.numero_dossier, o.date_sinistre, o.date_ods, o.assure, 
               o.police, o.matricule, o.numero_serie, o.marque, o.modele, o.puissance, 
               o.carburant, o.telephone,
               u.nom AS expert_nom, u.prenom AS expert_prenom, u.telephone_1 AS expert_tel
        FROM pv_expertises pv
        JOIN ods o ON pv.ods_id = o.id
        LEFT JOIN utilisateurs u ON pv.expert_id = u.id
        WHERE pv.id = :id
    ");
    $stmt->execute([':id' => $pv_id]);
    $pv = $stmt->fetch();

    if (!$pv) {
        $_SESSION['alert'] = [
            'type'    => 'danger',
            'title'   => 'Introuvable !',
            'message' => 'Le procès-verbal d\'expertise demandé n\'existe pas.'
        ];
        header("Location: ods_list.php");
        exit;
    }

    // 2. جلب قائمة الصدمات الخاصة بهذا المحضر (Slide 12: Liste Des chocs)
    $stmt_chocs = $pdo->prepare("SELECT * FROM chocs WHERE pv_id = :pv_id ORDER BY id ASC");
    $stmt_chocs->execute([':pv_id' => $pv_id]);
    $chocs = $stmt_chocs->fetchAll();

    // 3. جلب جميع قطع الغيار الموزعة عبر الصدمات (Slide 19: Liste Des fournitures)
    $stmt_fourn = $pdo->prepare("
        SELECT f.*, c.type_choc 
        FROM fournitures_choc f
        JOIN chocs c ON f.choc_id = c.id
        WHERE c.pv_id = :pv_id
        ORDER BY c.id ASC, f.id ASC
    ");
    $stmt_fourn->execute([':pv_id' => $pv_id]);
    $all_fournitures = $stmt_fourn->fetchAll();

    // 4. جلب قائمة الأتعاب والمصاريف (Slide 15 & 19: Honoraire)
    $stmt_hon = $pdo->prepare("SELECT * FROM pv_honoraires WHERE pv_id = :pv_id ORDER BY id ASC");
    $stmt_hon->execute([':pv_id' => $pv_id]);
    $honoraires = $stmt_hon->fetchAll();

    foreach ($honoraires as $h) {
        $total_honoraires += (float)$h['montant_total'];
    }

} catch (Exception $e) {
    $db_error = $e->getMessage();
}

$is_validated = ($pv['statut'] === 'Validé' || $pv['statut'] === 'Clôturé');

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<main id="main" class="main">

  <div class="pagetitle d-flex justify-content-between align-items-center">
    <div>
      <h1>Détails de l'Expertise</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Accueil</a></li>
          <li class="breadcrumb-item"><a href="ods_list.php">Expertise</a></li>
          <li class="breadcrumb-item active"><?= htmlspecialchars($pv['numero_pv']) ?></li>
        </ol>
      </nav>
    </div>
    <div class="d-flex gap-2">
      <a href="ods_list.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Liste des ODS
      </a>
      <?php if ($is_validated): ?>
        <a href="honoraire_print.php?pv_id=<?= $pv_id ?>" target="_blank" class="btn btn-sm btn-info text-white">
          <i class="bi bi-printer me-1"></i> Imprimer Honoraire
        </a>
        <a href="pv_print.php?pv_id=<?= $pv_id ?>" target="_blank" class="btn btn-sm btn-primary">
          <i class="bi bi-printer me-1"></i> Imprimer le PV
        </a>
      <?php endif; ?>
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

    <!-- بطاقة الحالة والترويسة الرئيسية -->
    <div class="card shadow-sm border-0 mb-4">
      <div class="card-body py-3 d-flex flex-wrap justify-content-between align-items-center">
        <div>
          <span class="text-muted small d-block">PROCES-VERBAL D'EXPERTISE</span>
          <h4 class="m-0 fw-bold text-primary d-inline-block me-3">
            <i class="bi bi-file-earmark-check me-1"></i> <?= htmlspecialchars($pv['numero_pv']) ?>
          </h4>
          <span class="badge bg-light text-dark border fs-6"><?= htmlspecialchars($pv['type_pv']) ?></span>
          <?php if (!empty($pv['est_additif'])): ?>
            <span class="badge bg-warning text-dark ms-1">Additif</span>
          <?php endif; ?>
        </div>
        <div class="text-md-end mt-2 mt-md-0">
          <?php if ($pv['statut'] === 'Validé'): ?>
            <div class="d-inline-block text-end">
              <span class="badge bg-success fs-6 px-3 py-2"><i class="bi bi-patch-check-fill me-1"></i> Validé par l'expert</span>
              <?php if (!empty($pv['code_validation'])): ?>
                <div class="small mt-1 text-muted">
                  Code certification: <code class="fw-bold text-success fs-7"><?= htmlspecialchars($pv['code_validation']) ?></code>
                </div>
              <?php endif; ?>
            </div>
          <?php else: ?>
            <span class="badge bg-secondary fs-6 px-3 py-2"><i class="bi bi-hourglass-split me-1"></i> En cours (Brouillon)</span>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- 1. لوحة تفاصيل الـ ODS القابلة للطي (Slide 10: Détails ODS) -->
    <div class="card shadow-sm border-0 mb-4">
      <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center" 
           data-bs-toggle="collapse" data-bs-target="#collapseOdsDetails" role="button" aria-expanded="true">
        <div class="d-flex align-items-center">
          <div class="p-2 bg-primary-subtle text-primary rounded-3 me-2">
            <i class="bi bi-folder2-open fs-5"></i>
          </div>
          <div>
            <h5 class="card-title m-0 p-0 fs-6 fw-bold text-dark">Détails ODS</h5>
            <small class="text-muted">Caractéristiques certifiées du dossier d'assurance et du véhicule (Slide 10)</small>
          </div>
        </div>
        <i class="bi bi-chevron-down fs-5 text-muted"></i>
      </div>

      <div class="collapse show" id="collapseOdsDetails">
        <div class="card-body pt-3 bg-light">
          <div class="row g-2">
            <div class="col-md-3">
              <div class="p-2 bg-white rounded border">
                <span class="text-muted small d-block">N° ODS</span>
                <strong class="text-primary"><?= htmlspecialchars($pv['numero_ods']) ?></strong>
              </div>
            </div>
            <div class="col-md-3">
              <div class="p-2 bg-white rounded border">
                <span class="text-muted small d-block">N° Dossier</span>
                <span class="badge bg-primary-subtle text-primary border"><?= htmlspecialchars($pv['numero_dossier']) ?></span>
              </div>
            </div>
            <div class="col-md-3">
              <div class="p-2 bg-white rounded border">
                <span class="text-muted small d-block">Assuré / Tiers</span>
                <strong class="text-dark"><?= htmlspecialchars($pv['assure']) ?></strong>
              </div>
            </div>
            <div class="col-md-3">
              <div class="p-2 bg-white rounded border">
                <span class="text-muted small d-block">Immatriculation</span>
                <span class="badge bg-dark text-warning font-monospace px-2 py-1"><?= htmlspecialchars($pv['matricule']) ?></span>
              </div>
            </div>
            <div class="col-md-3">
              <div class="p-2 bg-white rounded border">
                <span class="text-muted small d-block">Marque & Modèle</span>
                <span class="fw-bold text-dark"><?= htmlspecialchars($pv['marque'] . ' ' . $pv['modele']) ?></span>
              </div>
            </div>
            <div class="col-md-3">
              <div class="p-2 bg-white rounded border">
                <span class="text-muted small d-block">N° Châssis (VIN)</span>
                <code><?= htmlspecialchars(!empty($pv['numero_serie']) ? $pv['numero_serie'] : '-') ?></code>
              </div>
            </div>
            <div class="col-md-3">
              <div class="p-2 bg-white rounded border">
                <span class="text-muted small d-block">Police d'assurance</span>
                <span class="text-muted"><?= htmlspecialchars(!empty($pv['police']) ? $pv['police'] : 'Non renseignée') ?></span>
              </div>
            </div>
            <div class="col-md-3">
              <div class="p-2 bg-white rounded border">
                <span class="text-muted small d-block">Date Sinistre / ODS</span>
                <span><?= htmlspecialchars($pv['date_sinistre']) ?> / <?= htmlspecialchars($pv['date_ods']) ?></span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 2. لوحة معلومات الخبرة التقنية (Slide 11: Information) -->
    <div class="card shadow-sm border-0 mb-4">
      <div class="card-header bg-white border-bottom py-3 d-flex align-items-center">
        <div class="p-2 bg-info-subtle text-info rounded-3 me-2">
          <i class="bi bi-card-checklist fs-5"></i>
        </div>
        <div>
          <h5 class="card-title m-0 p-0 fs-6 fw-bold text-dark">Information de l'Expertise</h5>
          <small class="text-muted">Constatations d'usage, date, lieu, responsabilité et valeur vénale (Slide 11)</small>
        </div>
      </div>
      <div class="card-body pt-3">
        <div class="row g-3">
          <div class="col-md-3">
            <span class="text-muted small d-block">Date & Heure d'Expertise</span>
            <strong><?= htmlspecialchars($pv['date_expertise']) ?> <?= htmlspecialchars($pv['heure_expertise'] ?? '') ?></strong>
          </div>
          <div class="col-md-3">
            <span class="text-muted small d-block">Lieu d'Expertise</span>
            <span><?= htmlspecialchars(!empty($pv['lieu_expertise']) ? $pv['lieu_expertise'] : '-') ?></span>
          </div>
          <div class="col-md-2">
            <span class="text-muted small d-block">Couleur véhicule</span>
            <span><?= htmlspecialchars(!empty($pv['couleur_vehicule']) ? $pv['couleur_vehicule'] : '-') ?></span>
          </div>
          <div class="col-md-2">
            <span class="text-muted small d-block">Taux de responsabilité</span>
            <span class="badge bg-secondary-subtle text-secondary border"><?= (int)$pv['taux_responsabilite'] ?> %</span>
          </div>
          <div class="col-md-2">
            <span class="text-muted small d-block">Valeur Vénale</span>
            <strong class="text-dark"><?= number_format($pv['valeur_venale'], 2) ?> DA</strong>
          </div>
          <?php if (!empty($pv['observation'])): ?>
            <div class="col-12 mt-2">
              <div class="p-2 bg-light rounded border">
                <span class="text-muted small d-block fw-bold">Observations de l'expert :</span>
                <p class="m-0 text-dark small" style="white-space: pre-line;"><?= nl2br(htmlspecialchars($pv['observation'])) ?></p>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- 3. لوحة وجدول الصدمات (Slide 12: Liste Des chocs + Bouton Ajouter Un Choc avec Menu Déroulant) -->
    <div class="card shadow-sm border-0 mb-4">
      <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
          <div class="p-2 bg-danger-subtle text-danger rounded-3 me-2">
            <i class="bi bi-shield-slash fs-5"></i>
          </div>
          <div>
            <h5 class="card-title m-0 p-0 fs-6 fw-bold text-dark">Liste Des chocs</h5>
            <small class="text-muted">Impacts et chocs constatés sur le véhicule (Slide 12)</small>
          </div>
        </div>

        <?php if (!$is_validated): ?>
          <!-- الزر الأحمر مع القائمة المنسدلة: Ajouter Un Choc (Choc A, Choc B...) المطابق للسلايد 12 تماماً -->
          <div class="btn-group">
            <button type="button" class="btn btn-danger dropdown-toggle px-3 fw-bold shadow-sm" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bi bi-plus-lg me-1"></i> Ajouter Un Choc
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow">
              <li><a class="dropdown-item py-2 fw-semibold" href="choc_form.php?pv_id=<?= $pv_id ?>&type=A"><i class="bi bi-caret-right-fill text-danger me-1"></i> Choc "A"</a></li>
              <li><a class="dropdown-item py-2 fw-semibold" href="choc_form.php?pv_id=<?= $pv_id ?>&type=B"><i class="bi bi-caret-right-fill text-danger me-1"></i> Choc "B"</a></li>
              <li><a class="dropdown-item py-2 fw-semibold" href="choc_form.php?pv_id=<?= $pv_id ?>&type=C"><i class="bi bi-caret-right-fill text-danger me-1"></i> Choc "C"</a></li>
              <li><a class="dropdown-item py-2 fw-semibold" href="choc_form.php?pv_id=<?= $pv_id ?>&type=D"><i class="bi bi-caret-right-fill text-danger me-1"></i> Choc "D"</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item py-2 text-muted" href="choc_form.php?pv_id=<?= $pv_id ?>&type=Autre"><i class="bi bi-plus-circle me-1"></i> + Autre choc...</a></li>
            </ul>
          </div>
        <?php endif; ?>
      </div>

      <div class="card-body pt-3">
        <!-- جدول الصدمات كما في Slide 12: ID | Nom | Description | Montant TTC | Action -->
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" id="tableChocs">
            <thead class="table-light">
              <tr>
                <th scope="col" style="width: 70px;">ID</th>
                <th scope="col" style="width: 160px;">Nom</th>
                <th scope="col">Description</th>
                <th scope="col" class="text-end" style="width: 170px;">Montant TTC</th>
                <th scope="col" class="text-center" style="width: 140px;">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($chocs)): ?>
                <?php foreach ($chocs as $c): ?>
                  <tr>
                    <td><strong>#<?= $c['id'] ?></strong></td>
                    <td>
                      <span class="badge bg-danger-subtle text-danger border border-danger-subtle fs-7 fw-bold px-2 py-1">
                        <?= htmlspecialchars($c['type_choc']) ?>
                      </span>
                    </td>
                    <td>
                      <div class="text-dark fw-semibold"><?= htmlspecialchars(!empty($c['description']) ? $c['description'] : 'Sans description') ?></div>
                      <?php if (!empty($c['detail_reparation'])): ?>
                        <small class="text-muted d-block text-truncate" style="max-width: 450px;">
                          <em>Avis:</em> <?= htmlspecialchars($c['detail_reparation']) ?>
                        </small>
                      <?php endif; ?>
                    </td>
                    <td class="text-end fw-bold fs-7 text-dark pe-3">
                      <?= number_format($c['montant_ttc'], 2) ?> DA
                    </td>
                    <td class="text-center">
                      <div class="btn-group btn-group-sm">
                        <a href="choc_form.php?pv_id=<?= $pv_id ?>&choc_id=<?= $c['id'] ?>" class="btn btn-outline-primary" title="Modifier / Consulter">
                          <i class="bi bi-pencil-square"></i>
                        </a>
                        <?php if (!$is_validated): ?>
                          <a href="actions/delete_choc.php?id=<?= $c['id'] ?>&pv_id=<?= $pv_id ?>" 
                             class="btn btn-outline-danger" 
                             onclick="return confirm('Confirmez-vous la suppression de ce choc et de ses fournitures ?');"
                             title="Supprimer">
                            <i class="bi bi-trash"></i>
                          </a>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="5" class="text-center py-4 text-muted">
                    <i class="bi bi-exclamation-circle fs-4 d-block mb-1"></i>
                    Pas de résultat. Aucun choc n'a été encore ajouté à cette expertise.
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- ملخص المبالغ المالية الشاملة للصدمات (Slide 14 & 19) -->
        <?php if (!empty($chocs)): ?>
          <div class="row g-2 mt-3 pt-3 border-top bg-light p-2 rounded">
            <div class="col-md-2 col-sm-4 text-center">
              <span class="text-muted small d-block">Fournitures HT</span>
              <strong class="text-dark"><?= number_format($pv['total_fournitures_ht'], 2) ?> DA</strong>
            </div>
            <div class="col-md-2 col-sm-4 text-center">
              <span class="text-muted small d-block">Main d'oeuvre HT</span>
              <strong class="text-dark"><?= number_format($pv['montant_mo_ht'], 2) ?> DA</strong>
            </div>
            <div class="col-md-2 col-sm-4 text-center">
              <span class="text-muted small d-block">Peinture HT</span>
              <strong class="text-dark"><?= number_format($pv['montant_peinture'], 2) ?> DA</strong>
            </div>
            <div class="col-md-2 col-sm-4 text-center">
              <span class="text-muted small d-block">TVA Fournitures</span>
              <strong class="text-dark"><?= number_format($pv['tva_fourniture'], 2) ?> DA</strong>
            </div>
            <div class="col-md-2 col-sm-4 text-center">
              <span class="text-muted small d-block">Immobilisation</span>
              <strong class="text-dark"><?= (int)$pv['immobilisation_jours'] ?> Jours</strong>
            </div>
            <div class="col-md-2 col-sm-4 text-center bg-primary-subtle rounded py-1">
              <span class="text-primary small d-block fw-bold">TOTAL TTC (MTC)</span>
              <strong class="text-primary fs-6"><?= number_format($pv['montant_total_ttc'], 2) ?> DA</strong>
            </div>
          </div>
        <?php endif; ?>

      </div>
    </div>

    <!-- 4. لوحة قائمة قطع الغيار الشاملة (Slide 19: Liste Des fournitures) -->
    <?php if (!empty($all_fournitures)): ?>
      <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-bottom py-3 d-flex align-items-center">
          <div class="p-2 bg-success-subtle text-success rounded-3 me-2">
            <i class="bi bi-box-seam fs-5"></i>
          </div>
          <div>
            <h5 class="card-title m-0 p-0 fs-6 fw-bold text-dark">Liste Des fournitures</h5>
            <small class="text-muted">Récapitulatif des pièces à remplacer ou réparer (Slide 19)</small>
          </div>
        </div>
        <div class="card-body pt-3">
          <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th scope="col">Article</th>
                  <th scope="col">Catégorie / Choc</th>
                  <th scope="col" class="text-end">Prix Unitaire</th>
                  <th scope="col" class="text-center">Quantité</th>
                  <th scope="col" class="text-end">Montant Général</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($all_fournitures as $f): ?>
                  <tr>
                    <td><strong><?= htmlspecialchars($f['article']) ?></strong></td>
                    <td>
                      <span class="badge bg-secondary-subtle text-secondary border me-1"><?= htmlspecialchars($f['categorie']) ?></span>
                      <span class="badge bg-light text-dark border"><?= htmlspecialchars($f['type_choc']) ?></span>
                    </td>
                    <td class="text-end"><?= number_format($f['prix_unitaire_ht'], 2) ?> DA</td>
                    <td class="text-center fw-bold"><?= (int)$f['quantite'] ?></td>
                    <td class="text-end fw-bold text-primary"><?= number_format($f['total_ht'], 2) ?> DA</td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- 5. لوحة جدول الأتعاب (Slide 15 & 19: Honoraire) -->
    <div class="card shadow-sm border-0 mb-4">
      <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
          <div class="p-2 bg-warning-subtle text-warning-emphasis rounded-3 me-2">
            <i class="bi bi-cash-stack fs-5"></i>
          </div>
          <div>
            <h5 class="card-title m-0 p-0 fs-6 fw-bold text-dark">Honoraire</h5>
            <small class="text-muted">Frais et honoraires de l'expert associés à cette mission (Slides 15, 16, 17, 19)</small>
          </div>
        </div>

        <div>
          <a href="honoraire_manage.php?pv_id=<?= $pv_id ?>" class="btn btn-warning btn-sm text-dark fw-bold shadow-sm">
            <i class="bi bi-gear-fill me-1"></i> Gérer les honoraires
          </a>
        </div>
      </div>

      <div class="card-body pt-3">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th scope="col">Libellé</th>
                <th scope="col" class="text-center" style="width: 140px;">Nombre</th>
                <th scope="col" class="text-end" style="width: 200px;">Montant</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($honoraires)): ?>
                <?php foreach ($honoraires as $h): ?>
                  <tr>
                    <td>
                      <i class="bi bi-check2 text-primary me-2"></i>
                      <span class="fw-semibold"><?= htmlspecialchars($h['libelle']) ?></span>
                    </td>
                    <td class="text-center fw-bold"><?= (int)$h['nombre'] ?></td>
                    <td class="text-end fw-bold pe-3"><?= number_format($h['montant_total'], 2) ?> DA</td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="3" class="text-center py-3 text-muted">
                    Aucun honoraire renseigné pour le moment.
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <div class="text-end mt-3 pt-3 border-top">
          <span class="text-muted fw-bold me-2">Montant Total honoraire :</span>
          <span class="fs-5 fw-bolder text-warning-emphasis"><?= number_format($total_honoraires, 2) ?> DA</span>
        </div>

      </div>
    </div>

    <!-- 6. شريط أزرار التحكم والاعتماد النهائي (Slides 15, 18, 19) -->
    <div class="card shadow-sm border-0 mb-5 bg-white">
      <div class="card-body py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">

        <div>
          <a href="ods_list.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Retour à la liste
          </a>
        </div>

        <div class="d-flex flex-wrap gap-2">
          <?php if (!$is_validated): ?>
            <!-- أ) زر الحذف: Supprimer (Slide 15: Il est possible de supprimer l'expertise si pas validée par expert) -->
            <a href="actions/delete_pv.php?id=<?= $pv_id ?>" 
               class="btn btn-danger px-4 shadow-sm"
               onclick="return confirm('Attention ! Voulez-vous vraiment supprimer définitivement ce PV d\'expertise et ses données ?');">
              <i class="bi bi-trash-fill me-1"></i> Supprimer
            </a>

            <!-- ب) زر المصادقة والاعتماد: Valider (Slide 15 & 18: À la fin de la saisie, l'expert doit valider l'expertise) -->
            <a href="actions/validate_pv.php?id=<?= $pv_id ?>" 
               class="btn btn-primary px-4 fw-bold shadow-sm"
               onclick="return confirm('Confirmez-vous la validation définitive de cette expertise ? Un code de certification officiel sera généré.');">
              <i class="bi bi-check-circle-fill me-1"></i> Valider
            </a>

            <!-- ج) زر الانتقال للأتعاب: Honoraire (Slide 15: Après sauvegarde, expert peut passer à la partie honoraires) -->
            <a href="honoraire_manage.php?pv_id=<?= $pv_id ?>" class="btn btn-warning text-dark fw-bold px-4 shadow-sm">
              <i class="bi bi-cash-stack me-1"></i> Honoraire
            </a>

          <?php else: ?>

            <!-- د) الأزرار بعد الاعتماد: Honoraire | Imprimer Honoraire | Imprimer (Slide 19) -->
            <a href="honoraire_manage.php?pv_id=<?= $pv_id ?>" class="btn btn-warning text-dark fw-bold px-4 shadow-sm">
              <i class="bi bi-cash-stack me-1"></i> Honoraire
            </a>

            <a href="honoraire_print.php?pv_id=<?= $pv_id ?>" target="_blank" class="btn btn-info text-white fw-bold px-4 shadow-sm" style="background-color: #0b7285; border-color: #0b7285;">
              <i class="bi bi-printer-fill me-1"></i> Imprimer Honoraire
            </a>

            <a href="pv_print.php?pv_id=<?= $pv_id ?>" target="_blank" class="btn btn-info text-white fw-bold px-4 shadow-sm" style="background-color: #0b7285; border-color: #0b7285;">
              <i class="bi bi-printer-fill me-1"></i> Imprimer
            </a>

          <?php endif; ?>
        </div>

      </div>
    </div>

  </section>

</main><!-- End #main -->

<?php
require_once __DIR__ . '/includes/footer.php';
?>
