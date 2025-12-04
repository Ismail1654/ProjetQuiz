<?php

require_once 'config.php';
require_once 'functions.php';
 
require_auth();
 
$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
 

if (!in_array($role, ['ecole', 'entreprise', 'admin'])) {
    redirect('dashboard.php');
}
 
$error = '';
$rows = [];
 
try {
    $pdo = connectDB();
 
    
    $sql = "
        SELECT ur.user_id, ur.quiz_id, ur.score, ur.max_score, ur.answered_at,
               u.username, u.firstname, u.lastname, q.title AS quiz_title, q.author_id
        FROM user_responses ur
        LEFT JOIN users u ON ur.user_id = u.user_id
        LEFT JOIN quizzes q ON ur.quiz_id = q.quiz_id
        WHERE ( :role = 'admin' ) OR (q.author_id = :user_id)
        ORDER BY q.quiz_id, ur.answered_at DESC
        LIMIT 1000
    ";
 
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':role', $role, PDO::PARAM_STR);
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();
 
} catch (PDOException $e) {
    $error = "Erreur lors du chargement des résultats : " . $e->getMessage();
}
 
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Résultats — Quizzeo</title>
    <link rel="stylesheet" href="styles.css?v=final">
</head>
<body class="dashboard-layout">
    <div class="navbar">
        <div class="logo">QUIZZEO | Résultats</div>
        <nav>
            <a href="dashboard.php">Accueil</a>
            <a href="list_quizzes.php">Mes Quiz</a>
            <a href="logout.php" class="danger-link">Déconnexion</a>
        </nav>
    </div>
 
    <div class="main-content">
        <div class="container max-width-1000 dashboard-content">
            <h1>Résultats des Quiz</h1>
 
 
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
 
            <div class="table-responsive">
                <table class="simple-table quiz_results_table">
                    <thead>
                        <tr>
                            <th>Quiz</th>
                            <th>Participant</th>
                            <th>Score</th>
                            <th>%</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="5" class="muted">Aucun résultat trouvé.</td></tr>
                        <?php else: ?>
                            
                            <?php foreach ($rows as $r):
                                $name = trim(($r['firstname'] . ' ' . $r['lastname'])) ?: $r['username'];
                                $score = (int)$r['score'];
                                $max = (int)$r['max_score'];
                                $pct = ($max > 0) ? round(($score / $max) * 100) : 0;
                            ?>
                            <div class="quiz_results_table">
                                <tr>
                                    <td><?php echo htmlspecialchars($r['quiz_title'] ?? 'Quiz #' . (int)$r['quiz_id']); ?></td>
                                    <td><?php echo htmlspecialchars($name); ?></td>
                                    <td><?php echo $score; ?> / <?php echo $max; ?></td>
                                    <td><?php echo $pct; ?>%</td>
                                    <td><?php echo htmlspecialchars($r['answered_at']); ?></td>
                                </tr>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
 
            <p class="mt-20"><a href="dashboard.php">Retour</a></p>
        </div>
    </div>
 
    <footer class="footer">&copy; <?php echo date('Y'); ?> Quizzeo</footer>
</body>
</html>
 
 