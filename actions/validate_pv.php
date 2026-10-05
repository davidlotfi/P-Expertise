<?php
/**
 * actions/validate_pv.php
 * اعتماد وتأكيد محضر الخبرة من طرف الخبير وتوليد كود المصادقة الرسمي
 * مطابق للشرائح 18 و 19 و 22 من دليل E-Expertise
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

$pv_id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);

if ($pv_id <= 0) {
    $_SESSION['alert'] = [
        'type'    => 'danger',
        'title'   => 'Erreur !',
        'message' => 'Identifiant du PV invalide.'
    ];
    header('Location: ../ods_list.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. جلب تفاصيل المحضر والـ ODS
    $stmt = $pdo->prepare("
        SELECT pv.*, o.numero_ods, o.numero_dossier 
        FROM pv_expertises pv
        JOIN ods o ON pv.ods_id = o.id
        WHERE pv.id = :id
    ");
    $stmt->execute([':id' => $pv_id]);
    $pv = $stmt->fetch();

    if (!$pv) {
        throw new Exception("Le procès-verbal d'expertise est introuvable.");
    }

    if ($pv['statut'] === 'Validé' || $pv['statut'] === 'Clôturé') {
        throw new Exception("Ce procès-verbal est déjà validé.");
    }

    // 2. توليد كود التحقق والاعتماد الرسمي (كما في Slide 22: مثل EXP119520000003)
    // استخراج أرقام من ملف الحادث للسنة والرقم التسلسلي
    $clean_dossier = preg_replace('/[^0-9]/', '', $pv['numero_dossier']);
    $sub_dossier   = substr($clean_dossier, 5, 4) ?: '1195';
    $year_code     = date('y');
    $seq_code      = str_pad((string)$pv_id, 6, '0', STR_PAD_LEFT);
    $code_validation = "EXP" . $sub_dossier . $year_code . $seq_code;

    // 3. تحديث حالة المحضر إلى "Validé" مع حفظ كود وتاريخ الاعتماد
    $stmt_upd = $pdo->prepare("
        UPDATE pv_expertises SET 
            statut          = 'Validé',
            code_validation = :code,
            date_validation = NOW(),
            date_maj        = NOW()
        WHERE id = :id
    ");
    $stmt_upd->execute([
        ':code' => $code_validation,
        ':id'   => $pv_id
    ]);

    // 4. تحديث حالة الـ ODS إلى "Validé"
    $stmt_ods = $pdo->prepare("UPDATE ods SET statut = 'Validé' WHERE id = :ods_id");
    $stmt_ods->execute([':ods_id' => $pv['ods_id']]);

    // 5. تسجيل إشعار جديد في النظام
    try {
        $expert_id = $pv['expert_id'] ?: ($_SESSION['user_id'] ?? 1);
        $notif = $pdo->prepare("INSERT INTO notifications (destinataire_id, titre, message, date_creation) VALUES (?, ?, ?, NOW())");
        $notif->execute([
            $expert_id,
            "Validation de l'expertise {$pv['numero_pv']}",
            "L'expertise pour le dossier {$pv['numero_dossier']} a été validée avec succès. Code généré: {$code_validation}."
        ]);
    } catch (Exception $e_notif) {}

    $pdo->commit();

    $_SESSION['alert'] = [
        'type'    => 'success',
        'title'   => 'Expertise validée avec succès !',
        'message' => "Le procès-verbal a été certifié et validé par l'expert.<br>"
                   . "<strong>Code de validation généré :</strong> <code class='fs-6 fw-bold text-success'>{$code_validation}</code><br>"
                   . "Vous pouvez désormais imprimer le PV et le rapport d'honoraires."
    ];

    header("Location: ../pv_details.php?id=" . $pv_id);
    exit;

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['alert'] = [
        'type'    => 'danger',
        'title'   => 'Erreur de validation !',
        'message' => $e->getMessage()
    ];
    header("Location: ../pv_details.php?id=" . $pv_id);
    exit;
}
