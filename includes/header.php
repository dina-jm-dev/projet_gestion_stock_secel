<?php
/**
 * GestSecel - En-tête commun (navigation)
 */
if (!defined('GESTSECEL') || !secel_is_logged_in()) {
    return;
}
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<header class="site-header">
  <div class="header-inner">
    <a href="dashboard.php" class="logo"><?= htmlspecialchars(APP_NAME) ?></a>
    <nav class="nav-main">
      <a href="dashboard.php" class="nav-link <?= $current_page === 'dashboard' ? 'active' : '' ?>">Tableau de bord</a>
      <a href="produits.php" class="nav-link <?= $current_page === 'produits' ? 'active' : '' ?>">Produits</a>
      <a href="stocks.php" class="nav-link <?= $current_page === 'stocks' ? 'active' : '' ?>">Stocks</a>
      <a href="commandes.php" class="nav-link <?= $current_page === 'commandes' ? 'active' : '' ?>">Commandes</a>
      <?php if (secel_is_admin()): ?>
      <a href="utilisateurs.php" class="nav-link <?= $current_page === 'utilisateurs' ? 'active' : '' ?>">Utilisateurs</a>
      <?php endif; ?>
    </nav>
    <div class="header-user">
      <span class="user-name"><?= htmlspecialchars(secel_user()['prenom'] . ' ' . secel_user()['nom']) ?></span>
      <span class="user-role">(<?= secel_is_admin() ? 'Administrateur' : 'Employé' ?>)</span>
      <a href="logout.php" class="btn btn-logout">Déconnexion</a>
    </div>
    
  </div>
</header>
