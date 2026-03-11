<?php
/**
 * GestSecel - API Produits (CRUD) - Admin uniquement
 */
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';

header('Content-Type: application/json; charset=utf-8');
secel_require_login();
if (!secel_is_admin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès refusé.']);
    exit;
}

if (!secel_csrf_verify()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token CSRF invalide.']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'create':
        $ref = trim($_POST['reference'] ?? '');
        $nom = trim($_POST['nom'] ?? '');
        $categorie = trim($_POST['categorie'] ?? '');
        $prix = (float)($_POST['prix'] ?? 0);
        $stock = (int)($_POST['stock_actuel'] ?? 0);
        $seuil = (int)($_POST['seuil_alerte'] ?? 5);
        if ($ref === '' || $nom === '' || $categorie === '') {
            echo json_encode(['success' => false, 'message' => 'Référence, nom et catégorie requis.']);
            exit;
        }
        try {
            $pdo->prepare('INSERT INTO produits (reference, nom, categorie, prix, stock_actuel, seuil_alerte) VALUES (?,?,?,?,?,?)')
                ->execute([$ref, $nom, $categorie, $prix, $stock, $seuil]);
            if ($stock > 0) {
                $id = (int)$pdo->lastInsertId();
                $pdo->prepare('INSERT INTO mouvements_stock (produit_id, type, qte, stock_avant, stock_apres, user_id, motif) VALUES (?,?,?,0,?,?,?)')
                    ->execute([$id, 'entree', $stock, $stock, secel_user()['id'], 'Stock initial']);
            }
            echo json_encode(['success' => true, 'message' => 'Produit créé.', 'id' => (int)$pdo->lastInsertId()]);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                echo json_encode(['success' => false, 'message' => 'Cette référence existe déjà.']);
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
        $ref = trim($_POST['reference'] ?? '');
        $nom = trim($_POST['nom'] ?? '');
        $categorie = trim($_POST['categorie'] ?? '');
        $prix = (float)($_POST['prix'] ?? 0);
        $seuil = (int)($_POST['seuil_alerte'] ?? 5);
        if ($ref === '' || $nom === '' || $categorie === '') {
            echo json_encode(['success' => false, 'message' => 'Référence, nom et catégorie requis.']);
            exit;
        }
        try {
            $pdo->prepare('UPDATE produits SET reference=?, nom=?, categorie=?, prix=?, seuil_alerte=? WHERE id=?')
                ->execute([$ref, $nom, $categorie, $prix, $seuil, $id]);
            echo json_encode(['success' => true, 'message' => 'Produit modifié.']);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                echo json_encode(['success' => false, 'message' => 'Cette référence existe déjà.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erreur base de données.']);
            }
        }
        break;

    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID invalide.']);
            exit;
        }
        $st = $pdo->prepare('SELECT stock_actuel FROM produits WHERE id = ?');
        $st->execute([$id]);
        $row = $st->fetch();
        if (!$row) {
            echo json_encode(['success' => false, 'message' => 'Produit introuvable.']);
            exit;
        }
        if ((int)$row['stock_actuel'] !== 0) {
            echo json_encode(['success' => false, 'message' => 'Impossible de supprimer : le stock doit être nul.']);
            exit;
        }
        $pdo->prepare('DELETE FROM produits WHERE id = ?')->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Produit supprimé.']);
        break;

    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID invalide.']);
            exit;
        }
        $st = $pdo->prepare('SELECT id, reference, nom, categorie, prix, stock_actuel, seuil_alerte FROM produits WHERE id = ?');
        $st->execute([$id]);
        $p = $st->fetch();
        if (!$p) {
            echo json_encode(['success' => false, 'message' => 'Produit introuvable.']);
            exit;
        }
        echo json_encode(['success' => true, 'produit' => $p]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Action inconnue.']);
}
