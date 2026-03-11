<?php
/**
 * GestSecel - Nouvelle commande
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

secel_require_login();

$prods = $pdo->query('SELECT id, reference, nom, stock_actuel FROM produits ORDER BY nom')->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nouvelle commande - <?= htmlspecialchars(APP_NAME) ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    .ligne-commande { margin-bottom: 15px; display: flex; gap: 10px; align-items: center; }
  </style>
</head>
<body class="app-wrap">
<?php include INCLUDES_PATH . 'header.php'; ?>
<main class="main-content">
  <h1 class="page-title">Initier une nouvelle commande</h1>
  
  <div class="card" style="max-width: 800px; margin: 0 auto;">
    <form id="form-nouvelle-commande">
      <div class="form-group">
        <label for="commande-motif">Motif de la demande *</label>
        <textarea id="commande-motif" name="motif" class="form-control" rows="2" placeholder="Ex: Réapprovisionnement du service IT" required></textarea>
      </div>
      
      <div class="form-group">
        <label>Produits à commander</label>
        <div id="lignes-commande">
          <!-- Template de ligne -->
          <div class="ligne-commande">
            <select name="produit_id[]" class="form-control produit-select" style="flex: 1;">
              <option value="">-- Choisir un produit --</option>
              <?php foreach ($prods as $pr): ?>
              <option value="<?= (int)$pr['id'] ?>"><?= htmlspecialchars($pr['reference'] . ' - ' . $pr['nom']) ?> (En stock: <?= (int)$pr['stock_actuel'] ?>)</option>
              <?php endforeach; ?>
            </select>
            <input type="number" name="qte[]" class="form-control" min="1" value="1" style="width:100px;" placeholder="Qté">
            <button type="button" class="btn btn-sm btn-danger btn-supprimer-ligne">X</button>
          </div>
        </div>
        <button type="button" class="btn btn-sm btn-secondary" id="btn-ajouter-ligne" style="margin-top: 10px;">+ Ajouter un produit</button>
      </div>
      
      <div style="margin-top: 20px; display: flex; gap: 10px;">
        <button type="submit" class="btn btn-primary">Créer la commande</button>
        <a href="commandes.php" class="btn btn-secondary">Annuler</a>
      </div>
    </form>
  </div>
</main>

<?php include INCLUDES_PATH . 'footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  var csrf = '<?= htmlspecialchars(secel_csrf_token()) ?>';
  var conteneurLignes = document.getElementById('lignes-commande');
  
  // Modèle de base pour le clonage
  var templateHtml = conteneurLignes.querySelector('.ligne-commande').outerHTML;

  // Ajouter une ligne
  document.getElementById('btn-ajouter-ligne').addEventListener('click', function() {
    conteneurLignes.insertAdjacentHTML('beforeend', templateHtml);
    // Réinitialiser la nouvelle ligne
    var select = conteneurLignes.lastElementChild.querySelector('select');
    var input = conteneurLignes.lastElementChild.querySelector('input');
    if(select) select.value = '';
    if(input) input.value = '1';
  });

  // Supprimer une ligne
  conteneurLignes.addEventListener('click', function(e) {
    if (e.target.classList.contains('btn-supprimer-ligne')) {
      if (conteneurLignes.querySelectorAll('.ligne-commande').length > 1) {
        e.target.closest('.ligne-commande').remove();
      } else {
        // Vider la ligne plutôt que la supprimer si c'est la seule
        e.target.closest('.ligne-commande').querySelector('select').value = '';
        e.target.closest('.ligne-commande').querySelector('input').value = '1';
      }
    }
  });

  // Soumission
  document.getElementById('form-nouvelle-commande').addEventListener('submit', function(e) {
    e.preventDefault();
    var motif = document.getElementById('commande-motif').value.trim();
    var selects = document.querySelectorAll('#lignes-commande select.produit-select');
    var inputs = document.querySelectorAll('#lignes-commande input[name="qte[]"]');
    
    var produit_id = [];
    var qte = [];
    
    for (var i = 0; i < selects.length; i++) {
      var v = selects[i].value;
      var q = parseInt(inputs[i].value || '0', 10);
      if (v && q > 0) {
        produit_id.push(encodeURIComponent(v));
        qte.push(encodeURIComponent(q));
      }
    }
    
    if (produit_id.length === 0) {
      alert('Veuillez ajouter au moins une ligne avec un produit et une quantité valide.');
      return;
    }
    
    var data = 'action=create&csrf_token=' + encodeURIComponent(csrf) + '&motif=' + encodeURIComponent(motif);
    for (var j = 0; j < produit_id.length; j++) {
      data += '&produit_id[]=' + produit_id[j];
      data += '&qte[]=' + qte[j];
    }
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'api/commandes.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    
    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4) {
          try {
              var json = JSON.parse(xhr.responseText);
              if (json.success) {
                  window.location.href = 'commandes.php?msg=ok&id=' + (json.id || '');
              } else {
                  alert(json.message || 'Erreur lors de la création de la commande.');
              }
          } catch(err) {
              alert('Erreur réseau / serveur.');
          }
      }
    };
    xhr.send(data);
  });
});
</script>
</body>
</html>
