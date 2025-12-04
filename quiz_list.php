<?php

require_once 'config.php';
require_once 'functions.php';
require_auth();

$role = $_SESSION['role'];
$error = '';
$quizzes = [];



if ($role !== 'utilisateur') {
    redirect('dashboard.php');
}

try {
    $pdo = connectDB();
    $sql = "
        SELECT 
            q.quiz_id, 
            q.title, 
            q.description, 
            u.username AS author,
            COUNT(t.question_id) AS total_questions
        FROM 
            quizzes q
        JOIN 
            users u ON q.author_id = u.user_id
        LEFT JOIN 
            questions t ON q.quiz_id = t.quiz_id
        WHERE 
            q.is_active = 1 
        GROUP BY 
            q.quiz_id
        HAVING 
            total_questions > 0
        ORDER BY 
            q.created_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $quizzes = $stmt->fetchAll();

} catch (PDOException $e) {
    $error = "Erreur de base de données : impossible de charger la liste des quiz. " . $e->getMessage();
}






?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Disponibles - Quizzeo</title>
    <link rel="stylesheet" href="styles.css?v=final">
</head>
<body class="dashboard-layout">
    <div class="navbar">
        <div class="logo">QUIZZEO | <?= htmlspecialchars(ucfirst($role)); ?></div>
        <nav>
            <a href="dashboard.php">Accueil</a>
            <a href="historique.php">Résultats</a>
            <a href="logout.php">Déconnexion</a>
        </nav>
    </div>

    <div class="main-content">
        <div class="container dashboard-content max-width-800">
            <h1>Quiz Disponibles</h1>
            <p>Sélectionnez un quiz pour commencer.</p>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (empty($quizzes)): ?>
                <p>Aucun quiz actif avec des questions n'est disponible pour le moment.</p>
            <?php else: ?>
                <div class="quiz-list">
                    <?php foreach ($quizzes as $quiz): ?>
                        <div class="quiz-item quiz-card">
                            <h2 class="quiz-title"><?= htmlspecialchars($quiz['title']); ?> (<?= $quiz['total_questions']; ?> questions)</h2>
                            <p><?= htmlspecialchars($quiz['description']); ?></p>
                            <p class="small-text muted">Créé par : <?= htmlspecialchars($quiz['author']); ?></p>
                            <a href="take_quiz.php?quiz_id=<?= $quiz['quiz_id']; ?>" 
                               class="button btn-small mt-10">
                                Commencer le Quiz
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
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