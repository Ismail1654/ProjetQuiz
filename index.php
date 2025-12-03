<?php

require_once 'functions.php';
 
if (is_logged_in()) {
    redirect('dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenue sur QUIZZEO</title>
    <link rel="stylesheet" href="styles.css?v=final">
</head>

<body>
 
    <div class="main-content">
        <div class="container">
            <h1>Bienvenue sur <br></br><img src="logo.png" alt="Logo Quizzeo" class="logo-accueil"></h1>
            <p class="text-center mb-30">
                La plateforme de création et de gestion de quiz.
            </p>
 
            <p class="text-center font-bold mb-10">
                Veuillez vous connecter ou vous inscrire.
            </p>
            
            <button onclick="window.location.href='login.php'">
                Connexion
            </button>
 
            <div class="link-text">
                Vous n'avez pas de compte ? <a href="register.php">Créer un compte</a>
            </div>
        </div>
    </div>
    
    <footer class="footer">
        &copy; <?= date('Y'); ?> Quizzeo. Tous droits réservés.
    </footer>
 
</body>
</html></html>