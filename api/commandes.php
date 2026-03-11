<?php
/**
 * GestSecel - API Commandes (créer, valider, refuser)
 */
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';

header('Content-Type: application/json; charset=utf-8');
secel_require_login();

if (!secel_csrf_verify()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token CSRF invalide.']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$user_id = secel_user()['id'];
$is_admin = secel_is_admin();

switch ($action) {
    case 'create':
        // Récupère le motif et les lignes de commande envoyées par le formulaire
        $motif = trim($_POST['motif'] ?? '');
        $produit_ids = $_POST['produit_id'] ?? [];
        $qtes = $_POST['qte'] ?? [];
        if ($motif === '' || !is_array($produit_ids) || !is_array($qtes)) {
            echo json_encode(['success' => false, 'message' => 'Motif et lignes requis.']);
            exit;
        }
        // Construit un tableau de lignes valides (produit + quantité)
        $lignes = [];
        foreach ($produit_ids as $i => $pid) {
            $pid = (int)$pid;
            $q = (int)($qtes[$i] ?? 0);
            if ($pid > 0 && $q > 0) {
                $lignes[] = ['produit_id' => $pid, 'qte' => $q];
            }
        }
        if (empty($lignes)) {
            echo json_encode(['success' => false, 'message' => 'Ajoutez au moins une ligne avec quantité.']);
            exit;
        }
        // On utilise une transaction pour garantir que commande et lignes sont cohérentes
        $pdo->beginTransaction();
        try {
            $pdo->prepare('INSERT INTO commandes (user_id, statut, motif) VALUES (?,?,?)')
                ->execute([$user_id, 'en_attente', $motif]);
            $id_commande = (int)$pdo->lastInsertId();
            $stmt = $pdo->prepare('INSERT INTO details_commande (id_commande, id_produit, qte_demandee, qte_validee) VALUES (?,?,?,0)');
            foreach ($lignes as $l) {
                $stmt->execute([$id_commande, $l['produit_id'], $l['qte']]);
            }
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Commande créée.', 'id' => $id_commande]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la création.']);
        }
        break;

    case 'valider':
        if (!$is_admin) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Accès refusé.']);
            exit;
        }
        $id_commande = (int)($_POST['id_commande'] ?? 0);
        $lignes_raw = $_POST['lignes'] ?? '';
        $lignes = is_string($lignes_raw) ? json_decode($lignes_raw, true) : $lignes_raw;
        if ($id_commande <= 0 || !is_array($lignes)) {
            echo json_encode(['success' => false, 'message' => 'Données invalides.']);
            exit;
        }
        // Validation de commande : on met à jour les stocks produit par produit
        $pdo->beginTransaction();
        try {
            // Vérifie que la commande est encore en attente
            $st = $pdo->prepare('SELECT statut FROM commandes WHERE id = ?');
            $st->execute([$id_commande]);
            $cmd = $st->fetch();
            if (!$cmd || $cmd['statut'] !== 'en_attente') {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Commande introuvable ou déjà traitée.']);
                exit;
            }
            $tout_valide = true;
            $aucune_valide = true;
            foreach ($lignes as $l) {
                $detail_id = (int)($l['detail_id'] ?? 0);
                $qte_validee = (int)($l['qte_validee'] ?? 0);
                if ($detail_id <= 0) continue;
                // Récupère la ligne de commande et le stock disponible
                $sel = $pdo->prepare('SELECT d.id_produit, d.qte_demandee, p.stock_actuel FROM details_commande d JOIN produits p ON p.id = d.id_produit WHERE d.id = ? AND d.id_commande = ?');
                $sel->execute([$detail_id, $id_commande]);
                $det = $sel->fetch();
                if (!$det) continue;
                // On ne valide jamais plus que la quantité demandée ni plus que le stock disponible
                $max_qte = min($qte_validee, (int)$det['stock_actuel'], (int)$det['qte_demandee']);
                if ($max_qte > 0) {
                    $aucune_valide = false;
                    $pdo->prepare('UPDATE details_commande SET qte_validee = ? WHERE id = ?')->execute([$max_qte, $detail_id]);
                    $stock_avant = (int)$det['stock_actuel'];
                    $stock_apres = $stock_avant - $max_qte;
                    $pdo->prepare('UPDATE produits SET stock_actuel = ? WHERE id = ?')->execute([$stock_apres, $det['id_produit']]);
                    $pdo->prepare('INSERT INTO mouvements_stock (produit_id, type, qte, stock_avant, stock_apres, user_id, id_commande, motif) VALUES (?,?,?,?,?,?,?,?)')
                        ->execute([$det['id_produit'], 'commande', $max_qte, $stock_avant, $stock_apres, $user_id, $id_commande, 'Validation commande #' . $id_commande]);
                }
                if ($max_qte < (int)$det['qte_demandee']) $tout_valide = false;
            }
            $nouveau_statut = $aucune_valide ? 'en_attente' : ($tout_valide ? 'validee' : 'partiellement_validee');
            $pdo->prepare('UPDATE commandes SET statut = ?, validee_par = ?, date_validation = NOW() WHERE id = ?')
                ->execute([$nouveau_statut, $user_id, $id_commande]);
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Commande traitée.', 'statut' => $nouveau_statut]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Erreur.']);
        }
        break;

    case 'refuser':
        if (!$is_admin) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Accès refusé.']);
            exit;
        }
        $id_commande = (int)($_POST['id_commande'] ?? 0);
        $motif_refus = trim($_POST['motif_refus'] ?? '');
        if ($id_commande <= 0 || $motif_refus === '') {
            echo json_encode(['success' => false, 'message' => 'Commande et motif requis.']);
            exit;
        }
        $st = $pdo->prepare('SELECT id FROM commandes WHERE id = ? AND statut = ?');
        $st->execute([$id_commande, 'en_attente']);
        if (!$st->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Commande introuvable ou déjà traitée.']);
            exit;
        }
        $pdo->prepare('UPDATE commandes SET statut = ?, motif_refus = ?, validee_par = ?, date_validation = NOW() WHERE id = ?')
            ->execute(['refusee', $motif_refus, $user_id, $id_commande]);
        echo json_encode(['success' => true, 'message' => 'Commande refusée.']);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Action inconnue.']);
}
