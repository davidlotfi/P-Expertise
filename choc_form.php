<?php
/**
 * choc_form.php
 * صفحة ونموذج إضافة وتعديل الصدمات (Chocs) وقطع الغيار (Fournitures) والمبالغ المالية الملحقة
 * مطابقة تماماً للشاشات والحقول في دليل الاستخدام الرسمي E-Expertise (Slides 12, 13, 14)
 */

require_once __DIR__ . '/config/db.php';

$page_title = "Saisie d'un Choc | E-EXPERTISE";

$pv_id   = isset($_GET['pv_id']) ? (int)$_GET['pv_id'] : 0;
$choc_id = isset($_GET['choc_id']) ? (int)$_GET['choc_id'] : 0;
$type_preselect = isset($_GET['type']) ? trim($_GET['type']) : 'A';

if ($pv_id <= 0) {
    header("Location: ods_list.php");
    exit;
}

$pv = null;
$choc = null;
$fournitures = [];
$categories = [];

try {
    // 1. جلب تفاصيل المحضر والـ ODS المرتبط به
    $stmt_pv = $pdo->prepare("
        SELECT pv.*, o.numero_ods, o.numero_dossier, o.assure, o.matricule, o.marque, o.modele, o.police, o.numero_serie
        FROM pv_expertises pv
        JOIN ods o ON pv.ods_id = o.id
        WHERE pv.id = :id
    ");
    $stmt_pv->execute([':id' => $pv_id]);
    $pv = $stmt_pv->fetch();

    if (!$pv) {
        $_SESSION['alert'] = [
            'type'    => 'danger',
            'title'   => 'Introuvable !',
            'message' => 'Le procès-verbal demandé n\'existe pas.'
        ];
        header("Location: ods_list.php");
        exit;
    }

    // 2. جلب قائمة تصنيفات قطع الغيار من جدول categories_pieces
    $stmt_cat = $pdo->query("SELECT nom FROM categories_pieces ORDER BY id ASC");
    $categories = $stmt_cat->fetchAll(PDO::FETCH_COLUMN);

    if (empty($categories)) {
        // قيم افتراضية في حال كانت القاعدة جديدة
        $categories = ['CAPOT', 'AILES', 'PARE-CHOC', 'OPTIQUES / PHARES', 'RADIATEUR', 'PORTIERES', 'VITRAGE', 'MECANIQUE', 'TRAIN ROULANT'];
    }

    // 3. إن كنا في وضع تعديل صدمة موجودة (Edit Mode)
    if ($choc_id > 0) {
        $stmt_choc = $pdo->prepare("SELECT * FROM chocs WHERE id = :id AND pv_id = :pv_id");
        $stmt_choc->execute([':id' => $choc_id, ':pv_id' => $pv_id]);
        $choc = $stmt_choc->fetch();

        if ($choc) {
            $stmt_f = $pdo->prepare("SELECT * FROM fournitures_choc WHERE choc_id = :choc_id ORDER BY id ASC");
            $stmt_f->execute([':choc_id' => $choc_id]);
            $fournitures = $stmt_f->fetchAll();
        }
    }

} catch (Exception $e) {
    $db_error = $e->getMessage();
}

// تحديد نوع الصدمة الافتراضي: إذا لم تكن موجودة، نستخدم المعطى أو "Choc A"
$current_type = $choc ? $choc['type_choc'] : (strpos($type_preselect, 'Choc') !== false ? $type_preselect : 'Choc ' . $type_preselect);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<main id="main" class="main">

  <div class="pagetitle d-flex justify-content-between align-items-center">
    <div>
      <h1>Saisie du Choc (Expertise)</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Accueil</a></li>
          <li class="breadcrumb-item"><a href="ods_list.php">Expertise</a></li>
          <li class="breadcrumb-item"><a href="pv_details.php?id=<?= $pv_id ?>"><?= htmlspecialchars($pv['numero_pv']) ?></a></li>
          <li class="breadcrumb-item active"><?= htmlspecialchars($current_type) ?></li>
        </ol>
      </nav>
    </div>
    <div>
      <a href="pv_details.php?id=<?= $pv_id ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Retour au PV
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

    <!-- شريط معلومات الملف والمركبة عالي التباين (Dossier & Véhicule Info) -->
    <div class="card border-0 shadow-sm mb-4 bg-light">
      <div class="card-body py-3">
        <div class="row g-3 align-items-center">
          <div class="col-md-3">
            <span class="text-muted small d-block">N° ODS & Dossier</span>
            <strong class="text-primary"><?= htmlspecialchars($pv['numero_ods']) ?></strong>
            <span class="badge bg-secondary-subtle text-secondary border ms-1"><?= htmlspecialchars($pv['numero_dossier']) ?></span>
          </div>
          <div class="col-md-3">
            <span class="text-muted small d-block">Assuré / Tiers</span>
            <strong class="text-dark"><i class="bi bi-person me-1"></i><?= htmlspecialchars($pv['assure']) ?></strong>
          </div>
          <div class="col-md-3">
            <span class="text-muted small d-block">Véhicule & Immatriculation</span>
            <span class="fw-bold text-dark"><?= htmlspecialchars($pv['marque'] . ' ' . $pv['modele']) ?></span>
            <span class="badge bg-dark text-warning font-monospace ms-1 px-2"><?= htmlspecialchars($pv['matricule']) ?></span>
          </div>
          <div class="col-md-3 text-md-end">
            <span class="text-muted small d-block">N° PV d'expertise</span>
            <span class="badge bg-primary fs-6 px-3 py-1"><?= htmlspecialchars($pv['numero_pv']) ?></span>
          </div>
        </div>
      </div>
    </div>

    <!-- نموذج الصدمة وقطع الغيار (Choc Form) -->
    <form action="actions/save_choc.php" method="POST" id="formChoc" class="needs-validation" novalidate>
      <input type="hidden" name="pv_id" value="<?= $pv_id ?>">
      <input type="hidden" name="choc_id" value="<?= $choc ? (int)$choc['id'] : 0 ?>">

      <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
          <div class="d-flex align-items-center">
            <div class="p-2 bg-danger-subtle text-danger rounded-3 me-2">
              <i class="bi bi-bullseye fs-4"></i>
            </div>
            <div>
              <h5 class="card-title m-0 p-0 fs-6 fw-bold text-dark">
                <?= $choc ? 'Modifier le ' . htmlspecialchars($choc['type_choc']) : 'Ajout d\'un nouveau Choc' ?>
              </h5>
              <small class="text-muted">Définissez la localisation de l'impact et la description des dégâts (Slides 13 & 14)</small>
            </div>
          </div>
          <span class="badge bg-danger fs-6 px-3 py-2"><?= htmlspecialchars($current_type) ?></span>
        </div>

        <div class="card-body pt-3">
          <div class="row g-3">

            <!-- 1. Type Choc -->
            <div class="col-md-4">
              <label for="type_choc" class="form-label fw-bold text-dark small">
                Type Choc <span class="text-danger">*</span>
              </label>
              <select class="form-select form-select-lg fs-6 fw-bold border-danger-subtle" id="type_choc" name="type_choc" required>
                <?php
                $types_chocs = ['Choc A', 'Choc B', 'Choc C', 'Choc D', 'Choc E', 'Choc F'];
                foreach ($types_chocs as $tc):
                ?>
                  <option value="<?= $tc ?>" <?= (trim($current_type) === $tc || trim($current_type) === str_replace('Choc ', '', $tc)) ? 'selected' : '' ?>>
                    <?= $tc ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- 2. Description (Slide 13) -->
            <div class="col-md-8">
              <label for="description" class="form-label fw-bold text-dark small">Description</label>
              <textarea class="form-control" id="description" name="description" rows="2" placeholder="Description des points d'impact et des déformations observées sur cette partie..."><?= htmlspecialchars($choc['description'] ?? '') ?></textarea>
            </div>

          </div>
        </div>
      </div>

      <!-- 3. لوحة الرأي وتفاصيل التصليح Avis (Slide 13) -->
      <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-bottom py-3 d-flex align-items-center">
          <div class="p-2 bg-info-subtle text-info rounded-3 me-2">
            <i class="bi bi-info-circle-fill fs-5"></i>
          </div>
          <div>
            <h5 class="card-title m-0 p-0 fs-6 fw-bold text-dark">Avis</h5>
            <small class="text-muted">Avis technique de l'expert et recommandations de réparation</small>
          </div>
        </div>
        <div class="card-body pt-3">
          <div class="row g-2">
            <div class="col-12">
              <label for="detail_reparation" class="form-label fw-bold text-dark small">Détail de réparation</label>
              <textarea class="form-control" id="detail_reparation" name="detail_reparation" rows="3" placeholder="Saisir les opérations de tôlerie, redressage, découpe, ou remplacement préconisées..."><?= htmlspecialchars($choc['detail_reparation'] ?? '') ?></textarea>
            </div>
          </div>
        </div>
      </div>

      <!-- 4. جدول قطع الغيار والمواد المستعملة Fournitures (Slides 13 & 14) -->
      <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
          <div class="d-flex align-items-center">
            <div class="p-2 bg-success-subtle text-success rounded-3 me-2">
              <i class="bi bi-car-front-fill fs-5"></i>
            </div>
            <div>
              <h5 class="card-title m-0 p-0 fs-6 fw-bold text-dark">Fournitures</h5>
              <small class="text-muted">Sélectionnez la catégorie, l'article et chiffrez les pièces nécessaires</small>
            </div>
          </div>
          <div>
            <button type="button" class="btn btn-sm btn-outline-success" id="btnAddRowTop">
              <i class="bi bi-plus-lg me-1"></i> Ajouter une fourniture
            </button>
          </div>
        </div>

        <div class="card-body pt-3">
          <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-2" id="tableFournitures">
              <thead class="table-light text-center small text-uppercase fw-bold">
                <tr>
                  <th style="width: 50px;">#</th>
                  <th style="width: 220px;">Catégorie <span class="text-danger">*</span></th>
                  <th>Article <span class="text-danger">*</span></th>
                  <th style="width: 140px;">Prix (HT)</th>
                  <th style="width: 90px;">Nb</th>
                  <th style="width: 150px;">Total (HT)</th>
                  <th style="width: 120px;">Action</th>
                </tr>
              </thead>
              <tbody id="tbodyFournitures">
                <?php
                // إذا كان هناك قطع غيار مسجلة مسبقاً نعرضها، وإلا نعرض صفاً فارغاً افتراضياً
                $rows_to_render = !empty($fournitures) ? $fournitures : [
                    ['categorie' => 'CAPOT', 'article' => '', 'prix_unitaire_ht' => 0.00, 'quantite' => 1, 'total_ht' => 0.00]
                ];

                foreach ($rows_to_render as $index => $row):
                  $row_num = $index + 1;
                ?>
                  <tr class="fourniture-row">
                    <td class="text-center fw-bold text-muted row-index"><?= $row_num ?></td>
                    <td>
                      <select class="form-select form-select-sm select-categorie" name="fournitures[<?= $index ?>][categorie]" required>
                        <option value="">-- Choisir Catégorie --</option>
                        <?php foreach ($categories as $cat): ?>
                          <option value="<?= htmlspecialchars($cat) ?>" <?= ($row['categorie'] === $cat) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat) ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </td>
                    <td>
                      <input type="text" class="form-control form-control-sm input-article" name="fournitures[<?= $index ?>][article]" value="<?= htmlspecialchars($row['article']) ?>" placeholder="Ex: Capot moteur, Aile..." required>
                    </td>
                    <td>
                      <div class="input-group input-group-sm">
                        <input type="number" step="0.01" min="0" class="form-control text-end input-prix" name="fournitures[<?= $index ?>][prix]" value="<?= number_format($row['prix_unitaire_ht'], 2, '.', '') ?>">
                        <span class="input-group-text small">DA</span>
                      </div>
                    </td>
                    <td>
                      <input type="number" step="1" min="1" class="form-control form-control-sm text-center input-nb" name="fournitures[<?= $index ?>][nb]" value="<?= (int)($row['quantite'] ?? 1) ?>">
                    </td>
                    <td>
                      <div class="input-group input-group-sm">
                        <input type="number" step="0.01" readonly class="form-control text-end bg-light fw-bold input-total-row" name="fournitures[<?= $index ?>][total]" value="<?= number_format($row['total_ht'], 2, '.', '') ?>">
                        <span class="input-group-text small">DA</span>
                      </div>
                    </td>
                    <td class="text-center">
                      <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-primary btn-add-inline" title="Ajouter une ligne">+Ajouter</button>
                        <button type="button" class="btn btn-outline-danger btn-remove-row" title="Supprimer cette ligne">Supprimer</button>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <!-- أزرار الإضافة السفلية وملخص المجاميع كما في الشاشة 13 و 14 -->
          <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 pt-2 border-top">
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-sm btn-primary" id="btnAddNewRow">
                <i class="bi bi-plus-circle me-1"></i> +Ajouter
              </button>
              <button type="button" class="btn btn-sm btn-outline-secondary" id="btnAddAutreRow">
                <i class="bi bi-plus-square me-1"></i> +Autres
              </button>
            </div>

            <!-- مجاميع قطع الغيار: Total Unit و Total HT (Slide 14) -->
            <div class="d-flex align-items-center gap-3 mt-2 mt-md-0">
              <div class="d-flex align-items-center">
                <span class="fw-bold text-secondary me-2 small">Total Unit:</span>
                <div class="input-group input-group-sm" style="width: 140px;">
                  <input type="text" readonly id="display_total_unit" class="form-control text-end fw-bold bg-white" value="0.00">
                  <span class="input-group-text">DA</span>
                </div>
              </div>
              <div class="d-flex align-items-center">
                <span class="fw-bold text-dark me-2 small">Total HT:</span>
                <div class="input-group input-group-sm" style="width: 160px;">
                  <input type="text" readonly id="display_total_ht" name="total_fournitures_ht" class="form-control text-end fw-bold text-primary bg-primary-subtle" value="<?= number_format($choc['total_fournitures_ht'] ?? 0, 2, '.', '') ?>">
                  <span class="input-group-text fw-bold">DA</span>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- 5. لوحة المبالغ التكميلية Autres montants (Slide 14) -->
      <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-bottom py-3 d-flex align-items-center">
          <div class="p-2 bg-warning-subtle text-warning-emphasis rounded-3 me-2">
            <i class="bi bi-calculator-fill fs-5"></i>
          </div>
          <div>
            <h5 class="card-title m-0 p-0 fs-6 fw-bold text-dark">Autres montants</h5>
            <small class="text-muted">Main d'œuvre, peinture, TVA légale et calcul du coût global (MTC)</small>
          </div>
        </div>

        <div class="card-body pt-3">
          <div class="row g-3">

            <!-- Main d'oeuvre (Montant HT) -->
            <div class="col-lg-2 col-md-4 col-sm-6">
              <label for="montant_mo_ht" class="form-label fw-bold text-dark small">Main d'oeuvre (Montant)</label>
              <div class="input-group">
                <input type="number" step="0.01" min="0" class="form-control text-end calculate-trigger" id="montant_mo_ht" name="montant_mo_ht" value="<?= number_format($choc['montant_mo_ht'] ?? 0, 2, '.', '') ?>">
                <span class="input-group-text small">DA</span>
              </div>
            </div>

            <!-- Immobilisation (Jours) -->
            <div class="col-lg-2 col-md-4 col-sm-6">
              <label for="immobilisation_jours" class="form-label fw-bold text-dark small">Immobilisation (Jours)</label>
              <div class="input-group">
                <input type="number" step="1" min="0" class="form-control text-center" id="immobilisation_jours" name="immobilisation_jours" value="<?= (int)($choc['immobilisation_jours'] ?? 0) ?>">
                <span class="input-group-text small">Jours</span>
              </div>
            </div>

            <!-- Peinture (Montant HT) -->
            <div class="col-lg-2 col-md-4 col-sm-6">
              <label for="montant_peinture" class="form-label fw-bold text-dark small">Peinture</label>
              <div class="input-group">
                <input type="number" step="0.01" min="0" class="form-control text-end calculate-trigger" id="montant_peinture" name="montant_peinture" value="<?= number_format($choc['montant_peinture'] ?? 0, 2, '.', '') ?>">
                <span class="input-group-text small">DA</span>
              </div>
            </div>

            <!-- TVA fourniture (Auto 19% or custom) -->
            <div class="col-lg-2 col-md-4 col-sm-6">
              <label for="tva_fourniture" class="form-label fw-bold text-dark small">
                TVA fourniture <span class="badge bg-secondary-subtle text-secondary small">19%</span>
              </label>
              <div class="input-group">
                <input type="number" step="0.01" min="0" class="form-control text-end calculate-trigger bg-light" id="tva_fourniture" name="tva_fourniture" value="<?= number_format($choc['tva_fourniture'] ?? 0, 2, '.', '') ?>">
                <span class="input-group-text small">DA</span>
              </div>
            </div>

            <!-- MTC (Montant Total TTC) -->
            <div class="col-lg-2 col-md-4 col-sm-6">
              <label for="montant_ttc" class="form-label fw-bold text-primary small">MTC (Total TTC)</label>
              <div class="input-group">
                <input type="number" step="0.01" readonly class="form-control text-end fw-bold bg-success-subtle text-success fs-6" id="montant_ttc" name="montant_ttc" value="<?= number_format($choc['montant_ttc'] ?? 0, 2, '.', '') ?>">
                <span class="input-group-text fw-bold text-success">DA</span>
              </div>
            </div>

            <!-- Vétusté % -->
            <div class="col-lg-2 col-md-4 col-sm-6">
              <label for="taux_vetuste" class="form-label fw-bold text-dark small">Vétusté %</label>
              <div class="input-group">
                <input type="number" step="1" min="0" max="100" class="form-control text-center" id="taux_vetuste" name="taux_vetuste" value="<?= (int)($choc['taux_vetuste'] ?? 0) ?>">
                <span class="input-group-text small">%</span>
              </div>
            </div>

          </div>
        </div>
      </div>

      <!-- 6. أزرار التحكم السفلية (Slide 14: Retour à gauche, Sauvegarder à droite) -->
      <div class="d-flex justify-content-between align-items-center mb-5">
        <a href="pv_details.php?id=<?= $pv_id ?>" class="btn btn-secondary px-4 py-2">
          <i class="bi bi-arrow-left me-1"></i> Retour
        </a>
        <button type="submit" class="btn btn-primary px-5 py-2 fw-bold shadow-sm">
          <i class="bi bi-check2-circle me-1"></i> Sauvegarder
        </button>
      </div>

    </form>

  </section>

