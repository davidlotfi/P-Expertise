<?php
/**
 * honoraire_print.php
 * صفحة طباعة وتصدير مذكرة وكشف أتعاب ومصاريف الخبير الرسمي (Alliance Assurances)
 * مطابقة للمستندات الصادرة في الشريحة 19 من الدليل
 */

require_once __DIR__ . '/config/db.php';

$pv_id = isset($_GET['pv_id']) ? (int)$_GET['pv_id'] : 0;

if ($pv_id <= 0) {
    die("Identifiant d'expertise invalide.");
}

$stmt = $pdo->prepare("
    SELECT pv.*, 
           o.numero_ods, o.numero_dossier, o.date_sinistre, o.date_ods, o.assure, 
           o.police, o.matricule, o.marque, o.modele,
           u.nom AS expert_nom, u.prenom AS expert_prenom, u.telephone_1 AS expert_tel, u.email AS expert_email, u.soumis_tva
    FROM pv_expertises pv
    JOIN ods o ON pv.ods_id = o.id
    LEFT JOIN utilisateurs u ON pv.expert_id = u.id
    WHERE pv.id = :id
");
$stmt->execute([':id' => $pv_id]);
$pv = $stmt->fetch();

if (!$pv) {
    die("Expertise introuvable.");
}

// جلب الأتعاب
$stmt_hon = $pdo->prepare("SELECT * FROM pv_honoraires WHERE pv_id = :id ORDER BY id ASC");
$stmt_hon->execute([':id' => $pv_id]);
$honoraires = $stmt_hon->fetchAll();

$total_ht = 0.00;
foreach ($honoraires as $h) {
    $total_ht += (float)$h['montant_total'];
}

$soumis_tva = !empty($pv['soumis_tva']) ? 1 : 0;
$tva_honoraire = $soumis_tva ? ($total_ht * 0.19) : 0.00;
$total_ttc = $total_ht + $tva_honoraire;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Mémoire d'Honoraires - <?= htmlspecialchars($pv['numero_pv']) ?></title>
  <link href="Tamplate-frontend/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="Tamplate-frontend/assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background: #f8fafc;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      color: #1e293b;
      margin: 0;
      padding: 20px;
    }
    .print-container {
      max-width: 850px;
      margin: 0 auto;
      background: #ffffff;
      padding: 40px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
      border: 1px solid #e2e8f0;
    }
    .header-table td {
      border: none !important;
      padding: 4px 8px;
    }
    .title-box {
      background: #fffbeb;
      border: 2px solid #fde68a;
      border-radius: 6px;
      text-align: center;
      padding: 12px;
      margin: 20px 0;
    }
    .data-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 20px;
    }
    .data-table th, .data-table td {
      border: 1px solid #cbd5e1;
      padding: 8px 12px;
      font-size: 0.9rem;
    }
    .data-table th {
      background: #f8fafc;
      font-weight: 700;
    }
    .stamp-box {
      border: 2px dashed #94a3b8;
      border-radius: 8px;
      height: 120px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #64748b;
      font-size: 0.85rem;
      text-align: center;
      margin-top: 10px;
    }
    @media print {
      body {
        background: #ffffff;
        padding: 0;
      }
      .print-container {
        border: none;
        box-shadow: none;
        padding: 15px;
        max-width: 100%;
      }
      .no-print {
        display: none !important;
      }
    }
  </style>
