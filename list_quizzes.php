<?php

require_once 'config.php';
require_once 'functions.php';
require_auth();

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$error = '';
$quizzes = [];
$success = '';



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_quiz') {
    $posted_token = $_POST['csrf_token'] ?? null;
    if (!verify_csrf_token($posted_token)) {
        $error = "Jeton CSRF invalide. Opération annulée.";
    } else {
        $del_quiz_id = isset($_POST['quiz_id']) ? (int)$_POST['quiz_id'] : 0;
        if ($del_quiz_id <= 0) {
            $error = "Identifiant de quiz invalide.";
        } else {
            try {
                $pdo = connectDB();
                $pdo->beginTransaction();

                
                $stmt = $pdo->prepare("DELETE FROM questions WHERE quiz_id = ?");
                $stmt->execute([$del_quiz_id]);
                $stmt = $pdo->prepare("DELETE FROM user_responses WHERE quiz_id = ?");
                $stmt->execute([$del_quiz_id]);

                
                if ($role === 'admin') {
                    $stmt = $pdo->prepare("DELETE FROM quizzes WHERE quiz_id = ?");
                    $stmt->execute([$del_quiz_id]);
                } else {
                    $stmt = $pdo->prepare("DELETE FROM quizzes WHERE quiz_id = ? AND author_id = ?");
                    $stmt->execute([$del_quiz_id, $user_id]);
                }

                if ($pdo->commit()) {
                    $success = "Quiz supprimé avec succès.";
                } else {
                    $error = "Impossible de supprimer le quiz.";
                }

            } catch (PDOException $e) {
                if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
                $error = "Erreur lors de la suppression : " . $e->getMessage();
            }
        }
    }
}




if (!in_array($role, ['ecole', 'entreprise', 'admin'])) {
    redirect('dashboard.php');
}

try {
    $pdo = connectDB();
    $sql = "
        SELECT 
            q.quiz_id,
            q.author_id,
            q.title,
            q.description,
            q.is_active,
            q.created_at,
            COUNT(t.question_id) AS total_questions
        FROM 
            quizzes q
        LEFT JOIN 
            questions t ON q.quiz_id = t.quiz_id
        WHERE 
            (:role = 'admin') 
            OR (q.author_id = :user_id)
        GROUP BY 
            q.quiz_id
        ORDER BY 
            q.created_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':role', $role, PDO::PARAM_STR);
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);

    $stmt->execute();
    $quizzes = $stmt->fetchAll();

} catch (PDOException $e) {
    $error = "Erreur de base de données : impossible de charger les quiz. " . $e->getMessage();
}
?>



<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Quiz - Quizzeo</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="dashboard-layout">

    <div class="navbar">
        <div class="logo">QUIZZEO | <?= htmlspecialchars(ucfirst($role)) ?></div>
        <nav>
            <a href="dashboard.php">Accueil</a>
            <a href="create_quiz.php">Créer</a>
            <a href="logout.php">Déconnexion</a>
        </nav>
    </div>

    <div class="container dashboard-content max-width-1000">
        <h1>Les Quizzeos</h1>
        <p>Gérez les questions et le statut de vos quiz.</p>

        <a href="create_quiz.php" class="button width-auto inline-block mb-20">
            + Créer un nouveau Quiz
        </a>



        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>



        <?php if (empty($quizzes)): ?>
            <p>Vous n'avez créé aucun quiz.</p>
        <?php else: ?>
            <div class="quiz-list">

                <?php foreach ($quizzes as $quiz): ?>
                    <div class="quiz-item">

                        <h2><?= htmlspecialchars($quiz['title']) ?></h2>



                        <span class="status-tag <?= $quiz['is_active'] ? 'status-active' : 'status-inactive' ?>">
                            Statut : <?= $quiz['is_active'] ? 'Actif' : 'Désactivé' ?>
                        </span>



                        <p><?= htmlspecialchars($quiz['description']) ?></p>
                        <p>Nombre de questions : <strong><?= $quiz['total_questions'] ?></strong></p>

                        <div class="quiz-actions">
                                     <a href="manage_questions.php?quiz_id=<?= $quiz['quiz_id'] ?>"
                                         class="button width-auto">
                                Gérer les Questions (<?= $quiz['total_questions'] ?>)
                            </a>

                                     <a href="create_quiz.php?edit_id=<?= $quiz['quiz_id'] ?>"
                                         class="button secondary-action secondary-bg">
                                Modifier
                            </a>

                            <?php if ($role === 'admin' || $quiz['author_id'] == $user_id): ?>
                                <form method="POST" class="inline-form ml-10" onsubmit="return confirm('Supprimer ce quiz ? Cette action est irréversible.');">
                                    <?= csrf_input_field() ?>
                                    <input type="hidden" name="action" value="delete_quiz">
                                    <input type="hidden" name="quiz_id" value="<?= (int)$quiz['quiz_id'] ?>">
                                    <button type="submit" class="button danger">Supprimer</button>
                                </form>
                            <?php endif; ?>
                            
                            <?php if (!$quiz['is_active'] && ($role === 'admin' || $quiz['author_id'] == $user_id)): ?>
                                <a href="quiz_results.php?quiz_id=<?= (int)$quiz['quiz_id'] ?>" class="button secondary-action ml-10">Voir les résultats</a>
                            <?php endif; ?>
                        </div>

                    </div>
                <?php endforeach; ?>

            </div>
        <?php endif; ?>

    </div>

</body>
</html>

<footer class="footer">&copy; <?= date('Y') ?> Quizzeo</footer>
