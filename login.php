<?php

require_once 'config.php';
require_once 'functions.php';
 

if (is_logged_in()) {
    redirect('dashboard.php');
}
 
$error = '';
$registration_success = isset($_GET['registration']) && $_GET['registration'] === 'success';
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $username = sanitize_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
 
    if (empty($username) || empty($password)) {
        $error = "Veuillez entrer votre nom d'utilisateur et votre mot de passe.";
    } else {
        try {
            $pdo = connectDB();
            
            
            $stmt = $pdo->prepare("SELECT user_id, password_hash, role, is_active FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
 
            
            if ($user && password_verify($password, $user['password_hash'])) {
                
                if ($user['is_active'] == 0) {
                    $error = "Votre compte a été désactivé. Contactez un administrateur.";
                } else {
                    
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $username;
                    $_SESSION['role'] = $user['role'];
                    
                    redirect('dashboard.php');
                }
 
            } else {
                $error = "Nom d'utilisateur ou mot de passe incorrect.";
            }
 
        } catch (PDOException $e) {
            
            $error = "Erreur de connexion : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Quizzeo</title>
    <link rel="stylesheet" href="styles.css?v=final">
</head>
<body>
    <img src="logo.png" alt="Logo Quizzeo" class="logo-accueil">
 
    <div class="main-content">
        <div class="container max-width-450">
            <h1>Connexion à <img src="logo.png" alt="Logo Quizzeo" class="logo-accueil3"> </h1>
            
            <?php if ($registration_success): ?>
                 <div class="alert alert-success">Inscription réussie ! Vous pouvez maintenant vous connecter.</div>
            <?php endif; ?>
 
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error); ?></div>
            <?php endif; ?>
 
            <form action="login.php" method="POST">
                <div class="form-group">
                    <label for="username">Nom d'utilisateur :</label>
                    <input type="text" id="username" name="username" required value="<?= htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
 
                <div class="form-group">
                    <label for="password">Mot de passe :</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <br>
 
                <button type="submit">Se connecter</button>
            </form>
 
            <div class="link-text">
                Pas encore de compte ? <a href="register.php">S'inscrire</a>
            </div>
        </div>
    </div>
    
    <footer class="footer">
        &copy; <?= date('Y'); ?> Quizzeo. Tous droits réservés.
    </footer>
</body>
</html>
 