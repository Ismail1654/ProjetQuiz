<?php

require_once 'config.php'; 
require_once 'functions.php';
 

require_auth();
 
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$username = $_SESSION['username'];


$display_name_in_navbar = ucfirst($role); 
$welcome_name = htmlspecialchars($username); 
 

try {
    $pdo = connectDB();
    
    
    $stmt = $pdo->prepare("SELECT firstname, lastname FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user_details = $stmt->fetch();
 
    if ($user_details) {
        $firstname = trim($user_details['firstname'] ?? '');
        $lastname = trim($user_details['lastname'] ?? '');
 
        
        if (!empty($firstname) || !empty($lastname)) {
            
            $full_name = htmlspecialchars(trim("$firstname $lastname"));
            
            $display_name_in_navbar = $full_name;
            $welcome_name = $full_name; 
        }
    }
 
} catch (PDOException $e) {
    
    error_log("Erreur BDD au chargement du dashboard: " . $e->getMessage());
}

$page_title = "Tableau de Bord - " . ucfirst($role);
$content = '';
$primary_action = '';
$secondary_action = '';
$tertiary_action = '';
 

$quaternary_action = '';
 
 
switch ($role) {
    case 'admin':
        $page_title = "Tableau de Bord Administrateur";
        $content = "
            <p>Bienvenue, <span style='font-weight: bold;'>{$welcome_name}</span>. Vous avez un contrôle total sur la plateforme.</p>
            <p style='margin-bottom: 20px;'>Gérez les utilisateurs et modifiez tous les quiz.</p>
        ";
        $primary_action = "<button onclick=\"window.location.href='admin_users.php'\" class=\"button-primary\">Gérer les Utilisateurs</button>";
        $secondary_action = "<button onclick=\"window.location.href='admin_manage_quizzes.php'\" class=\"button\">Gérer les Quiz (Admin)</button>";
        
        $tertiary_action = "<button onclick=\"window.location.href='historique.php'\" class=\"button-secondary\">Historique Global</button>";
        break;
 
    case 'entreprise':
    case 'ecole':
        $page_title = "Tableau de Bord " . ucfirst($role);
        $content = "
            <p>Bienvenue, <span style='font-weight: bold;'>{$welcome_name}</span>. Gérez vos quiz et vos utilisateurs.</p>
            <p style='margin-bottom: 20px;'>Créez, modifiez et partagez vos contenus de formation ou d'évaluation.</p>
        ";
        $primary_action = "<button onclick=\"window.location.href='create_quiz.php'\" class=\"button-primary\">Créer un nouveau Quiz</button>";
        $secondary_action = "<button onclick=\"window.location.href='list_quizzes.php'\" class=\"button\">Gérer Mes Quiz</button>";
        
        $tertiary_action = "<button onclick=\"window.location.href='admin_users.php'\" class=\"button-secondary\">Gérer les Utilisateurs</button>";
        
        $quaternary_action = "<button onclick=\"window.location.href='all_results.php'\" class=\"button\">Voir les résultats</button>";
        break;
 
    case 'utilisateur':
        
        $stats = ['quiz_fait' => 0, 'score_moyen' => 0];
        try {
            
 
        } catch (PDOException $e) {
            error_log("Erreur BDD stats utilisateur: " . $e->getMessage());
        }
 
        $page_title = "Tableau de Bord Utilisateur";
        $content = "
            <p>Bienvenue sur votre espace, <span style='font-weight: bold;'>{$welcome_name}</span>. Prêt pour un nouveau quiz ?</p>
            <div style='display: flex; gap: 20px; margin-top: 20px; justify-content: center; flex-wrap: wrap;'>
               
            </div>
            <p style='margin-top: 30px; text-align: center;'>Explorez la liste des quiz ou consultez vos résultats précédents.</p>
        ";
        $primary_action = "<button onclick=\"window.location.href='quiz_list.php'\" class=\"button-primary\">Faire un Quiz</button>";
        $secondary_action = "<button onclick=\"window.location.href='historique.php'\" class=\"button-secondary\">Voir Mes Résultats</button>";
        $tertiary_action = "<button onclick=\"window.location.href='profile.php'\" class=\"button\">Modifier Mon Profil</button>";
        break;
    default:
        $content = "<h2 style='color: red;'>Rôle inconnu. Veuillez contacter l'administrateur.</h2>";
        break;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="styles.css?v=final">
</head>
<body class="dashboard-layout" data-role="<?= htmlspecialchars($role); ?>">
 
    <div class="navbar">
        <div class="logo"><img src="logo2.png" alt="Logo Quizzeo" class="logo-accueil">| <?php echo $welcome_name; ?></div>
        <nav>
            <a href="dashboard.php">Accueil</a>
            <?php if ($role === 'utilisateur'): ?>
                <a href="quiz_list.php">Faire un Quiz</a>
                <a href="historique.php">Résultats</a>
                <a href="profile.php">Mon Profil</a>
            <?php elseif ($role === 'ecole' || $role === 'entreprise'): ?>
                 <a href="list_quizzes.php">Mes Quiz</a>
                 <a href="admin_users.php">Utilisateurs</a>
                 <a href="all_results.php">Résultats</a>
            <?php elseif ($role === 'admin'): ?>
                 <a href="admin_users.php">Utilisateurs</a>
                 <a href="admin_manage_quizzes.php">Gérer les Quiz</a>
                 <a href="historique.php">Historique</a>
            <?php endif; ?>
            <a href="logout.php">Déconnexion</a>
        </nav>
    </div>
 
    <div class="main-content">
        <div class="container dashboard-content">
            <h1><?php echo htmlspecialchars($page_title); ?></h1>
 
            <?php echo $content; ?>
            <div class="action-row">
                <?php echo $primary_action; ?>
                <?php echo $secondary_action; ?>
                <?php echo $tertiary_action; ?>
                <?php echo $quaternary_action; ?>
            </div>
        </div>
    </div>
    
    <footer class="footer">
        &copy; <?php echo date('Y'); ?> Quizzeo. Tous droits réservés.
    </footer>
 
</body>
</html>
 