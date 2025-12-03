<?php
// functions.php - Fonctions utilitaires, d'authentification et de sécurité - SIMPLIFIÉ

// Démarrer la session au début de chaque page
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =================================
// SÉCURITÉ ET AUTHENTIFICATION
// =================================

/**
 * Vérifie si l'utilisateur est connecté.
 * @return bool
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Vérifie si l'utilisateur connecté a le rôle 'admin'.
 * @return bool
 */
function is_admin() {
    return is_logged_in() && ($_SESSION['role'] ?? '') === 'admin';
}

/**
 * Redirige l'utilisateur vers une page spécifiée et termine le script.
 * @param string $location Le chemin du fichier
 */
function redirect($location) {
    header("Location: $location");
    exit();
}

/**
 * Vérifie l'authentification et redirige vers 'login.php' si non connecté.
 */
function require_auth() {
    if (!is_logged_in()) {
        redirect('login.php');
    }
}

/**
 * Vérifie l'authentification ET les droits d'administrateur, redirige si non admin.
 */
function require_admin() {
    if (!is_admin()) {
        redirect('dashboard.php'); 
    }
}

/**
 * Déconnecte l'utilisateur en détruisant la session.
 */
function logout() {
    $_SESSION = array();
    session_destroy();
    redirect('login.php');
}


// =================================
// CAPTCHA (Pour Register)
// =================================

/**
 * Génère une opération CAPTCHA simple (ex: 5 + 3) et stocke le résultat.
 * @return string L'opération mathématique à afficher.
 */
function generate_captcha() {
    $num1 = rand(1, 9);
    $num2 = rand(1, 9);
    $_SESSION['captcha_result'] = $num1 + $num2; 
    $_SESSION['captcha_operation_display'] = "$num1 + $num2";
    return $_SESSION['captcha_operation_display'];
}

/**
 * Vérifie la réponse au CAPTCHA.
 * @param int $user_answer La réponse de l'utilisateur.
 * @return bool
 */
function verify_captcha($user_answer) {
    if (isset($_SESSION['captcha_result']) && (int)$user_answer === $_SESSION['captcha_result']) {
        unset($_SESSION['captcha_result']); // Nettoyer après succès
        return true;
    }
    return false;
}

// =================================
// CSRF (Pour les formulaires POST critiques)
// =================================

/**
 * Génère un token CSRF ou retourne l'existant.
 * @return string
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); 
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie le token CSRF envoyé par le formulaire.
 * @param string|null $token
 * @return bool
 */
function verify_csrf_token($token) {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    $valid = hash_equals($_SESSION['csrf_token'], $token); 
    if ($valid) {
        unset($_SESSION['csrf_token']);
    }
    return $valid;
}

/**
 * Retourne le champ input caché contenant le token CSRF.
 * @return string
 */
function csrf_input_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generate_csrf_token()) . '">';
}


// =================================
// FONCTIONS UTILITAIRES
// =================================

/**
 * Fonction pour sécuriser et nettoyer une chaîne de caractères (XSS).
 * @param string $data La donnée à nettoyer.
 * @return string La donnée nettoyée.
 */
function sanitize_input($data) {
    // trim: enlève les espaces. htmlspecialchars: empêche les injections HTML (XSS).
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}
?>