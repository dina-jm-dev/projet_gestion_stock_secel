<?php
/**
 * GestSecel - Ajuster le stock d'un produit
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

secel_require_login();
if (!secel_is_admin()) {
    header('Location: stocks.php');
    exit;
}

$produit_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$liste_produits = $pdo->query('SELECT id, reference, nom FROM produits ORDER BY nom')->fetchAll();

$produit_cible = null;
if ($produit_id > 0) {
    foreach ($liste_produits as $p) {
        if ((int)$p['id'] === $produit_id) {
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
  <title>Ajuster le stock - <?= htmlspecialchars(APP_NAME) ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-wrap">
<?php include INCLUDES_PATH . 'header.php'; ?>
<main class="main-content">
  <h1 class="page-title">Ajuster le stock</h1>
  
  <div class="card" style="max-width: 600px; margin: 0 auto;">
    <form id="form-mouvement">
      <?php if ($produit_cible): ?>
      <input type="hidden" id="mouvement-produit-id" value="<?= $produit_id ?>">
      <p style="margin-bottom: 15px;"><strong>Produit concerné :</strong> <?= htmlspecialchars($produit_cible['reference'] . ' - ' . $produit_cible['nom']) ?></p>
      <?php else: ?>
      <input type="hidden" id="mouvement-produit-id" value="">
      <div class="form-group">
        <label for="mouvement-produit-select">Produit *</label>
        <select id="mouvement-produit-select" class="form-control" required>
          <option value="">-- Choisir un produit --</option>
          <?php foreach ($liste_produits as $pr): ?>
          <option value="<?= (int)$pr['id'] ?>"><?= htmlspecialchars($pr['reference'] . ' - ' . $pr['nom']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
      
      <div class="form-group">
        <label>Type d'opération</label>
        <select id="mouvement-type" class="form-control" required>
          <option value="ajustement_plus">Augmentation</option>
          <option value="ajustement_moins">Diminution</option>
        </select>
      </div>
      <div class="form-group">
        <label for="mouvement-qte">Quantité *</label>
        <input type="number" id="mouvement-qte" class="form-control" min="1" required>
      </div>
      <div class="form-group">
        <label for="mouvement-motif">Motif de l'ajustement *</label>
        <textarea id="mouvement-motif" class="form-control" rows="2" placeholder="Saisie d'inventaire, perte, avarie..." required></textarea>
      </div>
      <div style="margin-top: 20px; display: flex; gap: 10px;">
        <button type="submit" class="btn btn-primary">Enregistrer le mouvement</button>
        <a href="stocks.php" class="btn btn-secondary">Annuler</a>
      </div>
    </form>
  </div>
</main>

<?php include INCLUDES_PATH . 'footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  var csrf = '<?= htmlspecialchars(secel_csrf_token()) ?>';
  var produitIdInput = document.getElementById('mouvement-produit-id');
  var produitSelect = document.getElementById('mouvement-produit-select');
  
  document.getElementById('form-mouvement').addEventListener('submit', function(e) {
    e.preventDefault();
    var pid = produitIdInput.value || (produitSelect ? produitSelect.value : '');
    
    if (!pid) {
      alert('Veuillez choisir un produit.');
      return;
    }
    
    var data = {
      action: 'ajustement',
      csrf_token: csrf,
      produit_id: pid,
      type: document.getElementById('mouvement-type').value,
      qte: document.getElementById('mouvement-qte').value,
      motif: document.getElementById('mouvement-motif').value.trim()
    };
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'api/stocks.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    
    var params = new URLSearchParams(data).toString();
    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4) {
          try {
              var json = JSON.parse(xhr.responseText);
              if (json.success) {
                  window.location.href = 'stocks.php?msg=ok';
              } else {
                  alert(json.message || 'Erreur lors de l\'enregistrement.');
              }
          } catch(e) {
              alert('Erreur technique côté serveur.');
          }
      }
    };
    xhr.send(params);
  });
});
</script>
</body>
</html>
