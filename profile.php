<?php
/**
 * profile.php
 * صفحة الملف الشخصي للخبير (Profil Utilisateur & Paramètres)
 * مطابقة تماماً لـ Slide 5 من دليل E-Expertise وقالب Bootstrap
 */

require_once __DIR__ . '/config/db.php';

$page_title = "Profil Utilisateur | E-EXPERTISE";

// جلب المستخدم الحالي
$user_id = $_SESSION['user_id'] ?? null;
$user = null;

try {
    if (isset($pdo)) {
        if ($user_id) {
            $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = :id");
            $stmt->execute([':id' => $user_id]);
            $user = $stmt->fetch();
        }

        // في حال عدم وجود جلسة، جلب الخبير ومزامنة الجلسة
        if (!$user) {
            $stmt = $pdo->query("SELECT * FROM utilisateurs ORDER BY id ASC LIMIT 1");
            $user = $stmt->fetch();
            if ($user) {
                $_SESSION['user_id']    = $user['id'];
                $_SESSION['user_name']  = $user['prenom'] . ' ' . $user['nom'];
                $_SESSION['user_role']  = $user['role'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['username']   = $user['username'];
                $user_id = $user['id'];
            }
        }

        // جلب الإحصائيات الخاصة بالخبير
        if ($user) {
            // عدد الـ ODS المسندة
            $stmt_ods = $pdo->prepare("SELECT COUNT(*) FROM ods WHERE expert_id = :id");
            $stmt_ods->execute([':id' => $user['id']]);
            $nb_ods = $stmt_ods->fetchColumn();

            // عدد محاضر الخبرة المنجزة
            $stmt_pv = $pdo->prepare("SELECT COUNT(*) FROM pv_expertises WHERE expert_id = :id");
            $stmt_pv->execute([':id' => $user['id']]);
            $nb_pvs = $stmt_pv->fetchColumn();

            // إجمالي الأتعاب المحصلة
            $stmt_hon = $pdo->prepare("
                SELECT COALESCE(SUM(ph.montant_total), 0) 
                FROM pv_honoraires ph
                JOIN pv_expertises pv ON ph.pv_id = pv.id
                WHERE pv.expert_id = :id
            ");
            $stmt_hon->execute([':id' => $user['id']]);
            $total_honoraires = $stmt_hon->fetchColumn();

            // آخر المهام المسندة للخبير
            $stmt_recent = $pdo->prepare("SELECT * FROM ods WHERE expert_id = :id ORDER BY id DESC LIMIT 5");
            $stmt_recent->execute([':id' => $user['id']]);
            $recent_ods = $stmt_recent->fetchAll();
        }
    }
} catch (Exception $e) {
    $db_error = $e->getMessage();
}

// التبويب النشط
$active_tab = $_GET['tab'] ?? ($_SESSION['alert']['tab'] ?? 'overview');
if ($active_tab === 'edit') $active_tab = 'profile-edit';
if ($active_tab === 'password') $active_tab = 'profile-change-password';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<main id="main" class="main">

  <div class="pagetitle">
    <h1>Profil Utilisateur</h1>
    <nav>
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Accueil</a></li>
        <li class="breadcrumb-item">Compte</li>
        <li class="breadcrumb-item active">Profil & Mot de passe</li>
      </ol>
    </nav>
  </div><!-- End Page Title -->

  <section class="section profile">

    <!-- رسائل التنبيه والنجاح -->
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

    <!-- تنبيه إلزامي عند أول دخول كما في Slide 5 -->
    <?php if ($user && !empty($user['doit_changer_mot_de_passe'])): ?>
      <div class="alert alert-warning border-0 border-start border-4 border-warning shadow-sm" role="alert">
        <div class="d-flex align-items-center">
          <i class="bi bi-shield-exclamation text-warning fs-3 me-3"></i>
          <div>
            <h6 class="alert-heading fw-bold mb-1">Changement de mot de passe obligatoire (Slide 5 du guide)</h6>
            <p class="mb-0 small text-muted">
              Lors de votre premier accès au portail E-Expertise, il est indispensable de personnaliser votre mot de passe avant toute autre action.
              <a href="profile.php?tab=password" class="fw-bold text-decoration-underline ms-1">Cliquez ici pour changer votre mot de passe</a>.
            </p>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <div class="row">

      <!-- العمود الأيسر: بطاقة هوية الخبير وإحصائياته -->
      <div class="col-xl-4">

        <div class="card shadow-sm border-0 mb-4">
          <div class="card-body profile-card pt-4 d-flex flex-column align-items-center text-center">

            <!-- الصورة الرمزية / Avatar -->
            <div class="rounded-circle d-flex align-items-center justify-content-center text-white mb-3 shadow-sm" style="width: 100px; height: 100px; background: linear-gradient(135deg, #0b5777, #00828a); font-size: 38px; font-weight: bold;">
              <?= strtoupper(substr($user['prenom'] ?? 'K', 0, 1) . substr($user['nom'] ?? 'Y', 0, 1)) ?>
            </div>

            <h4 class="fw-bold text-dark mb-1"><?= htmlspecialchars(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '')) ?></h4>
            <span class="badge bg-primary mb-2 px-3 py-1">EXPERT AUTOMOBILE</span>
            <div class="text-muted small mb-2"><i class="bi bi-tag me-1"></i> Spécialité: <?= htmlspecialchars($user['specialite'] ?? 'Automobile') ?></div>

            <div class="d-flex gap-2 mt-1">
              <?php if (!empty($user['soumis_tva'])): ?>
                <span class="badge bg-success-subtle text-success border border-success"><i class="bi bi-check-circle me-1"></i> Soumis à la TVA</span>
              <?php else: ?>
                <span class="badge bg-secondary-subtle text-secondary border"><i class="bi bi-dash-circle me-1"></i> Non Soumis à la TVA</span>
              <?php endif; ?>
            </div>

          </div>
        </div>

        <!-- بطاقة نشاط وأرقام الخبير -->
        <div class="card shadow-sm border-0 mb-4">
          <div class="card-header bg-white py-3 border-bottom">
            <h6 class="card-title m-0 p-0 fs-6 text-primary"><i class="bi bi-bar-chart-line me-2"></i> Activité & Statistiques</h6>
          </div>
          <div class="card-body pt-3">
            <ul class="list-group list-group-flush small">
              <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                <span><i class="bi bi-folder-check text-primary me-2"></i> ODS Affectés</span>
                <span class="badge bg-primary rounded-pill"><?= $nb_ods ?? 0 ?></span>
              </li>
              <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                <span><i class="bi bi-file-earmark-medical text-info me-2"></i> PVs d'Expertise Créés</span>
                <span class="badge bg-info rounded-pill"><?= $nb_pvs ?? 0 ?></span>
              </li>
              <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                <span><i class="bi bi-cash-coin text-success me-2"></i> Honoraires Cumulés</span>
                <span class="fw-bold text-success"><?= number_format($total_honoraires ?? 0, 2) ?> DA</span>
              </li>
            </ul>
          </div>
        </div>

      </div><!-- End Left Column -->

      <!-- العمود الأيمن: التبويبات ونماذج التعديل -->
      <div class="col-xl-8">

        <div class="card shadow-sm border-0">
          <div class="card-body pt-3">

            <!-- أزرار التبويبات (Bordered Tabs) -->
            <ul class="nav nav-tabs nav-tabs-bordered" role="tablist">

              <li class="nav-item" role="presentation">
                <button class="nav-link <?= ($active_tab === 'overview') ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#profile-overview">
                  <i class="bi bi-person me-1"></i> Aperçu
                </button>
              </li>

              <li class="nav-item" role="presentation">
                <button class="nav-link <?= ($active_tab === 'profile-edit') ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#profile-edit">
                  <i class="bi bi-pencil-square me-1"></i> Modifier Profil (Slide 5)
                </button>
              </li>

              <li class="nav-item" role="presentation">
                <button class="nav-link <?= ($active_tab === 'profile-change-password') ? 'active' : '' ?>" data-bs-toggle="tab" data-bs-target="#profile-change-password">
                  <i class="bi bi-key me-1"></i> Mot de Passe
                  <?php if (!empty($user['doit_changer_mot_de_passe'])): ?>
                    <span class="badge bg-danger ms-1">Requis</span>
                  <?php endif; ?>
                </button>
              </li>

              <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#profile-activity">
                  <i class="bi bi-clock-history me-1"></i> Historique ODS
                </button>
              </li>

            </ul>

            <div class="tab-content pt-4">

              <!-- ======================================================== -->
              <!-- 1. تبويب: نظرة عامة (Overview)                            -->
              <!-- ======================================================== -->
              <div class="tab-pane fade <?= ($active_tab === 'overview') ? 'show active' : '' ?> profile-overview" id="profile-overview">

                <h5 class="card-title fs-6 text-primary mb-3">Détails du Compte Expert</h5>

                <div class="row mb-2">
                  <div class="col-lg-3 col-md-4 label text-muted fw-bold">Nom Complet</div>
                  <div class="col-lg-9 col-md-8"><?= htmlspecialchars(($user['nom'] ?? '') . ' ' . ($user['prenom'] ?? '')) ?></div>
                </div>

                <div class="row mb-2">
                  <div class="col-lg-3 col-md-4 label text-muted fw-bold">Nom d'utilisateur</div>
                  <div class="col-lg-9 col-md-8"><code><?= htmlspecialchars($user['username'] ?? '') ?></code></div>
                </div>

                <div class="row mb-2">
                  <div class="col-lg-3 col-md-4 label text-muted fw-bold">Adresse Email</div>
                  <div class="col-lg-9 col-md-8"><?= htmlspecialchars($user['email'] ?? '') ?></div>
                </div>

                <div class="row mb-2">
                  <div class="col-lg-3 col-md-4 label text-muted fw-bold">Spécialité</div>
                  <div class="col-lg-9 col-md-8"><?= htmlspecialchars($user['specialite'] ?? 'Automobile') ?></div>
                </div>

                <div class="row mb-2">
                  <div class="col-lg-3 col-md-4 label text-muted fw-bold">Assujettissement TVA</div>
                  <div class="col-lg-9 col-md-8"><?= !empty($user['soumis_tva']) ? 'Oui (Soumis à la TVA)' : 'Non (Exonéré / Non soumis)' ?></div>
                </div>

                <div class="row mb-2">
                  <div class="col-lg-3 col-md-4 label text-muted fw-bold">N° Téléphone 1</div>
                  <div class="col-lg-9 col-md-8"><?= htmlspecialchars($user['telephone_1'] ?? '-') ?></div>
                </div>

                <div class="row mb-2">
                  <div class="col-lg-3 col-md-4 label text-muted fw-bold">N° Téléphone 2</div>
                  <div class="col-lg-9 col-md-8"><?= htmlspecialchars($user['telephone_2'] ?? 'Non renseigné') ?></div>
                </div>

                <div class="row mb-2">
                  <div class="col-lg-3 col-md-4 label text-muted fw-bold">Date de Création</div>
                  <div class="col-lg-9 col-md-8 text-muted"><?= htmlspecialchars($user['date_creation'] ?? '-') ?></div>
                </div>

                <div class="mt-4 pt-3 border-top">
                  <a href="profile.php?tab=edit" class="btn btn-primary btn-sm">
                    <i class="bi bi-pencil me-1"></i> Modifier mes informations
                  </a>
                  <a href="profile.php?tab=password" class="btn btn-outline-secondary btn-sm ms-2">
                    <i class="bi bi-shield-lock me-1"></i> Changer le mot de passe
                  </a>
                </div>

              </div>

              <!-- ======================================================== -->
              <!-- 2. تبويب: تعديل الملف الشخصي (Slide 5: Formulaire De modification) -->
              <!-- ======================================================== -->
              <div class="tab-pane fade <?= ($active_tab === 'profile-edit') ? 'show active' : '' ?> pt-2" id="profile-edit">

                <h5 class="card-title fs-6 text-primary mb-3">
                  <i class="bi bi-sliders me-1"></i> Formulaire De modification Utilisateur (Guide Slide 5)
                </h5>

                <form action="actions/update_profile.php" method="POST" class="needs-validation" novalidate>
                  <input type="hidden" name="action" value="update_info">

                  <div class="row g-3">

                    <div class="col-md-6">
                      <label for="nom" class="form-label fw-bold">Nom <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" id="nom" name="nom" value="<?= htmlspecialchars($user['nom'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-6">
                      <label for="prenom" class="form-label fw-bold">Prénom <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" id="prenom" name="prenom" value="<?= htmlspecialchars($user['prenom'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-6">
                      <label for="email" class="form-label fw-bold">Adresse Email <span class="text-danger">*</span></label>
                      <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-6">
                      <label for="username" class="form-label fw-bold">Nom d'utilisateur <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($user['username'] ?? '') ?>" required>
                    </div>

                    <!-- Spécialité (Slide 5: Automobile, Risque industriel, Transport) -->
                    <div class="col-12">
                      <label class="form-label fw-bold d-block">Spécialité :</label>
                      <div class="d-flex flex-wrap gap-4 pt-1">
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="specialite" id="spec_auto" value="Automobile" <?= ($user['specialite'] ?? 'Automobile') === 'Automobile' ? 'checked' : '' ?>>
                          <label class="form-check-label" for="spec_auto">Automobile</label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="specialite" id="spec_indus" value="Risque industriel" <?= ($user['specialite'] ?? '') === 'Risque industriel' ? 'checked' : '' ?>>
                          <label class="form-check-label" for="spec_indus">Risque industriel</label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="specialite" id="spec_trans" value="Transport" <?= ($user['specialite'] ?? '') === 'Transport' ? 'checked' : '' ?>>
                          <label class="form-check-label" for="spec_trans">Transport</label>
                        </div>
                      </div>
                    </div>

                    <!-- Soumis à la TVA (Slide 5: Case à cocher TVA) -->
                    <div class="col-12">
                      <div class="form-check form-switch p-3 border rounded bg-light">
                        <input class="form-check-input ms-0 me-2" type="checkbox" id="soumis_tva" name="soumis_tva" value="1" <?= !empty($user['soumis_tva']) ? 'checked' : '' ?>>
                        <label class="form-check-label fw-bold" for="soumis_tva">
                          Soumis à la TVA
                          <small class="d-block text-muted fw-normal">Cocher cette case si vous appliquez la taxe sur la valeur ajoutée sur vos honoraires</small>
                        </label>
                      </div>
                    </div>

                    <div class="col-md-6">
                      <label for="telephone_1" class="form-label fw-bold">N° Tel 1 <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" id="telephone_1" name="telephone_1" value="<?= htmlspecialchars($user['telephone_1'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-6">
                      <label for="telephone_2" class="form-label fw-bold">N° Tel 2</label>
                      <input type="text" class="form-control" id="telephone_2" name="telephone_2" value="<?= htmlspecialchars($user['telephone_2'] ?? '') ?>" placeholder="Optionnel">
                    </div>

                    <div class="col-12 text-end pt-3">
                      <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-check2-circle me-1"></i> Valider les modifications
                      </button>
                    </div>

                  </div>
                </form>

              </div>

              <!-- ======================================================== -->
              <!-- 3. تبويب: تغيير كلمة المرور (Slide 5: Informations de Connexion) -->
              <!-- ======================================================== -->
              <div class="tab-pane fade <?= ($active_tab === 'profile-change-password') ? 'show active' : '' ?> pt-2" id="profile-change-password">

                <h5 class="card-title fs-6 text-primary mb-2">
                  <i class="bi bi-key-fill me-1"></i> Informations de Connexion - Changer le mot de passe
                </h5>
                <p class="small text-muted mb-4">
                  Pour votre sécurité, choisissez un mot de passe d'au moins 6 caractères. Le mot de passe sera enregistré directement selon votre configuration.
                </p>

                <form action="actions/update_profile.php" method="POST" class="needs-validation" novalidate>
                  <input type="hidden" name="action" value="change_password">

                  <div class="row g-3">

                    <div class="col-md-12">
                      <label for="current_password" class="form-label fw-bold">Mot de passe actuel</label>
                      <input type="password" class="form-control" id="current_password" name="current_password" placeholder="Mot de passe temporaire ou actuel">
                    </div>

                    <div class="col-md-6">
                      <label for="new_password" class="form-label fw-bold">Nouveau mot de passe <span class="text-danger">*</span></label>
                      <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6" placeholder="Minimum 6 caractères">
                    </div>

                    <div class="col-md-6">
                      <label for="confirm_password" class="form-label fw-bold">Confirmation mot de passe <span class="text-danger">*</span></label>
                      <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6" placeholder="Retapez le mot de passe">
                    </div>

                    <div class="col-12 text-end pt-3">
                      <button type="submit" class="btn btn-success px-4 fw-bold">
                        <i class="bi bi-shield-check me-1"></i> Valider
                      </button>
                    </div>

                  </div>
                </form>

              </div>

              <!-- ======================================================== -->
              <!-- 4. تبويب: سجل الـ ODS المسندة للخبير                      -->
              <!-- ======================================================== -->
              <div class="tab-pane fade pt-2" id="profile-activity">

                <h5 class="card-title fs-6 text-primary mb-3">Derniers Ordres de Service (ODS) Traités</h5>

                <?php if (!empty($recent_ods)): ?>
                  <div class="table-responsive">
                    <table class="table table-hover align-middle small">
                      <thead class="table-light">
                        <tr>
                          <th>N° ODS</th>
                          <th>N° Dossier</th>
                          <th>Assuré</th>
                          <th>Véhicule</th>
                          <th>Statut</th>
                          <th>Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($recent_ods as $ro): ?>
                          <tr>
                            <td><strong><?= htmlspecialchars($ro['numero_ods']) ?></strong></td>
                            <td><?= htmlspecialchars($ro['numero_dossier']) ?></td>
                            <td><?= htmlspecialchars($ro['assure']) ?></td>
                            <td><?= htmlspecialchars($ro['marque'] . ' ' . $ro['modele']) ?></td>
                            <td>
                              <?php if ($ro['statut'] === 'Nouveau'): ?>
                                <span class="badge bg-info text-dark">Nouveau</span>
                              <?php elseif ($ro['statut'] === 'Validé'): ?>
                                <span class="badge bg-success">Validé</span>
                              <?php else: ?>
                                <span class="badge bg-warning text-dark"><?= htmlspecialchars($ro['statut']) ?></span>
                              <?php endif; ?>
                            </td>
                            <td>
                              <a href="pv_create.php?ods_id=<?= $ro['id'] ?>" class="btn btn-sm btn-outline-primary py-0 px-2">
                                <i class="bi bi-pencil-square"></i> PV
                              </a>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                <?php else: ?>
                  <div class="text-center py-4 text-muted">
                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                    Aucun ODS récent trouvé pour ce profil.
                  </div>
                <?php endif; ?>

              </div>

            </div><!-- End Tab Content -->

          </div>
        </div>

      </div><!-- End Right Column -->

    </div>

  </section>

</main><!-- End #main -->

<?php
require_once __DIR__ . '/includes/footer.php';
?>
