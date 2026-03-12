<?php
/**
 * GestSecel - Commandes (Employé : créer + suivi | Admin : valider/refuser)
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

secel_require_login();

$is_admin = secel_is_admin();
$user_id = secel_user()['id'];
$id_commande = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Détail d'une commande
$commande = null;
$details = [];
if ($id_commande > 0) {
    $stmt = $pdo->prepare('SELECT c.*, u.nom as u_nom, u.prenom as u_prenom FROM commandes c JOIN utilisateurs u ON u.id = c.user_id WHERE c.id = ?');
    $stmt->execute([$id_commande]);
    $commande = $stmt->fetch();
    if ($commande && ($is_admin || (int)$commande['user_id'] === $user_id)) {
        $stmt2 = $pdo->prepare('SELECT d.*, p.reference, p.nom as produit_nom, p.stock_actuel FROM details_commande d JOIN produits p ON p.id = d.id_produit WHERE d.id_commande = ?');
        $stmt2->execute([$id_commande]);
        $details = $stmt2->fetchAll();
    } else {
        $commande = null;
    }
}

// Liste des commandes (pour la page liste) + produits pour formulaire nouvelle commande
$liste_commandes = [];
$prods = [];
if (!$commande) {
    // L'administrateur voit toutes les commandes ; l'employé voit uniquement les siennes
    $sql = 'SELECT c.id, c.date_creation, c.statut, c.motif, u.nom, u.prenom FROM commandes c JOIN utilisateurs u ON u.id = c.user_id';
    $params = [];
    if (!$is_admin) {
        $sql .= ' WHERE c.user_id = ?';
        $params[] = $user_id;
    }
    $sql .= ' ORDER BY c.date_creation DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $liste_commandes = $stmt->fetchAll();
    $prods = $pdo->query('SELECT id, reference, nom, stock_actuel FROM produits ORDER BY nom')->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Commandes - <?= htmlspecialchars(APP_NAME) ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="conteneur-app">
<?php include INCLUDES_PATH . 'header.php'; ?>
<main class="contenu-principal">
  <h1 class="titre-page">Commandes</h1>

  <?php if (isset($_GET['msg']) && $_GET['msg'] === 'ok'): ?>
  <div class="alerte alerte-succes" id="flash">Commande enregistrée.</div>
  <?php endif; ?>
  <?php if (isset($_GET['msg']) && $_GET['msg'] === 'validee'): ?>
  <div class="alerte alerte-succes" id="flash">Commande validée. Stocks mis à jour.</div>
  <?php endif; ?>
  <?php if (isset($_GET['msg']) && $_GET['msg'] === 'refusee'): ?>
  <div class="alerte alerte-info" id="flash">Commande refusée.</div>
  <?php endif; ?>

  <?php if ($commande): ?>
  <!-- Détail commande -->
  <p><a href="commandes.php" class="bouton bouton-secondaire">← Retour à la liste</a></p>
  <div class="conteneur-tableau">
    <table class="tableau-donnees">
      <tr><th>N° commande</th><td>#<?= (int)$commande['id'] ?></td></tr>
      <tr><th>Date</th><td><?= date('d/m/Y H:i', strtotime($commande['date_creation'])) ?></td></tr>
      <tr><th>Demandeur</th><td><?= htmlspecialchars($commande['u_prenom'] . ' ' . $commande['u_nom']) ?></td></tr>
      <tr><th>Statut</th><td><span class="badge badge-<?= htmlspecialchars($commande['statut']) ?>"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $commande['statut']))) ?></span></td></tr>
      <?php if ($commande['motif']): ?><tr><th>Motif demande</th><td><?= htmlspecialchars($commande['motif']) ?></td></tr><?php endif; ?>
      <?php if ($commande['motif_refus']): ?><tr><th>Motif refus</th><td><?= htmlspecialchars($commande['motif_refus']) ?></td></tr><?php endif; ?>
    </table>
  </div>
  <h3 style="margin-top:20px;">Lignes de la commande</h3>
  <div class="conteneur-tableau">
    <table class="tableau-donnees">
      <thead>
        <tr>
          <th>Produit</th>
          <th>Référence</th>
          <th>Quantité demandée</th>
          <th>Quantité validée</th>
          <?php if ($is_admin && $commande['statut'] === 'en_attente'): ?><th>Valider</th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($details as $d): ?>
        <tr>
          <td><?= htmlspecialchars($d['produit_nom']) ?></td>
          <td><?= htmlspecialchars($d['reference']) ?></td>
          <td><?= (int)$d['qte_demandee'] ?></td>
          <td><?= (int)$d['qte_validee'] ?> (stock dispo: <?= (int)$d['stock_actuel'] ?>)</td>
          <?php if ($is_admin && $commande['statut'] === 'en_attente'): ?>
          <td>
            <input type="number" min="0" max="<?= min((int)$d['qte_demandee'], (int)$d['stock_actuel']) ?>" value="<?= min((int)$d['qte_demandee'], (int)$d['stock_actuel']) ?>" class="qte-validee-input" data-detail-id="<?= (int)$d['id'] ?>" data-max="<?= (int)$d['stock_actuel'] ?>" style="width:70px;">
          </td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($is_admin && $commande['statut'] === 'en_attente'): ?>
  <div class="barre-outils" style="margin-top:16px;">
    <button type="button" class="bouton bouton-principal" id="btn-valider-commande" data-id="<?= (int)$commande['id'] ?>">Valider (quantités ci-dessus)</button>
    <button type="button" class="bouton bouton-danger" id="btn-refuser-commande" data-id="<?= (int)$commande['id'] ?>">Refuser la commande</button>
  </div>
  <div id="validation-msg"></div>
  <?php endif; ?>

  <?php else: ?>
  <!-- Liste des commandes -->
  <div class="barre-outils">
    <a href="ajouter_commande.php" class="bouton bouton-principal">Nouvelle commande</a>
  </div>

  <div class="conteneur-tableau">
    <table class="tableau-donnees">
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
        <?php foreach ($liste_commandes as $c): ?>
        <tr>
          <td>#<?= (int)$c['id'] ?></td>
          <td><?= date('d/m/Y H:i', strtotime($c['date_creation'])) ?></td>
          <?php if ($is_admin): ?><td><?= htmlspecialchars($c['prenom'] . ' ' . $c['nom']) ?></td><?php endif; ?>
          <td><span class="badge badge-<?= htmlspecialchars($c['statut']) ?>"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $c['statut']))) ?></span></td>
          <td><a href="commandes.php?id=<?= (int)$c['id'] ?>" class="bouton bouton-petit bouton-secondaire">Voir</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if (empty($liste_commandes)): ?>
  <p>Aucune commande.</p>
  <?php endif; ?>
  <?php endif; ?>
</main>



<?php include INCLUDES_PATH . 'footer.php'; ?>
<script src="assets/js/app.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  window.GestSecelCommandes = {
    csrf: '<?= htmlspecialchars(secel_csrf_token()) ?>',
    isAdmin: <?= $is_admin ? 'true' : 'false' ?>,
    produits: <?= json_encode($prods ?? []) ?>
  };
});
</script>
</body>
</html>
