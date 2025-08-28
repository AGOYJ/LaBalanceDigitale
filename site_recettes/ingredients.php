<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
// Ajout d'un ingrédient
if (isset($_POST['add_ingredient'])) {
    $nom = trim($_POST['nom'] ?? '');
    $prix_kg = floatval($_POST['prix_kg'] ?? 0);
    if ($nom) {
        // Vérifier si l'ingrédient existe déjà
        $stmt = $pdo->prepare('SELECT id FROM ingredients WHERE nom = ?');
        $stmt->execute([$nom]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            $stmt = $pdo->prepare('INSERT INTO ingredients (nom, prix_kg) VALUES (?, ?)');
            $stmt->execute([$nom, $prix_kg]);
        }
        // sinon, ne rien faire (ou éventuellement mettre à jour le prix si souhaité)
    }
    header('Location: ingredients.php');
    exit;
}
// Modification du prix d'un ingrédient
if (isset($_POST['edit_ingredient'])) {
    $id = intval($_POST['id']);
    $prix_kg = floatval($_POST['prix_kg']);
    $stmt = $pdo->prepare('UPDATE ingredients SET prix_kg = ? WHERE id = ?');
    $stmt->execute([$prix_kg, $id]);
    header('Location: ingredients.php');
    exit;
}
// Suppression d'un ingrédient
if (isset($_POST['delete_ingredient'])) {
    $id = intval($_POST['id']);
    try {
        $stmt = $pdo->prepare('DELETE FROM ingredients WHERE id = ?');
        $stmt->execute([$id]);
    } catch (PDOException $e) {
        $error = "Impossible de supprimer cet ingrédient car il est utilisé dans une ou plusieurs recettes.";
    }
    if (!isset($error)) {
        header('Location: ingredients.php');
        exit;
    }
}
// Gestion de la recherche
$search = trim($_GET['search'] ?? '');
$where = '';
$params = [];
if ($search !== '') {
    $where = 'WHERE nom LIKE ?';
    $params[] = "%$search%";
}
// Récupérer tous les ingrédients (avec recherche)
$stmt = $pdo->prepare('SELECT id, nom, prix_kg FROM ingredients ' . $where . ' ORDER BY nom');
$stmt->execute($params);
$ingredients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Si requête AJAX, retourner uniquement le tableau HTML
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    ob_start();
    include 'ingredients_table_ajax.php';
    echo ob_get_clean();
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des ingrédients</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="page-box">
        
        <div class="nav">
            <h1>Les ingrédients</h1>
            <a href="index.php">Liste des recettes</a>
        </div>
    

        <?php if (!empty($error)): ?>
            <div style="error"> <?= htmlspecialchars($error) ?> </div>
        <?php endif; ?> 

        <form class="form-box" method="post">
            <input type="text" name="nom" placeholder="Nom de l'ingrédient" required style="width:30%">
            <input type="number" step="0.001" name="prix_kg" placeholder="Prix au kg (€)" required style="width:30%">
            <button type="submit" name="add_ingredient" class="btn-primary">+ Ajouter</button>
        </form>

        <form class="form-box" method="get" action="ingredients.php">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Rechercher un ingrédient..." autocomplete="off">
        </form>

        <div class="table-container" id="ingredients-table-container">
            <?php include 'ingredients_table_ajax.php'; ?>
        </div>

            <script>
            const searchInput = document.querySelector('input[name="search"]');
            const tableContainer = document.getElementById('ingredients-table-container');
            searchInput.addEventListener('input', function() {
                const value = this.value;
                const xhr = new XMLHttpRequest();
                xhr.open('GET', 'ingredients.php?search=' + encodeURIComponent(value), true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        tableContainer.innerHTML = xhr.responseText;
                    }
                };
                xhr.send();
            });
            </script>

    </div>
</body>
</html>
