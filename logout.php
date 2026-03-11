<?php
/**
 * GestSecel - Déconnexion
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

secel_logout();
header('Location: login.php');
exit;
