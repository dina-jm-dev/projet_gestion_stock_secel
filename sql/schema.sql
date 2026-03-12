-- GestSecel - Schéma base de données SECEL Cameroun
-- MySQL 8+

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Utilisateurs (admin + employés)
CREATE TABLE IF NOT EXISTS utilisateurs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  login VARCHAR(50) NOT NULL UNIQUE,
  mdp_hash VARCHAR(255) NOT NULL,
  role ENUM('admin', 'employe') NOT NULL DEFAULT 'employe',
  nom VARCHAR(100) NOT NULL,
  prenom VARCHAR(100) NOT NULL,
  actif TINYINT(1) NOT NULL DEFAULT 1,
  date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_login (login),
  INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Produits
CREATE TABLE IF NOT EXISTS produits (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reference VARCHAR(50) NOT NULL UNIQUE,
  nom VARCHAR(200) NOT NULL,
  categorie VARCHAR(100) NOT NULL,
  prix DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  stock_actuel INT NOT NULL DEFAULT 0,
  seuil_alerte INT NOT NULL DEFAULT 5,
  date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_modif DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_reference (reference),
  INDEX idx_categorie (categorie),
  INDEX idx_stock (stock_actuel)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Commandes (employé crée, admin valide/refuse)
CREATE TABLE IF NOT EXISTS commandes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  statut ENUM('en_attente', 'validee', 'partiellement_validee', 'refusee') NOT NULL DEFAULT 'en_attente',
  motif TEXT NULL,
  motif_refus TEXT NULL,
  validee_par INT UNSIGNED NULL,
  date_validation DATETIME NULL,
  FOREIGN KEY (user_id) REFERENCES utilisateurs(id) ON DELETE RESTRICT,
  FOREIGN KEY (validee_par) REFERENCES utilisateurs(id) ON DELETE SET NULL,
  INDEX idx_user (user_id),
  INDEX idx_statut (statut),
  INDEX idx_date (date_creation)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Détails d'une commande (lignes)
CREATE TABLE IF NOT EXISTS details_commande (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_commande INT UNSIGNED NOT NULL,
  id_produit INT UNSIGNED NOT NULL,
  qte_demandee INT NOT NULL,
  qte_validee INT NOT NULL DEFAULT 0,
  FOREIGN KEY (id_commande) REFERENCES commandes(id) ON DELETE CASCADE,
  FOREIGN KEY (id_produit) REFERENCES produits(id) ON DELETE RESTRICT,
  INDEX idx_commande (id_commande)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mouvements de stock (historique)
CREATE TABLE IF NOT EXISTS mouvements_stock (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  produit_id INT UNSIGNED NOT NULL,
  type ENUM('entree', 'sortie', 'ajustement_plus', 'ajustement_moins', 'inventaire', 'commande') NOT NULL,
  qte INT NOT NULL,
  stock_avant INT NOT NULL,
  stock_apres INT NOT NULL,
  date_mouvement DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  user_id INT UNSIGNED NULL,
  id_commande INT UNSIGNED NULL,
  motif TEXT NULL,
  FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE RESTRICT,
  FOREIGN KEY (user_id) REFERENCES utilisateurs(id) ON DELETE SET NULL,
  FOREIGN KEY (id_commande) REFERENCES commandes(id) ON DELETE SET NULL,
  INDEX idx_produit (produit_id),
  INDEX idx_date (date_mouvement),
  INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Token CSRF (optionnel, on peut aussi utiliser session)
-- Les tokens CSRF seront stockés en session PHP

SET FOREIGN_KEY_CHECKS = 1;
