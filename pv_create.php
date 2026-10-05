<?php
/**
 * pv_create.php
 * صفحة إنشاء محضر معاينة وخبرة جديد (Création d'un PV d'expertise)
 * مستوحاة ومطابقة لشرائح الدليل (Slides 8, 9, 10, 11, 14)
 */

require_once __DIR__ . '/config/db.php';

$page_title = "Création d'une Expertise | E-EXPERTISE";

// جلب قائمة الـ ODS المتاحة من قاعدة البيانات
$liste_ods = [];
$selected_ods = null;
$selected_ods_id = isset($_GET['ods_id']) ? (int)$_GET['ods_id'] : 0;

try {
    if (isset($pdo)) {
        // جلب جميع أوامر المهمة
        $stmt = $pdo->query("SELECT id, numero_ods, numero_dossier, assure, matricule, marque, modele FROM ods ORDER BY id DESC");
        $liste_ods = $stmt->fetchAll();

        // إن تم تحديد ODS محدد عبر الرابط، نجلب تفاصيله الكاملة (كما في Slide 10)
        if ($selected_ods_id > 0) {
            $stmt_detail = $pdo->prepare("SELECT * FROM ods WHERE id = :id");
            $stmt_detail->execute([':id' => $selected_ods_id]);
            $selected_ods = $stmt_detail->fetch();
        } elseif (!empty($liste_ods)) {
            // اختيار أول ODS افتراضياً
            $selected_ods_id = (int)$liste_ods[0]['id'];
            $selected_ods = $liste_ods[0];
        }
    }
} catch (Exception $e) {
    $db_error = $e->getMessage();
}

// تضمين رأس الصفحة والقائمة الجانبية
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<main id="main" class="main">

  <div class="pagetitle d-flex justify-content-between align-items-center">
    <div>
      <h1>Création d'Expertise</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Accueil</a></li>
          <li class="breadcrumb-item">Expertise</li>
          <li class="breadcrumb-item active">Nouveau PV</li>
        </ol>
      </nav>
    </div>
    <div>
      <a href="index.php" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Retour au tableau de bord
      </a>
    </div>
  </div><!-- End Page Title -->

  <section class="section">

    <!-- رسائل التنبيه والنجاح (Flash Alerts) عند الحفظ -->
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

    <?php if (empty($liste_ods)): ?>
      <div class="alert alert-warning shadow-sm" role="alert">
        <i class="bi bi-exclamation-circle-fill me-2"></i>
        <strong>Aucun ODS disponible !</strong> Veuillez d'abord importer les données via le fichier <code>database.sql</code> pour charger les dossiers d'assurance.
      </div>
    <?php endif; ?>

    <form action="actions/save_pv.php" method="POST" class="needs-validation" novalidate>

      <div class="row">

