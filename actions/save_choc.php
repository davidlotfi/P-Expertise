<?php
/**
 * actions/save_choc.php
 * معالجة وحفظ بيانات الصدمة (Choc) وتفاصيل قطع الغيار (Fournitures) والمبالغ المالية الملحقة
 * متطابق 100% مع الشاشات 13 و 14 من دليل E-Expertise وقاعدة البيانات
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../ods_list.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$pv_id   = isset($_POST['pv_id']) ? (int)$_POST['pv_id'] : 0;
$choc_id = isset($_POST['choc_id']) ? (int)$_POST['choc_id'] : 0;

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
    // 1. استلام وتطهير بيانات الصدمة الأساسية
    $type_choc          = trim($_POST['type_choc'] ?? 'Choc A');
    $description        = trim($_POST['description'] ?? '');
    $detail_reparation  = trim($_POST['detail_reparation'] ?? '');

    // المبالغ المالية (Slide 14)
    $montant_mo_ht        = isset($_POST['montant_mo_ht']) ? (float)$_POST['montant_mo_ht'] : 0.00;
    $immobilisation_jours = isset($_POST['immobilisation_jours']) ? (int)$_POST['immobilisation_jours'] : 0;
    $montant_peinture     = isset($_POST['montant_peinture']) ? (float)$_POST['montant_peinture'] : 0.00;
    $tva_fourniture       = isset($_POST['tva_fourniture']) ? (float)$_POST['tva_fourniture'] : 0.00;
    $taux_vetuste         = isset($_POST['taux_vetuste']) ? (float)$_POST['taux_vetuste'] : 0.00;
    $total_fournitures_ht = isset($_POST['total_fournitures_ht']) ? (float)$_POST['total_fournitures_ht'] : 0.00;
    $montant_ttc          = isset($_POST['montant_ttc']) ? (float)$_POST['montant_ttc'] : 0.00;

    // استلام مصفوفة قطع الغيار Fournitures
    $fournitures = $_POST['fournitures'] ?? [];

    // التحقق من صحة المدخلات
    if (empty($type_choc)) {
        throw new Exception("Veuillez spécifier le type de choc (Ex: Choc A, Choc B...).");
    }

    // بدء المعاملة Transaction لضمان سلامة العمليات المتعددة
    $pdo->beginTransaction();

    // 2. التحقق من وجود الـ PV وأن حالته تسمح بالتعديل (Brouillon)
    $stmt_pv = $pdo->prepare("SELECT id, statut, numero_pv FROM pv_expertises WHERE id = :id");
    $stmt_pv->execute([':id' => $pv_id]);
    $pv = $stmt_pv->fetch();

    if (!$pv) {
        throw new Exception("Le PV d'expertise associé est introuvable.");
    }

    // 3. إدراج أو تعديل بيانات الصدمة في جدول chocs
    if ($choc_id > 0) {
        // تعديل صدمة موجودة
        $sql_choc = "UPDATE chocs SET 
                        type_choc            = :type_choc,
                        description          = :description,
                        detail_reparation    = :detail_reparation,
                        montant_mo_ht        = :montant_mo_ht,
                        immobilisation_jours = :immobilisation_jours,
                        montant_peinture     = :montant_peinture,
                        tva_fourniture       = :tva_fourniture,
                        taux_vetuste         = :taux_vetuste,
                        total_fournitures_ht = :total_fournitures_ht,
                        montant_ttc          = :montant_ttc
                     WHERE id = :id AND pv_id = :pv_id";

        $stmt_choc = $pdo->prepare($sql_choc);
        $stmt_choc->execute([
            ':type_choc'            => $type_choc,
            ':description'          => $description,
            ':detail_reparation'    => $detail_reparation,
            ':montant_mo_ht'        => $montant_mo_ht,
            ':immobilisation_jours' => $immobilisation_jours,
            ':montant_peinture'     => $montant_peinture,
            ':tva_fourniture'       => $tva_fourniture,
            ':taux_vetuste'         => $taux_vetuste,
            ':total_fournitures_ht' => $total_fournitures_ht,
            ':montant_ttc'          => $montant_ttc,
            ':id'                   => $choc_id,
            ':pv_id'                => $pv_id
        ]);
    } else {
        // إنشاء صدمة جديدة
        $sql_choc = "INSERT INTO chocs (
                        pv_id, type_choc, description, detail_reparation,
                        montant_mo_ht, immobilisation_jours, montant_peinture,
                        tva_fourniture, taux_vetuste, total_fournitures_ht, montant_ttc, date_creation
                     ) VALUES (
                        :pv_id, :type_choc, :description, :detail_reparation,
                        :montant_mo_ht, :immobilisation_jours, :montant_peinture,
                        :tva_fourniture, :taux_vetuste, :total_fournitures_ht, :montant_ttc, NOW()
                     )";

        $stmt_choc = $pdo->prepare($sql_choc);
        $stmt_choc->execute([
            ':pv_id'                => $pv_id,
            ':type_choc'            => $type_choc,
            ':description'          => $description,
            ':detail_reparation'    => $detail_reparation,
            ':montant_mo_ht'        => $montant_mo_ht,
            ':immobilisation_jours' => $immobilisation_jours,
            ':montant_peinture'     => $montant_peinture,
            ':tva_fourniture'       => $tva_fourniture,
            ':taux_vetuste'         => $taux_vetuste,
            ':total_fournitures_ht' => $total_fournitures_ht,
            ':montant_ttc'          => $montant_ttc
        ]);

        $choc_id = (int)$pdo->lastInsertId();
    }

    // 4. حذف قطع الغيار القديمة لهذه الصدمة تمهيداً لإعادة إدراجها
    $stmt_del_f = $pdo->prepare("DELETE FROM fournitures_choc WHERE choc_id = :choc_id");
    $stmt_del_f->execute([':choc_id' => $choc_id]);

    // 5. إدراج قطع الغيار المدخلة في جدول fournitures_choc
    $calculated_total_ht = 0.00;
    if (!empty($fournitures) && is_array($fournitures)) {
        $sql_f = "INSERT INTO fournitures_choc (choc_id, categorie, article, prix_unitaire_ht, quantite, total_ht, date_creation)
                  VALUES (:choc_id, :categorie, :article, :prix_unitaire_ht, :quantite, :total_ht, NOW())";
        $stmt_f = $pdo->prepare($sql_f);

        foreach ($fournitures as $f) {
            $cat   = trim($f['categorie'] ?? '');
            $art   = trim($f['article'] ?? '');
            $prix  = isset($f['prix']) ? (float)$f['prix'] : 0.00;
            $quant = isset($f['nb']) ? (int)$f['nb'] : 1;
            if ($quant <= 0) $quant = 1;
            $tot   = $prix * $quant;

            // تجاهل الصفوف الفارغة تماماً
            if (empty($art) && empty($cat) && $prix <= 0) {
                continue;
            }

            if (empty($cat)) $cat = 'DIVERS';
            if (empty($art)) $art = 'Fourniture non spécifiée';

            $stmt_f->execute([
                ':choc_id'          => $choc_id,
                ':categorie'        => $cat,
                ':article'          => $art,
                ':prix_unitaire_ht' => $prix,
                ':quantite'         => $quant,
                ':total_ht'         => $tot
            ]);

            $calculated_total_ht += $tot;
        }
    }

    // تحديث إجمالي القطع في الصدمة إذا كان هناك فرق
    if (abs($calculated_total_ht - $total_fournitures_ht) > 0.01 && $calculated_total_ht > 0) {
        $total_fournitures_ht = $calculated_total_ht;
        $tva_fourniture = round($total_fournitures_ht * 0.19, 2);
        $montant_ttc = $total_fournitures_ht + $tva_fourniture + $montant_mo_ht + $montant_peinture;
        
        $pdo->prepare("UPDATE chocs SET total_fournitures_ht = :tot, tva_fourniture = :tva, montant_ttc = :ttc WHERE id = :id")
            ->execute([':tot' => $total_fournitures_ht, ':tva' => $tva_fourniture, ':ttc' => $montant_ttc, ':id' => $choc_id]);
    }

    // 6. تحديث الإجماليات الشاملة لمحضر الخبرة pv_expertises (مجموع كافة الصدمات)
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

    // إتمام العملية بنجاح
    $pdo->commit();

    $_SESSION['alert'] = [
        'type'    => 'success',
        'title'   => 'Choc enregistré avec succès !',
        'message' => "Les informations du <strong>" . htmlspecialchars($type_choc) . "</strong> et ses fournitures ont été enregistrées avec succès.<br>"
                   . "Montant Total Choc TTC: <strong>" . number_format($montant_ttc, 2) . " DA</strong>"
    ];

    header("Location: ../pv_details.php?id=" . $pv_id);
    exit;

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['alert'] = [
        'type'    => 'danger',
        'title'   => 'Erreur d\'enregistrement du choc !',
        'message' => $e->getMessage()
    ];
    header("Location: ../choc_form.php?pv_id=" . $pv_id . ($choc_id > 0 ? "&choc_id=" . $choc_id : ""));
    exit;
}
