<?php
/**
 * actions/delete_honoraire.php
 * حذف بند أتعاب من المحضر وتحديث الإجمالي
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

$honoraire_id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
$pv_id        = isset($_GET['pv_id']) ? (int)$_GET['pv_id'] : (isset($_POST['pv_id']) ? (int)$_POST['pv_id'] : 0);

if ($honoraire_id <= 0 || $pv_id <= 0) {
    $_SESSION['alert'] = [
        'type'    => 'danger',
        'title'   => 'Erreur !',
        'message' => 'Paramètres invalides pour la suppression de l\'honoraire.'
    ];
    header('Location: ../ods_list.php');
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt_pv = $pdo->prepare("SELECT statut FROM pv_expertises WHERE id = :id");
    $stmt_pv->execute([':id' => $pv_id]);
    $pv = $stmt_pv->fetch();

    if (!$pv) {
        throw new Exception("Le PV d'expertise est introuvable.");
    }

    if ($pv['statut'] === 'Validé' || $pv['statut'] === 'Clôturé') {
        throw new Exception("Impossible de modifier les honoraires d'un PV déjà validé ou clôturé.");
    }

    // جلب التسمية قبل الحذف
    $stmt_lib = $pdo->prepare("SELECT libelle FROM pv_honoraires WHERE id = :id AND pv_id = :pv_id");
    $stmt_lib->execute([':id' => $honoraire_id, ':pv_id' => $pv_id]);
    $libelle = $stmt_lib->fetchColumn() ?: "Frais";

    // حذف السجل
    $stmt_del = $pdo->prepare("DELETE FROM pv_honoraires WHERE id = :id AND pv_id = :pv_id");
    $stmt_del->execute([':id' => $honoraire_id, ':pv_id' => $pv_id]);

    // إعادة احتساب الإجمالي
    $stmt_sum = $pdo->prepare("SELECT COALESCE(SUM(montant_total), 0) FROM pv_honoraires WHERE pv_id = :pv_id");
    $stmt_sum->execute([':pv_id' => $pv_id]);
    $total_honoraire = (float)$stmt_sum->fetchColumn();

    $stmt_upd = $pdo->prepare("UPDATE pv_expertises SET total_honoraires = :tot WHERE id = :pv_id");
    $stmt_upd->execute([':tot' => $total_honoraire, ':pv_id' => $pv_id]);

    $pdo->commit();

    $_SESSION['alert'] = [
        'type'    => 'success',
        'title'   => 'Frais supprimé',
        'message' => "Le frais <strong>" . htmlspecialchars($libelle) . "</strong> a été supprimé avec succès."
    ];

    header("Location: ../honoraire_manage.php?pv_id=" . $pv_id);
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
    header("Location: ../honoraire_manage.php?pv_id=" . $pv_id);
    exit;
}
