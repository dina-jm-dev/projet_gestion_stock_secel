<?php
/**
 * GestSecel - En-tête commun (navigation)
 */
if (!defined('GESTSECEL') || !secel_is_logged_in()) {
    return;
}
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<header class="entete-site">
  <div class="entete-interne">
    <a href="dashboard.php" class="logo"><?= htmlspecialchars(APP_NAME) ?></a>
    <nav class="nav-principale">
      <a href="dashboard.php" class="lien-nav <?= $current_page === 'dashboard' ? 'actif' : '' ?>">Tableau de bord</a>
      <a href="produits.php" class="lien-nav <?= $current_page === 'produits' ? 'actif' : '' ?>">Produits</a>
      <a href="stocks.php" class="lien-nav <?= $current_page === 'stocks' ? 'actif' : '' ?>">Stocks</a>
      <a href="commandes.php" class="lien-nav <?= $current_page === 'commandes' ? 'actif' : '' ?>">Commandes</a>
      <?php if (secel_is_admin()): ?>
      <a href="utilisateurs.php" class="lien-nav <?= $current_page === 'utilisateurs' ? 'actif' : '' ?>">Utilisateurs</a>
      <?php endif; ?>
    </nav>
    <div class="utilisateur-entete">
      <span class="nom-utilisateur"><?= htmlspecialchars(secel_user()['prenom'] . ' ' . secel_user()['nom']) ?></span>
      <span class="role-utilisateur">(<?= secel_is_admin() ? 'Administrateur' : 'Employé' ?>)</span>
      <a href="logout.php" class="bouton bouton-deconnexion">Déconnexion</a>
    </div>
    
  </div>
</header>
