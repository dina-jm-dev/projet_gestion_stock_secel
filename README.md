# GestSecel — Gestion des stocks SECEL Cameroun

Application web de gestion des stocks pour SECEL (Douala), avec rôles **Administrateur** et **Employé**.

## Structure du projet

```
projet_gestion_stock_secel/
├── config/
│   ├── config.php      # Constantes, chemins, locale
│   └── database.php    # Connexion PDO MySQL
├── includes/
│   ├── auth.php        # Sessions, CSRF, RBAC (secel_require_login, secel_require_admin)
│   ├── header.php      # En-tête avec navigation
│   └── footer.php      # Pied de page
├── assets/
│   ├── css/
│   │   └── style.css   # Thème bleu et blanc, responsive
│   └── js/
│       └── app.js      # Modales, AJAX (produits, stocks, commandes, utilisateurs)
├── api/
│   ├── produits.php    # CRUD produits (admin)
│   ├── stocks.php      # Ajustement, historique (admin)
│   ├── commandes.php   # Créer, valider, refuser
│   └── utilisateurs.php # CRUD utilisateurs (admin)
├── sql/
│   └── schema.sql     # Schéma MySQL (tables utilisateurs, produits, commandes, details_commande, mouvements_stock)
├── index.php          # Redirection vers dashboard ou login
├── login.php          # Connexion
├── logout.php         # Déconnexion
├── dashboard.php      # Tableau de bord
├── produits.php       # Liste / CRUD produits
├── stocks.php         # Stocks, ajustements, historique
├── commandes.php      # Liste, détail, nouvelle commande, validation
├── utilisateurs.php   # Gestion utilisateurs (admin)
└── install.php        # Création BDD + comptes initiaux (à exécuter une fois)
```

## Installation

1. **Environnement** : PHP 8+, MySQL 8+, Apache (WAMP/XAMPP ou équivalent).

2. **Base de données**  
   - Créer une base `gestion_stock_secel` 
   - Ou importer `sql/schema.sql` puis exécuter une fois **install.php** dans le navigateur pour créer les utilisateurs initiaux.

3. **Configuration**  
   - Modifier si besoin `config/database.php` (hôte, nom de base, utilisateur, mot de passe).

4. **Accès**  
   - `http://localhost/projet_gestion_stock_secel/` ou `login.php` pour se connecter.

## Rôles

| Fonctionnalité              | Administrateur | Employé |
|----------------------------|----------------|---------|
| Tableau de bord             | Oui            | Oui     |
| Produits (consultation)     | Oui            | Oui     |
| Produits (ajout / modification / suppression) | Oui | Non  |
| Stocks (consultation)       | Oui            | Oui     |
| Stocks (ajustement, historique) | Oui        | Non     |
| Commandes (créer)           | —              | Oui     |
| Commandes (valider / refuser) | Oui          | Non     |
| Utilisateurs                | Oui            | Non     |

## Sécurité

- Sessions PHP avec régénération d’ID.
- Token CSRF sur les formulaires et requêtes AJAX.
- Requêtes préparées PDO (pas de concaténation SQL).
- Contrôle d’accès par rôle (RBAC) dans chaque page et API.

## Design

- Thème **bleu et blanc**, sans dégradé.
- Interface simple, lisible, responsive (mobile et desktop).
- Animations limitées (messages, modales).

Toute l’interface est en **français**.
