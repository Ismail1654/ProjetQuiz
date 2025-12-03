<?php
// config.php - Configuration de la Base de Données (BDD) - SIMPLIFIÉ

// Constantes de connexion (À ADAPTER si vous n'êtes pas sur MAMP)
define('DB_SERVER', '127.0.0.1');
define('DB_USERNAME', 'root');    
define('DB_PASSWORD', 'root');    
define('DB_NAME', 'quizz');       
define('DB_PORT', '8889');        

/**
 * Fonction de connexion sécurisée utilisant PDO
 * @return PDO L'objet PDO pour interagir avec la base de données.
 */
function connectDB() {
    $dsn = "mysql:host=" . DB_SERVER . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4"; 
    $options = [
        // Mode d'erreur pour les exceptions (utile pour le débogage)
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, 
        // Mode de récupération par défaut : tableau associatif (clé/valeur)
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,      
        // Désactiver l'émulation des requêtes (meilleure sécurité)
        PDO::ATTR_EMULATE_PREPARES   => false,                 
    ];

    try {
        $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, $options);
        return $pdo;
    } catch (PDOException $e) {
        // Laisse un message d'erreur de BDD visible pour le débutant
        die("\n            <h1 style='color: red; text-align: center;'>🛑 ERREUR DE CONNEXION BDD</h1>\n            <p style='text-align: center;'>Vérifiez votre configuration dans config.php.<br>Détails: " . htmlspecialchars($e->getMessage()) . "</p>\n        ");
    }
}
?>