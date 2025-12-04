<?php

define('DB_SERVER', '127.0.0.1');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', ''); // mot de passe vide par défaut pour WAMP/XAMPP
define('DB_NAME', 'quizz');

$defaultPorts = [
    '8889' => 'MAMP',
    '3306' => 'WAMP/XAMPP'
];

$detectedPort = isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : '3306';
$port = in_array($detectedPort, array_keys($defaultPorts)) ? $detectedPort : '3306';

if ($port === '8889') {
    define('DB_PASSWORD', 'root');
}

define('DB_PORT', $port);

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
        die("
            <h1 style='color: red; text-align: center;'>ERREUR CONNEXION BDD</h1>
            <p style='text-align: center;'>Vérifiez votre configuration dans config.php.<br>Détails: " . htmlspecialchars($e->getMessage()) . "</p>
        ");
    }
}
?>