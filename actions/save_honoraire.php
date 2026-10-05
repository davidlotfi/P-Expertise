<?php
/**
 * actions/save_honoraire.php
 * إضافة بند أتعاب أو مصاريف جديدة لمحضر الخبرة
 * متطابق مع الشاشات 16 و 17 من دليل E-Expertise وجداول types_frais_honoraires و pv_honoraires
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../ods_list.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$pv_id         = isset($_POST['pv_id']) ? (int)$_POST['pv_id'] : 0;
$type_frais_id = isset($_POST['type_frais_id']) ? (int)$_POST['type_frais_id'] : null;
$libelle_choisi= trim($_POST['libelle'] ?? '');
$nombre        = isset($_POST['nombre']) ? (int)$_POST['nombre'] : 1;
$montant_unit  = isset($_POST['montant_unitaire']) ? (float)$_POST['montant_unitaire'] : 0.00;

if ($pv_id <= 0) {
    $_SESSION['alert'] = [
        'type'    => 'danger',
        'title'   => 'Erreur !',
        'message' => 'Identifiant du PV invalide.'
    ];
    header('Location: ../ods_list.php');
    exit;
}

if ($nombre <= 0) {
    $nombre = 1;
}

try {
    $pdo->beginTransaction();

    // 1. التحقق من وجود الـ PV وحالته
    $stmt_pv = $pdo->prepare("SELECT id, statut, numero_pv FROM pv_expertises WHERE id = :id");
    $stmt_pv->execute([':id' => $pv_id]);
    $pv = $stmt_pv->fetch();

    if (!$pv) {
        throw new Exception("Le PV d'expertise associé est introuvable.");
    }

    // 2. تحديد التسمية والتعريفة من نوع المصاريف إن تم اختياره
    if ($type_frais_id && $type_frais_id > 0) {
        $stmt_tf = $pdo->prepare("SELECT libelle, tarif_defaut FROM types_frais_honoraires WHERE id = :id");
        $stmt_tf->execute([':id' => $type_frais_id]);
        $type_frais = $stmt_tf->fetch();
        if ($type_frais) {
            if (empty($libelle_choisi)) {
                $libelle_choisi = $type_frais['libelle'];
            }
            if ($montant_unit <= 0) {
                $montant_unit = (float)$type_frais['tarif_defaut'];
            }
        }
    }

    if (empty($libelle_choisi)) {
        throw new Exception("Veuillez sélectionner ou renseigner la désignation des frais.");
    }

    $montant_total = $nombre * $montant_unit;

    // 3. إدراج السجل في جدول pv_honoraires
    $stmt_ins = $pdo->prepare("
        INSERT INTO pv_honoraires (pv_id, type_frais_id, libelle, nombre, montant_unitaire, montant_total, date_creation)
        VALUES (:pv_id, :type_frais_id, :libelle, :nombre, :montant_unitaire, :montant_total, NOW())
    ");
    $stmt_ins->execute([
        ':pv_id'            => $pv_id,
        ':type_frais_id'    => ($type_frais_id > 0 ? $type_frais_id : null),
        ':libelle'          => $libelle_choisi,
        ':nombre'           => $nombre,
        ':montant_unitaire' => $montant_unit,
        ':montant_total'    => $montant_total
    ]);

    // 4. إعادة احتساب وتحديث إجمالي الأتعاب في جدول pv_expertises
    $stmt_sum = $pdo->prepare("SELECT COALESCE(SUM(montant_total), 0) FROM pv_honoraires WHERE pv_id = :pv_id");
    $stmt_sum->execute([':pv_id' => $pv_id]);
    $total_honoraire = (float)$stmt_sum->fetchColumn();

    $stmt_upd = $pdo->prepare("UPDATE pv_expertises SET total_honoraires = :tot WHERE id = :pv_id");
    $stmt_upd->execute([':tot' => $total_honoraire, ':pv_id' => $pv_id]);

    $pdo->commit();

    $_SESSION['alert'] = [
        'type'    => 'success',
        'title'   => 'Frais ajouté avec succès !',
        'message' => "Le frais <strong>" . htmlspecialchars($libelle_choisi) . "</strong> a été ajouté.<br>"
                   . "Quantité: <strong>{$nombre}</strong> | Montant: <strong>" . number_format($montant_total, 2) . " DA</strong>"
    ];

    header("Location: ../honoraire_manage.php?pv_id=" . $pv_id);
    exit;

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['alert'] = [
        'type'    => 'danger',
        'title'   => 'Erreur d\'ajout d\'honoraire !',
        'message' => $e->getMessage()
    ];
    header("Location: ../honoraire_manage.php?pv_id=" . $pv_id);
    exit;
}
