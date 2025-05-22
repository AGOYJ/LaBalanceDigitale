-- Structure de la base de données pour le gestionnaire de recettes

-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

-- Table des recettes
CREATE TABLE IF NOT EXISTS recettes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(100) NOT NULL,
    description TEXT,
    mode_operatoire TEXT,
    utilisateur_id INT,
    prix_vente DECIMAL(10,2) DEFAULT 0,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id)
);

-- Table des ingrédients
CREATE TABLE IF NOT EXISTS ingredients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL UNIQUE,
    prix_kg DECIMAL(10,2) DEFAULT 0
);

-- Table de liaison recette/ingrédients
CREATE TABLE IF NOT EXISTS recette_ingredients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recette_id INT,
    ingredient_id INT,
    quantite DECIMAL(10,2),
    unite VARCHAR(20),
    FOREIGN KEY (recette_id) REFERENCES recettes(id),
    FOREIGN KEY (ingredient_id) REFERENCES ingredients(id)
);
