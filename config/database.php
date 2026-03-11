<?php
/**
 * GestSecel - Connexion base de données PDO
 */

declare(strict_types=1);

if (!defined('GESTSECEL')) {
    die('Accès direct interdit.');
}

$db_host = 'localhost';
$db_name = 'gestion_stock_secel';
$db_user = 'root';
$db_pass = '';
$db_charset = 'utf8mb4';

$dsn = "mysql:host=$db_host;dbname=$db_name;charset=$db_charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    // En production : logger et afficher message générique
    die('Erreur de connexion à la base de données. Veuillez contacter l\'administrateur.');
}
