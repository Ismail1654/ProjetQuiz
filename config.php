<?php


$envServer   = getenv('DB_SERVER') ?: getenv('DB_HOST');
$envPort     = getenv('DB_PORT');
$envUser     = getenv('DB_USERNAME');
$envPassword = getenv('DB_PASSWORD');
$envName     = getenv('DB_NAME');


if (!$envServer) {
    if (defined('PHP_OS_FAMILY')) {
        $osFamily = PHP_OS_FAMILY;
    } else {
        $osFamily = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'Windows' : (PHP_OS === 'Darwin' ? 'Darwin' : 'Other');
    }

    if ($osFamily === 'Windows') {
        
        $envServer   = '127.0.0.1';
        $envPort     = $envPort ?: '3306';
        $envUser     = $envUser ?: 'root';
        $envPassword = $envPassword !== false ? $envPassword : '';
    } elseif ($osFamily === 'Darwin') {
       
        $envServer   = '127.0.0.1';
        $envPort     = $envPort ?: '8889';
        $envUser     = $envUser ?: 'root';
        $envPassword = $envPassword !== false ? $envPassword : 'root';
    } else {
        
        $envServer   = '127.0.0.1';
        $envPort     = $envPort ?: '3306';
        $envUser     = $envUser ?: 'root';
        $envPassword = $envPassword !== false ? $envPassword : '';
    }
}


if (!defined('DB_SERVER'))   define('DB_SERVER', $envServer);
if (!defined('DB_PORT'))     define('DB_PORT', $envPort ?: '3306');
if (!defined('DB_USERNAME')) define('DB_USERNAME', $envUser ?: 'root');
if (!defined('DB_PASSWORD')) define('DB_PASSWORD', $envPassword !== false ? $envPassword : '');
if (!defined('DB_NAME'))     define('DB_NAME', $envName ?: 'quizz');

function mask_password_for_display($pwd) {
    if ($pwd === '' || $pwd === null) return '(empty)';
    return str_repeat('*', min(6, strlen($pwd)));
}

function connectDB() {
    $dsn = "mysql:host=" . DB_SERVER . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, $options);
        return $pdo;
    } catch (PDOException $e) {
        $masked = mask_password_for_display(DB_PASSWORD);
        $msg = "<h1 style='color: red; text-align: center;'>ERREUR CONNEXION BDD</h1>";
        $msg .= "<p style='text-align: center;'>Vérifiez votre configuration dans <code>config.php</code> ou vos variables d'environnement.<br>";
        $msg .= "Tentative: " . htmlspecialchars(DB_SERVER) . ":" . htmlspecialchars(DB_PORT) . " / DB=" . htmlspecialchars(DB_NAME) . " / user=" . htmlspecialchars(DB_USERNAME) . " / pwd=" . htmlspecialchars($masked) . "<br>";
        $msg .= "Détails: " . htmlspecialchars($e->getMessage()) . "</p>";
        die($msg);
    }
}
?>
