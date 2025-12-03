<?php

require_once 'config.php';
require_once 'functions.php';
 
require_auth();
 
$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$edit_id = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0; 
 
$authorized_roles = ['admin', 'ecole', 'entreprise'];
if (!in_array($role, $authorized_roles)) {
    redirect('dashboard.php');
}
 
$error = '';
$success = '';
$title = '';
$description = '';
$page_title = $edit_id > 0 ? "Modifier le Quiz ID: $edit_id" : "Créer un nouveau Quiz";
 
 
if ($edit_id > 0) {
    try {
        $pdo = connectDB();
        
        $sql_select = "SELECT title, description, author_id FROM quizzes WHERE quiz_id = ?";
        if ($role !== 'admin') {
             $sql_select .= " AND author_id = $user_id"; 
        }
 
        $stmt = $pdo->prepare($sql_select);
        $stmt->execute([$edit_id]);
        $quiz_data = $stmt->fetch();
 
        if (!$quiz_data) {
            $error = "Quiz introuvable ou vous n'avez pas les droits d'édition.";
            $edit_id = 0; 
        } else {
         
            $title = $quiz_data['title'];
            $description = $quiz_data['description'];
        }
 
    } catch (PDOException $e) {
        $error = "Erreur DB lors du chargement : " . $e->getMessage();
        $edit_id = 0;
    }
}
 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if (empty($title) || empty($description)) {
        $error = "Veuillez remplir le titre et la description du quiz.";
    } else {
        try {
            $pdo = connectDB();
            
            if ($edit_id > 0) {
                $stmt = $pdo->prepare("UPDATE quizzes SET title = ?, description = ? WHERE quiz_id = ?");
                $stmt->execute([$title, $description, $edit_id]);
                
                $success = "Les détails du Quiz ont été modifiés avec succès.";
                redirect("manage_questions.php?quiz_id=" . $edit_id . "&success=" . urlencode($success));
 
            } else {
                
                $stmt = $pdo->prepare("INSERT INTO quizzes (author_id, title, description, created_at, is_active) VALUES (?, ?, ?, NOW(), 1)");
                $stmt->execute([$user_id, $title, $description]);
 
                $quiz_id = $pdo->lastInsertId();
                $success = "Quiz créé. Passez à l'étape des questions.";
                
                redirect("manage_questions.php?quiz_id=" . $quiz_id . "&success=" . urlencode($success));
            }
 
        } catch (PDOException $e) {
            $error = "Erreur DB: Impossible d'enregistrer le quiz. Message: " . $e->getMessage();
        }
    }
}

?>






<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title); ?> - Quizzeo</title>
    <link rel="stylesheet" href="styles.css?v=final">
</head>
<body class="dashboard-layout">
    <div class="navbar">
        <div class="logo">QUIZZEO | <?= htmlspecialchars(ucfirst($role)); ?></div>

        <nav>
            <a href="dashboard.php">Accueil</a>
            <a href="list_quizzes.php">Mes Quiz</a>
            <a href="logout.php">Déconnexion</a>
        </nav>

    </div>
 
    <div class="main-content">
        <div class="container dashboard-content">
            <h1><?= htmlspecialchars($page_title); ?></h1>
            <p>Étape 1 : Informations générales du quiz</p>
 
            <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success); ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error); ?></div><?php endif; ?>
            
            <?php if ($edit_id === 0 && isset($_GET['edit_id']) && $error): ?>
                <?php else: ?>
                <form action="create_quiz.php?<?php if ($edit_id > 0) echo 'edit_id=' . $edit_id; ?>" method="POST">
                    
                    <div class="form-group">
                        <label for="title">Titre du Quiz :</label>
                        <input type="text" id="title" name="title" required value="<?= htmlspecialchars($title); ?>">
                    </div>
 
                    <div class="form-group">
                        <label for="description">Description :</label>
                        <textarea id="description" name="description" rows="4" required><?= htmlspecialchars($description); ?></textarea>
                    </div>
 
                    <button type="submit"><?= $edit_id > 0 ? 'Enregistrer les Modifications' : 'Passer à l\'ajout des Questions'; ?></button>
                </form>
            <?php endif; ?>
            
            <div class="link-text">
                <a href="list_quizzes.php">Retour à la liste des Quiz</a>
            </div>
        </div>
    </div>
    
    <footer class="footer">
        &copy; <?= date('Y') ?> Quizzeo. Tous droits réservés.
    </footer>

    
</body>
</html>