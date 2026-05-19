<?php
define('APP_NAME',    'Tsingy Rouge Madagascar');
define('APP_VERSION', '1.0.0');
// ⚠️ Adaptez selon votre serveur :
define('APP_URL',     'http://localhost/tsingy_rouge');
define('UPLOAD_DIR',  __DIR__ . '/../uploads/');
define('UPLOAD_URL',  APP_URL . '/uploads/');
define('STOCK_ALERTE_SEUIL', 10);
define('CLIENT_TOP_SEUIL',   300);
date_default_timezone_set('Indian/Antananarivo');
define('DEVISE', 'Ar');