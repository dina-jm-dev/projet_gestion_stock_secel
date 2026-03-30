<?php
/**
 * GestSecel - Historique des mouvements de stock (administrateur uniquement)
 * Données issues de la table mouvements_stock (pas de table dupliquée).
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

secel_require_admin();

// Jointure produit + utilisateur pour affichage lisible
$requete = '
    SELECT m.id, m.type, m.qte, m.date_mouvement, m.motif,
           p.reference, p.nom AS nom_produit,
           u.prenom, u.nom AS nom_utilisateur
    FROM mouvements_stock m
    INNER JOIN produits p ON p.id = m.produit_id
    LEFT JOIN utilisateurs u ON u.id = m.user_id
    ORDER BY m.date_mouvement DESC
    LIMIT 500
';
$mouvements = $pdo->query($requete)->fetchAll();

/**
 * Libellé français du type de mouvement (valeurs ENUM en base).
 */
function libelle_type_mouvement(string $type): string
{
    $libelles = [
        'entree' => 'Entrée',
        'sortie' => 'Sortie',
        'ajustement_plus' => 'Augmentation',
        'ajustement_moins' => 'Diminution',
        'inventaire' => 'Inventaire',
        'commande' => 'Commande',
    ];
    return $libelles[$type] ?? $type;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Historique des mouvements - <?= htmlspecialchars(APP_NAME) ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="conteneur-app">
<?php include INCLUDES_PATH . 'header.php'; ?>
<main class="contenu-principal">
  <h1 class="titre-page">Historique des mouvements de stock</h1>
  <p style="margin-bottom: 16px; color: var(--gris-700);">Les 500 derniers mouvements enregistrés.</p>

  <div class="barre-outils">
    <a href="ajouter_stock.php" class="bouton bouton-principal">Enregistrer un mouvement</a>
    <a href="stocks.php" class="bouton bouton-secondaire">Retour aux stocks</a>
  </div>

  <div class="conteneur-tableau">
    <table class="tableau-donnees">
      <thead>
        <tr>
          <th>Date</th>
          <th>Produit</th>
          <th>Référence</th>
          <th>Type</th>
          <th>Quantité</th>
          <th>Utilisateur</th>
          <th>Motif</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($mouvements as $ligne): ?>
        <tr>
          <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($ligne['date_mouvement']))) ?></td>
          <td><?= htmlspecialchars($ligne['nom_produit']) ?></td>
          <td><?= htmlspecialchars($ligne['reference']) ?></td>
          <td><?= htmlspecialchars(libelle_type_mouvement($ligne['type'])) ?></td>
          <td><?= (int) $ligne['qte'] ?></td>
          <td><?= htmlspecialchars(trim(($ligne['prenom'] ?? '') . ' ' . ($ligne['nom_utilisateur'] ?? '')) ?: '—') ?></td>
          <td><?= htmlspecialchars($ligne['motif'] ?? '') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if (empty($mouvements)): ?>
  <p>Aucun mouvement enregistré pour le moment.</p>
  <?php endif; ?>
</main>
<?php include INCLUDES_PATH . 'footer.php'; ?>
</body>
</html>
