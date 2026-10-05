<?php
/**
 * actions/save_pv.php
 * ملف معالجة واستلام بيانات محضر المعاينة والخبرة وحفظها في قاعدة البيانات
 * يتوافق مع تدفق العمل المذكور في دليل E-Expertise
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// التأكد من أن الطلب تم عبر POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pv_create.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

try {
    // 1. استلام وتطهير البيانات المدخلة
    $ods_id              = isset($_POST['ods_id']) ? (int)$_POST['ods_id'] : 0;
    $type_pv             = trim($_POST['type_pv'] ?? 'PV EXPERTISE AUTOMOBILE');
    $est_additif         = isset($_POST['est_additif']) ? 1 : 0;
    $date_expertise      = trim($_POST['date_expertise'] ?? date('Y-m-d'));
    $heure_expertise     = !empty($_POST['heure_expertise']) ? trim($_POST['heure_expertise']) : null;
    $lieu_expertise      = trim($_POST['lieu_expertise'] ?? '');
    $couleur_vehicule    = trim($_POST['couleur_vehicule'] ?? '');
    $taux_responsabilite = isset($_POST['taux_responsabilite']) ? (int)$_POST['taux_responsabilite'] : 0;
    $valeur_venale       = isset($_POST['valeur_venale']) ? (float)$_POST['valeur_venale'] : 0.00;
    $observation         = trim($_POST['observation'] ?? '');

    // المبالغ المالية التكميلية (Slide 14)
    $montant_mo_ht        = isset($_POST['montant_mo_ht']) ? (float)$_POST['montant_mo_ht'] : 0.00;
    $immobilisation_jours = isset($_POST['immobilisation_jours']) ? (int)$_POST['immobilisation_jours'] : 0;
    $montant_peinture     = isset($_POST['montant_peinture']) ? (float)$_POST['montant_peinture'] : 0.00;
    $tva_fourniture       = isset($_POST['tva_fourniture']) ? (float)$_POST['tva_fourniture'] : 0.00;
    $taux_vetuste         = isset($_POST['taux_vetuste']) ? (float)$_POST['taux_vetuste'] : 0.00;

    // حساب إجمالي تقريبي أولي للـ TTC
    $montant_total_ttc    = $montant_mo_ht + $montant_peinture + $tva_fourniture;

    // 2. التحقق من صحة المدخلات الأساسية
    $errors = [];
    if ($ods_id <= 0) {
        $errors[] = "Veuillez sélectionner un ordre de service (ODS) valide.";
    }
    if (empty($date_expertise)) {
        $errors[] = "La date d'expertise est obligatoire.";
    }

    if (!empty($errors)) {
        $_SESSION['alert'] = [
            'type'    => 'danger',
            'title'   => 'Erreur de validation !',
            'message' => implode('<br>', $errors)
        ];
        header("Location: ../pv_create.php?ods_id=" . $ods_id);
        exit;
    }

    // 3. تحديد معرف الخبير (من الجلسة أو الافتراضي المسجل في قاعدة البيانات)
    $expert_id = $_SESSION['user_id'] ?? null;
    if (!$expert_id) {
        // جلب معرف الخبير المسجل في قاعدة البيانات
        $stmt_exp = $pdo->query("SELECT id FROM utilisateurs ORDER BY id ASC LIMIT 1");
        $expert_id = $stmt_exp->fetchColumn() ?: 1;
    }

    // جلب معلومات الـ ODS للتأكد منه وتوليد رقم الـ PV
    $stmt_ods = $pdo->prepare("SELECT numero_ods, numero_dossier FROM ods WHERE id = :id");
    $stmt_ods->execute([':id' => $ods_id]);
    $ods = $stmt_ods->fetch();

    if (!$ods) {
        $_SESSION['alert'] = [
            'type'    => 'danger',
            'title'   => 'ODS introuvable !',
            'message' => "L'ordre de service sélectionné n'existe pas."
        ];
        header("Location: ../pv_create.php");
        exit;
    }

    // توليد رقم فريد للمحضر مطابق لنمط النظام: PV-{ID}-{DATE}
    $numero_pv = "PV-" . date('ymd') . "-" . rand(1000, 9999);

    // 4. إدراج محضر الخبرة في جدول pv_expertises باستخدام PDO Prepared Statements
    $sql = "INSERT INTO pv_expertises (
                ods_id, expert_id, numero_pv, type_pv, est_additif, 
                date_expertise, heure_expertise, lieu_expertise, couleur_vehicule,
                taux_responsabilite, valeur_venale, observation,
                montant_mo_ht, immobilisation_jours, montant_peinture, tva_fourniture,
                taux_vetuste, montant_total_ttc, statut, date_creation
            ) VALUES (
                :ods_id, :expert_id, :numero_pv, :type_pv, :est_additif,
                :date_expertise, :heure_expertise, :lieu_expertise, :couleur_vehicule,
                :taux_responsabilite, :valeur_venale, :observation,
                :montant_mo_ht, :immobilisation_jours, :montant_peinture, :tva_fourniture,
                :taux_vetuste, :montant_total_ttc, 'Brouillon', NOW()
            )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':ods_id'              => $ods_id,
        ':expert_id'           => $expert_id,
        ':numero_pv'           => $numero_pv,
        ':type_pv'             => $type_pv,
        ':est_additif'         => $est_additif,
        ':date_expertise'      => $date_expertise,
        ':heure_expertise'     => $heure_expertise,
        ':lieu_expertise'      => $lieu_expertise,
        ':couleur_vehicule'    => $couleur_vehicule,
        ':taux_responsabilite' => $taux_responsabilite,
        ':valeur_venale'       => $valeur_venale,
        ':observation'         => $observation,
        ':montant_mo_ht'       => $montant_mo_ht,
        ':immobilisation_jours'=> $immobilisation_jours,
        ':montant_peinture'    => $montant_peinture,
        ':tva_fourniture'      => $tva_fourniture,
        ':taux_vetuste'        => $taux_vetuste,
        ':montant_total_ttc'   => $montant_total_ttc
    ]);

    $pv_id = $pdo->lastInsertId();

    // 5. تحديث حالة الـ ODS إلى "En cours" أو "Expertise faite"
    $update_ods = $pdo->prepare("UPDATE ods SET statut = 'Expertise faite' WHERE id = :ods_id");
    $update_ods->execute([':ods_id' => $ods_id]);

    // 6. تسجيل إشعار جديد للنظام (كما في Slide 3 من الدليل: إشعار عند إنشاء الخبرة)
    try {
        $notif_stmt = $pdo->prepare("INSERT INTO notifications (destinataire_id, titre, message, date_creation) VALUES (:uid, :titre, :msg, NOW())");
        $notif_stmt->execute([
            ':uid'   => $expert_id,
            ':titre' => "Création de l'expertise {$numero_pv}",
            ':msg'   => "L'expertise pour l'ODS N° {$ods['numero_ods']} a été créée avec succès en statut Brouillon."
        ]);
    } catch (Exception $ex) {
        // تجاهل أي خطأ ثانوي في جدول الإشعارات
    }

    // 7. تخزين رسالة النجاح في الجلسة وإعادة التوجيه إلى صفحة تفاصيل المحضر (pv_details.php)
    $_SESSION['alert'] = [
        'type'    => 'success',
        'title'   => 'Expertise créée avec succès !',
        'message' => "Le procès-verbal d'expertise a été enregistré.<br>"
                   . "<strong>N° PV:</strong> <code>{$numero_pv}</code> | "
                   . "<strong>ODS:</strong> {$ods['numero_ods']} | "
                   . "<strong>Type:</strong> {$type_pv}<br>"
                   . "Vous pouvez maintenant ajouter les chocs constatés (Choc A, Choc B...) et les honoraires associés."
    ];

    header("Location: ../pv_details.php?id=" . $pv_id);
    exit;

} catch (PDOException $e) {
    // معالجة أخطاء قاعدة البيانات
    $_SESSION['alert'] = [
        'type'    => 'danger',
        'title'   => 'Erreur SQL lors de l\'enregistrement !',
        'message' => "Une erreur s'est produite lors de l'insertion dans la base de données: " . htmlspecialchars($e->getMessage())
    ];
    header("Location: ../pv_create.php?ods_id=" . ($ods_id ?? 0));
    exit;
}
