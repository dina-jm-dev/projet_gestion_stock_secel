<?php
/**
 * GestSecel - Pied de page commun
 */
if (!defined('GESTSECEL')) {
    return;
}
?>
<footer class="pied-site">
  <div class="pied-interne">
    <span class="app-pied"><?= htmlspecialchars(APP_NAME) ?></span>
    <span class="annee-pied"><?= (int) APP_YEAR ?></span>
    <span class="droits-pied">Tous droits réservés</span>
  </div>
</footer>
