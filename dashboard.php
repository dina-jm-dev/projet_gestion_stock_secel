<?php
/**
 * GestSecel - Tableau de bord
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

secel_require_login();

$is_admin = secel_is_admin();
$user_id = secel_user()['id'];

// Stocks sous seuil (pour tous)
$stmt_critiques = $pdo->query('SELECT COUNT(*) FROM produits WHERE stock_actuel <= seuil_alerte AND stock_actuel >= 0');
$nb_stocks_critiques = (int) $stmt_critiques->fetchColumn();

// Commandes en attente (admin : toutes ; employé : les siennes)
if ($is_admin) {
    $stmt_attente = $pdo->query("SELECT COUNT(*) FROM commandes WHERE statut = 'en_attente'");
} else {
    $stmt_attente = $pdo->prepare('SELECT COUNT(*) FROM commandes WHERE statut = \'en_attente\' AND user_id = ?');
    $stmt_attente->execute([$user_id]);
}
$nb_commandes_attente = (int) $stmt_attente->fetchColumn();

// Nombre de produits (pour stats)
$nb_produits = (int) $pdo->query('SELECT COUNT(*) FROM produits')->fetchColumn();

// Dernières commandes (admin : 5 dernières ; employé : ses 5 dernières)
if ($is_admin) {
    $stmt_cmd = $pdo->query("SELECT c.id, c.date_creation, c.statut, u.nom, u.prenom 
      FROM commandes c 
      JOIN utilisateurs u ON u.id = c.user_id 
      ORDER BY c.date_creation DESC LIMIT 5");
} else {
    $stmt_cmd = $pdo->prepare('SELECT id, date_creation, statut FROM commandes WHERE user_id = ? ORDER BY date_creation DESC LIMIT 5');
    $stmt_cmd->execute([$user_id]);
}
$dernieres_commandes = $stmt_cmd->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tableau de bord - <?= htmlspecialchars(APP_NAME) ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-wrap">
<?php include INCLUDES_PATH . 'header.php'; ?>
<main class="main-content">
  <h1 class="page-title">Tableau de bord</h1>

  <div class="dashboard-cards">
    <div class="card">
      <h3>Stocks sous seuil d'alerte</h3>
      <div class="value"><?= $nb_stocks_critiques ?></div>
      <a href="stocks.php">Voir les stocks</a>
    </div>
    <div class="card">
      <h3>Commandes en attente</h3>
      <div class="value"><?= $nb_commandes_attente ?></div>
      <a href="commandes.php">Voir les commandes</a>
    </div>
    <div class="card">
      <h3>Nombre de produits</h3>
      <div class="value"><?= $nb_produits ?></div>
      <a href="produits.php">Voir les produits</a>
    </div>
    <?php if ($is_admin): ?>
    <div class="card">
      <h3>Gestion utilisateurs</h3>
      <div class="value">—</div>
      <a href="utilisateurs.php">Gérer les utilisateurs</a>
    </div>
    <?php endif; ?>
  </div>

  <section>
    <h2 style="font-size:1.1rem; margin-bottom:12px;"><?= $is_admin ? 'Dernières commandes à traiter' : 'Mes dernières commandes' ?></h2>
    <?php if (empty($dernieres_commandes)): ?>
    <p>Aucune commande.</p>
    <?php else: ?>
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>N°</th>
            <th>Date</th>
            <?php if ($is_admin): ?><th>Demandeur</th><?php endif; ?>
            <th>Statut</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($dernieres_commandes as $c): ?>
          <tr>
            <td>#<?= (int)$c['id'] ?></td>
            <td><?= date('d/m/Y H:i', strtotime($c['date_creation'])) ?></td>
            <?php if ($is_admin): ?><td><?= htmlspecialchars($c['prenom'] . ' ' . $c['nom']) ?></td><?php endif; ?>
            <td><span class="badge badge-<?= htmlspecialchars($c['statut']) ?>"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $c['statut']))) ?></span></td>
            <td><a href="commandes.php?id=<?= (int)$c['id'] ?>" class="btn btn-sm btn-secondary">Voir</a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </section>
</main>
<?php include INCLUDES_PATH . 'footer.php'; ?>
<script src="assets/js/app.js"></script>
</body>
</html>
