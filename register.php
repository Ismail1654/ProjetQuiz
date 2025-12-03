<?php
require_once 'config.php';
require_once 'functions.php';
 
if (is_logged_in()) {
    redirect('dashboard.php');
}
 
$error = '';
$username = '';
$email = '';
$selected_role = 'utilisateur';
 
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $username = sanitize_input($_POST['username'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $captcha_answer = $_POST['captcha_answer'] ?? '';
 
    
    $allowed_roles = ['utilisateur', 'ecole', 'entreprise'];
    $posted_role = $_POST['role'] ?? 'utilisateur';
    $selected_role = in_array($posted_role, $allowed_roles) ? $posted_role : 'utilisateur';
 
    
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Veuillez remplir tous les champs obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Le format de l'adresse email est invalide.";
    } elseif (strlen($password) < 6) {
        $error = "Le mot de passe doit contenir au moins 6 caractères.";
    } elseif ($password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas.";
    } elseif (!verify_captcha($captcha_answer)) {
        $error = "Réponse au CAPTCHA incorrecte. Veuillez réessayer.";
    } else {
        
        try {
            $pdo = connectDB();
 
            
            $stmt_check = $pdo->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
            $stmt_check->execute([$username, $email]);
            if ($stmt_check->fetch()) {
                $error = "Ce nom d'utilisateur ou cet email est déjà utilisé.";
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
 
                
                $stmt_insert = $pdo->prepare("INSERT INTO users (username, email, password_hash, role, is_active) VALUES (?, ?, ?, ?, 1)");
                $stmt_insert->execute([$username, $email, $password_hash, $selected_role]);
 
                
                redirect('login.php?registration=success');
            }
 
        } catch (PDOException $e) {
            
            $error = "Erreur d'inscription : impossible de créer le compte. " . $e->getMessage();
        }
    }
}
 
 


if (!isset($_SESSION['captcha_operation_display']) || $_SERVER['REQUEST_METHOD'] === 'GET' || !empty($error)) {
    generate_captcha();
}
 
$captcha_operation = $_SESSION['captcha_operation_display'] ?? '??';
 
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Quizzeo</title>
    <link rel="stylesheet" href="styles.css?v=final">
</head>
<body>
    <img src="logo.png" alt="Logo Quizzeo" class="logo-accueil">
 
    <div class="main-content">
        <div class="container max-width-450">
            <h1>Créer un compte <img src="logo.png" alt="Logo Quizzeo" class="logo-accueil3"></h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error); ?></div>
            <?php endif; ?>
 
            <form action="register.php" method="POST">
                <div class="form-group">
                    <label for="username">Nom :</label>
                    <input type="text" id="username" name="username" required value="<?= htmlspecialchars($username); ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Mail :</label>
                    <input type="email" id="email" name="email" required value="<?= htmlspecialchars($email); ?>">
                </div>
 
                <div class="form-group">
                    <label for="password">Mot de passe (minimum 6 caractères) :</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirmer le mot de passe :</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <div class="form-group">
                    <label for="role">Status :</label>
                    <select id="role" name="role" required>
                        <option value="utilisateur" <?= $selected_role === 'utilisateur' ? 'selected' : '' ?>>utilisateur</option>
                        <option value="ecole" <?= $selected_role === 'ecole' ? 'selected' : '' ?>>École</option>
                            <option value="entreprise" <?= $selected_role === 'entreprise' ? 'selected' : '' ?>>Entreprise</option>
                    </select>
                </div>
                
                <div class="captcha-group">
                    <label for="captcha_answer">Captcha : combien font <?= $captcha_operation; ?> ?</label>
                    <input type="number" id="captcha_answer" name="captcha_answer" required placeholder="Réponse">
                </div>
 
                <button type="submit">S'inscrire</button>
            </form>
 
            <div class="link-text">
                  Déjà un compte ? <a href="login.php">Connectez vous !</a>
            </div>
        </div>
    </div>
    
    <footer class="footer">
        &copy; <?= date('Y'); ?> Quizzeo. Tous droits réservés.
    </footer>
</body>
</html>
 