<style>
  /* ==================================================== */
  /* تحسينات بصرية وألوان متقدمة لبطاقات ODS و PV           */
  /* ==================================================== */
  .card-header-styled {
    background: #ffffff;
    border-bottom: 2px solid #e2e8f0;
    padding: 1rem 1.25rem;
  }
  .card-header-accent-blue {
    border-bottom: 2px solid #0b5777 !important;
  }

  /* 1. لوحة تفاصيل الـ ODS والمركبة */
  .ods-details-panel {
    background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
    border: 1px solid #cbd5e1;
    border-left: 5px solid #0b5777;
    border-radius: 10px;
    padding: 18px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
  }
  .ods-panel-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: #0b5777;
  }
  .ods-info-item {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 10px 12px;
    height: 100%;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
  }
  .ods-info-label {
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    color: #64748b;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
  }
  .ods-info-value {
    font-size: 0.92rem;
    font-weight: 700;
    color: #0f172a;
    word-break: break-word;
  }
  .matricule-badge {
    background: #181d24;
    color: #facc15;
    font-family: 'Courier New', monospace, sans-serif;
    font-weight: 800;
    letter-spacing: 1.5px;
    padding: 3px 9px;
    border-radius: 4px;
    border: 1px solid #0f172a;
    display: inline-block;
  }

  /* 2. بطاقات اختيار أنواع الـ PV */
  .pv-type-option {
    position: relative;
    cursor: pointer;
    border-radius: 12px;
    border: 2px solid #e2e8f0;
    background: #ffffff;
    padding: 16px;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    align-items: flex-start;
    height: 100%;
    user-select: none;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
  }
  .pv-type-option:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 18px rgba(0, 0, 0, 0.08);
  }
  .pv-type-option input[type="radio"] {
    position: absolute;
    top: 16px;
    right: 16px;
    width: 20px;
    height: 20px;
    accent-color: #0b5777;
    cursor: pointer;
  }
  .pv-icon-box {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.45rem;
    flex-shrink: 0;
    margin-right: 14px;
    transition: all 0.2s ease;
  }
  .pv-title {
    font-size: 0.94rem;
    font-weight: 800;
    color: #1e293b;
    margin-bottom: 4px;
    padding-right: 28px;
  }
  .pv-desc {
    font-size: 0.8rem;
    color: #64748b;
    line-height: 1.35;
    margin: 0;
  }

  /* تخصيص الألوان لكل نوع محضر */
  /* أ) PV EXPERTISE AUTOMOBILE (أزرق أساسي) */
  .pv-card-auto .pv-icon-box {
    background: #e0f2fe;
    color: #0284c7;
  }
  .pv-card-auto:hover {
    border-color: #38bdf8;
  }
  .pv-card-auto:has(input:checked),
  .pv-card-auto.active {
    border-color: #0284c7 !important;
    background: #f0f9ff !important;
    box-shadow: 0 6px 16px rgba(2, 132, 199, 0.18) !important;
  }
  .pv-card-auto:has(input:checked) .pv-icon-box,
  .pv-card-auto.active .pv-icon-box {
    background: #0284c7;
    color: #ffffff;
  }

  /* ب) PV DE REFORME (أحمر ناري / إلغاء) */
  .pv-card-reforme .pv-icon-box {
    background: #fee2e2;
    color: #dc2626;
  }
  .pv-card-reforme:hover {
    border-color: #f87171;
  }
  .pv-card-reforme:has(input:checked),
  .pv-card-reforme.active {
    border-color: #dc2626 !important;
    background: #fef2f2 !important;
    box-shadow: 0 6px 16px rgba(220, 38, 38, 0.18) !important;
  }
  .pv-card-reforme:has(input:checked) .pv-icon-box,
  .pv-card-reforme.active .pv-icon-box {
    background: #dc2626;
    color: #ffffff;
  }

  /* ج) PV VOL TOTAL (برتقالي / كهرماني) */
  .pv-card-vol .pv-icon-box {
    background: #ffedd5;
    color: #ea580c;
  }
  .pv-card-vol:hover {
    border-color: #fb923c;
  }
  .pv-card-vol:has(input:checked),
  .pv-card-vol.active {
    border-color: #ea580c !important;
    background: #fff7ed !important;
    box-shadow: 0 6px 16px rgba(234, 88, 12, 0.18) !important;
  }
  .pv-card-vol:has(input:checked) .pv-icon-box,
  .pv-card-vol.active .pv-icon-box {
    background: #ea580c;
    color: #ffffff;
  }

  /* د) PV INCENDIE VEHICULE (قرمزي / حريق) */
  .pv-card-incendie .pv-icon-box {
    background: #ffe4e6;
    color: #e11d48;
  }
  .pv-card-incendie:hover {
    border-color: #fb7185;
  }
  .pv-card-incendie:has(input:checked),
  .pv-card-incendie.active {
    border-color: #e11d48 !important;
    background: #fff1f2 !important;
    box-shadow: 0 6px 16px rgba(225, 29, 72, 0.18) !important;
  }
  .pv-card-incendie:has(input:checked) .pv-icon-box,
  .pv-card-incendie.active .pv-icon-box {
    background: #e11d48;
    color: #ffffff;
  }

  /* هـ) PV R.A.S (أخضر زمردي / لا توجد أضرار) */
  .pv-card-ras .pv-icon-box {
    background: #dcfce7;
    color: #16a34a;
  }
  .pv-card-ras:hover {
    border-color: #4ade80;
  }
  .pv-card-ras:has(input:checked),
  .pv-card-ras.active {
    border-color: #16a34a !important;
    background: #f0fdf4 !important;
    box-shadow: 0 6px 16px rgba(22, 163, 74, 0.18) !important;
  }
  .pv-card-ras:has(input:checked) .pv-icon-box,
  .pv-card-ras.active .pv-icon-box {
    background: #16a34a;
    color: #ffffff;
  }

  /* و) PV CARENCE (بنفسجي / غياب المعاينة) */
  .pv-card-carence .pv-icon-box {
    background: #ede9fe;
    color: #7c3aed;
  }
  .pv-card-carence:hover {
    border-color: #a78bfa;
  }
  .pv-card-carence:has(input:checked),
  .pv-card-carence.active {
    border-color: #7c3aed !important;
    background: #f5f3ff !important;
    box-shadow: 0 6px 16px rgba(124, 58, 237, 0.18) !important;
  }
  .pv-card-carence:has(input:checked) .pv-icon-box,
  .pv-card-carence.active .pv-icon-box {
    background: #7c3aed;
    color: #ffffff;
  }
