<?php
/**
 * config/db.php
 * ملف الاتصال بقاعدة البيانات باستخدام PDO
 * خاص بمشروع بوابة إدارة الخبرة (E-Expertise)
 */

// إعدادات الاتصال بقاعدة البيانات (يمكن تعديلها حسب بيئة العمل الخاصة بك في XAMPP/LAMPP)
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'e_expertise');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

try {
    // سلسلة الاتصال DSN
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

    // خيارات PDO لضمان الأمان والأداء العالي
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // تفعيل رمي الاستثناءات عند حدوث أخطاء SQL
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // إرجاع النتائج كمصفوفات ترابطية تلقائياً
        PDO::ATTR_EMULATE_PREPARES   => false,                  // تعطيل المحاكاة واستخدام الاستعلامات المحضرة الحقيقية للحماية من SQL Injection
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET // ضمان تشفير النصوص باللغة العربية والفرنسية بشكل صحيح
    ];

    // إنشاء كائن الاتصال
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

    // التحقق التلقائي من تحديثات الجداول وحقول الصدمات والأتعاب لدعم التشغيل الفوري
    try {
        $tableExists = $pdo->query("SHOW TABLES LIKE 'chocs'")->fetch();
        if ($tableExists) {
            $colCheck = $pdo->query("SHOW COLUMNS FROM `chocs` LIKE 'montant_mo_ht'")->fetch();
            if (!$colCheck) {
                $pdo->exec("ALTER TABLE `chocs` 
                    ADD COLUMN `montant_mo_ht` DECIMAL(12, 2) DEFAULT 0.00 AFTER `detail_reparation`,
                    ADD COLUMN `immobilisation_jours` INT DEFAULT 0 AFTER `montant_mo_ht`,
                    ADD COLUMN `montant_peinture` DECIMAL(12, 2) DEFAULT 0.00 AFTER `immobilisation_jours`,
                    ADD COLUMN `tva_fourniture` DECIMAL(12, 2) DEFAULT 0.00 AFTER `montant_peinture`,
                    ADD COLUMN `taux_vetuste` DECIMAL(5, 2) DEFAULT 0.00 AFTER `tva_fourniture`,
                    ADD COLUMN `total_fournitures_ht` DECIMAL(12, 2) DEFAULT 0.00 AFTER `taux_vetuste`
                ");
            }
        }
        $pvTableExists = $pdo->query("SHOW TABLES LIKE 'pv_expertises'")->fetch();
        if ($pvTableExists) {
            $colPvCheck = $pdo->query("SHOW COLUMNS FROM `pv_expertises` LIKE 'total_honoraires'")->fetch();
            if (!$colPvCheck) {
                $pdo->exec("ALTER TABLE `pv_expertises` ADD COLUMN `total_honoraires` DECIMAL(12, 2) DEFAULT 0.00 AFTER `montant_total_ttc`");
            }
        }
    } catch (Exception $migrationEx) {
        // تجاوز بهدوء في حال كان الاستعلام الأولي قيد الإنشاء
    }

} catch (PDOException $e) {
    // تسجيل الخطأ أو عرضه بطريقة آمنة
    // في بيئة التطوير، يمكنك عرض تفاصيل الخطأ:
    die("فشل الاتصال بقاعدة البيانات: " . $e->getMessage());
    
    // في بيئة الإنتاج الفعلية يُفضل تسجيل الخطأ وعرض رسالة ودية للمستخدم:
    // error_log($e->getMessage());
    // die("عذراً، حدث خطأ أثناء الاتصال بالخادم. يرجى المحاولة لاحقاً.");
}
