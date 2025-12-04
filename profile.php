<?php
 
require_once 'config.php';
require_once 'functions.php';
 
require_auth();
 
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$error = '';
$success = '';
 
$email = '';
$firstname = '';
$lastname = '';
 
 
try {
    $pdo = connectDB();
    $stmt = $pdo->prepare("SELECT username, email, firstname, lastname FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
 
    if ($user) {
        $username = $user['username'];
        $email = $user['email'];
        $firstname = $user['firstname'] ?? ''; 
        $lastname = $user['lastname'] ?? '';
    } else {
        $error = "Utilisateur introuvable. Veuillez vous reconnecter.";
        logout();
    }
 
} catch (PDOException $e) {
    $error = "Erreur de base de données : impossible de charger le profil. " . $e->getMessage();
}
 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    
    $new_username = sanitize_input($_POST['username'] ?? $username);
    $new_email = sanitize_input($_POST['email'] ?? $email);
    $new_firstname = sanitize_input($_POST['firstname'] ?? $firstname);
    $new_lastname = sanitize_input($_POST['lastname'] ?? $lastname);
    $new_password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $username = $new_username;
    $email = $new_email;
    $firstname = $new_firstname;
    $lastname = $new_lastname;
 
    if (empty($new_username) || empty($new_email)) {
        $error = "Le nom d'utilisateur et l'email sont obligatoires.";
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Le format de l'adresse email est invalide.";
    } elseif (!empty($new_password) && $new_password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas.";
    } elseif (!empty($new_password) && strlen($new_password) < 6) {
        $error = "Le mot de passe doit contenir au moins 6 caractères.";
    } else {

        try {
            $stmt_check = $pdo->prepare("SELECT user_id FROM users WHERE (username = ? OR email = ?) AND user_id != ?");
            $stmt_check->execute([$new_username, $new_email, $user_id]);
            
            if ($stmt_check->fetch()) {
                $error = "Ce nom d'utilisateur ou cet email est déjà utilisé par un autre compte.";
            } else {
                $sql_update = "UPDATE users SET username = ?, email = ?, firstname = ?, lastname = ?, updated_at = NOW()";
                $update_params = [$new_username, $new_email, $new_firstname, $new_lastname];
 
                if (!empty($new_password)) {
                    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $sql_update .= ", password_hash = ?";
                    $update_params[] = $password_hash;
                }
                
                $sql_update .= " WHERE user_id = ?";
                $update_params[] = $user_id;
 
                $stmt_update = $pdo->prepare($sql_update);
                $stmt_update->execute($update_params);
 
                $_SESSION['username'] = $new_username;
                
                $success = "Votre profil a été mis à jour avec succès !";
            }
 
        } catch (PDOException $e) {
            $error = "Erreur lors de la mise à jour du profil: " . $e->getMessage();
        }
    }
}
?>





<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - Quizzeo</title>
    <link rel="stylesheet" href="styles.css?v=final">
</head>
<body class="dashboard-layout">
    <div class="navbar">
        <div class="logo">QUIZZEO | <?= htmlspecialchars(ucfirst($role)); ?></div>
        <nav>
            <a href="dashboard.php">Accueil</a>
            <?php if ($role === 'utilisateur'): ?>
                <a href="quiz_list.php">Quiz</a>
                <a href="historique.php">Résultats</a>
            <?php elseif (in_array($role, ['ecole', 'entreprise', 'admin'])): ?>
                 <a href="admin_manage_quizzes.php">Gérer les Quiz</a>
                 <a href="admin_users.php">Gérer les Utilisateurs</a>
            <?php endif; ?>
            <a href="logout.php" class="danger-link">Déconnexion</a>
        </nav>
    </div>
 
    <div class="main-content">
        <div class="container dashboard-content max-width-600">
            <h1>Mon Profil (Rôle : <?= htmlspecialchars(ucfirst($role)); ?>)</h1>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= $error; ?></div>
            <?php endif; ?>
 
            <form action="profile.php" method="POST">
                
                <div class="form-group">
                    <label for="username">Nom d'utilisateur :</label>
                    <input type="text" id="username" name="username" required value="<?= htmlspecialchars($username); ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email :</label>
                    <input type="email" id="email" name="email" required value="<?= htmlspecialchars($email); ?>">
                </div>
 
                <div class="form-group">
                    <label for="firstname">Prénom :</label>
                    <input type="text" id="firstname" name="firstname" value="<?= htmlspecialchars($firstname); ?>">
                </div>
 
                <div class="form-group">
                    <label for="lastname">Nom :</label>
                    <input type="text" id="lastname" name="lastname" value="<?= htmlspecialchars($lastname); ?>">
                </div>
                
                <hr>
                
                <h2>Changer de Mot de Passe </h2>
                <p class="small-text muted">Laissez vide si vous ne souhaitez pas le modifier.</p>
 
                <div class="form-group">
                    <label for="password">Nouveau Mot de Passe (min. 6 caractères) :</label>
                    <input type="password" id="password" name="password">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirmer le Mot de Passe :</label>
                    <input type="password" id="confirm_password" name="confirm_password">
                </div>
 
                <button type="submit" class="button">Enregistrer les Modifications</button>
            </form>
 
            <div class="link-text">
                <a href="dashboard.php">Retour au Tableau de Bord</a>
            </div>
        </div>
    </div>
    
    <footer class="footer">
        &copy; <?= date('Y'); ?> Quizzeo. Tous droits réservés.
    </footer>
</body>
</html>