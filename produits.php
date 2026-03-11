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
    <a href="ajouter_produit.php" class="btn btn-primary">Ajouter un produit</a>
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
            <a href="ajouter_produit.php?id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-secondary">Modifier</a>
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
