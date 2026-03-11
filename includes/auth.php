<?php
/**
 * GestSecel - Authentification, sessions, CSRF, RBAC
 */

declare(strict_types=1);

if (!defined('GESTSECEL')) {
    die('Accès direct interdit.');
}

// Démarrer session si pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Régénération d'ID de session pour éviter fixation
 */
function secel_session_regenerate(): void {
    if (!isset($_SESSION['_regenerated']) || (time() - $_SESSION['_regenerated']) > 300) {
        session_regenerate_id(true);
        $_SESSION['_regenerated'] = time();
    }
}

/**
 * Génère ou récupère le token CSRF
 */
function secel_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie le token CSRF (POST, GET ou header X-CSRF-Token)
 */
function secel_csrf_verify(): bool {
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/**
 * Utilisateur connecté (tableau ou null)
 */
function secel_user(): ?array {
    return $_SESSION['user'] ?? null;
}

/**
 * Vérifie si l'utilisateur est connecté
 */
function secel_is_logged_in(): bool {
    return secel_user() !== null;
}

/**
 * Vérifie si le rôle est administrateur
 */
function secel_is_admin(): bool {
    $u = secel_user();
    return $u && ($u['role'] ?? '') === 'admin';
}

/**
 * Vérifie si le rôle est employé
 */
function secel_is_employe(): bool {
    $u = secel_user();
    return $u && ($u['role'] ?? '') === 'employe';
}

/**
 * Exiger une connexion, sinon redirection vers login
 */
function secel_require_login(): void {
    if (!secel_is_logged_in()) {
        header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    secel_session_regenerate();
}

/**
 * Exiger le rôle admin, sinon accès refusé
 */
function secel_require_admin(): void {
    secel_require_login();
    if (!secel_is_admin()) {
        header('HTTP/1.1 403 Forbidden');
        echo 'Accès réservé aux administrateurs.';
        exit;
    }
}

/**
 * Connexion utilisateur (après vérification login/mot de passe)
 */
function secel_login(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id'       => (int) $user['id'],
        'login'    => $user['login'],
        'role'     => $user['role'],
        'nom'      => $user['nom'],
        'prenom'   => $user['prenom'],
    ];
    $_SESSION['_regenerated'] = time();
}

/**
 * Déconnexion
 */
function secel_logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
