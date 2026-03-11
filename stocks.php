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
    <a href="ajouter_stock.php" class="btn btn-primary">Ajuster un stock</a>
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
            <a href="ajouter_stock.php?id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-primary">Ajuster</a>
          </td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main>

<?php include INCLUDES_PATH . 'footer.php'; ?>
<script src="assets/js/app.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  window.GestSecelStocks = { csrf: '<?= htmlspecialchars(secel_csrf_token()) ?>', isAdmin: <?= $is_admin ? 'true' : 'false' ?> };
});
</script>
</body>
</html>
