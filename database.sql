-- ==========================================================
-- قاعدة بيانات نظام إدارة الخبرة (E-Expertise)
-- مستخرجة ومطابقة لدليل الاستخدام الرسمي المرفق (assets/guide-application.pdf)
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `e_expertise`
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE `e_expertise`;

-- تعطيل فحص المفاتيح الأجنبية مؤقتاً لتجنب مشاكل الترتيب
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------
-- 1. جدول المستخدمين (utilisateurs)
-- يطابق صفحة تسجيل الدخول وتعديل الحساب (Slides 4, 5, 20)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `utilisateurs`;
CREATE TABLE `utilisateurs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nom` VARCHAR(100) NOT NULL,
    `prenom` VARCHAR(100) NOT NULL,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL COMMENT 'كلمة المرور مخزنة بنص صريح بدون تشفير بناء على طلب المستخدم',
    `specialite` ENUM('Automobile', 'Risque industriel', 'Transport', 'Autre') DEFAULT 'Automobile',
    `soumis_tva` TINYINT(1) DEFAULT 0 COMMENT 'هل الخبير خاضع للرسم على القيمة المضافة TVA',
    `telephone_1` VARCHAR(30) NOT NULL,
    `telephone_2` VARCHAR(30) NULL,
    `role` ENUM('expert') DEFAULT 'expert' COMMENT 'الخبير هو المسؤول الوحيد عن المنصة',
    `doit_changer_mot_de_passe` TINYINT(1) DEFAULT 0,
    `actif` TINYINT(1) DEFAULT 1,
    `date_creation` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `date_maj` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 2. جدول أوامر المهمة ODS (Ordres de Service)
