<?php
require_once 'config.php';
require_once 'functions.php';

require_auth();

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

$error = '';
$success = '';
$users = [];

if (!in_array($role, ['admin', 'entreprise', 'ecole'])) {
    redirect('dashboard.php');
}

$gestion_title = "Gestion des Utilisateurs";
$allowed_new_roles = ['utilisateur'];
$sql_filter = "WHERE user_id != ? AND role = 'utilisateur'"; 

if ($role === 'admin') {
    $allowed_new_roles = ['utilisateur', 'ecole', 'entreprise', 'admin'];
    $sql_filter = "WHERE user_id != ?"; 
    $gestion_title = "Gestion globale des Utilisateurs (Admin)";
} elseif ($role === 'entreprise') {
    $allowed_new_roles = ['utilisateur', 'ecole'];
    $sql_filter = "WHERE user_id != ? AND role IN ('utilisateur','ecole')"; 
    $gestion_title = "Gestion Utilisateurs / Écoles (Entreprise)";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['new_role'])) {
    $target_user_id = (int) $_POST['user_id'];
    $new_role = trim($_POST['new_role']);

    if ($target_user_id === $user_id) {
        $error = "Impossible de modifier votre propre rôle.";
    } elseif (!in_array($new_role, $allowed_new_roles)) {
        $error = "Vous ne pouvez pas attribuer ce rôle ($new_role).";
    } else {
        try {
            $pdo = connectDB();
            $conditions = match($role) {
                'admin' => '',
                'entreprise' => "AND role IN('utilisateur','ecole')",
                'ecole' => "AND role = 'utilisateur'",
                default => 'AND 1=0'
            };
            $stmt_check = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ? $conditions");
            $stmt_check->execute([$target_user_id]);

            if (!$stmt_check->fetch()) {
                $error = "Accès refusé ou utilisateur inconnu.";
            } else {
                $stmt_up = $pdo->prepare("UPDATE users SET role=? WHERE user_id=?");
                $stmt_up->execute([$new_role, $target_user_id]);
                $success = "Rôle mis à jour avec succès pour l'utilisateur ID: $target_user_id";
            }
        } catch (PDOException $e) {
            $error = "Erreur DB : " . $e->getMessage();
        }
    }
}

try {
    $pdo = connectDB();
    $stmt = $pdo->prepare("SELECT user_id, username, email, role FROM users $sql_filter ORDER BY role, username");
    $stmt->execute([$user_id]);
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Erreur lors du chargement des utilisateurs : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($gestion_title) ?></title>
<link rel="stylesheet" href="styles.css?v=final">
</head>
<body class="dashboard-layout">
<div class="navbar">
    <div class="logo">QUIZZEO | <?= htmlspecialchars(ucfirst($role)) ?></div>
    <nav>
        <a href="dashboard.php">Accueil</a>
        <a href="admin_manage_quizzes.php">Gérer les Quiz</a>
        <a href="logout.php">Déconnexion</a>
    </nav>
</div>
<div class="main-content">
    <div class="container dashboard-content">
        <h1><?= htmlspecialchars($gestion_title) ?></h1>
        <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

        <?php if (empty($users)): ?>
            <div class="alert alert-info">Aucun utilisateur à afficher selon vos droits.</div>
        <?php else: ?>
        <table class="users-table">
            <thead><tr><th>ID</th><th>Nom</th><th>Email</th><th>Rôle</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><?= htmlspecialchars($u['user_id']) ?></td>
                <td><?= htmlspecialchars($u['username']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= htmlspecialchars(ucfirst($u['role'])) ?></td>
                <td>
                    <form method="POST" class="form-role-update">
                        <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                        <select name="new_role">
                            <?php foreach ($allowed_new_roles as $r): ?>
                                <option value="<?= $r ?>" <?= $u['role']===$r ? 'selected':'' ?>><?= ucfirst($r) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="button-small">OK</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        <div class="link-text"><a href="dashboard.php">Retour</a></div>
    </div>
</div>
<footer class="footer">&copy; <?= date('Y') ?> Quizzeo.</footer>
</body>
</html>
