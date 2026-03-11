<?php
/**
 * GestSecel - Gestion des stocks (Admin : ajustements, inventaire, historique | Employé : consultation)
 * Affiche tous les stocks sans barre de recherche ni filtre.
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

secel_require_login();

$is_admin = secel_is_admin();

// Récupère tous les produits pour afficher l'état complet des stocks
$stmt = $pdo->query('SELECT id, reference, nom, categorie, stock_actuel, seuil_alerte FROM produits ORDER BY nom');
$produits = $stmt->fetchAll();
$liste_produits = $is_admin ? $pdo->query('SELECT id, reference, nom FROM produits ORDER BY nom')->fetchAll() : [];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Stocks - <?= htmlspecialchars(APP_NAME) ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-wrap">
<?php include INCLUDES_PATH . 'header.php'; ?>
<main class="main-content">
  <h1 class="page-title">Stocks</h1>

  <?php if (isset($_GET['msg']) && $_GET['msg'] === 'ok'): ?>
  <div class="alert alert-success" id="flash">Mouvement enregistré.</div>
  <?php endif; ?>

  <?php if ($is_admin): ?>
  <div class="toolbar">
    <button type="button" class="btn btn-primary" id="btn-mouvement">Ajuster un stock</button>
  </div>
  <?php endif; ?>

  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>Référence</th>
          <th>Nom</th>
          <th>Catégorie</th>
          <th>Stock actuel</th>
          <th>Seuil alerte</th>
          <?php if ($is_admin): ?><th class="actions">Actions</th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($produits as $p): ?>
        <tr data-id="<?= (int)$p['id'] ?>" data-ref="<?= htmlspecialchars($p['reference']) ?>">
          <td><?= htmlspecialchars($p['reference']) ?></td>
          <td><?= htmlspecialchars($p['nom']) ?></td>
          <td><?= htmlspecialchars($p['categorie']) ?></td>
          <td><?= (int)$p['stock_actuel'] ?><?= ($p['stock_actuel'] <= $p['seuil_alerte']) ? ' <span class="badge badge-alerte">Alerte</span>' : '' ?></td>
          <td><?= (int)$p['seuil_alerte'] ?></td>
          <?php if ($is_admin): ?>
          <td class="actions">
            <button type="button" class="btn btn-sm btn-secondary btn-histoire" data-id="<?= (int)$p['id'] ?>" data-ref="<?= htmlspecialchars($p['reference']) ?>">Historique</button>
            <button type="button" class="btn btn-sm btn-primary btn-open-mouvement" data-id="<?= (int)$p['id'] ?>" data-ref="<?= htmlspecialchars($p['reference']) ?>">Ajuster</button>
          </td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main>

<?php if ($is_admin): ?>
<!-- Modal Ajustement -->
<div class="modal-overlay" id="modal-mouvement">
  <div class="modal">
    <h2>Ajuster le stock</h2>
    <form id="form-mouvement">
      <input type="hidden" name="produit_id" id="mouvement-produit-id" value="">
      <div class="form-group" id="group-select-produit">
        <label for="mouvement-produit-select">Produit *</label>
        <select id="mouvement-produit-select" class="form-control">
          <option value="">-- Choisir un produit --</option>
          <?php foreach ($liste_produits as $pr): ?>
          <option value="<?= (int)$pr['id'] ?>"><?= htmlspecialchars($pr['reference'] . ' - ' . $pr['nom']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <p id="mouvement-produit-ref-line" style="display:none;"><strong>Produit :</strong> <span id="mouvement-produit-ref"></span></p>
      <div class="form-group">
        <label>Type</label>
        <select id="mouvement-type" name="type" class="form-control">
          <option value="ajustement_plus">Augmentation</option>
          <option value="ajustement_moins">Diminution</option>
        </select>
      </div>
      <div class="form-group">
        <label for="mouvement-qte">Quantité *</label>
        <input type="number" id="mouvement-qte" name="qte" class="form-control" min="1" required>
      </div>
      <div class="form-group">
        <label for="mouvement-motif">Motif *</label>
        <textarea id="mouvement-motif" name="motif" class="form-control" rows="2" required></textarea>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" id="btn-cancel-mouvement">Annuler</button>
        <button type="submit" class="btn btn-primary">Enregistrer</button>
      </div>
    </form>
  </div>
</div>
<!-- Modal Historique -->
<div class="modal-overlay" id="modal-histoire">
  <div class="modal">
    <h2>Historique des mouvements — <span id="histoire-ref"></span></h2>
    <div id="histoire-content"></div>
    <div class="modal-actions">
      <button type="button" class="btn btn-secondary" id="btn-close-histoire">Fermer</button>
    </div>
  </div>
</div>
<?php endif; ?>

<?php include INCLUDES_PATH . 'footer.php'; ?>
<script src="assets/js/app.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  window.GestSecelStocks = { csrf: '<?= htmlspecialchars(secel_csrf_token()) ?>', isAdmin: <?= $is_admin ? 'true' : 'false' ?> };
});
</script>
</body>
</html>
