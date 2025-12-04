<?php


require_once 'config.php';
require_once 'functions.php';
require_auth();


$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

$quiz_id = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;
$questions = [];
$quiz = null;


function ensure_points_column($pdo) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM questions LIKE 'points'");
        $exists = $stmt->fetch();
        if (!$exists) {
           
            $pdo->exec("ALTER TABLE questions ADD COLUMN points INT NOT NULL DEFAULT 1");
        }
    } catch (PDOException $e) {
        
        error_log("Info: impossibilité de garantir la colonne points: " . $e->getMessage());
    }
}



if (!in_array($role, ['ecole', 'entreprise', 'admin'])) {
    redirect('dashboard.php');
}


if ($quiz_id <= 0) {
    $error = "ID de quiz invalide.";
} else {
    try {
        $pdo = connectDB();

       
        ensure_points_column($pdo);

        $stmt = $pdo->prepare("SELECT quiz_id, title, author_id FROM quizzes WHERE quiz_id = ?");
        $stmt->execute([$quiz_id]);
        $quiz = $stmt->fetch();

        if (!$quiz) {
            $error = "Quiz introuvable.";
        } elseif ($quiz['author_id'] != $user_id && $role !== 'admin') {
            $error = "Vous devez être l'auteur du quiz ou admin pour le modifier.";
        }

    } catch (PDOException $e) {
        $error = "Erreur DB : " . $e->getMessage();
    }
}



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_question' && !$error) {
    $posted_token = $_POST['csrf_token'] ?? null;
    if (!verify_csrf_token($posted_token)) {
        $error = "Jeton CSRF invalide. Opération annulée.";
    } else {
        $delete_q_id = isset($_POST['question_id']) ? (int)$_POST['question_id'] : 0;
        if ($delete_q_id <= 0) {
            $error = "ID de question invalide.";
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM questions WHERE question_id=? AND quiz_id=?");
                $stmt->execute([$delete_q_id, $quiz_id]);

                if ($stmt->rowCount() > 0) {
                    redirect("manage_questions.php?quiz_id=$quiz_id&success=" . urlencode("Question supprimée avec succès."));
                } else {
                    $error = "Impossible de supprimer : droits insuffisants ou question inexistante.";
                }
            } catch (PDOException $e) {
                $error = "Erreur suppression : " . $e->getMessage();
            }
        }
    }
}



$question_text = '';
$options_form = ['', '', '', ''];
$correct_answer_index_form = -1;
$points_form = 1;


