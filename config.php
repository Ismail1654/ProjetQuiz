<?php

define('DB_SERVER', '127.0.0.1');
define('DB_USERNAME', 'root');    
define('DB_PASSWORD', 'root');    // j'utilise mamp donc mdp par defaut 'root', sinon ne rien mettre.
define('DB_NAME', 'quizz');       
define('DB_PORT', '8889');        


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
        die("\n            <h1 style='color: red; text-align: center;'>ERREUR CONNEXION BDD</h1>\n            <p style='text-align: center;'>Vérifiez votre configuration dans config.php.<br>Détails: " . htmlspecialchars($e->getMessage()) . "</p>\n        ");
    }
}
?>