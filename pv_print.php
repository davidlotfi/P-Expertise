<?php
/**
 * pv_print.php
 * صفحة طباعة وتصدير محضر الخبرة والمعاينة الفنية الرسمي (Alliance Assurances)
 * مطابقة للتقارير الرسمية ونموذج الطباعة المشار إليه في الشريحة 19
 */

require_once __DIR__ . '/config/db.php';

$pv_id = isset($_GET['pv_id']) ? (int)$_GET['pv_id'] : 0;

if ($pv_id <= 0) {
    die("Identifiant d'expertise invalide.");
}

$stmt = $pdo->prepare("
    SELECT pv.*, 
           o.numero_ods, o.numero_dossier, o.date_sinistre, o.date_ods, o.assure, 
           o.police, o.matricule, o.numero_serie, o.marque, o.modele, o.puissance, 
           o.carburant, o.telephone,
           u.nom AS expert_nom, u.prenom AS expert_prenom, u.telephone_1 AS expert_tel, u.email AS expert_email
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

// جلب الصدمات
$stmt_chocs = $pdo->prepare("SELECT * FROM chocs WHERE pv_id = :id ORDER BY id ASC");
$stmt_chocs->execute([':id' => $pv_id]);
$chocs = $stmt_chocs->fetchAll();

// جلب قطع الغيار
$stmt_f = $pdo->prepare("
    SELECT f.*, c.type_choc 
    FROM fournitures_choc f
    JOIN chocs c ON f.choc_id = c.id
    WHERE c.pv_id = :id
    ORDER BY c.id ASC, f.id ASC
");
$stmt_f->execute([':id' => $pv_id]);
$fournitures = $stmt_f->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Rapport d'Expertise - <?= htmlspecialchars($pv['numero_pv']) ?></title>
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
      max-width: 900px;
      margin: 0 auto;
      background: #ffffff;
      padding: 35px 40px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
      border: 1px solid #e2e8f0;
    }
    .header-table td {
      border: none !important;
      padding: 4px 8px;
    }
    .title-box {
      background: #f1f5f9;
      border: 2px solid #cbd5e1;
      border-radius: 6px;
      text-align: center;
      padding: 10px;
      margin: 15px 0;
    }
    .table-section-title {
      font-size: 0.85rem;
      font-weight: 800;
      text-transform: uppercase;
      background: #0b5777;
      color: #ffffff;
      padding: 6px 12px;
      margin-top: 20px;
      margin-bottom: 0;
      border-radius: 4px 4px 0 0;
    }
    .data-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 15px;
    }
    .data-table th, .data-table td {
      border: 1px solid #cbd5e1;
      padding: 6px 10px;
      font-size: 0.85rem;
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
      <button onclick="window.print()" class="btn btn-primary btn-sm px-4 fw-bold">
        <i class="bi bi-printer me-1"></i> Imprimer ce PV
      </button>
    </div>
  </div>

  <div class="print-container">

    <!-- ترويسة التقرير الرسمي -->
    <table class="w-100 header-table mb-2">
      <tr>
        <td style="width: 50%; vertical-align: top;">
          <h5 class="fw-bold text-primary m-0">ALLIANCE ASSURANCES</h5>
          <div class="small text-muted">Direction des Indemnisations Automobiles</div>
          <div class="small text-muted">Portail E-EXPERTISE Automobile</div>
        </td>
        <td style="width: 50%; text-align: right; vertical-align: top;">
          <h6 class="fw-bold m-0"><?= htmlspecialchars($pv['expert_nom'] . ' ' . $pv['expert_prenom']) ?></h6>
          <div class="small text-muted">Cabinet d'Expertise Automobile Agréé</div>
          <div class="small text-muted">Tél: <?= htmlspecialchars($pv['expert_tel'] ?? '-') ?></div>
        </td>
      </tr>
    </table>

    <div class="title-box">
      <h5 class="m-0 fw-bold text-uppercase text-dark"><?= htmlspecialchars($pv['type_pv']) ?></h5>
      <div class="small text-muted mt-1">
        N° PV : <strong><?= htmlspecialchars($pv['numero_pv']) ?></strong> | 
        Code Certification : <code><?= htmlspecialchars($pv['code_validation'] ?? 'EXP-BROUILLON') ?></code>
      </div>
    </div>

    <!-- تفاصيل الملف والـ ODS -->
    <div class="table-section-title"><i class="bi bi-folder2 me-1"></i> I. Références du Dossier & de l'Ordre de Service</div>
    <table class="data-table">
      <tr>
        <td style="width: 25%; font-weight: bold; background: #f8fafc;">N° ODS</td>
        <td style="width: 25%;"><?= htmlspecialchars($pv['numero_ods']) ?></td>
        <td style="width: 25%; font-weight: bold; background: #f8fafc;">N° Dossier Sinistre</td>
        <td style="width: 25%;"><?= htmlspecialchars($pv['numero_dossier']) ?></td>
      </tr>
      <tr>
        <td style="font-weight: bold; background: #f8fafc;">Date Sinistre</td>
        <td><?= htmlspecialchars($pv['date_sinistre']) ?></td>
        <td style="font-weight: bold; background: #f8fafc;">Date ODS</td>
        <td><?= htmlspecialchars($pv['date_ods']) ?></td>
      </tr>
      <tr>
        <td style="font-weight: bold; background: #f8fafc;">Assuré / Tiers</td>
        <td><?= htmlspecialchars($pv['assure']) ?></td>
        <td style="font-weight: bold; background: #f8fafc;">Police Assurance</td>
        <td><?= htmlspecialchars(!empty($pv['police']) ? $pv['police'] : '-') ?></td>
      </tr>
    </table>

    <!-- تفاصيل المركبة -->
    <div class="table-section-title"><i class="bi bi-car-front me-1"></i> II. Caractéristiques du Véhicule</div>
    <table class="data-table">
      <tr>
        <td style="width: 25%; font-weight: bold; background: #f8fafc;">Immatriculation</td>
        <td style="width: 25%; font-family: monospace; font-weight: bold;"><?= htmlspecialchars($pv['matricule']) ?></td>
        <td style="width: 25%; font-weight: bold; background: #f8fafc;">Marque & Modèle</td>
        <td style="width: 25%;"><?= htmlspecialchars($pv['marque'] . ' ' . $pv['modele']) ?></td>
      </tr>
      <tr>
        <td style="font-weight: bold; background: #f8fafc;">N° Châssis (VIN)</td>
        <td><code><?= htmlspecialchars(!empty($pv['numero_serie']) ? $pv['numero_serie'] : '-') ?></code></td>
        <td style="font-weight: bold; background: #f8fafc;">Couleur</td>
        <td><?= htmlspecialchars(!empty($pv['couleur_vehicule']) ? $pv['couleur_vehicule'] : '-') ?></td>
      </tr>
      <tr>
        <td style="font-weight: bold; background: #f8fafc;">Date & Lieu Expertise</td>
        <td><?= htmlspecialchars($pv['date_expertise']) ?> (<?= htmlspecialchars($pv['lieu_expertise'] ?? '-') ?>)</td>
        <td style="font-weight: bold; background: #f8fafc;">Valeur Vénale</td>
        <td><?= number_format($pv['valeur_venale'], 2) ?> DA</td>
      </tr>
    </table>

    <!-- تفاصيل الصدمات Chocs -->
    <div class="table-section-title"><i class="bi bi-shield-slash me-1"></i> III. Localisation des Chocs & Détails des Réparations</div>
    <table class="data-table">
      <thead>
        <tr>
          <th style="width: 15%;">Impact</th>
          <th style="width: 35%;">Description</th>
          <th style="width: 35%;">Avis & Détail Réparation</th>
          <th style="width: 15%; text-align: right;">Montant TTC</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($chocs)): ?>
          <?php foreach ($chocs as $c): ?>
            <tr>
              <td><strong><?= htmlspecialchars($c['type_choc']) ?></strong></td>
              <td><?= nl2br(htmlspecialchars($c['description'] ?? '-')) ?></td>
              <td><?= nl2br(htmlspecialchars($c['detail_reparation'] ?? '-')) ?></td>
              <td style="text-align: right; font-weight: bold;"><?= number_format($c['montant_ttc'], 2) ?> DA</td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="4" class="text-center text-muted">Aucun choc renseigné.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>

    <!-- قطع الغيار Fournitures -->
    <?php if (!empty($fournitures)): ?>
      <div class="table-section-title"><i class="bi bi-box-seam me-1"></i> IV. Pièces de Rechange (Fournitures)</div>
      <table class="data-table">
        <thead>
          <tr>
            <th style="width: 5%;">#</th>
            <th style="width: 20%;">Catégorie</th>
            <th>Désignation Article</th>
            <th style="width: 15%; text-align: right;">Prix Unitaire HT</th>
            <th style="width: 10%; text-align: center;">Qté</th>
            <th style="width: 15%; text-align: right;">Total HT</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($fournitures as $idx => $f): ?>
            <tr>
              <td class="text-center text-muted"><?= $idx + 1 ?></td>
              <td><?= htmlspecialchars($f['categorie']) ?></td>
              <td><strong><?= htmlspecialchars($f['article']) ?></strong></td>
              <td style="text-align: right;"><?= number_format($f['prix_unitaire_ht'], 2) ?> DA</td>
              <td style="text-align: center; font-weight: bold;"><?= (int)$f['quantite'] ?></td>
              <td style="text-align: right; font-weight: bold;"><?= number_format($f['total_ht'], 2) ?> DA</td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>

    <!-- التقييم المالي الشامل -->
    <div class="table-section-title"><i class="bi bi-calculator me-1"></i> V. Récapitulation Financière des Réparations</div>
    <table class="data-table">
      <tr>
        <td style="width: 70%; font-weight: bold;">Total Pièces & Fournitures (HT)</td>
        <td style="width: 30%; text-align: right; font-weight: bold;"><?= number_format($pv['total_fournitures_ht'], 2) ?> DA</td>
      </tr>
      <tr>
        <td>Main d'œuvre (Montant HT)</td>
        <td style="text-align: right;"><?= number_format($pv['montant_mo_ht'], 2) ?> DA</td>
      </tr>
      <tr>
        <td>Peinture (Montant HT)</td>
        <td style="text-align: right;"><?= number_format($pv['montant_peinture'], 2) ?> DA</td>
      </tr>
      <tr>
        <td>TVA Fournitures (19%)</td>
        <td style="text-align: right;"><?= number_format($pv['tva_fourniture'], 2) ?> DA</td>
      </tr>
      <tr>
        <td>Immobilisation estimée</td>
        <td style="text-align: right;"><?= (int)$pv['immobilisation_jours'] ?> Jours</td>
      </tr>
      <tr style="background: #f1f5f9; font-size: 0.95rem;">
        <td style="font-weight: 800; color: #0b5777;">MONTANT TOTAL DES DOMMAGES (TTC / MTC)</td>
        <td style="text-align: right; font-weight: 800; color: #0b5777;"><?= number_format($pv['montant_total_ttc'], 2) ?> DA</td>
      </tr>
    </table>

    <!-- التوقيع والخاتم -->
    <div class="row mt-4">
      <div class="col-6">
        <div class="small fw-bold text-muted">Date d'édition : <?= date('d/m/Y') ?></div>
        <div class="small text-muted">Document certifié conforme émis par le système E-Expertise.</div>
      </div>
      <div class="col-6 text-end">
        <div class="fw-bold small">Cachet et Signature de l'Expert Automobile</div>
        <div class="stamp-box">
          Signature & Cachet Officiel<br>
          <?= htmlspecialchars($pv['expert_nom'] . ' ' . $pv['expert_prenom']) ?><br>
          <?= htmlspecialchars($pv['code_validation'] ?? '') ?>
        </div>
      </div>
    </div>

  </div>

</body>
</html>
