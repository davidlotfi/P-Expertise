<?php
/**
 * honoraire_manage.php
 * صفحة إدارة وإضافة أتعاب ومصاريف الخبير للمحضر
 * مطابقة تماماً للشاشات والحقول في دليل الاستخدام الرسمي E-Expertise (Slides 15, 16, 17)
 */

require_once __DIR__ . '/config/db.php';

$page_title = "Gestion des Honoraires | E-EXPERTISE";

$pv_id = isset($_GET['pv_id']) ? (int)$_GET['pv_id'] : 0;

if ($pv_id <= 0) {
    header("Location: ods_list.php");
    exit;
}

$pv = null;
$types_frais = [];
$honoraires_list = [];
$total_honoraires = 0.00;

try {
    // 1. جلب تفاصيل المحضر والـ ODS المرتبط به
    $stmt_pv = $pdo->prepare("
        SELECT pv.*, o.numero_ods, o.numero_dossier, o.assure, o.matricule, o.marque, o.modele
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

    // 2. جلب أنواع المصاريف المعتمدة من جدول types_frais_honoraires
    $stmt_tf = $pdo->query("SELECT * FROM types_frais_honoraires ORDER BY id ASC");
    $types_frais = $stmt_tf->fetchAll();

    if (empty($types_frais)) {
        // تعبئة البيانات القياسية في حال لم تكن مسجلة بعد
        $defaults = [
            ['libelle' => 'Déplacement véhicule personnel - 40 km', 'tarif_defaut' => 200.00],
            ['libelle' => 'Déplacement véhicule personnel >= 40 km', 'tarif_defaut' => 400.00],
            ['libelle' => 'Photos', 'tarif_defaut' => 40.00],
            ['libelle' => 'Frais de dossier', 'tarif_defaut' => 150.00],
            ['libelle' => 'Restauration', 'tarif_defaut' => 500.00],
            ['libelle' => 'Hébergement', 'tarif_defaut' => 1500.00],
            ['libelle' => 'Honoraire de base expertise', 'tarif_defaut' => 800.00]
        ];
        foreach ($defaults as $d) {
            $pdo->prepare("INSERT IGNORE INTO types_frais_honoraires (libelle, tarif_defaut) VALUES (?, ?)")
                ->execute([$d['libelle'], $d['tarif_defaut']]);
        }
        $types_frais = $pdo->query("SELECT * FROM types_frais_honoraires ORDER BY id ASC")->fetchAll();
    }

    // 3. جلب قائمة الأتعاب المسجلة مسبقاً لهذا المحضر
    $stmt_hon = $pdo->prepare("SELECT * FROM pv_honoraires WHERE pv_id = :pv_id ORDER BY id ASC");
    $stmt_hon->execute([':pv_id' => $pv_id]);
    $honoraires_list = $stmt_hon->fetchAll();

    foreach ($honoraires_list as $h) {
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
      <h1>Honoraire</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
          <li class="breadcrumb-item"><a href="ods_list.php">Liste des ODS en instances</a></li>
          <li class="breadcrumb-item"><a href="pv_details.php?id=<?= $pv_id ?>">Expertise</a></li>
          <li class="breadcrumb-item active">Honoraires</li>
        </ol>
      </nav>
    </div>
    <div>
      <a href="pv_details.php?id=<?= $pv_id ?>" class="btn btn-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Retour à l'expertise
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

    <!-- شريط معلومات الملف ODS كما في Slide 16 -->
    <div class="card shadow-sm border-0 mb-4 bg-light">
      <div class="card-body py-3">
        <div class="row align-items-center">
          <div class="col-md-4">
            <span class="text-muted small">ODS N°:</span>
            <span class="fw-bold fs-6 text-primary ms-1"><?= htmlspecialchars($pv['numero_ods']) ?></span>
          </div>
          <div class="col-md-5">
            <span class="text-muted small">Dossier N°:</span>
            <span class="fw-bold fs-6 text-dark ms-1"><?= htmlspecialchars($pv['numero_dossier']) ?></span>
          </div>
          <div class="col-md-3 text-md-end">
            <span class="badge bg-secondary px-3 py-1">PV N°: <?= htmlspecialchars($pv['numero_pv']) ?></span>
          </div>
        </div>
      </div>
    </div>

    <!-- نموذج إضافة الأتعاب والمصاريف (Slide 16: Frais:*, Nombre:*, Bouton Ajouter) -->
    <?php if (!$is_validated): ?>
      <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-bottom py-3">
          <div class="d-flex align-items-center">
            <div class="p-2 bg-warning-subtle text-warning-emphasis rounded-3 me-2">
              <i class="bi bi-cash-stack fs-5"></i>
            </div>
            <div>
              <h5 class="card-title m-0 p-0 fs-6 fw-bold text-dark">Ajouter des frais d'honoraires</h5>
              <small class="text-muted">Il est possible d'ajouter les autres frais honoraires : sélectionner les frais (Slide 16)</small>
            </div>
          </div>
        </div>
        <div class="card-body pt-3">
          <form action="actions/save_honoraire.php" method="POST" class="needs-validation" novalidate id="formHonoraire">
            <input type="hidden" name="pv_id" value="<?= $pv_id ?>">
            <input type="hidden" name="libelle" id="libelle_hidden" value="">

            <div class="row g-3 align-items-end">

              <!-- قائمة Frais:* المطابقة للسلايد 16 -->
              <div class="col-md-5">
                <label for="type_frais_select" class="form-label fw-bold text-dark small">
                  Frais:<span class="text-danger">*</span>
                </label>
                <select class="form-select border-primary-subtle" id="type_frais_select" name="type_frais_id" required>
                  <option value="">-- Sélectionner un type de frais --</option>
                  <?php foreach ($types_frais as $tf): ?>
                    <option value="<?= $tf['id'] ?>" data-tarif="<?= $tf['tarif_defaut'] ?>" data-libelle="<?= htmlspecialchars($tf['libelle']) ?>">
                      <?= htmlspecialchars($tf['libelle']) ?> (<?= number_format($tf['tarif_defaut'], 2) ?> DA)
                    </option>
                  <?php endforeach; ?>
                  <option value="custom" data-tarif="0" data-libelle="">+ Autre frais personnalisé...</option>
                </select>
              </div>

              <!-- حقل التسمية المخصصة (يظهر فقط إن تم اختيار Autre frais) -->
              <div class="col-md-3 d-none" id="div_custom_libelle">
                <label for="custom_libelle" class="form-label fw-bold text-dark small">Désignation</label>
                <input type="text" class="form-control" id="custom_libelle" placeholder="Ex: Frais de remorquage...">
              </div>

              <!-- حقل Nombre:* المطابق للسلايد 16 -->
              <div class="col-md-2" id="div_nombre">
                <label for="nombre_input" class="form-label fw-bold text-dark small">
                  Nombre :<span class="text-danger">*</span>
                </label>
                <input type="number" step="1" min="1" class="form-control text-center fw-bold" id="nombre_input" name="nombre" value="1" required>
              </div>

              <!-- حقل التعريفة الفردية -->
              <div class="col-md-2">
                <label for="montant_unitaire" class="form-label fw-bold text-dark small">Tarif Unitaire (DA)</label>
                <div class="input-group">
                  <input type="number" step="0.01" min="0" class="form-control text-end fw-semibold" id="montant_unitaire" name="montant_unitaire" value="0.00" required>
                  <span class="input-group-text small">DA</span>
                </div>
              </div>

              <!-- زر الإضافة Ajouter المطابق للسلايد 16 -->
              <div class="col-md-3 col-lg-2">
                <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm" style="min-height: 38px;">
                  <i class="bi bi-plus-lg me-1"></i> Ajouter
                </button>
              </div>

            </div>
          </form>
        </div>
      </div>
    <?php endif; ?>

    <!-- جدول الأتعاب المسجلة المطابق للسلايد 16 و 17 -->
    <div class="card shadow-sm border-0 mb-4">
      <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
        <h5 class="card-title m-0 p-0 fs-6 fw-bold text-dark">
          <i class="bi bi-table me-2 text-primary"></i> Détail des Honoraires & Frais
        </h5>
        <span class="badge bg-secondary"><?= count($honoraires_list) ?> ligne(s)</span>
      </div>
      <div class="card-body pt-3">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" id="tableHonoraires">
            <thead class="table-light">
              <tr>
                <th scope="col" style="min-width: 280px;">Libellé</th>
                <th scope="col" class="text-center" style="width: 120px;">Nombre</th>
                <th scope="col" class="text-end" style="width: 180px;">Montant</th>
                <th scope="col" class="text-center" style="width: 100px;">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($honoraires_list)): ?>
                <?php foreach ($honoraires_list as $item): ?>
                  <tr>
                    <td>
                      <i class="bi bi-check-circle text-success me-2"></i>
                      <span class="fw-semibold text-dark"><?= htmlspecialchars($item['libelle']) ?></span>
                      <?php if ($item['montant_unitaire'] > 0 && $item['nombre'] > 1): ?>
                        <small class="text-muted d-block ps-4">(<?= number_format($item['montant_unitaire'], 2) ?> DA &times; <?= $item['nombre'] ?>)</small>
                      <?php endif; ?>
                    </td>
                    <td class="text-center fw-bold"><?= (int)$item['nombre'] ?></td>
                    <td class="text-end fw-bold fs-7 text-dark pe-4">
                      <?= number_format($item['montant_total'], 2) ?> DA
                    </td>
                    <td class="text-center">
                      <?php if (!$is_validated): ?>
                        <!-- زر الحذف الأحمر كما في Slide 17 -->
                        <a href="actions/delete_honoraire.php?id=<?= $item['id'] ?>&pv_id=<?= $pv_id ?>" 
                           class="btn btn-sm btn-danger px-2 py-1 shadow-sm" 
                           onclick="return confirm('Voulez-vous vraiment supprimer ce frais ?');" 
                           title="Supprimer">
                          <i class="bi bi-trash-fill"></i>
                        </a>
                      <?php else: ?>
                        <span class="text-muted small"><i class="bi bi-lock-fill"></i></span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="4" class="text-center py-4 text-muted">
                    <i class="bi bi-inbox fs-4 d-block mb-1"></i>
                    Aucun honoraire ajouté pour le moment.
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- شريط الإجمالي: Montant Total honoraire : XX,XX DA (Slide 16 & 17) -->
        <div class="row align-items-center mt-3 pt-3 border-top">
          <div class="col-md-6">
            <span class="text-muted small">
              <i class="bi bi-info-circle me-1"></i> Après ajout de tous les frais, cliquer sur le bouton <strong>Retour</strong> (Slide 17).
            </span>
          </div>
          <div class="col-md-6 text-md-end">
            <div class="d-inline-block p-2 px-3 bg-light rounded-3 border">
              <span class="text-muted fw-bold me-2">Montant Total honoraire :</span>
              <span class="fs-5 fw-bolder text-primary"><?= number_format($total_honoraires, 2) ?> DA</span>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- زر الرجوع السفلي Retour كما في Slide 17 -->
    <div class="mb-5">
      <a href="pv_details.php?id=<?= $pv_id ?>" class="btn btn-secondary px-4 py-2">
        <i class="bi bi-arrow-left me-1"></i> Retour
      </a>
    </div>

  </section>

</main><!-- End #main -->

<script>
document.addEventListener('DOMContentLoaded', function () {
  const selectFrais = document.getElementById('type_frais_select');
  const inputTarif = document.getElementById('montant_unitaire');
  const hiddenLibelle = document.getElementById('libelle_hidden');
  const divCustom = document.getElementById('div_custom_libelle');
  const inputCustom = document.getElementById('custom_libelle');

  if (selectFrais) {
    selectFrais.addEventListener('change', function () {
      const selectedOption = selectFrais.options[selectFrais.selectedIndex];
      const val = selectFrais.value;

      if (val === 'custom') {
        divCustom.classList.remove('d-none');
        inputTarif.value = '0.00';
        hiddenLibelle.value = inputCustom.value;
        inputCustom.focus();
      } else if (val) {
        divCustom.classList.add('d-none');
        const tarif = selectedOption.getAttribute('data-tarif') || '0';
        const libelle = selectedOption.getAttribute('data-libelle') || selectedOption.text;
        inputTarif.value = parseFloat(tarif).toFixed(2);
        hiddenLibelle.value = libelle;
      } else {
        divCustom.classList.add('d-none');
        inputTarif.value = '0.00';
        hiddenLibelle.value = '';
      }
    });

    if (inputCustom) {
      inputCustom.addEventListener('input', function () {
        if (selectFrais.value === 'custom') {
          hiddenLibelle.value = inputCustom.value;
        }
      });
    }
  }
});
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
