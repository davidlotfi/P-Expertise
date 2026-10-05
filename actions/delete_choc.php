<?php
/**
 * actions/delete_choc.php
 * حذف صدمة من المحضر وإعادة احتساب المبالغ الإجمالية للـ PV
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

$choc_id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
$pv_id   = isset($_GET['pv_id']) ? (int)$_GET['pv_id'] : (isset($_POST['pv_id']) ? (int)$_POST['pv_id'] : 0);

if ($choc_id <= 0 || $pv_id <= 0) {
    $_SESSION['alert'] = [
        'type'    => 'danger',
        'title'   => 'Erreur !',
        'message' => 'Paramètres invalides pour la suppression du choc.'
    ];
    header('Location: ../ods_list.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // التحقق من حالة الـ PV
    $stmt_pv = $pdo->prepare("SELECT statut FROM pv_expertises WHERE id = :id");
    $stmt_pv->execute([':id' => $pv_id]);
    $pv = $stmt_pv->fetch();

    if (!$pv) {
        throw new Exception("Le PV d'expertise est introuvable.");
    }

    if ($pv['statut'] === 'Validé' || $pv['statut'] === 'Clôturé') {
        throw new Exception("Impossible de supprimer une anomalie/choc d'un PV déjà validé ou clôturé.");
    }

    // جلب اسم الصدمة
    $stmt_name = $pdo->prepare("SELECT type_choc FROM chocs WHERE id = :id AND pv_id = :pv_id");
    $stmt_name->execute([':id' => $choc_id, ':pv_id' => $pv_id]);
    $type_choc = $stmt_name->fetchColumn() ?: "Choc";

    // حذف الصدمة (جدول fournitures_choc يحذف تلقائياً بفضل ON DELETE CASCADE)
    $stmt_del = $pdo->prepare("DELETE FROM chocs WHERE id = :id AND pv_id = :pv_id");
    $stmt_del->execute([':id' => $choc_id, ':pv_id' => $pv_id]);

    // إعادة احتساب إجماليات المحضر
    $stmt_totals = $pdo->prepare("
        SELECT 
            COALESCE(SUM(total_fournitures_ht), 0) AS sum_fournitures,
            COALESCE(SUM(montant_mo_ht), 0) AS sum_mo,
            COALESCE(SUM(montant_peinture), 0) AS sum_peinture,
            COALESCE(SUM(tva_fourniture), 0) AS sum_tva,
            COALESCE(SUM(montant_ttc), 0) AS sum_ttc
        FROM chocs 
        WHERE pv_id = :pv_id
    ");
    $stmt_totals->execute([':pv_id' => $pv_id]);
    $totals = $stmt_totals->fetch();

    $stmt_upd_pv = $pdo->prepare("
        UPDATE pv_expertises SET 
            total_fournitures_ht = :tot_fourn,
            montant_mo_ht        = :tot_mo,
            montant_peinture     = :tot_peint,
            tva_fourniture       = :tot_tva,
            montant_total_ttc    = :tot_ttc,
            date_maj             = NOW()
        WHERE id = :pv_id
    ");
    $stmt_upd_pv->execute([
        ':tot_fourn' => $totals['sum_fournitures'],
        ':tot_mo'    => $totals['sum_mo'],
        ':tot_peint' => $totals['sum_peinture'],
        ':tot_tva'   => $totals['sum_tva'],
        ':tot_ttc'   => $totals['sum_ttc'],
        ':pv_id'     => $pv_id
    ]);

    $pdo->commit();

    $_SESSION['alert'] = [
        'type'    => 'success',
        'title'   => 'Suppression effectuée',
        'message' => "Le <strong>" . htmlspecialchars($type_choc) . "</strong> a été supprimé avec succès."
    ];

    header("Location: ../pv_details.php?id=" . $pv_id);
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
