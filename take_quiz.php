<?php

require_once 'config.php';
require_once 'functions.php';
 
require_auth();
 
$quiz_id = (int) ($_GET['quiz_id'] ?? 0);
$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
 
$error = '';
$quiz_data = null;
$questions = [];
$score = null; 
$max_score = 0;
$quiz_title = "Quiz ID: " . htmlspecialchars($quiz_id);
$success = '';
 
if ($quiz_id <= 0) {
    redirect('quiz_list.php');
}
 
try {
    $pdo = connectDB();
 
    $stmt = $pdo->prepare("SELECT title, description, is_active FROM quizzes WHERE quiz_id = ?");
    $stmt->execute([$quiz_id]);
    $quiz_data = $stmt->fetch();
 
    if (!$quiz_data || $quiz_data['is_active'] == 0) {
        $error = "Ce quiz est introuvable ou n'est plus actif.";
    } else {
        $quiz_title = $quiz_data['title'];
        
        try {
            $stmt_questions = $pdo->prepare("SELECT question_id, texte_question, COALESCE(options_json, '[]') AS options_combined, reponse_correcte, COALESCE(points,1) AS points FROM questions WHERE quiz_id = ? ORDER BY question_id ASC");
            $stmt_questions->execute([$quiz_id]);
            $questions = $stmt_questions->fetchAll();
        } catch (PDOException $e) {

            if ($e->getCode() === '42S22' || stripos($e->getMessage(), "Unknown column 'points'") !== false) {

                $stmt_questions = $pdo->prepare("SELECT question_id, texte_question, COALESCE(options_json, '[]') AS options_combined, reponse_correcte FROM questions WHERE quiz_id = ? ORDER BY question_id ASC");
                $stmt_questions->execute([$quiz_id]);
                $questions = $stmt_questions->fetchAll();

                foreach ($questions as &$qtmp) { $qtmp['points'] = 1; }
                unset($qtmp);
            } else {
                throw $e; 
            }
        }
 
        $total_questions = count($questions);
        $max_score = 0;
        foreach ($questions as $qtmp) { $max_score += (int)($qtmp['points'] ?? 1); }
    }
    
} catch (PDOException $e) {
    $error = "Erreur de base de données lors du chargement du quiz: " . $e->getMessage();
}
 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error && isset($total_questions) && $total_questions > 0) {
 
    $user_answers = $_POST['answer'] ?? [];
    $current_score = 0;
    
    if (count($user_answers) !== $total_questions) {
        $error = "Veuillez répondre à toutes les questions avant de soumettre.";
    } else {
        $current_score = 0;
        $computed_max = 0;
        foreach ($questions as $q) {
            $q_id = $q['question_id'];
            $q_points = (int)($q['points'] ?? 1);
            $computed_max += $q_points;
            $user_response_index = $user_answers[$q_id] ?? null;
            
            $correct_response_index = (int) $q['reponse_correcte'];
 
            if (isset($user_answers[$q_id]) && is_numeric($user_response_index) && (int)$user_response_index === $correct_response_index) {
                $current_score += $q_points;
            }
        }
        
        try {
            $pdo->beginTransaction();
 
            $stmt_insert = $pdo->prepare("INSERT INTO user_responses (user_id, quiz_id, score, max_score, answered_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt_insert->execute([$user_id, $quiz_id, $current_score, $computed_max]);
 
            $pdo->commit();
            $score = $current_score;
            $max_score = $computed_max;
            
        } catch (PDOException $e) {
            if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
            $error = "Erreur lors de l'enregistrement de votre score: " . $e->getMessage();
        }
    }
} 
?>






<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($quiz_title); ?> - Quizzeo</title>
    <link rel="stylesheet" href="styles.css?v=final">
</head>
<body class="dashboard-layout">
    <div class="navbar">
        <div class="logo">QUIZZEO | <?= htmlspecialchars(ucfirst($role)); ?></div>
        <nav>
            <a href="dashboard.php">Accueil</a>
            <a href="quiz_list.php">Quiz</a>
            <a href="historique.php">Résultats</a>
            <a href="logout.php" class="danger-link">Déconnexion</a>
        </nav>
    </div>
 
    <div class="main-content">
        <div class="container dashboard-content max-width-800">
            <h1><?= htmlspecialchars($quiz_title); ?></h1>
            <p class="quiz-description"><?= htmlspecialchars($quiz_data['description'] ?? ''); ?></p>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= $error; ?></div>
            <?php endif; ?>
 
            <?php if ($score !== null):
                $percentage = $max_score > 0 ? round(($score / $max_score) * 100) : 0;
                $message = $percentage >= 70 ? 'Félicitations ! Excellent score !' : ($percentage >= 50 ? 'Bon résultat ! Vous avez réussi la majorité.' : 'Vous ferez mieux la prochaine fois.');
                $color = $percentage >= 70 ? 'var(--accent-color)' : ($percentage >= 50 ? 'var(--secondary-color)' : '#C0392B');
            ?>
                <div class="result-display" style="border-color: <?= $color; ?>;">
                    <h2>Quiz Terminé !</h2>
                    <p class="large-score" style="color: <?= $color; ?>;">
                        Votre Score : <?= $score; ?> / <?= $max_score; ?>
                    </p>
                    <p class="large-text" style="color: <?= $color; ?>;">
                        (<?= $percentage; ?> %)
                    </p>
                    <p><?= $message; ?></p>
                    <div class="mt-20">
                        <a href="historique.php" class="button">Voir tous mes résultats</a>
                    </div>
                </div>
 
            <?php elseif (empty($questions) && $quiz_data): ?>
                 <div class="alert alert-info">Ce quiz n'a pas encore de questions.</div>
 
            <?php else: ?>
                <form action="take_quiz.php?quiz_id=<?= $quiz_id; ?>" method="POST">
                    
                    <input type="hidden" name="quiz_id" value="<?= $quiz_id; ?>">
                    
                    <?php
                    $q_number = 1;
                    foreach ($questions as $q):
                        $options = json_decode($q['options_combined'], true) ?? [];
                    ?>
                        <div class="question-block">
                            <h3>Question <?= $q_number++; ?>: <?= htmlspecialchars($q['texte_question']); ?></h3>
                            
                            <ul class="options-list">
                                <?php foreach ($options as $index => $option_text): ?>
                                    <li>
                                        <label>
                                            <input type="radio"
                                                   name="answer[<?= $q['question_id']; ?>]"
                                                   value="<?= $index; ?>"
                                                   required>
                                            <?= htmlspecialchars($option_text); ?>
                                        </label>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
 
                    <button type="submit" class="button">Soumettre le Quiz</button>
                </form>
            <?php endif; ?>
 
            <p class="mt-20"><a href="quiz_list.php">Retour à la liste des quiz</a></p>
        </div>
    </div>
    
    <footer class="footer">
        &copy; <?= date('Y'); ?> Quizzeo. Tous droits réservés.
    </footer>
</body>
</html>
 