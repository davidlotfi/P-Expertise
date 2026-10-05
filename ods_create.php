<?php
/**
 * ods_create.php
 * صفحة إضافة أمر مهمة جديد (Création d'un nouvel Ordre de Service - ODS)
 * تتيح لخبير السيارات تسجيل ملفات وأوامر مهمة جديدة بنفسه
 */

require_once __DIR__ . '/config/db.php';

$page_title = "Nouveau ODS | E-EXPERTISE";

// استرجاع البيانات المدخلة سابقاً في حال حدوث خطأ
$old = $_SESSION['form_data_ods'] ?? [];

// توليد أرقام افتراضية مقترحة
$default_year = date('y');
$suggested_ods = $old['numero_ods'] ?? ("16001 " . $default_year . "/" . str_pad(rand(10, 9999), 4, '0', STR_PAD_LEFT));
$suggested_dossier = $old['numero_dossier'] ?? ("16001 " . $default_year . " 1195 " . str_pad(rand(100, 999), 4, '0', STR_PAD_LEFT));

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<main id="main" class="main">

  <div class="pagetitle d-flex justify-content-between align-items-center">
    <div>
      <h1>Nouveau Ordre de Service</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Accueil</a></li>
          <li class="breadcrumb-item"><a href="ods_list.php">Expertise</a></li>
          <li class="breadcrumb-item active">Nouveau ODS</li>
        </ol>
      </nav>
    </div>
    <div>
      <a href="ods_list.php" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Retour à la liste des ODS
      </a>
    </div>
  </div><!-- End Page Title -->

  <section class="section">

    <!-- رسائل التنبيه والخطأ -->
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

    <form action="actions/save_ods.php" method="POST" class="needs-validation" novalidate>

      <div class="row">

        <!-- 1. بطاقة بيانات المهمة والملف -->
        <div class="col-lg-6">
          <div class="card shadow-sm border-0 mb-4 h-100">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center">
              <i class="bi bi-file-earmark-medical text-primary fs-5 me-2"></i>
              <h5 class="card-title m-0 p-0 fs-6 text-primary">1. Références de la Mission & Dossier</h5>
            </div>
            <div class="card-body pt-3">
              <div class="row g-3">

                <div class="col-md-6">
                  <label for="numero_ods" class="form-label fw-bold small">N° Ordre de Service (ODS) <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-hash"></i></span>
                    <input type="text" class="form-control" id="numero_ods" name="numero_ods" value="<?= htmlspecialchars($suggested_ods) ?>" required>
                  </div>
                  <div class="form-text small">Exemple: 16001 <?= date('y') ?>/0001</div>
                </div>

                <div class="col-md-6">
                  <label for="numero_dossier" class="form-label fw-bold small">N° Dossier Sinistre <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-folder2"></i></span>
                    <input type="text" class="form-control" id="numero_dossier" name="numero_dossier" value="<?= htmlspecialchars($suggested_dossier) ?>" required>
                  </div>
                  <div class="form-text small">Exemple: 16001 <?= date('y') ?> 1195 0227</div>
                </div>

                <div class="col-md-6">
                  <label for="date_ods" class="form-label fw-bold small">Date de l'ODS <span class="text-danger">*</span></label>
                  <input type="date" class="form-control" id="date_ods" name="date_ods" value="<?= htmlspecialchars($old['date_ods'] ?? date('Y-m-d')) ?>" required>
                </div>

                <div class="col-md-6">
                  <label for="date_sinistre" class="form-label fw-bold small">Date Sinistre / Accident <span class="text-danger">*</span></label>
                  <input type="date" class="form-control" id="date_sinistre" name="date_sinistre" value="<?= htmlspecialchars($old['date_sinistre'] ?? date('Y-m-d')) ?>" required>
                </div>

                <div class="col-12">
                  <label for="police" class="form-label fw-bold small">N° Police d'assurance</label>
                  <input type="text" class="form-control" id="police" name="police" value="<?= htmlspecialchars($old['police'] ?? '') ?>" placeholder="Ex: 16001 23 1112 0193">
                </div>

              </div>
            </div>
          </div>
        </div>

        <!-- 2. بطاقة معلومات المؤمن له / المتضرر -->
        <div class="col-lg-6">
          <div class="card shadow-sm border-0 mb-4 h-100">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center">
              <i class="bi bi-person-lines-fill text-primary fs-5 me-2"></i>
              <h5 class="card-title m-0 p-0 fs-6 text-primary">2. Informations de l'Assuré / Contact</h5>
            </div>
            <div class="card-body pt-3">
              <div class="row g-3">

                <div class="col-12">
                  <label for="assure" class="form-label fw-bold small">Nom et Prénom de l'Assuré / Tiers <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" class="form-control" id="assure" name="assure" value="<?= htmlspecialchars($old['assure'] ?? '') ?>" placeholder="Ex: BENALI Mohamed" required>
                  </div>
                </div>

                <div class="col-md-12">
                  <label for="telephone" class="form-label fw-bold small">Numéro de Téléphone</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                    <input type="text" class="form-control" id="telephone" name="telephone" value="<?= htmlspecialchars($old['telephone'] ?? '') ?>" placeholder="Ex: 0555 12 34 56">
                  </div>
                </div>

                <div class="col-12">
                  <div class="alert alert-info border-0 bg-light p-3 small mb-0">
                    <i class="bi bi-info-circle me-1 text-primary"></i>
                    <strong>Information :</strong> L'ordre de service sera automatiquement assigné à votre compte expert et apparaîtra en statut <code>Nouveau</code> prêt pour la saisie de l'expertise.
                  </div>
                </div>

              </div>
            </div>
          </div>
        </div>

        <!-- 3. بطاقة بيانات المركبة -->
        <div class="col-12">
          <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center">
              <i class="bi bi-car-front-fill text-primary fs-5 me-2"></i>
              <h5 class="card-title m-0 p-0 fs-6 text-primary">3. Caractéristiques du Véhicule</h5>
            </div>
            <div class="card-body pt-3">
              <div class="row g-3">

                <div class="col-md-4">
                  <label for="matricule" class="form-label fw-bold small">N° d'Immatriculation (Matricule) <span class="text-danger">*</span></label>
                  <input type="text" class="form-control fw-bold" id="matricule" name="matricule" value="<?= htmlspecialchars($old['matricule'] ?? '') ?>" placeholder="Ex: 014820-114-16" required>
                </div>

                <div class="col-md-4">
                  <label for="marque" class="form-label fw-bold small">Marque du Véhicule <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" list="marques_list" id="marque" name="marque" value="<?= htmlspecialchars($old['marque'] ?? '') ?>" placeholder="Ex: RENAULT, PEUGEOT, AUDI..." required>
                  <datalist id="marques_list">
                    <option value="RENAULT">
                    <option value="PEUGEOT">
                    <option value="VOLKSWAGEN">
                    <option value="AUDI">
                    <option value="KIA">
                    <option value="HYUNDAI">
                    <option value="TOYOTA">
                    <option value="DACIA">
                    <option value="SEAT">
                    <option value="SKODA">
                    <option value="NISSAN">
                    <option value="FIAT">
                    <option value="CHEVROLET">
                    <option value="CITROEN">
                    <option value="MERCEDES-BENZ">
                    <option value="BMW">
                    <option value="SUZUKI">
                    <option value="FORD">
                  </datalist>
                </div>

                <div class="col-md-4">
                  <label for="modele" class="form-label fw-bold small">Modèle du Véhicule <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" id="modele" name="modele" value="<?= htmlspecialchars($old['modele'] ?? '') ?>" placeholder="Ex: Clio 4, 208, A3, Golf..." required>
                </div>

                <div class="col-md-4">
                  <label for="numero_serie" class="form-label fw-bold small"> VIN</label>
                  <input type="text" class="form-control text-uppercase" id="numero_serie" name="numero_serie" value="<?= htmlspecialchars($old['numero_serie'] ?? '') ?>" placeholder="Ex: VF33CRHYB8329104">
                </div>

                <div class="col-md-4">
                  <label for="carburant" class="form-label fw-bold small">Type</label>
                  <select class="form-select" id="carburant" name="carburant">
                    <option value="2 - Diesel" <?= (($old['carburant'] ?? '') === '2 - Diesel') ? 'selected' : '' ?>>2 - Diesel</option>
                    <option value="1 - Essence" <?= (($old['carburant'] ?? '') === '1 - Essence') ? 'selected' : '' ?>>1 - Essence</option>
                    <option value="3 - GPL" <?= (($old['carburant'] ?? '') === '3 - GPL') ? 'selected' : '' ?>>3 - GPL</option>
                    <option value="4 - Hybride" <?= (($old['carburant'] ?? '') === '4 - Hybride') ? 'selected' : '' ?>>4 - Hybride</option>
                    <option value="5 - Electrique" <?= (($old['carburant'] ?? '') === '5 - Electrique') ? 'selected' : '' ?>>5 - Electrique</option>
                  </select>
                </div>

                <div class="col-md-4">
                  <label for="puissance" class="form-label fw-bold small">Puissance Fiscale</label>
                  <input type="text" class="form-control" id="puissance" name="puissance" value="<?= htmlspecialchars($old['puissance'] ?? '5 CV') ?>" placeholder="Ex: 5 CV, 6 CV, 7 à 10 CV">
                </div>

              </div>
            </div>
          </div>
        </div>

        <!-- 4. بطاقة الملاحظات والتعليمات -->
        <div class="col-12">
          <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center">
              <i class="bi bi-chat-left-text text-primary fs-5 me-2"></i>
              <h5 class="card-title m-0 p-0 fs-6 text-primary">4. Remarques & Instructions Particulières</h5>
            </div>
            <div class="card-body pt-3">
              <div class="row g-3">
                <div class="col-12">
                  <textarea class="form-control" id="remarque" name="remarque" rows="3" placeholder="Lieu d'immobilisation du véhicule, consignes particulières d'expertise ou de convocation..."><?= htmlspecialchars($old['remarque'] ?? '') ?></textarea>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- أزرار الحفظ والإرسال -->
        <div class="col-12 text-end mb-5">
          <a href="ods_list.php" class="btn btn-outline-secondary me-2 px-3">
            <i class="bi bi-x-circle me-1"></i> Annuler
          </a>
          <button type="submit" name="action_save" value="save" class="btn btn-success px-4 me-2">
            <i class="bi bi-check-circle me-1"></i> Enregistrer l'ODS
          </button>
          <button type="submit" name="action_and_pv" value="1" class="btn btn-primary px-4 fw-bold">
            <i class="bi bi-pencil-square me-1"></i> Enregistrer & Traiter l'Expertise
          </button>
        </div>

      </div>

    </form>

  </section>

</main><!-- End #main -->

<?php
// إزالة القيم المؤقتة
unset($_SESSION['form_data_ods']);
require_once __DIR__ . '/includes/footer.php';
?>
