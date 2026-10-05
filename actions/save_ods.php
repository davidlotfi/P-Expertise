<?php
/**
 * actions/save_ods.php
 * ملف معالجة واستلام بيانات إضافة أمر مهمة جديد (Nouveau ODS)
 * يتيح لخبير السيارات إنشاء أمر مهمة جديد بنفسه وحفظه في قاعدة البيانات
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../ods_list.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

try {
    // 1. استلام وتطهير البيانات المدخلة
    $numero_ods     = trim($_POST['numero_ods'] ?? '');
    $numero_dossier = trim($_POST['numero_dossier'] ?? '');
    $date_ods       = trim($_POST['date_ods'] ?? date('Y-m-d'));
    $date_sinistre  = trim($_POST['date_sinistre'] ?? date('Y-m-d'));
    $police         = trim($_POST['police'] ?? '');
    $assure         = trim($_POST['assure'] ?? '');
    $telephone      = trim($_POST['telephone'] ?? '');
    $matricule      = trim($_POST['matricule'] ?? '');
    $marque         = trim($_POST['marque'] ?? '');
    $modele         = trim($_POST['modele'] ?? '');
    $numero_serie   = trim($_POST['numero_serie'] ?? '');
    $puissance      = trim($_POST['puissance'] ?? '');
    $carburant      = trim($_POST['carburant'] ?? '');
    $remarque       = trim($_POST['remarque'] ?? '');
    $redirect_to_pv = isset($_POST['action_and_pv']) ? true : false;

    // توليد رقم ODS تلقائي في حال تركه فارغاً: نمط 16001 YY/XXXX
    if (empty($numero_ods)) {
        $year_suffix = date('y');
        $random_seq = str_pad(rand(10, 9999), 4, '0', STR_PAD_LEFT);
        $numero_ods = "16001 {$year_suffix}/{$random_seq}";
    }

    // توليد رقم ملف تلقائي في حال تركه فارغاً: نمط 16001 YY 1195 XXXX
    if (empty($numero_dossier)) {
        $year_suffix = date('y');
        $random_seq = str_pad(rand(10, 9999), 4, '0', STR_PAD_LEFT);
        $numero_dossier = "16001 {$year_suffix} 1195 {$random_seq}";
    }

    // 2. التحقق من الحقول الإجبارية
    $errors = [];
    if (empty($assure)) {
        $errors[] = "Le nom et prénom de l'assuré sont obligatoires.";
    }
    if (empty($matricule)) {
        $errors[] = "Le numéro d'immatriculation du véhicule est obligatoire.";
    }
    if (empty($marque)) {
        $errors[] = "La marque du véhicule est obligatoire.";
    }
    if (empty($modele)) {
        $errors[] = "Le modèle du véhicule est obligatoire.";
    }
    if (empty($date_ods)) {
        $errors[] = "La date de l'ODS est obligatoire.";
    }
    if (empty($date_sinistre)) {
        $errors[] = "La date du sinistre est obligatoire.";
    }

    // التأكد من عدم تكرار رقم أمر المهمة (N° ODS Unique)
    $stmt_check = $pdo->prepare("SELECT id FROM ods WHERE numero_ods = :num LIMIT 1");
    $stmt_check->execute([':num' => $numero_ods]);
    if ($stmt_check->fetch()) {
        $errors[] = "Le numéro d'ODS <strong>" . htmlspecialchars($numero_ods) . "</strong> existe déjà. Veuillez en choisir un autre.";
    }

    if (!empty($errors)) {
        $_SESSION['alert'] = [
            'type'    => 'danger',
            'title'   => 'Erreur de saisie !',
            'message' => implode('<br>', $errors)
        ];
        // حفظ القيم المدخلة مؤقتاً في الجلسة لاستعادتها في النموذج
        $_SESSION['form_data_ods'] = $_POST;
        header('Location: ../ods_create.php');
        exit;
    }

    // 3. تحديد معرف الخبير (الخبير الحالي من الجلسة أو من قاعدة البيانات)
    $expert_id = $_SESSION['user_id'] ?? null;
    if (!$expert_id) {
        $stmt_exp = $pdo->query("SELECT id FROM utilisateurs ORDER BY id ASC LIMIT 1");
        $expert_id = $stmt_exp->fetchColumn() ?: 1;
    }

    // 4. إدراج أمر المهمة الجديد في جدول ods
    $sql = "INSERT INTO ods (
                numero_ods, numero_dossier, date_sinistre, date_ods, 
                assure, police, matricule, numero_serie, marque, 
                modele, puissance, carburant, telephone, remarque, 
                expert_id, statut, date_chargement
            ) VALUES (
                :numero_ods, :numero_dossier, :date_sinistre, :date_ods,
                :assure, :police, :matricule, :numero_serie, :marque,
                :modele, :puissance, :carburant, :telephone, :remarque,
                :expert_id, 'Nouveau', NOW()
            )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':numero_ods'     => $numero_ods,
        ':numero_dossier' => $numero_dossier,
        ':date_sinistre'  => $date_sinistre,
        ':date_ods'       => $date_ods,
        ':assure'         => $assure,
        ':police'         => $police,
        ':matricule'      => $matricule,
        ':numero_serie'   => $numero_serie,
        ':marque'         => $marque,
        ':modele'         => $modele,
        ':puissance'      => $puissance,
        ':carburant'      => $carburant,
        ':telephone'      => $telephone,
        ':remarque'       => $remarque,
        ':expert_id'      => $expert_id
    ]);

    $ods_id = $pdo->lastInsertId();

    // 5. تسجيل إشعار في النظام
    try {
        $notif = $pdo->prepare("INSERT INTO notifications (destinataire_id, titre, message, date_creation) VALUES (:uid, :titre, :msg, NOW())");
        $notif->execute([
            ':uid'   => $expert_id,
            ':titre' => "Nouvel ODS créé : {$numero_ods}",
            ':msg'   => "L'ordre de service pour le véhicule {$marque} {$modele} (Immat: {$matricule}) a été créé avec succès."
        ]);
    } catch (Exception $ex) {
        // تجاهل أي خطأ ثانوي في الإشعارات
    }

    // مسح البيانات المؤقتة
    unset($_SESSION['form_data_ods']);

    $_SESSION['alert'] = [
        'type'    => 'success',
        'title'   => 'ODS créé avec succès !',
        'message' => "L'ordre de service <strong>{$numero_ods}</strong> pour l'assuré <strong>" . htmlspecialchars($assure) . "</strong> a été enregistré avec succès."
    ];

    // إعادة التوجيه وفقاً لاختيار الخبير
    if ($redirect_to_pv) {
        header("Location: ../pv_create.php?ods_id={$ods_id}");
    } else {
        header("Location: ../ods_list.php");
    }
    exit;

} catch (PDOException $e) {
    $_SESSION['alert'] = [
        'type'    => 'danger',
        'title'   => 'Erreur SQL !',
        'message' => "Une erreur s'est produite lors de l'enregistrement: " . htmlspecialchars($e->getMessage())
    ];
    header('Location: ../ods_create.php');
    exit;
}