</style>

        <!-- 1. اختيار وتفاصيل أمر المهمة ODS (Slides 7 & 10) -->
        <div class="col-12">
          <div class="card shadow-sm border-0 mb-4">
            <div class="card-header card-header-styled card-header-accent-blue d-flex justify-content-between align-items-center">
              <div class="d-flex align-items-center">
                <div class="p-2 bg-primary-subtle text-primary rounded-3 me-3">
                  <i class="bi bi-file-earmark-text-fill fs-4"></i>
                </div>
                <div>
                  <h5 class="card-title m-0 p-0 fs-6 fw-bold text-dark">1. Ordre de Service (ODS) Associé</h5>
                  <small class="text-muted">Sélectionnez le dossier à expertiser et vérifiez les données du sinistre et du véhicule</small>
                </div>
              </div>
              <?php if ($selected_ods): ?>
                <span class="badge bg-primary px-3 py-2 fs-6 shadow-sm">
                  <i class="bi bi-patch-check-fill me-1"></i> ODS N°: <?= htmlspecialchars($selected_ods['numero_ods']) ?>
                </span>
              <?php endif; ?>
            </div>
            <div class="card-body pt-3">
              <div class="row g-3 align-items-center">
                <div class="col-md-7">
                  <label for="ods_select" class="form-label fw-bold text-dark">
                    <i class="bi bi-folder2-open text-primary me-1"></i> Sélectionner l'ODS à traiter <span class="text-danger">*</span>
                  </label>
                  <select class="form-select form-select-lg fs-6 fw-semibold border-primary-subtle shadow-sm" id="ods_select" name="ods_id" required onchange="window.location.href='pv_create.php?ods_id=' + this.value">
                    <option value="">-- Choisir un ODS --</option>
                    <?php foreach ($liste_ods as $item): ?>
                      <option value="<?= $item['id'] ?>" <?= ($item['id'] == $selected_ods_id) ? 'selected' : '' ?>>
                        N° ODS: <?= htmlspecialchars($item['numero_ods']) ?> | Dossier: <?= htmlspecialchars($item['numero_dossier']) ?> | <?= htmlspecialchars($item['assure']) ?> (<?= htmlspecialchars($item['marque'] . ' ' . $item['modele']) ?>)
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-5">
                  <div class="p-3 bg-light rounded-3 border d-flex align-items-center justify-content-between mt-md-4">
                    <div>
                      <label class="form-check-label fw-bold text-dark d-block" for="est_additif">Expertise Additive (Additif)</label>
                      <small class="text-muted">Cocher cette case si ce PV est un complément</small>
                    </div>
                    <div class="form-check form-switch m-0">
                      <input class="form-check-input fs-4" type="checkbox" id="est_additif" name="est_additif" value="1">
                    </div>
                  </div>
                </div>
              </div>

              <!-- بطاقة تفاصيل الـ ODS المحددة (عالية الوضوح والتباين) -->
              <?php if ($selected_ods): ?>
                <div class="ods-details-panel mt-4">
                  <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom border-primary-subtle">
                    <div class="ods-panel-title d-flex align-items-center">
                      <i class="bi bi-shield-check text-primary fs-5 me-2"></i>
                      <span>Détails du Dossier & Caractéristiques du Véhicule (Lecture seule)</span>
                    </div>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 small">
                      <i class="bi bi-lock-fill me-1"></i> Données certifiées
                    </span>
                  </div>

                  <div class="row g-2">
                    <!-- N° Dossier -->
                    <div class="col-lg-3 col-md-6">
                      <div class="ods-info-item">
                        <div class="ods-info-label"><i class="bi bi-folder2 text-primary me-1"></i> N° Dossier Sinistre</div>
                        <div class="ods-info-value">
                          <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-bold fs-7"><?= htmlspecialchars($selected_ods['numero_dossier']) ?></span>
                        </div>
                      </div>
                    </div>

                    <!-- Assuré -->
                    <div class="col-lg-3 col-md-6">
                      <div class="ods-info-item">
                        <div class="ods-info-label"><i class="bi bi-person-fill text-success me-1"></i> Assuré / Tiers</div>
                        <div class="ods-info-value text-dark"><?= htmlspecialchars($selected_ods['assure']) ?></div>
                      </div>
                    </div>

                    <!-- Immatriculation (لوحة ترقيم واضحة) -->
                    <div class="col-lg-3 col-md-6">
                      <div class="ods-info-item">
                        <div class="ods-info-label"><i class="bi bi-card-text text-warning me-1"></i> Immatriculation</div>
                        <div class="ods-info-value">
                          <span class="matricule-badge"><?= htmlspecialchars($selected_ods['matricule']) ?></span>
                        </div>
                      </div>
                    </div>

                    <!-- Date Sinistre -->
                    <div class="col-lg-3 col-md-6">
                      <div class="ods-info-item">
                        <div class="ods-info-label"><i class="bi bi-calendar-event text-danger me-1"></i> Date Sinistre</div>
                        <div class="ods-info-value text-dark"><?= htmlspecialchars($selected_ods['date_sinistre'] ?? '-') ?></div>
                      </div>
                    </div>

                    <!-- Marque & Modèle -->
                    <div class="col-lg-3 col-md-6">
                      <div class="ods-info-item">
                        <div class="ods-info-label"><i class="bi bi-car-front text-primary me-1"></i> Marque & Modèle</div>
                        <div class="ods-info-value text-primary fw-bold">
                          <i class="bi bi-tag-fill me-1 small"></i><?= htmlspecialchars($selected_ods['marque'] . ' ' . $selected_ods['modele']) ?>
                        </div>
                      </div>
                    </div>

                    <!-- Police d'assurance -->
                    <div class="col-lg-3 col-md-6">
                      <div class="ods-info-item">
                        <div class="ods-info-label"><i class="bi bi-file-earmark-lock text-info me-1"></i> Police Assurance</div>
                        <div class="ods-info-value text-muted"><?= htmlspecialchars(!empty($selected_ods['police']) ? $selected_ods['police'] : 'Non renseignée') ?></div>
                      </div>
                    </div>

                    <!-- N° Série / Châssis -->
                    <div class="col-lg-3 col-md-6">
                      <div class="ods-info-item">
                        <div class="ods-info-label"><i class="bi bi-upc-scan text-secondary me-1"></i> N° Châssis (VIN)</div>
                        <div class="ods-info-value">
                          <code class="text-dark bg-light px-2 py-1 rounded small border"><?= htmlspecialchars(!empty($selected_ods['numero_serie']) ? $selected_ods['numero_serie'] : '-') ?></code>
                        </div>
                      </div>
                    </div>

                    <!-- Carburant & Puissance -->
                    <div class="col-lg-3 col-md-6">
                      <div class="ods-info-item">
                        <div class="ods-info-label"><i class="bi bi-fuel-pump text-success me-1"></i> Carburant / Puissance</div>
                        <div class="ods-info-value">
                          <span class="badge bg-secondary-subtle text-secondary-emphasis border">
                            <?= htmlspecialchars(($selected_ods['carburant'] ?? '-') . ' / ' . ($selected_ods['puissance'] ?? '-')) ?>
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endif; ?>

            </div>
          </div>
        </div>

        <!-- 2. نوع محضر الخبرة (Slide 9: بطاقات ملونة وواضحة جداً) -->
        <div class="col-12">
          <div class="card shadow-sm border-0 mb-4">
            <div class="card-header card-header-styled card-header-accent-blue d-flex justify-content-between align-items-center">
              <div class="d-flex align-items-center">
                <div class="p-2 bg-info-subtle text-info-emphasis rounded-3 me-3">
                  <i class="bi bi-ui-checks-grid fs-4"></i>
                </div>
                <div>
                  <h5 class="card-title m-0 p-0 fs-6 fw-bold text-dark">2. Type de Procès-Verbal (PV)</h5>
                  <small class="text-muted">Sélectionnez le type d'expertise approprié pour ce dossier</small>
                </div>
              </div>
              <span class="badge bg-light text-secondary border px-3 py-1">6 Catégories</span>
            </div>
            <div class="card-body pt-3">
              <div class="row g-3">

                <!-- أ) PV EXPERTISE AUTOMOBILE -->
                <div class="col-lg-4 col-md-6">
                  <label class="pv-type-option pv-card-auto" for="pv_auto">
                    <div class="pv-icon-box">
                      <i class="bi bi-car-front-fill"></i>
                    </div>
                    <div class="flex-grow-1">
                      <div class="pv-title">PV EXPERTISE AUTO</div>
                      <p class="pv-desc">Évaluation standard des dommages et chiffrage des réparations</p>
                    </div>
                    <input type="radio" name="type_pv" id="pv_auto" value="PV EXPERTISE AUTOMOBILE" checked>
                  </label>
                </div>

                <!-- ب) PV DE REFORME -->
                <div class="col-lg-4 col-md-6">
                  <label class="pv-type-option pv-card-reforme" for="pv_reforme">
                    <div class="pv-icon-box">
                      <i class="bi bi-exclamation-octagon-fill"></i>
                    </div>
                    <div class="flex-grow-1">
                      <div class="pv-title">PV DE REFORME</div>
                      <p class="pv-desc">Véhicule économiquement ou techniquement irréparable</p>
                    </div>
                    <input type="radio" name="type_pv" id="pv_reforme" value="PV DE REFORME">
                  </label>
                </div>

                <!-- ج) PV VOL TOTAL -->
                <div class="col-lg-4 col-md-6">
                  <label class="pv-type-option pv-card-vol" for="pv_vol">
                    <div class="pv-icon-box">
                      <i class="bi bi-shield-x"></i>
                    </div>
                    <div class="flex-grow-1">
                      <div class="pv-title">PV VOL TOTAL</div>
                      <p class="pv-desc">Constat de disparition totale ou vol non retrouvé</p>
                    </div>
                    <input type="radio" name="type_pv" id="pv_vol" value="PV VOL TOTAL">
                  </label>
                </div>

                <!-- د) PV INCENDIE VEHICULE -->
                <div class="col-lg-4 col-md-6">
                  <label class="pv-type-option pv-card-incendie" for="pv_incendie">
                    <div class="pv-icon-box">
                      <i class="bi bi-fire"></i>
                    </div>
                    <div class="flex-grow-1">
                      <div class="pv-title">PV INCENDIE</div>
                      <p class="pv-desc">Sinistre causé par embrasement, court-circuit ou incendie</p>
                    </div>
                    <input type="radio" name="type_pv" id="pv_incendie" value="PV INCENDIE VEHICULE">
                  </label>
                </div>

                <!-- هـ) PV R.A.S -->
                <div class="col-lg-4 col-md-6">
                  <label class="pv-type-option pv-card-ras" for="pv_ras">
                    <div class="pv-icon-box">
                      <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <div class="flex-grow-1">
                      <div class="pv-title">PV R.A.S</div>
                      <p class="pv-desc">Rien À Signaler : aucune anomalie ou dommage constaté</p>
                    </div>
                    <input type="radio" name="type_pv" id="pv_ras" value="PV R.A.S">
                  </label>
                </div>

                <!-- و) PV CARENCE -->
                <div class="col-lg-4 col-md-6">
                  <label class="pv-type-option pv-card-carence" for="pv_carence">
                    <div class="pv-icon-box">
                      <i class="bi bi-person-x-fill"></i>
                    </div>
                    <div class="flex-grow-1">
                      <div class="pv-title">PV CARENCE</div>
                      <p class="pv-desc">Non-présentation du véhicule après convocation légale</p>
                    </div>
                    <input type="radio" name="type_pv" id="pv_carence" value="PV CARENCE">
                  </label>
                </div>

              </div>
            </div>
          </div>
        </div>

        <!-- 3. معلومات الخبرة التقنية (Slide 11) -->
        <div class="col-lg-7">
          <div class="card shadow-sm border-0 mb-4 h-100">
            <div class="card-header card-header-styled card-header-accent-blue d-flex align-items-center">
              <div class="p-2 bg-success-subtle text-success rounded-3 me-3">
                <i class="bi bi-card-checklist fs-4"></i>
              </div>
              <div>
                <h5 class="card-title m-0 p-0 fs-6 fw-bold text-dark">3. Informations de l'Expertise</h5>
                <small class="text-muted">Constats techniques, date, lieu et responsabilité</small>
              </div>
            </div>
            <div class="card-body pt-3">
              <div class="row g-3">

                <div class="col-md-6">
                  <label for="date_expertise" class="form-label fw-bold small text-dark">Date d'Expertise <span class="text-danger">*</span></label>
                  <input type="date" class="form-control" id="date_expertise" name="date_expertise" value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="col-md-6">
                  <label for="heure_expertise" class="form-label fw-bold small text-dark">Heure d'Expertise</label>
                  <input type="time" class="form-control" id="heure_expertise" name="heure_expertise" value="<?= date('H:i') ?>">
                </div>

                <div class="col-md-6">
                  <label for="lieu_expertise" class="form-label fw-bold small text-dark">Lieu d'Expertise</label>
                  <input type="text" class="form-control" id="lieu_expertise" name="lieu_expertise" placeholder="Ex: Garage Central, Alger">
                </div>

                <div class="col-md-6">
                  <label for="couleur_vehicule" class="form-label fw-bold small text-dark">Couleur du Véhicule</label>
                  <input type="text" class="form-control" id="couleur_vehicule" name="couleur_vehicule" placeholder="Ex: Gris métallisé, Blanc...">
                </div>

                <div class="col-md-6">
                  <label for="taux_responsabilite" class="form-label fw-bold small text-dark">Taux de Responsabilité Estimé</label>
                  <select class="form-select" id="taux_responsabilite" name="taux_responsabilite">
                    <option value="0">0 % (Non responsable)</option>
                    <option value="25">25 %</option>
                    <option value="50">50 % (Partagé)</option>
                    <option value="75">75 %</option>
                    <option value="100">100 % (Totalement responsable)</option>
                  </select>
                </div>

                <div class="col-md-6">
                  <label for="valeur_venale" class="form-label fw-bold small text-dark">Valeur Vénale (DZD)</label>
                  <div class="input-group">
                    <input type="number" step="0.01" class="form-control" id="valeur_venale" name="valeur_venale" placeholder="0.00">
                    <span class="input-group-text bg-light fw-bold">DA</span>
                  </div>
                </div>

                <div class="col-12">
                  <label for="observation" class="form-label fw-bold small text-dark">Observations et Remarques</label>
                  <textarea class="form-control" id="observation" name="observation" rows="3" placeholder="Saisir les remarques détaillées de l'expert..."></textarea>
                </div>

              </div>
            </div>
          </div>
        </div>

        <!-- 4. المبالغ والتقديرات المالية (Slide 14) -->
        <div class="col-lg-5">
          <div class="card shadow-sm border-0 mb-4 h-100">
            <div class="card-header card-header-styled card-header-accent-blue d-flex align-items-center">
              <div class="p-2 bg-warning-subtle text-warning-emphasis rounded-3 me-3">
                <i class="bi bi-calculator fs-4"></i>
              </div>
              <div>
                <h5 class="card-title m-0 p-0 fs-6 fw-bold text-dark">4. Autres Montants & Chiffrage (HT)</h5>
                <small class="text-muted">Estimations financières des réparations</small>
              </div>
            </div>
            <div class="card-body pt-3">
              <div class="row g-3">

                <div class="col-12">
                  <label for="montant_mo_ht" class="form-label fw-bold small text-dark">Main d'œuvre (Montant HT)</label>
                  <div class="input-group">
                    <input type="number" step="0.01" class="form-control" id="montant_mo_ht" name="montant_mo_ht" value="0.00">
                    <span class="input-group-text bg-light fw-bold">DA</span>
                  </div>
                </div>

                <div class="col-12">
                  <label for="immobilisation_jours" class="form-label fw-bold small text-dark">Immobilisation (Jours)</label>
                  <div class="input-group">
                    <input type="number" class="form-control" id="immobilisation_jours" name="immobilisation_jours" value="0">
                    <span class="input-group-text bg-light fw-bold">Jours</span>
                  </div>
                </div>

                <div class="col-12">
                  <label for="montant_peinture" class="form-label fw-bold small text-dark">Peinture (Montant HT)</label>
                  <div class="input-group">
                    <input type="number" step="0.01" class="form-control" id="montant_peinture" name="montant_peinture" value="0.00">
                    <span class="input-group-text bg-light fw-bold">DA</span>
                  </div>
                </div>

                <div class="col-12">
                  <label for="tva_fourniture" class="form-label fw-bold small text-dark">TVA Fourniture</label>
                  <div class="input-group">
                    <input type="number" step="0.01" class="form-control" id="tva_fourniture" name="tva_fourniture" value="0.00">
                    <span class="input-group-text bg-light fw-bold">DA</span>
                  </div>
                </div>

                <div class="col-12">
                  <label for="taux_vetuste" class="form-label fw-bold small text-dark">Vétusté (%)</label>
                  <div class="input-group">
                    <input type="number" step="0.1" class="form-control" id="taux_vetuste" name="taux_vetuste" value="0">
                    <span class="input-group-text bg-light fw-bold">%</span>
                  </div>
                </div>

              </div>
            </div>
          </div>
        </div>

        <!-- 5. أزرار التحكم والحفظ (Slide 14 & 15) -->
        <div class="col-12 text-end mt-2 mb-4">
          <a href="index.php" class="btn btn-outline-secondary px-4 me-2">
            <i class="bi bi-x-circle me-1"></i> Annuler
          </a>
          <button type="submit" class="btn btn-primary px-5 py-2 fw-bold shadow-sm">
            <i class="bi bi-check2-circle me-1"></i> Sauvegarder l'expertise
          </button>
        </div>

      </div>

    </form>

  </section>

</main><!-- End #main -->

<script>
  // تفعيل التحديد اللوني والتفاعلي لبطاقات أنواع المحاضر عند النقر
  document.addEventListener('DOMContentLoaded', function () {
    const radioInputs = document.querySelectorAll('input[name="type_pv"]');
    
    function updateSelectedCard() {
      document.querySelectorAll('.pv-type-option').forEach(function (card) {
        card.classList.remove('active');
      });
      const checkedRadio = document.querySelector('input[name="type_pv"]:checked');
      if (checkedRadio) {
        const parentCard = checkedRadio.closest('.pv-type-option');
        if (parentCard) {
          parentCard.classList.add('active');
        }
      }
    }

    radioInputs.forEach(function (radio) {
      radio.addEventListener('change', updateSelectedCard);
    });

    updateSelectedCard();
  });
</script>

<?php
// تضمين الفوتر وروابط الجافاسكريبت
require_once __DIR__ . '/includes/footer.php';
?>
