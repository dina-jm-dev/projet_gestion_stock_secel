<?php
/**
 * GestSecel - API Stocks (ajustement, historique) - Admin uniquement
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
    case 'ajustement':
        // Récupère les informations d'ajustement manuel saisies par l'administrateur
        $produit_id = (int)($_POST['produit_id'] ?? 0);
        $type = $_POST['type'] ?? '';
        $qte = (int)($_POST['qte'] ?? 0);
        $motif = trim($_POST['motif'] ?? '');
        if ($produit_id <= 0 || $qte <= 0 || $motif === '') {
            echo json_encode(['success' => false, 'message' => 'Données invalides.']);
            exit;
        }
        if (!in_array($type, ['ajustement_plus', 'ajustement_moins'], true)) {
            echo json_encode(['success' => false, 'message' => 'Type invalide.']);
            exit;
        }
        // Transaction pour garantir que stock produit et mouvement restent synchronisés
        $pdo->beginTransaction();
        try {
            // Récupère le stock actuel du produit
            $st = $pdo->prepare('SELECT stock_actuel FROM produits WHERE id = ?');
            $st->execute([$produit_id]);
            $row = $st->fetch();
            if (!$row) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Produit introuvable.']);
                exit;
            }
            $stock_avant = (int)$row['stock_actuel'];
            if ($type === 'ajustement_moins' && $qte > $stock_avant) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Stock insuffisant.']);
                exit;
            }
            $stock_apres = $type === 'ajustement_plus' ? $stock_avant + $qte : $stock_avant - $qte;
            $pdo->prepare('UPDATE produits SET stock_actuel = ? WHERE id = ?')->execute([$stock_apres, $produit_id]);
            $pdo->prepare('INSERT INTO mouvements_stock (produit_id, type, qte, stock_avant, stock_apres, user_id, motif) VALUES (?,?,?,?,?,?,?)')
                ->execute([$produit_id, $type, $qte, $stock_avant, $stock_apres, secel_user()['id'], $motif]);
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Mouvement enregistré.', 'stock_apres' => $stock_apres]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Erreur.']);
        }
        break;

    case 'historique':
        $produit_id = (int)($_GET['produit_id'] ?? 0);
        if ($produit_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID invalide.']);
            exit;
        }
        $st = $pdo->prepare('SELECT m.type, m.qte, m.stock_avant, m.stock_apres, m.date_mouvement, m.motif, u.prenom, u.nom 
            FROM mouvements_stock m 
            LEFT JOIN utilisateurs u ON u.id = m.user_id 
            WHERE m.produit_id = ? ORDER BY m.date_mouvement DESC LIMIT 50');
        $st->execute([$produit_id]);
        $rows = $st->fetchAll();
        echo json_encode(['success' => true, 'mouvements' => $rows]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Action inconnue.']);
}
