<?php
/**
 * GestSecel - Saisie d'un mouvement de stock (entrée, sortie ou ajustement)
 * Administrateur uniquement ; enregistrement dans mouvements_stock via api/stocks.php
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

secel_require_login();
if (!secel_is_admin()) {
    header('Location: stocks.php');
    exit;
}

$produit_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$liste_produits = $pdo->query('SELECT id, reference, nom FROM produits ORDER BY nom')->fetchAll();

$produit_cible = null;
if ($produit_id > 0) {
    foreach ($liste_produits as $p) {
        if ((int) $p['id'] === $produit_id) {
            $produit_cible = $p;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mouvement de stock - <?= htmlspecialchars(APP_NAME) ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="conteneur-app">
<?php include INCLUDES_PATH . 'header.php'; ?>
<main class="contenu-principal">
  <h1 class="titre-page">Enregistrer un mouvement de stock</h1>

  <div class="carte" style="max-width: 640px;">
    <form id="formulaire-mouvement" class="form" method="post" action="#">
      <?php if ($produit_cible): ?>
      <input type="hidden" id="mouvement-produit-id" value="<?= (int) $produit_id ?>">
      <p style="margin-bottom: 15px;"><strong>Produit :</strong> <?= htmlspecialchars($produit_cible['reference'] . ' — ' . $produit_cible['nom']) ?></p>
      <?php else: ?>
      <input type="hidden" id="mouvement-produit-id" value="">
      <div class="form">
        <label for="mouvement-produit-select">Produit</label>
        <select id="mouvement-produit-select" class="info-verification" required>
          <option value="">Choisir un produit</option>
          <?php foreach ($liste_produits as $pr): ?>
          <option value="<?= (int) $pr['id'] ?>"><?= htmlspecialchars($pr['reference'] . ' — ' . $pr['nom']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>

      <div class="form">
        <label for="mouvement-type">Type de mouvement</label>
        <select id="mouvement-type" class="info-verification" required>
          <option value="entree">Entrée</option>
          <option value="sortie">Sortie</option>
          <option value="ajustement_plus">Augmentation (ajustement)</option>
          <option value="ajustement_moins">Diminution (ajustement)</option>
        </select>
      </div>
      <div class="form">
        <label for="mouvement-qte">Quantité</label>
        <input type="number" id="mouvement-qte" class="info-verification" min="1" required>
      </div>
      <div class="form">
        <label for="mouvement-motif">Motif</label>
        <textarea id="mouvement-motif" class="info-verification" rows="3" placeholder="Réception fournisseur, prélèvement, inventaire…" required></textarea>
      </div>
      <div style="margin-top: 20px; display: flex; flex-wrap: wrap; gap: 10px;">
        <button type="submit" class="bouton bouton-principal">Enregistrer</button>
        <a href="stocks.php" class="bouton bouton-secondaire">Annuler</a>
        <a href="historique.php" class="bouton bouton-secondaire">Voir l'historique</a>
      </div>
    </form>
  </div>
</main>

<?php include INCLUDES_PATH . 'footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  var jetonCsrf = '<?= htmlspecialchars(secel_csrf_token()) ?>';
  var champProduitId = document.getElementById('mouvement-produit-id');
  var selectProduit = document.getElementById('mouvement-produit-select');

  document.getElementById('formulaire-mouvement').addEventListener('submit', function(e) {
    e.preventDefault();
    var idProduit = champProduitId.value || (selectProduit ? selectProduit.value : '');
    if (!idProduit) {
      alert('Veuillez choisir un produit.');
      return;
    }
    var donnees = {
      action: 'ajustement',
      csrf_token: jetonCsrf,
      produit_id: idProduit,
      type: document.getElementById('mouvement-type').value,
      qte: document.getElementById('mouvement-qte').value,
      motif: document.getElementById('mouvement-motif').value.trim()
    };
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'api/stocks.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.onreadystatechange = function() {
      if (xhr.readyState !== 4) return;
      try {
        var reponse = JSON.parse(xhr.responseText);
        if (reponse.success) {
          window.location.href = 'stocks.php?msg=ok';
        } else {
          alert(reponse.message || 'Erreur lors de l\'enregistrement.');
        }
      } catch (err) {
        alert('Erreur technique côté serveur.');
      }
    };
    xhr.send(new URLSearchParams(donnees).toString());
  });
});
</script>
</body>
</html>