if (isset($_GET['success'])) {
    $success = htmlspecialchars($_GET['success']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {

    $question_text = trim($_POST['question_text'] ?? '');
    $options_form = [
        trim($_POST['option1'] ?? ''),
        trim($_POST['option2'] ?? ''),
        trim($_POST['option3'] ?? ''),
        trim($_POST['option4'] ?? ''),
    ];
    $points_form = isset($_POST['points']) ? (int)$_POST['points'] : 1;
    $correct_answer_index_form = isset($_POST['correct_answer']) ? (int)$_POST['correct_answer'] : -1;

    $action_q_id = isset($_POST['action_q_id']) ? (int)$_POST['action_q_id'] : null;

  
    if ($question_text === '') {
        $error = "Veuillez entrer un texte de question.";
    } elseif (count(array_filter($options_form)) < 2) {
        $error = "Au moins deux options doivent être renseignées.";
    } elseif (!isset($options_form[$correct_answer_index_form]) || $options_form[$correct_answer_index_form] === '') {
        $error = "Veuillez sélectionner une réponse correcte valide.";
    } elseif ($points_form <= 0) {
        $error = "Veuillez indiquer un nombre de points positif pour la question.";
    } else {
        try {
            $options_json = json_encode($options_form);

            if ($action_q_id) {
                
                $stmt = $pdo->prepare("UPDATE questions SET texte_question=?, options_json=?, reponse_correcte=?, points=? WHERE question_id=? AND quiz_id=?");
                $stmt->execute([
                    $question_text,
                    $options_json,
                    $correct_answer_index_form,
                    $points_form,
                    $action_q_id,
                    $quiz_id
                ]);

                redirect("manage_questions.php?quiz_id=$quiz_id&success=" . urlencode("Question modifiée avec succès."));
            } else {
                
                $stmt = $pdo->prepare("INSERT INTO questions (quiz_id, texte_question, options_json, reponse_correcte, points) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $quiz_id,
                    $question_text,
                    $options_json,
                    $correct_answer_index_form,
                    $points_form
                ]);

                redirect("manage_questions.php?quiz_id=$quiz_id&success=" . urlencode("Question ajoutée avec succès."));
            }

        } catch (PDOException $e) {
            $error = "Erreur insertion / modification : " . $e->getMessage();
        }
    }
}



$question_id_to_edit = null;

if (!$error && isset($_GET['edit_q_id']) && is_numeric($_GET['edit_q_id'])) {
    $question_id_to_edit = (int)$_GET['edit_q_id'];

    try {
        
        $stmt = $pdo->prepare("SELECT texte_question, options_json AS options, reponse_correcte, COALESCE(points,1) AS points 
                   FROM questions WHERE question_id=? AND quiz_id=?");
        $stmt->execute([$question_id_to_edit, $quiz_id]);

        $data = $stmt->fetch();
        if ($data) {
            $question_text = $data['texte_question'];
            $options_form = json_decode($data['options'], true) ?: ['', '', '', ''];
            $correct_answer_index_form = (int)$data['reponse_correcte'];
            $points_form = (int)($data['points'] ?? 1);
        } else {
            $question_id_to_edit = null;
            $error = "Question introuvable.";
        }

    } catch (PDOException $e) {
        $error = "Erreur chargement question : " . $e->getMessage();
    }
}


if (!$error) {
    try {
        
        $stmt = $pdo->prepare("SELECT question_id, texte_question, options_json AS options, reponse_correcte, COALESCE(points,1) AS points FROM questions WHERE quiz_id=? ORDER BY question_id ASC");
        $stmt->execute([$quiz_id]);
        $questions = $stmt->fetchAll();
    } catch (PDOException $e) {
        $error = "Erreur DB : " . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Gestion Questions - <?= htmlspecialchars($quiz['title'] ?? '') ?></title>
<link rel="stylesheet" href="styles.css?v=final">



</head>
<body class="dashboard-layout">

<div class="navbar">
    <div class="logo">QUIZZEO | Gestion</div>
    <nav>
        <a href="dashboard.php">Accueil</a>
        <a href="list_quizzes.php">Mes Quiz</a>
        <a href="logout.php">Déconnexion</a>
    </nav>
</div>

<div class="main-content">
<div class="container dashboard-content max-width-900">

    <h1><?= $question_id_to_edit ? "Modifier une question" : "Ajouter une question" ?></h1>
    <h3>Quiz : <b><?= htmlspecialchars($quiz['title'] ?? '') ?></b></h3>

    <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

    <?php if ($quiz): ?>

    
    <form method="POST" class="form-card">
        
        <?php if ($question_id_to_edit): ?>
            <input type="hidden" name="action_q_id" value="<?= $question_id_to_edit ?>">
        <?php endif; ?>

        <div class="form-group">
            <label>Texte de la question :</label>
            <textarea name="question_text" required><?= htmlspecialchars($question_text) ?></textarea>
        </div>

        <div class="form-group">
            <label>Options (cocher la bonne réponse) :</label>

            <?php for ($i=0; $i<4; $i++): ?>
                <div class="radio-option">
                    <input type="radio" name="correct_answer" value="<?= $i ?>"
                        <?= $correct_answer_index_form == $i ? 'checked' : '' ?>>
                    <input type="text" name="option<?= $i+1 ?>" placeholder="Option <?= $i+1 ?>"
                           value="<?= htmlspecialchars($options_form[$i] ?? '') ?>">
                </div>
            <?php endfor; ?>

        </div>

        <div class="form-group">
            <label>Points pour cette question :</label>
            <input type="number" name="points" min="1" value="<?= htmlspecialchars($points_form) ?>" required>
            <p class="small-text">Indiquez combien de points vaut cette question (ex: 1, 2, ...).</p>
        </div>

        <button class="button-primary">
            <?= $question_id_to_edit ? "Enregistrer" : "Ajouter la question" ?>
        </button>

        <?php if ($question_id_to_edit): ?>
            <a href="manage_questions.php?quiz_id=<?= $quiz_id ?>" class="button secondary-action mt-10">Annuler</a>
        <?php endif; ?>

    </form>

    <h2 class="mt-40">Questions existantes (<?= count($questions) ?>)</h2>

    <?php if (empty($questions)): ?>
        <div class="alert alert-info">Aucune question pour ce quiz.</div>
    <?php else: ?>
        <?php foreach ($questions as $q): 
            $opts = json_decode($q['options'], true) ?: [];
        ?>
            <div class="form-card">
                <h3>#<?= $q['question_id'] ?> — <?= htmlspecialchars($q['texte_question']) ?> <span class="muted">(<?= (int)$q['points'] ?> pts)</span></h3>

                    <?php foreach ($opts as $i => $opt): ?>
                    <div class="questions-list-item <?= ($i == $q['reponse_correcte']) ? 'correct-answer' : 'wrong-answer' ?>">
                        <?= htmlspecialchars($opt) ?>
                        <?= $i == $q['reponse_correcte'] ? " (CORRECT)" : "" ?>
                    </div>
                <?php endforeach; ?>

                <div class="text-right mt-10">
                    <a class="button secondary-action"
                       href="manage_questions.php?quiz_id=<?= $quiz_id ?>&edit_q_id=<?= $q['question_id'] ?>">
                        Modifier
                    </a>
                    <form method="POST" class="inline-form ml-10" onsubmit="return confirm('Supprimer cette question ?');">
                        <?= csrf_input_field() ?>
                        <input type="hidden" name="action" value="delete_question">
                        <input type="hidden" name="question_id" value="<?= (int)$q['question_id'] ?>">
                        <button type="submit" class="button secondary-action danger ml-10">Supprimer</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php endif; ?>

    <div class="link-text mt-20">
        <a href="list_quizzes.php">Retour aux quiz</a>
    </div>

</div>
</div>

<footer class="footer">&copy; <?= date('Y') ?> Quizzeo</footer>

</body>
</html>