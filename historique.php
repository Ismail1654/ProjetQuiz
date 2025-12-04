<?php

require_once 'config.php';
require_once 'functions.php';
 
require_auth();
 
$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$error = '';
$results = [];
 
if (!in_array($role, ['utilisateur', 'ecole', 'entreprise', 'admin'])) {
    redirect('dashboard.php');
}

 
$page_title = ($role === 'utilisateur') ? "Mon Historique de Quiz" : "Historique Global des Résultats";
 
try {
    $pdo = connectDB();
    
    
    $sql = "
        SELECT
            r.score,
            r.max_score,
            q.title AS quiz_title,
            u_quiz_author.username AS author_name,
            u_respondent.username AS respondent_name, /* NOUVEAU: Nom de l'utilisateur qui a répondu */
            r.answered_at
        FROM
            user_responses r
        JOIN
            quizzes q ON r.quiz_id = q.quiz_id
        JOIN
            users u_quiz_author ON q.author_id = u_quiz_author.user_id
        JOIN
            users u_respondent ON r.user_id = u_respondent.user_id /* NOUVEAU: Jointure */
    ";
    
    $where_clauses = [];
    $params = [];
    
    
    if ($role === 'utilisateur') {
        $where_clauses[] = "r.user_id = ?";
        $params[] = $user_id;
    }
    
    
    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(" AND ", $where_clauses);
    }
    
    $sql .= " ORDER BY r.answered_at DESC";
 
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll();
 
} catch (PDOException $e) {
    $error = "Erreur de base de données : impossible de charger l'historique. " . htmlspecialchars($e->getMessage());
}
 
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - Quizzeo</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
 
    <div class="navbar">
        <span class="logo">Quizzeo</span>
        <nav>
            <a href="dashboard.php">Accueil</a>
            <?php if ($role !== 'utilisateur'): ?>
                 <a href="admin_users.php">Utilisateurs</a>
            <?php else: ?>
                 <a href="profile.php">Mon Profil</a>
            <?php endif; ?>
            <a href="logout.php">Déconnexion</a>
        </nav>
    </div>
 
    <div class="main-content">
        <div class="container dashboard-content max-width-1000">
            <h1><?php echo htmlspecialchars($page_title); ?></h1>
 
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
 
            <?php if (empty($results)): ?>
                <p>Aucun résultat de quiz n'a été enregistré pour le moment.</p>
            <?php else: ?>
                <div class="quiz-list">
                <?php foreach ($results as $result):
                    $percentage = $result['max_score'] > 0 ? round(($result['score'] / $result['max_score']) * 100) : 0;
                    
                    $color = match (true) {
                        $percentage >= 70 => 'var(--accent-color)', 
                        $percentage >= 50 => 'var(--secondary-color)', 
                        default => '#C0392B', 
                    };
                ?>
                    <div class="result-item quiz-card">
                        <div>
                            <h3 class="no-margin"><?= htmlspecialchars($result['quiz_title']); ?></h3>
                            <?php if ($role !== 'utilisateur'): ?>
                                <p class="no-margin font-bold small-text">Utilisateur : <?= htmlspecialchars($result['respondent_name']); ?></p>
                            <?php endif; ?>
                            <p class="no-margin small-text muted">Créé par : <?= htmlspecialchars($result['author_name']); ?></p>
                            <p class="no-margin small-text muted">Terminé le : <?= (new DateTime($result['answered_at']))->format('d/m/Y à H:i'); ?></p>
                        </div>
                        <div class="text-right">
                            <p class="no-margin large-score" style="color: <?= $color; ?>;">
                                <?= $result['score']; ?> / <?= $result['max_score']; ?>
                            </p>
                            <p class="no-margin small-text muted"><?= $percentage; ?> %</p>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
                
                <div class="link-text mt-20"><a href="dashboard.php">Retour au Tableau de Bord</a></div>
            <?php endif; ?>
        </div>
    </div>
    
    <footer class="footer">
        &copy; <?= date('Y'); ?> Quizzeo. Tous droits réservés.
    </footer>
</body>
</html>
 
 