</head>
<body>

  <!-- شريط التحكم بالطباعة -->
  <div class="print-container no-print mb-3 py-2 d-flex justify-content-between align-items-center">
    <div>
      <a href="pv_details.php?id=<?= $pv_id ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Retour au dossier
      </a>
    </div>
    <div class="d-flex gap-2">
      <button onclick="window.print()" class="btn btn-warning btn-sm px-4 fw-bold text-dark shadow-sm">
        <i class="bi bi-printer me-1"></i> Imprimer la note d'honoraires
      </button>
    </div>
  </div>

  <div class="print-container">

    <table class="w-100 header-table mb-3">
      <tr>
        <td style="width: 50%; vertical-align: top;">
          <h5 class="fw-bold text-primary m-0">ALLIANCE ASSURANCES</h5>
          <div class="small text-muted">Direction Indemnisation & Règlements</div>
          <div class="small text-muted">Service Contrôle des Honoraires d'Expertise</div>
        </td>
        <td style="width: 50%; text-align: right; vertical-align: top;">
          <h6 class="fw-bold m-0"><?= htmlspecialchars($pv['expert_nom'] . ' ' . $pv['expert_prenom']) ?></h6>
          <div class="small text-muted">Expert Automobile Agréé</div>
          <div class="small text-muted">Tél: <?= htmlspecialchars($pv['expert_tel'] ?? '-') ?></div>
        </td>
      </tr>
    </table>

    <div class="title-box">
      <h4 class="m-0 fw-bold text-dark text-uppercase">NOTE & MÉMOIRE D'HONORAIRES</h4>
      <div class="small text-muted mt-1">
        Réf PV : <strong><?= htmlspecialchars($pv['numero_pv']) ?></strong> | 
        Code Certification : <code><?= htmlspecialchars($pv['code_validation'] ?? 'EXP-BROUILLON') ?></code>
      </div>
    </div>

    <!-- بطاقة المراجع -->
    <table class="data-table">
      <tr>
        <td style="width: 25%; font-weight: bold; background: #f8fafc;">N° Ordre de Service</td>
        <td style="width: 25%;"><?= htmlspecialchars($pv['numero_ods']) ?></td>
        <td style="width: 25%; font-weight: bold; background: #f8fafc;">N° Dossier Sinistre</td>
        <td style="width: 25%;"><?= htmlspecialchars($pv['numero_dossier']) ?></td>
      </tr>
      <tr>
        <td style="font-weight: bold; background: #f8fafc;">Assuré / Bénéficiaire</td>
        <td><?= htmlspecialchars($pv['assure']) ?></td>
        <td style="font-weight: bold; background: #f8fafc;">Véhicule (Immat)</td>
        <td><?= htmlspecialchars($pv['marque'] . ' ' . $pv['modele']) ?> (<code><?= htmlspecialchars($pv['matricule']) ?></code>)</td>
      </tr>
      <tr>
        <td style="font-weight: bold; background: #f8fafc;">Date d'Expertise</td>
        <td><?= htmlspecialchars($pv['date_expertise']) ?></td>
        <td style="font-weight: bold; background: #f8fafc;">Statut Réglementaire</td>
        <td><?= $soumis_tva ? '<span class="badge bg-success">Assujetti à la TVA</span>' : '<span class="badge bg-secondary">Non assujetti TVA</span>' ?></td>
      </tr>
    </table>

    <!-- جدول بنود ومصاريف الأتعاب -->
    <h6 class="fw-bold text-dark mt-4 mb-2">Détail des Frais & Vacations d'Expertise</h6>
    <table class="data-table">
      <thead>
        <tr>
          <th style="width: 5%;">#</th>
          <th>Désignation de la prestation ou vacation</th>
          <th style="width: 15%; text-align: center;">Nombre</th>
          <th style="width: 20%; text-align: right;">Tarif Unitaire HT</th>
          <th style="width: 20%; text-align: right;">Montant Total HT</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($honoraires)): ?>
          <?php foreach ($honoraires as $idx => $item): ?>
            <tr>
              <td class="text-center text-muted"><?= $idx + 1 ?></td>
              <td><strong><?= htmlspecialchars($item['libelle']) ?></strong></td>
              <td style="text-align: center; font-weight: bold;"><?= (int)$item['nombre'] ?></td>
              <td style="text-align: right;"><?= number_format($item['montant_unitaire'], 2) ?> DA</td>
              <td style="text-align: right; font-weight: bold;"><?= number_format($item['montant_total'], 2) ?> DA</td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="5" class="text-center text-muted py-3">Aucun frais enregistré.</td></tr>
        <?php endif; ?>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="4" style="text-align: right; font-weight: bold;">TOTAL HONORAIRES & VACATIONS (HT)</td>
          <td style="text-align: right; font-weight: bold; font-size: 1rem;"><?= number_format($total_ht, 2) ?> DA</td>
        </tr>
        <?php if ($soumis_tva): ?>
          <tr>
            <td colspan="4" style="text-align: right; font-weight: bold;">T.V.A LÉGALE (19%)</td>
            <td style="text-align: right; font-weight: bold;"><?= number_format($tva_honoraire, 2) ?> DA</td>
          </tr>
        <?php endif; ?>
        <tr style="background: #fef3c7; font-size: 1.05rem;">
          <td colspan="4" style="text-align: right; font-weight: 800; color: #92400e;">NET À PAYER (TTC)</td>
          <td style="text-align: right; font-weight: 800; color: #92400e;"><?= number_format($total_ttc, 2) ?> DA</td>
        </tr>
      </tfoot>
    </table>

    <!-- التوقيع والتأشيرة -->
    <div class="row mt-5">
      <div class="col-6">
        <div class="small fw-bold text-muted">Fait le : <?= date('d/m/Y') ?></div>
        <div class="small text-muted">Transmis à la Direction Indemnisation pour liquidation et virement.</div>
      </div>
      <div class="col-6 text-end">
        <div class="fw-bold small">Cachet et Signature de l'Expert</div>
        <div class="stamp-box">
          Cabinet d'Expertise Agréé<br>
          <?= htmlspecialchars($pv['expert_nom'] . ' ' . $pv['expert_prenom']) ?><br>
          <?= htmlspecialchars($pv['code_validation'] ?? '') ?>
        </div>
      </div>
    </div>

  </div>

</body>
</html>
