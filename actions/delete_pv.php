<?php
/**
 * actions/delete_pv.php
 * حذف محضر الخبرة إذا لم يكن معتمداً من قبل الخبير
 * مطابق للشريحة 15 من دليل الاستخدام E-Expertise
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

    $stmt = $pdo->prepare("SELECT id, ods_id, numero_pv, statut FROM pv_expertises WHERE id = :id");
    $stmt->execute([':id' => $pv_id]);
    $pv = $stmt->fetch();

    if (!$pv) {
        throw new Exception("Le procès-verbal d'expertise est introuvable.");
    }

    if ($pv['statut'] === 'Validé' || $pv['statut'] === 'Clôturé') {
        throw new Exception("Impossible de supprimer une expertise déjà validée ou certifiée.");
    }

    $ods_id = $pv['ods_id'];
    $num_pv = $pv['numero_pv'];

    // حذف المحضر (الترابط FOREIGN KEY CASCADE يحذف الصدمات وقطع الغيار والأتعاب تلقائياً)
    $stmt_del = $pdo->prepare("DELETE FROM pv_expertises WHERE id = :id");
    $stmt_del->execute([':id' => $pv_id]);

    // إعادة حالة الـ ODS إلى Nouveau إن لم تكن هناك محاضر أخرى مرتبطة به
    $count_pvs = $pdo->prepare("SELECT COUNT(*) FROM pv_expertises WHERE ods_id = :ods_id");
    $count_pvs->execute([':ods_id' => $ods_id]);
    if ((int)$count_pvs->fetchColumn() === 0) {
        $pdo->prepare("UPDATE ods SET statut = 'Nouveau' WHERE id = :ods_id")->execute([':ods_id' => $ods_id]);
    }

    $pdo->commit();

    $_SESSION['alert'] = [
        'type'    => 'success',
        'title'   => 'Expertise supprimée',
        'message' => "Le procès-verbal <strong>{$num_pv}</strong> a été supprimé avec succès."
    ];

    header("Location: ../ods_list.php");
    exit;

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['alert'] = [
        'type'    => 'danger',
        'title'   => 'Erreur de suppression !',
        'message' => $e->getMessage()
    ];
    header("Location: ../pv_details.php?id=" . $pv_id);
    exit;
}
