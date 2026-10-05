<?php
/**
 * actions/update_profile.php
 * ملف معالجة تحديث بيانات الملف الشخصي وتغيير كلمة المرور للخبير
 * مطابق لمتطلبات Slide 5 من دليل E-Expertise
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../profile.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

// التحقق من معرف المستخدم
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    // في حال عدم وجود جلسة نشطة، استهداف الخبير الوحيد المسجل في قاعدة البيانات
    $stmt_def = $pdo->query("SELECT id FROM utilisateurs ORDER BY id ASC LIMIT 1");
    $user_id = $stmt_def->fetchColumn() ?: 1;
}

$action = $_POST['action'] ?? 'update_info';

try {
    if ($action === 'update_info') {
        // ==========================================
        // 1. تحديث المعلومات العامة للخبير (Slide 5)
        // ==========================================
        $nom         = trim($_POST['nom'] ?? '');
        $prenom      = trim($_POST['prenom'] ?? '');
        $email       = trim($_POST['email'] ?? '');
        $username    = trim($_POST['username'] ?? '');
        $specialite  = trim($_POST['specialite'] ?? 'Automobile');
        $soumis_tva  = isset($_POST['soumis_tva']) ? 1 : 0;
        $telephone_1 = trim($_POST['telephone_1'] ?? '');
        $telephone_2 = trim($_POST['telephone_2'] ?? '');

        // التحقق من الحقول الإجبارية
        $errors = [];
        if (empty($nom))         $errors[] = "Le champ 'Nom' est obligatoire.";
        if (empty($prenom))      $errors[] = "Le champ 'Prénom' est obligatoire.";
        if (empty($email))       $errors[] = "L'adresse email est obligatoire.";
        if (empty($username))    $errors[] = "Le nom d'utilisateur est obligatoire.";
        if (empty($telephone_1)) $errors[] = "Le N° de téléphone 1 est obligatoire.";

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Format de l'adresse email invalide.";
        }

        // التأكد من عدم تكرار البريد أو اسم المستخدم لمستخدم آخر
        $stmt_check = $pdo->prepare("SELECT id FROM utilisateurs WHERE (email = :email OR username = :username) AND id != :id");
        $stmt_check->execute([
            ':email'    => $email,
            ':username' => $username,
            ':id'       => $user_id
        ]);
        if ($stmt_check->fetch()) {
            $errors[] = "L'adresse email ou le nom d'utilisateur est déjà utilisé par un autre compte.";
        }

        if (!empty($errors)) {
            $_SESSION['alert'] = [
                'type'    => 'danger',
                'title'   => 'Erreur de saisie !',
                'message' => implode('<br>', $errors),
                'tab'     => 'profile-edit'
            ];
            header('Location: ../profile.php?tab=edit');
            exit;
        }

        // تنفيذ التحديث في قاعدة البيانات
        $sql = "UPDATE utilisateurs SET 
                    nom = :nom,
                    prenom = :prenom,
                    email = :email,
                    username = :username,
                    specialite = :specialite,
                    soumis_tva = :soumis_tva,
                    telephone_1 = :telephone_1,
                    telephone_2 = :telephone_2,
                    date_maj = NOW()
                WHERE id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nom'         => $nom,
            ':prenom'      => $prenom,
            ':email'       => $email,
            ':username'    => $username,
            ':specialite'  => $specialite,
            ':soumis_tva'  => $soumis_tva,
            ':telephone_1' => $telephone_1,
            ':telephone_2' => $telephone_2,
            ':id'          => $user_id
        ]);

        // تحديث متغيرات الجلسة
        $_SESSION['user_name']  = $prenom . ' ' . $nom;
        $_SESSION['user_email'] = $email;
        $_SESSION['username']   = $username;

        $_SESSION['alert'] = [
            'type'    => 'success',
            'title'   => 'Profil mis à jour !',
            'message' => 'Vos informations personnelles ont été modifiées avec succès.',
            'tab'     => 'profile-edit'
        ];

        header('Location: ../profile.php?tab=edit');
        exit;

    } elseif ($action === 'change_password') {
        // ==========================================
        // 2. تغيير كلمة المرور (حفظ كنص عادي بدون تشفير)
        // ==========================================
        $current_password = $_POST['current_password'] ?? '';
        $new_password     = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        $errors = [];

        if (empty($new_password)) {
            $errors[] = "Le nouveau mot de passe est obligatoire.";
        } elseif (strlen($new_password) < 6) {
            $errors[] = "Le mot de passe doit comporter au moins 6 caractères.";
        }

        if ($new_password !== $confirm_password) {
            $errors[] = "La confirmation du mot de passe ne correspond pas.";
        }

        // جلب كلمة المرور الحالية المخزنة
        $stmt_user = $pdo->prepare("SELECT password, doit_changer_mot_de_passe FROM utilisateurs WHERE id = :id");
        $stmt_user->execute([':id' => $user_id]);
        $curr_user = $stmt_user->fetch();

        // التحقق من كلمة المرور الحالية (نص عادي، مع دعم التوافق مع التشفير السابق)
        if ($curr_user && !empty($current_password)) {
            $is_valid = ($current_password === $curr_user['password']) || password_verify($current_password, $curr_user['password']);
            if (!$is_valid) {
                $errors[] = "Le mot de passe actuel est incorrect.";
            }
        }

        if (!empty($errors)) {
            $_SESSION['alert'] = [
                'type'    => 'danger',
                'title'   => 'Erreur mot de passe !',
                'message' => implode('<br>', $errors),
                'tab'     => 'profile-change-password'
            ];
            header('Location: ../profile.php?tab=password');
            exit;
        }

        // حفظ كلمة المرور مباشرة كنص صريح دون تشفير بناءً على طلبك
        $stmt_pwd = $pdo->prepare("UPDATE utilisateurs SET password = :pwd, doit_changer_mot_de_passe = 0, date_maj = NOW() WHERE id = :id");
        $stmt_pwd->execute([
            ':pwd' => $new_password,
            ':id'  => $user_id
        ]);

        $_SESSION['alert'] = [
            'type'    => 'success',
            'title'   => 'Mot de passe modifié !',
            'message' => 'Votre mot de passe a été mis à jour avec succès. Vous pouvez désormais utiliser votre compte en toute sécurité.',
            'tab'     => 'profile-change-password'
        ];

        header('Location: ../profile.php?tab=password');
        exit;
    }

} catch (PDOException $e) {
    $_SESSION['alert'] = [
        'type'    => 'danger',
        'title'   => 'Erreur SQL !',
        'message' => "Une erreur s'est produite lors de l'enregistrement: " . htmlspecialchars($e->getMessage())
    ];
    header('Location: ../profile.php');
    exit;
}
