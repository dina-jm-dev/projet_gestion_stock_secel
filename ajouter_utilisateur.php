<?php
/**
 * GestSecel - Ajouter ou Modifier un utilisateur
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

secel_require_login();
secel_require_admin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user = null;

if ($id > 0) {
    $st = $pdo->prepare('SELECT * FROM utilisateurs WHERE id = ?');
    $st->execute([$id]);
    $user = $st->fetch();
}
$is_edit = $user !== false && $user !== null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $is_edit ? 'Modifier' : 'Ajouter' ?> un utilisateur - <?= htmlspecialchars(APP_NAME) ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-wrap">
<?php include INCLUDES_PATH . 'header.php'; ?>
<main class="main-content">
  <h1 class="page-title"><?= $is_edit ? 'Modifier l\'utilisateur' : 'Ajouter un utilisateur' ?></h1>
  
  <div class="card" style="max-width: 600px; margin: 0 auto;">
    <form id="form-user">
      <input type="hidden" id="user-id" value="<?= $is_edit ? (int)$user['id'] : '' ?>">
      
      <div class="form-group">
        <label for="user-login">Login *</label>
        <input type="text" id="user-login" class="form-control" value="<?= $is_edit ? htmlspecialchars($user['login']) : '' ?>" required>
      </div>
      
      <div class="form-group">
        <label for="user-password">Mot de passe <?= $is_edit ? '<span id="pwd-optional" style="font-size:0.9em;color:#666;">(laisser vide pour ne pas changer)</span>' : '*' ?></label>
        <input type="password" id="user-password" class="form-control" autocomplete="new-password" <?= $is_edit ? '' : 'required' ?>>
      </div>
      
      <div class="form-group">
        <label for="user-nom">Nom *</label>
        <input type="text" id="user-nom" class="form-control" value="<?= $is_edit ? htmlspecialchars($user['nom']) : '' ?>" required>
      </div>
      
      <div class="form-group">
        <label for="user-prenom">Prénom *</label>
        <input type="text" id="user-prenom" class="form-control" value="<?= $is_edit ? htmlspecialchars($user['prenom']) : '' ?>" required>
      </div>
      
      <div class="form-group">
        <label for="user-role">Rôle *</label>
        <select id="user-role" class="form-control" required>
          <option value="employe" <?= ($is_edit && $user['role'] === 'employe') ? 'selected' : '' ?>>Employé</option>
          <option value="admin" <?= ($is_edit && $user['role'] === 'admin') ? 'selected' : '' ?>>Administrateur</option>
        </select>
      </div>
      
      <div class="form-group" style="margin-top: 15px;">
        <label>
          <input type="checkbox" id="user-actif" value="1" <?= (!$is_edit || (int)$user['actif'] === 1) ? 'checked' : '' ?>>
          Compte actif
        </label>
      </div>
      
      <div style="margin-top: 20px; display: flex; gap: 10px;">
        <button type="submit" class="btn btn-primary">Enregistrer</button>
        <a href="utilisateurs.php" class="btn btn-secondary">Annuler</a>
      </div>
    </form>
  </div>
</main>

<?php include INCLUDES_PATH . 'footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  var csrf = '<?= htmlspecialchars(secel_csrf_token()) ?>';
  
  document.getElementById('form-user').addEventListener('submit', function(e) {
    e.preventDefault();
    var id = document.getElementById('user-id').value;
    var action = id ? 'update' : 'create';
    
    var data = {
      action: action,
      csrf_token: csrf,
      login: document.getElementById('user-login').value.trim(),
      nom: document.getElementById('user-nom').value.trim(),
      prenom: document.getElementById('user-prenom').value.trim(),
      role: document.getElementById('user-role').value,
      actif: document.getElementById('user-actif').checked ? '1' : '0'
    };
    
    var pwd = document.getElementById('user-password').value;
    if (action === 'update') {
      data.id = id;
      if (pwd) data.password = pwd;
    } else {
      data.password = pwd;
    }
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'api/utilisateurs.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    
    var params = new URLSearchParams(data).toString();
    
    xhr.onreadystatechange = function() {
      if (xhr.readyState === 4) {
          try {
              var json = JSON.parse(xhr.responseText);
              if (json.success) {
                  window.location.href = 'utilisateurs.php?msg=ok';
              } else {
                  alert(json.message || 'Erreur.');
              }
          } catch(err) {
              alert('Erreur serveur.');
          }
      }
    };
    xhr.send(params);
  });
});
</script>
</body>
</html>
