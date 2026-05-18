<?php
// ============================================================
// config/constants.php — Constantes globales
// ============================================================

define('APP_NAME',    'Tsingy Rouge Madagascar');
define('APP_VERSION', '1.0.0');

// ⚠️  ADAPTER selon votre serveur :
// XAMPP Windows : http://localhost/tsingy_rouge
// WAMP Windows  : http://localhost/tsingy_rouge
// Hébergement   : https://votredomaine.mg
define('APP_URL',    'http://localhost/tsingy_rouge');

define('UPLOAD_DIR',  __DIR__ . '/../uploads/');
define('UPLOAD_URL',  APP_URL . '/uploads/');

// Seuil alerte stock faible
define('STOCK_ALERTE_SEUIL', 10);

// Seuil meilleur client (pièces/mois)
define('CLIENT_TOP_SEUIL', 300);

// Timezone Madagascar (EAT = UTC+3)
date_default_timezone_set('Indian/Antananarivo');

// Devise
define('DEVISE', 'Ar'); // Ariary malgache
