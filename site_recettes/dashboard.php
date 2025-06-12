<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Accueil</title>
    <link rel="stylesheet" href="assets/css/style.css"> 
</head>
<body>
<nav class="menu">
    <a href="dashboard.php">Accueil</a> |
    <a href="recettes.php">Liste des recettes</a> |
    <a href="ingredients.php">Liste des ingrédients</a> |
    <a href="index.php?logout=1">Déconnexion</a>
</nav>
<div class="dashboard-container">
    <div class="dashboard-card">
        <h1>Bienvenue sur votre gestionnaire de recettes !</h1>
        <p>La Balance Digitale est une application web dédiée aux artisans boulangers, pâtissiers, chocolatiers, et confiseurs. elle permet de centraliser, gérer et adapter facilement les recettes, en calculant automatiquement les quantités nécessaires selon la production, les information lié au coût de fabrication et d’autres informations utiles. Pensée pour les professionnels exigeants, elle facilite la création, la modification et l’expérimentation de nouvelles recettes.</p>
        <p>Utilisez le menu pour accéder rapidement aux principales fonctionnalités :</p>
        <ul class="dashboard-list">
            <li><a href="recettes.php">Voir toutes les recettes</a></li>
            <li><a href="ajouter_recette.php">Ajouter une nouvelle recette</a></li>
            <li><a href="ingredients.php">Voir la liste des ingrédients</a></li>
        </ul>
    </div>
</div>
</body>
</html>
