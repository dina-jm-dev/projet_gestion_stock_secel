<?php
/**
 * GestSecel - Pied de page commun
 */
if (!defined('GESTSECEL')) {
    return;
}
?>
<footer class="site-footer">
  <div class="footer-inner">
    <span class="footer-app"><?= htmlspecialchars(APP_NAME) ?></span>
    <span class="footer-year"><?= (int) APP_YEAR ?></span>
    <span class="footer-rights">Tous droits réservés</span>
  </div>
</footer>
