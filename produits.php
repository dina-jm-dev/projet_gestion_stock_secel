<?php
/**
 * GestSecel - Gestion des produits (Admin : CRUD | Employé : consultation)
 * Affichage simple de tous les produits sans recherche ni filtre.
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

secel_require_login();

$is_admin = secel_is_admin();

// Récupère tous les produits pour un affichage complet et pédagogique
$stmt = $pdo->query('SELECT id, reference, nom, categorie, prix, stock_actuel, seuil_alerte FROM produits ORDER BY nom');
$produits = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Produits - <?= htmlspecialchars(APP_NAME) ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-wrap">
<?php include INCLUDES_PATH . 'header.php'; ?>
<main class="main-content">
  <h1 class="page-title">Produits</h1>

  <?php if (isset($_GET['msg']) && $_GET['msg'] === 'ok'): ?>
  <div class="alert alert-success" id="flash">Opération effectuée avec succès.</div>
  <?php endif; ?>
  <?php if (isset($_GET['msg']) && $_GET['msg'] === 'erreur'): ?>
  <div class="alert alert-error" id="flash">Une erreur est survenue.</div>
  <?php endif; ?>

  <?php if ($is_admin): ?>
  <div class="toolbar">
    <button type="button" class="btn btn-primary" id="btn-add-product">Ajouter un produit</button>
  </div>
  <?php endif; ?>

  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>Référence</th>
          <th>Nom</th>
          <th>Catégorie</th>
          <th>Prix unitaire</th>
          <th>Stock</th>
          <th>Seuil alerte</th>
          <?php if ($is_admin): ?><th class="actions">Actions</th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($produits as $p): ?>
        <tr data-id="<?= (int)$p['id'] ?>">
          <td><?= htmlspecialchars($p['reference']) ?></td>
          <td><?= htmlspecialchars($p['nom']) ?></td>
          <td><?= htmlspecialchars($p['categorie']) ?></td>
          <td><?= number_format((float)$p['prix'], 2, ',', ' ') ?> FCFA</td>
          <td><?= (int)$p['stock_actuel'] ?><?= ($p['stock_actuel'] <= $p['seuil_alerte']) ? ' <span class="badge badge-alerte">Alerte</span>' : '' ?></td>
          <td><?= (int)$p['seuil_alerte'] ?></td>
          <?php if ($is_admin): ?>
          <td class="actions">
            <button type="button" class="btn btn-sm btn-secondary btn-edit-product" data-id="<?= (int)$p['id'] ?>">Modifier</button>
            <button type="button" class="btn btn-sm btn-danger btn-delete-product" data-id="<?= (int)$p['id'] ?>" data-ref="<?= htmlspecialchars($p['reference']) ?>">Supprimer</button>
          </td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if (empty($produits)): ?>
  <p>Aucun produit trouvé.</p>
  <?php endif; ?>
</main>

<?php if ($is_admin): ?>
<!-- Modal Ajout/Modification produit -->
<div class="modal-overlay" id="modal-product">
  <div class="modal">
    <h2 id="modal-product-title">Ajouter un produit</h2>
    <form id="form-product">
      <input type="hidden" name="id" id="product-id" value="">
      <div class="form-group">
        <label for="product-reference">Référence *</label>
        <input type="text" id="product-reference" name="reference" class="form-control" required>
      </div>
      <div class="form-group">
        <label for="product-nom">Nom *</label>
        <input type="text" id="product-nom" name="nom" class="form-control" required>
      </div>
      <div class="form-group">
        <label for="product-categorie">Catégorie *</label>
        <input type="text" id="product-categorie" name="categorie" class="form-control" required>
      </div>
      <div class="form-group">
        <label for="product-prix">Prix unitaire (FCFA) *</label>
        <input type="number" id="product-prix" name="prix" class="form-control" step="0.01" min="0" required>
      </div>
      <div class="form-group">
        <label for="product-stock">Stock initial</label>
        <input type="number" id="product-stock" name="stock_actuel" class="form-control" min="0" value="0">
      </div>
      <div class="form-group">
        <label for="product-seuil">Seuil d'alerte</label>
        <input type="number" id="product-seuil" name="seuil_alerte" class="form-control" min="0" value="5">
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" id="btn-cancel-product">Annuler</button>
        <button type="submit" class="btn btn-primary">Enregistrer</button>
      </div>
    </form>
  </div>
</div>
<!-- Modal Confirmation suppression -->
<div class="modal-overlay" id="modal-delete-product">
  <div class="modal">
    <h2>Confirmer la suppression</h2>
    <p>Voulez-vous vraiment supprimer le produit <strong id="delete-product-ref"></strong> ? Le stock doit être nul.</p>
    <div class="modal-actions">
      <button type="button" class="btn btn-secondary" id="btn-cancel-delete-product">Annuler</button>
      <button type="button" class="btn btn-danger" id="btn-confirm-delete-product">Supprimer</button>
    </div>
  </div>
</div>
<?php endif; ?>

<?php include INCLUDES_PATH . 'footer.php'; ?>
<script src="assets/js/app.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  var csrf = '<?= htmlspecialchars(secel_csrf_token()) ?>';
  var isAdmin = <?= $is_admin ? 'true' : 'false' ?>;
  if (isAdmin) {
    window.GestSecelProduits = { csrf: csrf };
  }
});
</script>
</body>
</html>
