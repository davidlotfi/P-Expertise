<?php
/**
 * includes/header.php
 * الشريط العلوي ورأس الصفحة (Header & Navbar)
 * متوافق ومتكامل مع قالب Tamplate-frontend ونظام E-Expertise
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// تحديد المسار النسبي لمجلد أصول القالب (Tamplate-frontend/assets)
if (!isset($assets_path)) {
    $assets_path = 'Tamplate-frontend/assets/';
}

// عنوان الصفحة الافتراضي إن لم يُحدد
$page_title = $page_title ?? 'E-EXPERTISE | Alliance Assurances';

// في حال وجود اتصال بقاعدة البيانات ولم تُهيأ الجلسة، جلب الخبير المسؤول تلقائياً
if (empty($_SESSION['user_id']) && isset($pdo)) {
    try {
        $stmt_auto = $pdo->query("SELECT * FROM utilisateurs ORDER BY id ASC LIMIT 1");
        $expert_auto = $stmt_auto->fetch();
        if ($expert_auto) {
            $_SESSION['user_id']    = $expert_auto['id'];
            $_SESSION['user_name']  = $expert_auto['prenom'] . ' ' . $expert_auto['nom'];
            $_SESSION['user_role']  = $expert_auto['role'];
            $_SESSION['user_email'] = $expert_auto['email'];
            $_SESSION['username']   = $expert_auto['username'];
        }
    } catch (Exception $e) {
        // تجاوز الخطأ في حال لم يتم تهيئة القاعدة بعد
    }
}

// استرجاع معلومات المستخدم الحالي أو قيم افتراضية متوافقة مع الخبير
$user_name = $_SESSION['user_name'] ?? 'Karim YAHYAOUI';
$user_role = $_SESSION['user_role'] ?? 'expert';
$user_email = $_SESSION['user_email'] ?? 'yahyaoui@allianceassurances.com.dz';
$role_badge = 'Expert Automobile';
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title><?= htmlspecialchars($page_title) ?></title>
  <meta content="Portail E-Expertise pour la gestion des sinistres et expertises automobiles - Alliance Assurances" name="description">
  <meta content="assurance, expertise, automobile, ods, sinistre, algerie" name="keywords">

  <!-- Favicons -->
  <link href="<?= $assets_path ?>img/favicon.png" rel="icon">
  <link href="<?= $assets_path ?>img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Google Fonts -->
  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

  <!-- Vendor CSS Files (من مجلد Tamplate-frontend) -->
  <link href="<?= $assets_path ?>vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= $assets_path ?>vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="<?= $assets_path ?>vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="<?= $assets_path ?>vendor/quill/quill.snow.css" rel="stylesheet">
  <link href="<?= $assets_path ?>vendor/quill/quill.bubble.css" rel="stylesheet">
  <link href="<?= $assets_path ?>vendor/remixicon/remixicon.css" rel="stylesheet">
  <link href="<?= $assets_path ?>vendor/simple-datatables/style.css" rel="stylesheet">

  <!-- Template Main CSS File -->
  <link href="<?= $assets_path ?>css/style.css" rel="stylesheet">

  <!-- لمسات تنسيق مخصصة لنظام E-Expertise وشركة Alliance Assurances -->
  <style>
    :root {
      --alliance-blue: #0b5777;
      --alliance-teal: #00828a;
      --alliance-light: #f4f8fa;
    }
    .header .logo span {
      color: #0b5777;
      font-weight: 800;
      letter-spacing: 0.5px;
    }
    .header .logo small {
      font-size: 11px;
      color: #00828a;
      display: block;
      line-height: 1;
      font-weight: 600;
    }
    .badge-ods-nouveau {
      background-color: #0dcaf0;
      color: #000;
    }
    .badge-ods-encours {
      background-color: #ffc107;
      color: #000;
    }
    .badge-ods-valide {
      background-color: #198754;
      color: #fff;
    }
    .badge-role {
      font-size: 0.72rem;
      padding: 3px 8px;
    }
    .nav-profile .user-avatar {
      width: 36px;
      height: 36px;
      background: linear-gradient(135deg, #0b5777, #00828a);
      color: white;
      font-weight: bold;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 14px;
    }
  </style>
</head>

<body>

  <!-- ======= Header / Navbar العلوي ======= -->
  <header id="header" class="header fixed-top d-flex align-items-center">

    <!-- الشعار وزر القائمة الجانبية -->
    <div class="d-flex align-items-center justify-content-between">
      <a href="index.php" class="logo d-flex align-items-center text-decoration-none">
        <i class="bi bi-shield-shaded fs-3 me-2 text-primary"></i>
        <div>
          <span class="d-none d-lg-block">E-EXPERTISE</span>
          <small class="d-none d-lg-block">Zahra Assurances</small>
        </div>
      </a>
      <i class="bi bi-list toggle-sidebar-btn" title="تبديل القائمة الجانبية"></i>
    </div><!-- End Logo -->

    <!-- شريط البحث السريع عن الملفات وأوامر المهمة -->
    <div class="search-bar">
      <form class="search-form d-flex align-items-center" method="GET" action="ods_list.php">
        <input type="text" name="search" placeholder="Recherche N° ODS, Dossier, Matricule..." title="Entrer le terme de recherche">
        <button type="submit" title="Rechercher"><i class="bi bi-search"></i></button>
      </form>
    </div><!-- End Search Bar -->

    <!-- القائمة العلوية وأدوات المستخدم -->
    <nav class="header-nav ms-auto">
      <ul class="d-flex align-items-center">

        <!-- أيقونة البحث في الشاشات الصغيرة -->
        <li class="nav-item d-block d-lg-none">
          <a class="nav-link nav-icon search-bar-toggle" href="#">
            <i class="bi bi-search"></i>
          </a>
        </li><!-- End Search Icon-->

        <!-- قائمة الإشعارات (كما ورد في Slide 3 من الدليل) -->
        <li class="nav-item dropdown">
          <a class="nav-link nav-icon" href="#" data-bs-toggle="dropdown" title="Notifications">
            <i class="bi bi-bell"></i>
            <span class="badge bg-primary badge-number">3</span>
          </a>

          <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow notifications">
            <li class="dropdown-header">
              Vous avez 3 nouvelles notifications
              <a href="notifications.php"><span class="badge rounded-pill bg-primary p-2 ms-2">Voir tout</span></a>
            </li>
            <li><hr class="dropdown-divider"></li>

            <li class="notification-item">
              <i class="bi bi-file-earmark-plus text-info"></i>
              <div>
                <h4>Nouvel ODS affecté</h4>
                <p>ODS N° 16001 20/0000 - Dossier 1195 0227</p>
                <p>Il y a 30 min</p>
              </div>
            </li>
            <li><hr class="dropdown-divider"></li>

            <li class="notification-item">
              <i class="bi bi-check-circle text-success"></i>
              <div>
                <h4>Validation Expertise</h4>
                <p>Rapport d'expertise validé avec code EXP119520000003</p>
                <p>Il y a 2 heures</p>
              </div>
            </li>
            <li><hr class="dropdown-divider"></li>

            <li class="notification-item">
              <i class="bi bi-cash-stack text-warning"></i>
              <div>
                <h4>Honoraires traités</h4>
                <p>Règlement des frais de dossier et déplacement</p>
                <p>Il y a 1 jour</p>
              </div>
            </li>
            <li><hr class="dropdown-divider"></li>

            <li class="dropdown-footer">
              <a href="notifications.php">Afficher toutes les notifications</a>
            </li>
          </ul>
        </li><!-- End Notification Nav -->

        <!-- القائمة المنسدلة لملف الخبير / المستخدم -->
        <li class="nav-item dropdown pe-3">

          <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
            <div class="user-avatar me-2">
              <?= strtoupper(substr($user_name, 0, 1)) ?>
            </div>
            <span class="d-none d-md-block dropdown-toggle ps-1"><?= htmlspecialchars($user_name) ?></span>
          </a>

          <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
            <li class="dropdown-header text-center">
              <h6><?= htmlspecialchars($user_name) ?></h6>
              <span class="badge bg-secondary badge-role"><?= htmlspecialchars($role_badge) ?></span>
              <div class="small text-muted mt-1"><?= htmlspecialchars($user_email) ?></div>
            </li>
            <li><hr class="dropdown-divider"></li>

            <li>
              <a class="dropdown-item d-flex align-items-center" href="profile.php">
                <i class="bi bi-person me-2 text-primary"></i>
                <span>Mon Profil & Mot de passe</span>
              </a>
            </li>
            <li><hr class="dropdown-divider"></li>

            <li>
              <a class="dropdown-item d-flex align-items-center" href="rapport_honoraires.php">
                <i class="bi bi-wallet2 me-2 text-success"></i>
                <span>Mes Honoraires</span>
              </a>
            </li>
            <li><hr class="dropdown-divider"></li>

            <li>
              <a class="dropdown-item d-flex align-items-center" href="logout.php">
                <i class="bi bi-box-arrow-right me-2 text-danger"></i>
                <span>Se déconnecter</span>
              </a>
            </li>

          </ul><!-- End Profile Dropdown Items -->
        </li><!-- End Profile Nav -->

      </ul>
    </nav><!-- End Icons Navigation -->

  </header><!-- End Header -->
