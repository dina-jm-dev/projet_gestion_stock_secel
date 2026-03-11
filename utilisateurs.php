<?php
/**
 * GestSecel - Gestion des utilisateurs (Admin uniquement)
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

secel_require_admin();

$msg = '';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'ok') $msg = 'success';
    elseif ($_GET['msg'] === 'erreur') $msg = 'error';
}

$stmt = $pdo->query('SELECT id, login, role, nom, prenom, actif, date_creation FROM utilisateurs ORDER BY role, nom');
$utilisateurs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Utilisateurs - <?= htmlspecialchars(APP_NAME) ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-wrap">
<?php include INCLUDES_PATH . 'header.php'; ?>
<main class="main-content">
  <h1 class="page-title">Gestion des utilisateurs</h1>

  <?php if ($msg === 'success'): ?>
  <div class="alert alert-success" id="flash">Opération effectuée avec succès.</div>
  <?php endif; ?>
  <?php if ($msg === 'error'): ?>
  <div class="alert alert-error" id="flash">Une erreur est survenue.</div>
  <?php endif; ?>

  <div class="toolbar">
    <a href="ajouter_utilisateur.php" class="btn btn-primary">Ajouter un utilisateur</a>
  </div>

  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>Login</th>
          <th>Nom</th>
          <th>Prénom</th>
          <th>Rôle</th>
          <th>Actif</th>
          <th>Date création</th>
          <th class="actions">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($utilisateurs as $u): ?>
        <tr data-id="<?= (int)$u['id'] ?>">
          <td><?= htmlspecialchars($u['login']) ?></td>
          <td><?= htmlspecialchars($u['nom']) ?></td>
          <td><?= htmlspecialchars($u['prenom']) ?></td>
          <td><?= $u['role'] === 'admin' ? 'Administrateur' : 'Employé' ?></td>
          <td>
            <?php if ((int)$u['actif']): ?>
              <span class="badge badge-actif">Actif</span>
            <?php else: ?>
              <span class="badge badge-inactif">Inactif</span>
            <?php endif; ?>
          </td>
          <td><?= date('d/m/Y', strtotime($u['date_creation'])) ?></td>
          <td class="actions">
            <a href="ajouter_utilisateur.php?id=<?= (int)$u['id'] ?>" class="btn btn-sm btn-secondary">Modifier</a>
            <?php if ((int)$u['id'] !== secel_user()['id']): ?>
            <button type="button" class="btn btn-sm btn-danger btn-delete-user" data-id="<?= (int)$u['id'] ?>" data-login="<?= htmlspecialchars($u['login']) ?>">Supprimer / Désactiver</button>
            <?php endif; ?>
          </td>
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
  window.GestSecelUsers = { csrf: '<?= htmlspecialchars(secel_csrf_token()) ?>' };
});
</script>
</body>
</html>