</main><!-- End #main -->

<!-- سكريبت إدارة الصفوف الديناميكية واحتساب المبالغ لحظياً (Live Calculation Script) -->
<script>
document.addEventListener('DOMContentLoaded', function () {
  const tbody = document.getElementById('tbodyFournitures');
  const btnAddNewRow = document.getElementById('btnAddNewRow');
  const btnAddAutreRow = document.getElementById('btnAddAutreRow');
  const btnAddRowTop = document.getElementById('btnAddRowTop');

  const displayTotalUnit = document.getElementById('display_total_unit');
  const displayTotalHT = document.getElementById('display_total_ht');

  const inputMO = document.getElementById('montant_mo_ht');
  const inputPeinture = document.getElementById('montant_peinture');
  const inputTVA = document.getElementById('tva_fourniture');
  const inputMTC = document.getElementById('montant_ttc');

  const categories = <?= json_encode($categories) ?>;

  // إعادة ترقيم الصفوف وتحديث أسماء المدخلات
  function reindexRows() {
    const rows = tbody.querySelectorAll('.fourniture-row');
    rows.forEach(function (row, idx) {
      row.querySelector('.row-index').textContent = idx + 1;
      row.querySelector('.select-categorie').name = 'fournitures[' + idx + '][categorie]';
      row.querySelector('.input-article').name = 'fournitures[' + idx + '][article]';
      row.querySelector('.input-prix').name = 'fournitures[' + idx + '][prix]';
      row.querySelector('.input-nb').name = 'fournitures[' + idx + '][nb]';
      row.querySelector('.input-total-row').name = 'fournitures[' + idx + '][total]';
    });
  }

  // حساب كافة المجاميع (Fournitures + TVA + MO + Peinture = MTC)
  function recalculateAll() {
    let sumTotalUnit = 0;
    let sumTotalHT = 0;

    const rows = tbody.querySelectorAll('.fourniture-row');
    rows.forEach(function (row) {
      const prix = parseFloat(row.querySelector('.input-prix').value) || 0;
      const nb = parseInt(row.querySelector('.input-nb').value) || 0;
      const rowTotal = prix * nb;

      row.querySelector('.input-total-row').value = rowTotal.toFixed(2);

      sumTotalUnit += prix;
      sumTotalHT += rowTotal;
    });

    displayTotalUnit.value = sumTotalUnit.toFixed(2);
    displayTotalHT.value = sumTotalHT.toFixed(2);

    // حساب رسم القيمة المضافة لقطع الغيار (19% القياسية في الجزائر)
    const tvaFourniture = Math.round((sumTotalHT * 0.19) * 100) / 100;
    inputTVA.value = tvaFourniture.toFixed(2);

    // حساب المبلغ الإجمالي مع الرسوم MTC
    const mo = parseFloat(inputMO.value) || 0;
    const peinture = parseFloat(inputPeinture.value) || 0;
    const mtc = sumTotalHT + tvaFourniture + mo + peinture;

    inputMTC.value = mtc.toFixed(2);
  }

  // إضافة صف جديد
  function createRow(defaultCat = '', defaultArt = '') {
    const newIdx = tbody.querySelectorAll('.fourniture-row').length;
    const tr = document.createElement('tr');
    tr.className = 'fourniture-row';

    let catOptions = '<option value="">-- Choisir Catégorie --</option>';
    categories.forEach(function (c) {
      const selected = (c === defaultCat) ? 'selected' : '';
      catOptions += '<option value="' + c + '" ' + selected + '>' + c + '</option>';
    });

    tr.innerHTML = `
      <td class="text-center fw-bold text-muted row-index">${newIdx + 1}</td>
      <td>
        <select class="form-select form-select-sm select-categorie" name="fournitures[${newIdx}][categorie]" required>
          ${catOptions}
        </select>
      </td>
      <td>
        <input type="text" class="form-control form-control-sm input-article" name="fournitures[${newIdx}][article]" value="${defaultArt}" placeholder="Ex: Capot moteur, Aile..." required>
      </td>
      <td>
        <div class="input-group input-group-sm">
          <input type="number" step="0.01" min="0" class="form-control text-end input-prix" name="fournitures[${newIdx}][prix]" value="0.00">
          <span class="input-group-text small">DA</span>
        </div>
      </td>
      <td>
        <input type="number" step="1" min="1" class="form-control form-control-sm text-center input-nb" name="fournitures[${newIdx}][nb]" value="1">
      </td>
      <td>
        <div class="input-group input-group-sm">
          <input type="number" step="0.01" readonly class="form-control text-end bg-light fw-bold input-total-row" name="fournitures[${newIdx}][total]" value="0.00">
          <span class="input-group-text small">DA</span>
        </div>
      </td>
      <td class="text-center">
        <div class="btn-group btn-group-sm">
          <button type="button" class="btn btn-outline-primary btn-add-inline" title="Ajouter une ligne">+Ajouter</button>
          <button type="button" class="btn btn-outline-danger btn-remove-row" title="Supprimer cette ligne">Supprimer</button>
        </div>
      </td>
    `;

    tbody.appendChild(tr);
    reindexRows();
    tr.querySelector('.input-article').focus();
    recalculateAll();
  }

  // أحداث النقر والإدخال في جدول القطع
  tbody.addEventListener('input', function (e) {
    if (e.target.classList.contains('input-prix') || e.target.classList.contains('input-nb')) {
      recalculateAll();
    }
  });

  tbody.addEventListener('click', function (e) {
    if (e.target.classList.contains('btn-remove-row')) {
      const rows = tbody.querySelectorAll('.fourniture-row');
      if (rows.length > 1) {
        e.target.closest('.fourniture-row').remove();
        reindexRows();
        recalculateAll();
      } else {
        // إذا كان الصف الوحيد، نقوم بتفريغ حقوله فقط
        const singleRow = rows[0];
        singleRow.querySelector('.input-article').value = '';
        singleRow.querySelector('.input-prix').value = '0.00';
        singleRow.querySelector('.input-nb').value = '1';
        recalculateAll();
      }
    } else if (e.target.classList.contains('btn-add-inline')) {
      createRow();
    }
  });

  // أحداث أزرار إضافة الصفوف
  if (btnAddNewRow) btnAddNewRow.addEventListener('click', () => createRow());
  if (btnAddAutreRow) btnAddAutreRow.addEventListener('click', () => createRow('DIVERS', 'Fourniture diverse'));
  if (btnAddRowTop) btnAddRowTop.addEventListener('click', () => createRow());

  // أحداث إدخال مبالغ اليد العاملة والطلاء و TVA
  document.querySelectorAll('.calculate-trigger').forEach(function (elem) {
    elem.addEventListener('input', function () {
      const sumTotalHT = parseFloat(displayTotalHT.value) || 0;
      const tva = parseFloat(inputTVA.value) || 0;
      const mo = parseFloat(inputMO.value) || 0;
      const peinture = parseFloat(inputPeinture.value) || 0;
      inputMTC.value = (sumTotalHT + tva + mo + peinture).toFixed(2);
    });
  });

  // حساب أولي عند فتح الصفحة
  recalculateAll();
});
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
