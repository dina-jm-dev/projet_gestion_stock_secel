<?php
/**
 * GestSecel - Configuration générale
 * SECEL Cameroun - Gestion des stocks
 */

declare(strict_types=1);

// Empêcher accès direct
if (!defined('GESTSECEL')) {
    define('GESTSECEL', true);
}

// Fuseau et locale
date_default_timezone_set('Africa/Douala');
setlocale(LC_TIME, 'fr_FR.UTF-8');

// Constantes application
define('APP_NAME', 'GestSecel');
define('APP_VERSION', '1.0');
define('APP_YEAR', date('Y'));

// Chemins
define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('CONFIG_PATH', ROOT_PATH . 'config' . DIRECTORY_SEPARATOR);
define('INCLUDES_PATH', ROOT_PATH . 'includes' . DIRECTORY_SEPARATOR);
define('ASSETS_PATH', ROOT_PATH . 'assets' . DIRECTORY_SEPARATOR);

// Erreurs (désactiver en production)
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Session (démarrage différé dans includes/auth.php)
ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_samesite', 'Strict');
