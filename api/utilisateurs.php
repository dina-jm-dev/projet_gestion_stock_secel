<?php
/**
 * GestSecel - API Utilisateurs (CRUD) - Admin uniquement
 */
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';

header('Content-Type: application/json; charset=utf-8');
secel_require_admin();

if (!secel_csrf_verify()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token CSRF invalide.']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID invalide.']);
            exit;
        }
        $st = $pdo->prepare('SELECT id, login, role, nom, prenom, actif FROM utilisateurs WHERE id = ?');
        $st->execute([$id]);
        $u = $st->fetch();
        if (!$u) {
            echo json_encode(['success' => false, 'message' => 'Utilisateur introuvable.']);
            exit;
        }
        echo json_encode(['success' => true, 'utilisateur' => $u]);
        break;

    case 'create':
        $login = trim($_POST['login'] ?? '');
        $password = $_POST['password'] ?? '';
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $role = $_POST['role'] ?? 'employe';
        if ($login === '' || $nom === '' || $prenom === '' || $password === '') {
            echo json_encode(['success' => false, 'message' => 'Login, nom, prénom et mot de passe requis.']);
            exit;
        }
        if (!in_array($role, ['admin', 'employe'], true)) $role = 'employe';
        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $pdo->prepare('INSERT INTO utilisateurs (login, mdp_hash, role, nom, prenom) VALUES (?,?,?,?,?)')
                ->execute([$login, $hash, $role, $nom, $prenom]);
            echo json_encode(['success' => true, 'message' => 'Utilisateur créé.', 'id' => (int)$pdo->lastInsertId()]);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                echo json_encode(['success' => false, 'message' => 'Ce login existe déjà.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erreur base de données.']);
            }
        }
        break;

    case 'update':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID invalide.']);
            exit;
        }
        $login = trim($_POST['login'] ?? '');
        $password = $_POST['password'] ?? '';
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $role = $_POST['role'] ?? 'employe';
        $actif = isset($_POST['actif']) ? 1 : 0;
        if ($login === '' || $nom === '' || $prenom === '') {
            echo json_encode(['success' => false, 'message' => 'Login, nom et prénom requis.']);
            exit;
        }
        if (!in_array($role, ['admin', 'employe'], true)) $role = 'employe';
        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare('UPDATE utilisateurs SET login=?, mdp_hash=?, nom=?, prenom=?, role=?, actif=? WHERE id=?')
                ->execute([$login, $hash, $nom, $prenom, $role, $actif, $id]);
        } else {
            $pdo->prepare('UPDATE utilisateurs SET login=?, nom=?, prenom=?, role=?, actif=? WHERE id=?')
                ->execute([$login, $nom, $prenom, $role, $actif, $id]);
        }
        echo json_encode(['success' => true, 'message' => 'Utilisateur modifié.']);
        break;

    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID invalide.']);
            exit;
        }
        if ($id === secel_user()['id']) {
            echo json_encode(['success' => false, 'message' => 'Vous ne pouvez pas supprimer votre propre compte.']);
            exit;
        }
        // Désactivation plutôt que suppression pour garder l'historique
        $pdo->prepare('UPDATE utilisateurs SET actif = 0 WHERE id = ?')->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Utilisateur désactivé.']);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Action inconnue.']);
}
