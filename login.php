<?php
/**
 * GestSecel - Page de connexion
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$error = '';

if (secel_is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!secel_csrf_verify()) {
        $error = 'Session invalide. Veuillez réessayer.';
    } else {
        $login = trim($_POST['login'] ?? '');
        $mdp = $_POST['password'] ?? '';

        if ($login === '' || $mdp === '') {
            $error = 'Veuillez saisir le login et le mot de passe.';
        } else {
            // LOWER() pour accepter admin/Admin/ADMIN etc.
            $stmt = $pdo->prepare('SELECT id, login, mdp_hash, role, nom, prenom FROM utilisateurs WHERE LOWER(login) = LOWER(?) AND actif = 1');
            $stmt->execute([trim($login)]);
            $user = $stmt->fetch();

            if ($user && password_verify($mdp, $user['mdp_hash'])) {
                secel_login($user);
                $redirect = $_GET['redirect'] ?? 'dashboard.php';
                $redirect = (strpos($redirect, '/') === 0 || preg_match('#^https?://#', $redirect)) ? 'dashboard.php' : $redirect;
                header('Location: ' . $redirect);
                exit;
            }
            $error = 'Login ou mot de passe incorrect.';
        }
    }
}

$csrf = secel_csrf_token();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Connexion - <?= htmlspecialchars(APP_NAME) ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="page-connexion">
  <div class="connexion-formulaire">
    <h1><?= htmlspecialchars(APP_NAME) ?></h1>
    <?php if ($error): ?>
    <div class="alerte alerte-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post" action="login.php">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <div class="form">
        <label for="login">Identifiant</label>
        <input type="text" id="login" name="login" class="info-verification" required autofocus
               value="<?= htmlspecialchars($_POST['login'] ?? '') ?>">
      </div>
      <div class="form">
        <label for="password">Mot de passe</label>
        <input type="password" id="password" name="password" class="info-verification" required>
      </div>
      <button type="submit" class="bouton bouton-principal">Se connecter</button>
    </form>
  </div>
</body>
</html>
