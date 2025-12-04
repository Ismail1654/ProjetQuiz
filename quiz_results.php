<?php

require_once 'config.php';
require_once 'functions.php';
 
require_auth();
 
$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
 
$quiz_id = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;
if ($quiz_id <= 0) redirect('list_quizzes.php');
 
$error = '';
$quiz = null;
$results = [];
 
try {
    $pdo = connectDB();
 

    $stmt = $pdo->prepare("SELECT q.quiz_id, q.title, q.author_id, q.is_active, au.role AS author_role FROM quizzes q LEFT JOIN users au ON q.author_id = au.user_id WHERE q.quiz_id = ?");
    $stmt->execute([$quiz_id]);
    $quiz = $stmt->fetch();
 
    if (!$quiz) {
        $error = "Quiz introuvable.";
        
    } else {
 
        
        $allowed = false;
        if ($role === 'admin') $allowed = true;
 
        if ($quiz['author_id'] == $user_id) $allowed = true;
 
        if ($role === 'entreprise' && isset($quiz['author_role']) && $quiz['author_role'] === 'ecole') $allowed = true;
 
 
 
        if (! $allowed) {
            $error = "Vous n'êtes pas autorisé à voir les résultats de ce quiz.";
 
 
        } else {
        
 
            $sql = "
                SELECT ur.user_id, ur.score, ur.max_score, ur.answered_at, u.username, u.firstname, u.lastname
                FROM user_responses ur
                LEFT JOIN users u ON ur.user_id = u.user_id
                WHERE ur.quiz_id = :quiz_id
                ORDER BY ur.answered_at DESC
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':quiz_id' => $quiz_id]);
            $results = $stmt->fetchAll();
        }
    }
 
} catch (PDOException $e) {
    $error = "Erreur DB: " . $e->getMessage();
}
 
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résultats - <?= htmlspecialchars($quiz['title'] ?? 'Quiz') ?></title>
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
        <div class="container max-width-900 dashboard-content">
            <h1>Résultats pour : <?= htmlspecialchars($quiz['title'] ?? 'Quiz') ?></h1>
 
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php else: ?>
 
                <?php if (empty($results)): ?>
                    <div class="alert alert-info">Aucun participant n'a encore répondu à ce quiz.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="simple-table">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Score</th>
                                    <th>%</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($results as $r):
                                $name = trim(($r['firstname'] . ' ' . $r['lastname'])) ?: $r['username'];
                                $score = (int)$r['score'];
                                $max = (int)$r['max_score'];
                                $pct = ($max > 0) ? round(($score / $max) * 100) : 0;
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($name); ?></td>
                                    <td><?php echo $score; ?> / <?php echo $max; ?></td>
                                    <td><?php echo $pct; ?>%</td>
                                    <td><?php echo htmlspecialchars($r['answered_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
 
            <?php endif; ?>
 
            <p class="mt-20"><a href="list_quizzes.php">Retour</a></p>
        </div>
    </div>
 
    <footer class="footer">&copy; <?= date('Y') ?> Quizzeo</footer>
</body>
</html>
 
 