-- واردة من نظام IRIS وتُسند للخبير (Slides 3, 7, 8, 10)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `ods`;
CREATE TABLE `ods` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `numero_ods` VARCHAR(50) NOT NULL UNIQUE COMMENT 'رقم أمر المهمة مثل 16001 20/0000',
    `numero_dossier` VARCHAR(50) NOT NULL COMMENT 'رقم الملف / الحادث مثل 16001 20 1195 0227',
    `date_sinistre` DATE NOT NULL,
    `date_ods` DATE NOT NULL,
    `assure` VARCHAR(150) NOT NULL COMMENT 'اسم المؤمّن له أو الطرف المتضرر',
    `police` VARCHAR(100) NULL COMMENT 'رقم وثيقة التأمين',
    `matricule` VARCHAR(50) NOT NULL COMMENT 'رقم لوحة الترقيم للمركبة',
    `numero_serie` VARCHAR(100) NULL COMMENT 'الرقم التسلسلي في الطراز (N° Série / Châssis)',
    `marque` VARCHAR(100) NOT NULL COMMENT 'العلامة التجارية مثل AUDI, KIA',
    `modele` VARCHAR(100) NOT NULL COMMENT 'الموديل مثل A3',
    `puissance` VARCHAR(50) NULL COMMENT 'القوة الجبائية مثل 7 à 10 CV',
    `carburant` VARCHAR(50) NULL COMMENT 'نوع الوقود: Diesel, Essence...',
    `telephone` VARCHAR(30) NULL,
    `remarque` TEXT NULL,
    `expert_id` INT UNSIGNED NULL COMMENT 'معرف الخبير المكلف بالـ ODS',
    `statut` ENUM('Nouveau', 'En cours', 'Expertise faite', 'Validé', 'Clôturé') DEFAULT 'Nouveau',
    `date_chargement` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_ods_expert` FOREIGN KEY (`expert_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL,
    INDEX `idx_ods_statut` (`statut`),
    INDEX `idx_ods_expert` (`expert_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 3. جدول محاضر المعاينة والخبرة (pv_expertises)
-- يغطي أنواع الـ PV الستة والإدخالات المالية (Slides 9, 10, 11, 14, 18, 21, 22)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `pv_expertises`;
CREATE TABLE `pv_expertises` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ods_id` INT UNSIGNED NOT NULL,
    `expert_id` INT UNSIGNED NOT NULL,
    `numero_pv` VARCHAR(50) NOT NULL UNIQUE,
    `type_pv` ENUM(
        'PV EXPERTISE AUTOMOBILE',
        'PV DE REFORME',
        'PV VOL TOTAL',
        'PV INCENDIE VEHICULE',
        'PV R.A.S',
        'PV CARENCE'
    ) NOT NULL DEFAULT 'PV EXPERTISE AUTOMOBILE',
    `est_additif` TINYINT(1) DEFAULT 0 COMMENT 'هل هو محضر إضافي Additif',
    `date_expertise` DATE NOT NULL,
    `heure_expertise` TIME NULL,
    `lieu_expertise` VARCHAR(255) NULL,
    `couleur_vehicule` VARCHAR(100) NULL,
    `taux_responsabilite` INT DEFAULT 0 COMMENT 'نسبة المسؤولية المقدرة: 0%, 50%, 100%...',
    `valeur_venale` DECIMAL(12, 2) DEFAULT 0.00 COMMENT 'القيمة السوقية للمركبة Valeur Vénale',
    `observation` TEXT NULL,
    -- المبالغ المالية التكميلية (Slide 14)
    `montant_mo_ht` DECIMAL(12, 2) DEFAULT 0.00 COMMENT 'مبلغ اليد العاملة خارج الرسم Main d\'oeuvre HT',
    `immobilisation_jours` INT DEFAULT 0 COMMENT 'أيام التوقف عن السير',
    `montant_peinture` DECIMAL(12, 2) DEFAULT 0.00 COMMENT 'مبلغ الطلاء Peinture',
    `tva_fourniture` DECIMAL(12, 2) DEFAULT 0.00 COMMENT 'رسم القيمة المضافة لقطع الغيار',
    `taux_vetuste` DECIMAL(5, 2) DEFAULT 0.00 COMMENT 'نسبة الاهتلاك / التقادم Vétusté %',
    `total_fournitures_ht` DECIMAL(12, 2) DEFAULT 0.00 COMMENT 'إجمالي قطع الغيار HT',
    `montant_total_ttc` DECIMAL(12, 2) DEFAULT 0.00 COMMENT 'المبلغ الإجمالي مع الرسوم MTC / TTC',
    `total_honoraires` DECIMAL(12, 2) DEFAULT 0.00 COMMENT 'إجمالي أتعاب ومصاريف الخبير',
    -- حالات المحضر والتأكيد (الخبير هو المسؤول الوحيد عن التأكيد والاعتماد)
    `statut` ENUM('Brouillon', 'Validé', 'Clôturé') DEFAULT 'Brouillon',
    `date_validation` DATETIME NULL COMMENT 'تاريخ اعتماد المحضر من قبل الخبير',
    `code_validation` VARCHAR(50) NULL COMMENT 'كود التأكيد الذي يولد تلقائياً مثل EXP119520000003',
    `date_creation` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `date_maj` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_pv_ods` FOREIGN KEY (`ods_id`) REFERENCES `ods` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pv_expert` FOREIGN KEY (`expert_id`) REFERENCES `utilisateurs` (`id`) ON DELETE RESTRICT,
    INDEX `idx_pv_statut` (`statut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 4. جدول الصدمات (chocs)
-- يدعم إضافة صدمة A، B، C، D... مع تفاصيلها (Slides 12, 13)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `chocs`;
CREATE TABLE `chocs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `pv_id` INT UNSIGNED NOT NULL,
    `type_choc` VARCHAR(50) NOT NULL COMMENT 'اسم الصدمة مثل Choc A, Choc B...',
    `description` TEXT NULL COMMENT 'وصف الصدمة',
    `detail_reparation` TEXT NULL COMMENT 'الرأي وتفاصيل التصليح Avis / Détail de réparation',
    `montant_mo_ht` DECIMAL(12, 2) DEFAULT 0.00 COMMENT 'مبلغ اليد العاملة خارج الرسم Main d\'oeuvre HT',
    `immobilisation_jours` INT DEFAULT 0 COMMENT 'أيام التوقف عن السير',
    `montant_peinture` DECIMAL(12, 2) DEFAULT 0.00 COMMENT 'مبلغ الطلاء Peinture',
    `tva_fourniture` DECIMAL(12, 2) DEFAULT 0.00 COMMENT 'رسم القيمة المضافة لقطع الغيار',
    `taux_vetuste` DECIMAL(5, 2) DEFAULT 0.00 COMMENT 'نسبة الاهتلاك / التقادم Vétusté %',
    `total_fournitures_ht` DECIMAL(12, 2) DEFAULT 0.00 COMMENT 'إجمالي قطع الغيار HT',
    `montant_ttc` DECIMAL(12, 2) DEFAULT 0.00 COMMENT 'المبلغ الإجمالي للصدمة مع الرسوم MTC / TTC',
    `date_creation` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_chocs_pv` FOREIGN KEY (`pv_id`) REFERENCES `pv_expertises` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 5. جدول تصنيفات قطع الغيار (categories_pieces)
-- (Slide 13, 14: Liste des catégories: CAPOT, AILES...)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `categories_pieces`;
CREATE TABLE `categories_pieces` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nom` VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 6. جدول قطع الغيار / المواد المستعملة لكل صدمة (fournitures_choc)
-- (Slides 13, 14, 19: Fournitures)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `fournitures_choc`;
CREATE TABLE `fournitures_choc` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `choc_id` INT UNSIGNED NOT NULL,
    `categorie` VARCHAR(100) NOT NULL,
    `article` VARCHAR(150) NOT NULL COMMENT 'اسم القطعة مثل Capot moteur, Aile arrière droit',
    `prix_unitaire_ht` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `quantite` INT NOT NULL DEFAULT 1,
    `total_ht` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `date_creation` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_fournitures_choc` FOREIGN KEY (`choc_id`) REFERENCES `chocs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 7. جدول أنواع مصاريف وأتعاب الخبير (types_frais_honoraires)
-- مطابقة للقائمة المنسدلة في الدليل (Slide 16, 17)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `types_frais_honoraires`;
CREATE TABLE `types_frais_honoraires` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `libelle` VARCHAR(150) NOT NULL UNIQUE,
    `tarif_defaut` DECIMAL(10, 2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 8. جدول أتعاب ومصاريف المحضر (pv_honoraires)
-- يربط كل محضر بأتعاب الخبير المحسوبة (Slides 15, 16, 17, 19, 21)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `pv_honoraires`;
CREATE TABLE `pv_honoraires` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `pv_id` INT UNSIGNED NOT NULL,
    `type_frais_id` INT UNSIGNED NULL,
    `libelle` VARCHAR(150) NOT NULL,
    `nombre` INT NOT NULL DEFAULT 1,
    `montant_unitaire` DECIMAL(10, 2) DEFAULT 0.00,
    `montant_total` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `date_creation` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_honoraires_pv` FOREIGN KEY (`pv_id`) REFERENCES `pv_expertises` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_honoraires_type` FOREIGN KEY (`type_frais_id`) REFERENCES `types_frais_honoraires` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 9. جدول الإشعارات (notifications)
-- مطابقة لمتطلبات إشعارات النظام (Slide 3)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `destinataire_id` INT UNSIGNED NOT NULL,
    `titre` VARCHAR(150) NOT NULL,
    `message` TEXT NOT NULL,
    `est_lu` TINYINT(1) DEFAULT 0,
    `date_creation` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_notif_user` FOREIGN KEY (`destinataire_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إعادة تفعيل فحص المفاتيح الأجنبية
SET FOREIGN_KEY_CHECKS = 1;

-- ==========================================================
-- بيانات أولية قياسية مستخرجة من الدليل لتسهيل التجربة
-- ==========================================================

-- 1. أنواع المصاريف الرسمية للأتعاب (Slide 16)
INSERT INTO `types_frais_honoraires` (`libelle`, `tarif_defaut`) VALUES
('Déplacement véhicule personnel - 40 km', 200.00),
('Déplacement véhicule personnel >= 40 km', 400.00),
('Photos', 40.00),
('Frais de dossier', 150.00),
('Restauration', 500.00),
('Hébergement', 1500.00),
('Honoraire de base expertise', 800.00);

-- 2. تصنيفات قطع الغيار النموذجية (Slide 14)
INSERT INTO `categories_pieces` (`nom`) VALUES
('CAPOT'),
('AILES'),
('PARE-CHOC'),
('OPTIQUES / PHARES'),
('RADIATEUR'),
('PORTIERES'),
('VITRAGE'),
('MECANIQUE'),
('TRAIN ROULANT');

-- 3. حساب خبير السيارات المسؤول الوحيد عن المنصة (كلمة المرور بدون تشفير: password123)
INSERT INTO `utilisateurs` (`id`, `nom`, `prenom`, `username`, `email`, `password`, `specialite`, `soumis_tva`, `telephone_1`, `telephone_2`, `role`, `doit_changer_mot_de_passe`) VALUES
(1, 'YAHYAOUI', 'Karim', 'expert_yahyaoui', 'yahyaoui@allianceassurances.com.dz', 'password123', 'Automobile', 1, '0550123456', '0660123456', 'expert', 0);

-- 4. أوامر مهمة ODS تجريبية مسندة للخبير مباشرة (expert_id = 1)
INSERT INTO `ods` (`numero_ods`, `numero_dossier`, `date_sinistre`, `date_ods`, `assure`, `police`, `matricule`, `numero_serie`, `marque`, `modele`, `puissance`, `carburant`, `telephone`, `expert_id`, `statut`) VALUES
('16001 20/0000', '16001 20 1195 0227', '2020-06-23', '2020-06-24', 'GASMI ABDELAZIZ', '16001 19 1112 0193', '025724-115-16', 'WAUZZZ8V9F1039994', 'AUDI', 'A3', '7 à 10 CV', '2 - Diesel', '0555001122', 1, 'Nouveau'),
('16001 20/0001', '16001 20 1195 0223', '2020-06-23', '2020-06-23', 'BEN ARBIA', '16001 19 1112 0190', '014820-114-16', 'VF33CRHYB8329104', 'PEUGEOT', '208', '5 CV', '1 - Essence', '0555334455', 1, 'Nouveau'),
('16001 20/0002', '16001 20 1195 0237', '2020-06-25', '2020-06-25', 'BIO ORGANIX', '16001 19 1112 0205', '008745-117-16', 'KNADM411ACB128491', 'KIA', 'RIO', '6 CV', '1 - Essence', '0555667788', 1, 'Nouveau');
