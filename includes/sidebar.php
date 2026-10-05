<?php
/**
 * includes/sidebar.php
 * القائمة الجانبية للتنقل (Sidebar)
 * مصممة وفقاً لهيكل وتدفق العمل المذكور في دليل E-Expertise (Slide 6 وما يتبعها)
 */

$current_page = basename($_SERVER['PHP_SELF']);
$user_role = $_SESSION['user_role'] ?? 'expert';

// تحديد إن كانت أي صفحة من صفحات قسم الخبرة نشطة لإبقاء القائمة الفرعية مفتوحة
$is_expertise_active = in_array($current_page, ['ods_list.php', 'ods_create.php', 'pv_list.php', 'pv_create.php', 'pv_details.php', 'chocs.php']);
?>

<!-- ======= القائمة الجانبية (Sidebar) ======= -->
<aside id="sidebar" class="sidebar">

  <ul class="sidebar-nav" id="sidebar-nav">

    <!-- 1. لوحة التحكم (Dashboard) -->
    <li class="nav-item">
      <a class="nav-link <?= ($current_page === 'index.php' || $current_page === 'dashboard.php') ? '' : 'collapsed' ?>" href="index.php">
        <i class="bi bi-grid"></i>
        <span>Dashboard</span>
      </a>
    </li><!-- End Dashboard Nav -->

    <li class="nav-heading">Processus d'Expertise</li>

    <!-- 2. قسم الخبرة والمعاينة (Expertise -> Liste des ODS) - كما في Slide 6 -->
    <li class="nav-item">
      <a class="nav-link <?= $is_expertise_active ? '' : 'collapsed' ?>" data-bs-target="#expertise-nav" data-bs-toggle="collapse" href="#">
        <i class="bi bi-clipboard2-check"></i>
        <span>Expertise</span>
        <i class="bi bi-chevron-down ms-auto"></i>
      </a>
      <ul id="expertise-nav" class="nav-content collapse <?= $is_expertise_active ? 'show' : '' ?>" data-bs-parent="#sidebar-nav">
        <li>
          <a href="ods_list.php" class="<?= ($current_page === 'ods_list.php') ? 'active' : '' ?>">
            <i class="bi bi-circle"></i>
            <span>+ Liste des ODS</span>
          </a>
        </li>
        <li>
          <a href="ods_create.php" class="<?= ($current_page === 'ods_create.php') ? 'active' : '' ?>">
            <i class="bi bi-circle"></i>
            <span>+ Nouveau ODS</span>
          </a>
        </li>
        <li>
          <a href="pv_list.php" class="<?= ($current_page === 'pv_list.php') ? 'active' : '' ?>">
            <i class="bi bi-circle"></i>
            <span>Traitements (PVs)</span>
          </a>
        </li>
        <li>
          <a href="pv_create.php" class="<?= ($current_page === 'pv_create.php') ? 'active' : '' ?>">
            <i class="bi bi-circle"></i>
            <span>Nouveau PV d'expertise</span>
          </a>
        </li>
      </ul>
    </li><!-- End Expertise Nav -->

    <!-- 3. كشف وتقرير الأتعاب (Rapport Honoraire) - كما في Slide 6 و 16 و 17 -->
    <li class="nav-item">
      <a class="nav-link <?= ($current_page === 'rapport_honoraires.php') ? '' : 'collapsed' ?>" href="rapport_honoraires.php">
        <i class="bi bi-cash-stack"></i>
        <span>Rapport Honoraire</span>
      </a>
    </li><!-- End Rapport Honoraire Nav -->

    <!-- 4. الحساب والملف الشخصي للخبير -->
    <li class="nav-heading">Compte & Configuration</li>

    <li class="nav-item">
      <a class="nav-link <?= ($current_page === 'profile.php') ? '' : 'collapsed' ?>" href="profile.php">
        <i class="bi bi-person-gear"></i>
        <span>Profil & Mot de passe</span>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link text-danger collapsed" href="logout.php">
        <i class="bi bi-box-arrow-right text-danger"></i>
        <span>Déconnexion</span>
      </a>
    </li>

  </ul>

</aside><!-- End Sidebar -->
