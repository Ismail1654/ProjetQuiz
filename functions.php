<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}
function is_admin() {
    return is_logged_in() && ($_SESSION['role'] ?? '') === 'admin';
}

function redirect($location) {
    header("Location: $location");
    exit();
}

function require_auth() {
    if (!is_logged_in()) {
        redirect('login.php');
    }
}

function require_admin() {
    if (!is_admin()) {
        redirect('dashboard.php'); 
    }
}

function logout() {
    $_SESSION = array();
    session_destroy();
    redirect('login.php');
}

function generate_captcha() {
    $num1 = rand(1, 9);
    $num2 = rand(1, 9);
    $_SESSION['captcha_result'] = $num1 + $num2; 
    $_SESSION['captcha_operation_display'] = "$num1 + $num2";
    return $_SESSION['captcha_operation_display'];
}

function verify_captcha($user_answer) {
    if (isset($_SESSION['captcha_result']) && (int)$user_answer === $_SESSION['captcha_result']) {
        unset($_SESSION['captcha_result']); 
        return true;
    }
    return false;
}

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); 
    }
    return $_SESSION['csrf_token'];
}

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

<<<<<<< HEAD
/**
 * @return string
 */
=======
>>>>>>> be3de4da96a05dd7abeb1eee644620f22da6a6bf
function csrf_input_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generate_csrf_token()) . '">';
}

<<<<<<< HEAD
/**
 
 * @param string 
 * @return string 
 */
function sanitize_input($data) {
   
=======
function sanitize_input($data) {
    
>>>>>>> be3de4da96a05dd7abeb1eee644620f22da6a6bf
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}
?>