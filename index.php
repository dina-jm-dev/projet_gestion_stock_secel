<?php
/**
 * GestSecel - Point d'entrée : redirection vers dashboard ou login
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

if (secel_is_logged_in()) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
