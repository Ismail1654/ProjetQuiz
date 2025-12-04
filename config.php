<?php
// 🔥 IMPORTANT : démarrer la session AVANT tout
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$envServer   = getenv('DB_SERVER') ?: getenv('DB_HOST');
$envPort     = getenv('DB_PORT');
$envUser     = getenv('DB_USERNAME');
$envPassword = getenv('DB_PASSWORD');
$envName     = getenv('DB_NAME');

if (!$envServer) {
    if (defined('PHP_OS_FAMILY')) {
        $osFamily = PHP_OS_FAMILY;
    } else {
        $osFamily = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'Windows' :
                    (PHP_OS === 'Darwin' ? 'Darwin' : 'Other');
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
        return new PDO($dsn, DB_USERNAME, DB_PASSWORD, $options);
    } catch (PDOException $e) {
        $masked = mask_password_for_display(DB_PASSWORD);
        die(
            "<h1 style='color:red;text-align:center;'>ERREUR CONNEXION BDD</h1>
             Tentative : " . htmlspecialchars(DB_SERVER) . ":" . htmlspecialchars(DB_PORT) .
             " / DB=" . htmlspecialchars(DB_NAME) .
             " / user=" . htmlspecialchars(DB_USERNAME) .
             " / pwd=" . htmlspecialchars($masked) .
             "<br>Erreur : " . htmlspecialchars($e->getMessage())
        );
    }
}
?>
