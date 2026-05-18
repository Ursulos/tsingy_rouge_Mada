<?php
// ============================================================
// config/database.php — Connexion PDO
// ============================================================

// ⚠️  ADAPTER vos identifiants MySQL ici :
define('DB_HOST',    'localhost');
define('DB_NAME',    'tsingy_rouge');   // nom de votre base
define('DB_USER',    'root');           // utilisateur MySQL
define('DB_PASS',    '');               // mot de passe (vide pour XAMPP par défaut)
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST
             . ";dbname=" . DB_NAME
             . ";charset=" . DB_CHARSET;

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die('<div style="font-family:sans-serif;padding:40px;color:#c0392b;">
                <h2>⚠️ Connexion base de données impossible</h2>
                <p>Vérifiez <code>config/database.php</code> (DB_USER, DB_PASS, DB_NAME).</p>
                <small>' . htmlspecialchars($e->getMessage()) . '</small>
            </div>');
        }
    }
    return $pdo;
}
