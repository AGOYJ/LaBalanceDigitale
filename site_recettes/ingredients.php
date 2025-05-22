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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des ingrédients</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<nav class="menu">
    <a href="dashboard.php">Accueil</a> |
    <a href="recettes.php">Liste des recettes</a> |
    <a href="ingredients.php">Liste des ingrédients</a> |
    <a href="index.php?logout=1">Déconnexion</a>
</nav>
<div class="recette-box" style="max-width:700px;">
    <h1>Ingrédients disponibles</h1>
    <?php if (!empty($error)): ?>
        <div style="color:#e74c3c;background:#fff0f0;border:1px solid #e74c3c;border-radius:6px;padding:10px;margin-bottom:18px;text-align:center;font-weight:500;"> <?= htmlspecialchars($error) ?> </div>
    <?php endif; ?>
    <form method="post" style="margin-bottom:18px;display:flex;gap:12px;align-items:center;">
        <input type="text" name="nom" placeholder="Nom de l'ingrédient" required style="width:40%">
        <input type="number" step="0.000001" name="prix_kg" placeholder="Prix au kg (€)" required style="width:30%">
        <button type="submit" name="add_ingredient" style="background:#43a047;color:#fff;border:none;border-radius:6px;padding:8px 18px;font-weight:bold;">+ Ajouter</button>
    </form>
    <form class="search-bar" method="get" action="ingredients.php" style="margin-bottom:18px;display:flex;gap:12px;align-items:center;">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Rechercher un ingrédient...">
        <button type="submit">Rechercher</button>
        <?php if ($search): ?>
            <a href="ingredients.php" style="background:#d32f2f;color:#fff;padding:8px 14px;border-radius:6px;text-decoration:none;font-weight:bold;">Réinitialiser</a>
        <?php endif; ?>
    </form>
    <table class="ingredients-table">
        <tr style="background:#eee;font-weight:bold;">
            <td>Nom</td>
            <td>Prix au kg (€)</td>
            <td>Prix au g (€)</td>
            <td>Actions</td>
        </tr>
        <?php foreach ($ingredients as $ing): ?>
        <tr>
            <td><?= htmlspecialchars($ing['nom']) ?></td>
            <td>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="id" value="<?= $ing['id'] ?>">
                    <input type="number" step="0.000001" name="prix_kg" value="<?= htmlspecialchars($ing['prix_kg']) ?>" style="width:90px;">
                    <button type="submit" name="edit_ingredient" style="background:#1976d2;color:#fff;border:none;border-radius:4px;padding:4px 10px;margin-left:4px;">💾</button>
                </form>
            </td>
            <td><?= number_format($ing['prix_kg']/1000, 6, ',', ' ') ?></td>
            <td>
                <form method="post" style="display:inline;" onsubmit="return confirm('Supprimer cet ingrédient ?');">
                    <input type="hidden" name="id" value="<?= $ing['id'] ?>">
                    <button type="submit" name="delete_ingredient" class="btn-suppr">🗑️</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php if (empty($ingredients)): ?>
        <div style="color:#888;text-align:center;margin-top:18px;">Aucun ingrédient enregistré.</div>
    <?php endif; ?>
</div>
</body>
</html>
