<?php
/**
 * GestSecel - Ajouter ou Modifier un produit
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

secel_require_login();
if (!secel_is_admin()) {
    header('Location: produits.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$produit = null;

if ($id > 0) {
    $st = $pdo->prepare('SELECT * FROM produits WHERE id = ?');
    $st->execute([$id]);
    $produit = $st->fetch();
}
$is_edit = $produit !== false && $produit !== null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $is_edit ? 'Modifier' : 'Ajouter' ?> un produit - <?= htmlspecialchars(APP_NAME) ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="conteneur-app">
<?php include INCLUDES_PATH . 'header.php'; ?>
<main class="contenu-principal">
  <h1 class="titre-page"><?= $is_edit ? 'Modifier le produit' : 'Ajouter un produit' ?></h1>
  
  <div class="carte" style="max-width: 600px; margin: 0 auto;">
    <form id="form-product">
      <input type="hidden" id="product-id" value="<?= $is_edit ? (int)$produit['id'] : '' ?>">
      <div class="form">
        <label for="product-reference">Référence *</label>
        <input type="text" id="product-reference" class="info-verification" value="<?= $is_edit ? htmlspecialchars($produit['reference']) : '' ?>" required>
      </div>
      <div class="form">
        <label for="product-nom">Nom *</label>
        <input type="text" id="product-nom" class="info-verification" value="<?= $is_edit ? htmlspecialchars($produit['nom']) : '' ?>" required>
      </div>
      <div class="form">
        <label for="product-categorie">Catégorie *</label>
        <input type="text" id="product-categorie" class="info-verification" value="<?= $is_edit ? htmlspecialchars($produit['categorie']) : '' ?>" required>
      </div>
      <div class="form">
        <label for="product-prix">Prix unitaire (FCFA) *</label>
        <input type="number" id="product-prix" class="info-verification" step="0.01" min="0" value="<?= $is_edit ? htmlspecialchars($produit['prix']) : '' ?>" required>
      </div>
      <div class="form">
        <label for="product-stock">Stock initial</label>
        <input type="number" id="product-stock" class="info-verification" min="0" value="<?= $is_edit ? htmlspecialchars($produit['stock_actuel']) : '0' ?>" <?= $is_edit ? 'disabled title="Stock modifiable dans le suivi des stocks"' : '' ?>>
      </div>
      <div class="form">
        <label for="product-seuil">Seuil d'alerte</label>
        <input type="number" id="product-seuil" class="info-verification" min="0" value="<?= $is_edit ? htmlspecialchars($produit['seuil_alerte']) : '5' ?>">
      </div>
      <div style="margin-top: 20px; display: flex; gap: 10px;">
        <button type="submit" class="bouton bouton-principal">Enregistrer</button>
        <a href="produits.php" class="bouton bouton-secondaire">Annuler</a>
      </div>
    </form>
  </div>
</main>

<?php include INCLUDES_PATH . 'footer.php'; ?>
<script src="assets/js/app.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  window.GestSecelProduits = { csrf: '<?= htmlspecialchars(secel_csrf_token()) ?>' };
  
  document.getElementById('form-product').addEventListener('submit', function(e) {
    e.preventDefault();
    var id = document.getElementById('product-id').value;
    var action = id ? 'update' : 'create';
    var data = {
      action: action,
      reference: document.getElementById('product-reference').value.trim(),
      nom: document.getElementById('product-nom').value.trim(),
      categorie: document.getElementById('product-categorie').value.trim(),
      prix: document.getElementById('product-prix').value,
      seuil_alerte: document.getElementById('product-seuil').value
    };
    if (action === 'update') {
        data.id = id;
    } else {
        data.stock_actuel = document.getElementById('product-stock').value || '0';
    }
    
    // Appel direct avec la méthode exposée (dépend de app.js si postJson y est accessible, mais postJson est emballée dans un IIFE...)
    // Wait, in app.js postJson was inside an IIFE, meaning it's not global!
    // Let's implement fetch directly here since we can't mutate postJson visibility simply without editing app.js again or doing a custom fetch.
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'api/produits.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    data.csrf_token = window.GestSecelProduits.csrf;
    var params = new URLSearchParams(data).toString();
    
    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4) {
          try {
              var json = JSON.parse(xhr.responseText);
              if (json.success) {
                  window.location.href = 'produits.php?msg=ok';
              } else {
                  alert(json.message || 'Erreur');
              }
          } catch(e) {
              alert('Erreur serveur');
          }
      }
    };
    xhr.send(params);
  });
});
</script>
</body>
</html>
