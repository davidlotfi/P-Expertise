<?php
/**
 * login.php
 * صفحة تسجيل الدخول لبوابة E-Expertise
 * مخصصة لخبير السيارات المسؤول الوحيد عن المنصة
 * التحقق من كلمة المرور يتم مباشرة بنص صريح بدون تشفير بناءً على طلب المستخدم
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// إن كان المستخدم مسجل دخوله بالفعل، نقوم بتوجيهه للرئيسية
if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/config/db.php';

$error = '';
$username_val = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $username_val = $username;

    if (empty($username) || empty($password)) {
        $error = 'Veuillez saisir votre nom d\'utilisateur et votre mot de passe.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE username = :u OR email = :u LIMIT 1");
            $stmt->execute([':u' => $username]);
            $user = $stmt->fetch();

            // فحص كلمة المرور بدون تشفير (مقارنة مباشرة كنص صريح)
            if ($user && ($user['password'] === $password || password_verify($password, $user['password']))) {
                // تسجيل بيانات الجلسة للخبير
                $_SESSION['user_id']    = $user['id'];
                $_SESSION['user_name']  = $user['prenom'] . ' ' . $user['nom'];
                $_SESSION['user_role']  = $user['role'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['username']   = $user['username'];

                header('Location: index.php');
                exit;
            } else {
                $error = 'Identifiant ou mot de passe incorrect.';
            }
        } catch (Exception $e) {
            $error = 'Erreur lors de la connexion: ' . htmlspecialchars($e->getMessage());
        }
    }
}

$assets_path = 'Tamplate-frontend/assets/';
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>Connexion Expert | E-EXPERTISE</title>
  <meta content="Portail E-Expertise pour la gestion des sinistres et expertises automobiles" name="description">

  <!-- Favicons -->
  <link href="<?= $assets_path ?>img/favicon.png" rel="icon">
  <link href="<?= $assets_path ?>img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Google Fonts -->
  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="<?= $assets_path ?>vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= $assets_path ?>vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="<?= $assets_path ?>vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="<?= $assets_path ?>vendor/remixicon/remixicon.css" rel="stylesheet">

  <!-- Template Main CSS File -->
  <link href="<?= $assets_path ?>css/style.css" rel="stylesheet">

  <style>
    :root {
      --alliance-blue: #0b5777;
      --alliance-teal: #00828a;
    }
    body {
      background-color: #f6f9ff;
    }
    .card {
      border: none;
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }
    .logo span {
      color: #0b5777;
      font-weight: 800;
      letter-spacing: 0.5px;
    }
    .logo small {
      font-size: 11px;
      color: #00828a;
      display: block;
      line-height: 1;
      font-weight: 600;
    }
    .btn-primary {
      background-color: var(--alliance-blue);
      border-color: var(--alliance-blue);
    }
    .btn-primary:hover {
      background-color: #08435d;
      border-color: #08435d;
    }
  </style>
</head>

<body>

  <main>
    <div class="container">

      <section class="section register min-vh-100 d-flex flex-column align-items-center justify-content-center py-4">
        <div class="container">
          <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7 d-flex flex-column align-items-center justify-content-center">

              <!-- الشعار -->
              <div class="d-flex justify-content-center py-4">
                <a href="index.php" class="logo d-flex align-items-center w-auto text-decoration-none">
                  <i class="bi bi-shield-shaded fs-2 me-2 text-primary"></i>
                  <div>
                    <span class="d-block fs-3">E-EXPERTISE</span>
                    <small>Alliance Assurances</small>
                  </div>
                </a>
              </div><!-- End Logo -->

              <div class="card mb-3 w-100">

                <div class="card-body p-4">

                  <div class="pt-2 pb-3 text-center">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 mb-2">
                      <i class="bi bi-person-badge me-1"></i> Espace Expert Automobile
                    </span>
                    <h5 class="card-title text-center pb-0 fs-4 fw-bold text-dark">Connexion au Portail</h5>
                    <p class="text-center small text-muted">Accédez à vos ordres de service (ODS) et rapports d'expertise</p>
                  </div>

                  <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show small py-2" role="alert">
                      <i class="bi bi-exclamation-triangle-fill me-1"></i>
                      <?= htmlspecialchars($error) ?>
                      <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                  <?php endif; ?>

                  <form class="row g-3 needs-validation" method="POST" action="login.php" novalidate>

                    <div class="col-12">
                      <label for="yourUsername" class="form-label fw-bold small">Nom d'utilisateur ou Email</label>
                      <div class="input-group has-validation">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" name="username" class="form-control" id="yourUsername" value="<?= htmlspecialchars($username_val) ?>" placeholder="expert_yahyaoui" required autofocus>
                        <div class="invalid-feedback">Veuillez entrer votre identifiant.</div>
                      </div>
                    </div>

                    <div class="col-12">
                      <label for="yourPassword" class="form-label fw-bold small">Mot de passe</label>
                      <div class="input-group has-validation">
                        <span class="input-group-text"><i class="bi bi-key"></i></span>
                        <input type="password" name="password" class="form-control" id="yourPassword" placeholder="••••••••" required>
                        <div class="invalid-feedback">Veuillez entrer votre mot de passe !</div>
                      </div>
                    </div>

                    <div class="col-12 pt-2">
                      <button class="btn btn-primary w-100 fw-bold py-2 shadow-sm" type="submit">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Se connecter
                      </button>
                    </div>

                  </form>

                  <!-- تلميح بيانات الدخول التجريبية -->
                  <div class="mt-4 p-3 bg-light rounded text-center small border">
                    <div class="fw-bold text-secondary mb-1"><i class="bi bi-info-circle me-1"></i> Accès Démonstration Expert :</div>
                    <div class="text-muted">
                      Utilisateur : <code class="text-primary fw-bold">expert_yahyaoui</code> | Mot de passe : <code class="text-danger fw-bold">password123</code>
                    </div>
                  </div>

                </div>
              </div>

              <div class="text-center small text-muted">
                &copy; <?= date('Y') ?> <strong>E-EXPERTISE</strong> - Alliance Assurances
              </div>

            </div>
          </div>
        </div>

      </section>

    </div>
  </main><!-- End #main -->

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Vendor JS Files -->
  <script src="<?= $assets_path ?>vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

</body>

</html>
