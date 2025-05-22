<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
// Gestion recherche et tri
$search = trim($_GET['search'] ?? '');
$order = ($_GET['order'] ?? '') === 'alpha' ? 'alpha' : 'date';
$where = '';
$params = [];
if ($search !== '') {
    $where = 'WHERE r.titre LIKE ?';
    $params[] = "%$search%";
}
$orderBy = $order === 'alpha' ? 'r.titre ASC' : 'r.date_creation DESC';
$sql = "SELECT r.id, r.titre, r.date_creation FROM recettes r $where ORDER BY $orderBy";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$recettes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des recettes</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
    .search-bar { margin-bottom: 18px; display: flex; gap: 12px; align-items: center; }
    .search-bar input[type='text'] { padding: 7px; border-radius: 6px; border: 1px solid #b0b8d1; width: 220px; }
    .search-bar button, .search-bar a { background: #1976d2; color: #fff; border: none; border-radius: 6px; padding: 8px 16px; font-weight: bold; text-decoration: none; }
    .search-bar a.selected { background: #43a047; }
    </style>
</head>
<body>
<nav class="menu">
    <a href="dashboard.php">Accueil</a> |
    <a href="recettes.php">Liste des recettes</a> |
    <a href="ingredients.php">Liste des ingrédients</a> |
    <a href="index.php?logout=1">Déconnexion</a>
</nav>
<div class="recette-box" style="max-width:900px;">
    <h1>Toutes les recettes</h1>
    <a href="ajouter_recette.php" style="display:inline-block;background:#1976d2;color:#fff;padding:10px 22px;border-radius:8px;text-decoration:none;font-weight:bold;margin-bottom:18px;">+ Ajouter une recette</a>
    <form class="search-bar" method="get" action="recettes.php">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Rechercher une recette...">
        <button type="submit">Rechercher</button>
        <a href="recettes.php?order=alpha<?= $search ? '&search=' . urlencode($search) : '' ?>" class="<?= $order==='alpha'?'selected':'' ?>">Trier A-Z</a>
        <a href="recettes.php<?= $search ? '?search=' . urlencode($search) : '' ?>" class="<?= $order==='date'?'selected':'' ?>">Trier par date</a>
    </form>
    <table class="ingredients-table">
        <tr style="background:#eee;font-weight:bold;">
            <td>Titre</td>
            <td>Date</td>
            <td>Détail</td>
        </tr>
        <?php foreach ($recettes as $recette): ?>
        <tr>
            <td><?= htmlspecialchars($recette['titre']) ?></td>
            <td><?= htmlspecialchars($recette['date_creation']) ?></td>
            <td>
                <a href="recette.php?id=<?= $recette['id'] ?>" style="color:#1976d2;font-weight:bold;">Voir</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php if (empty($recettes)): ?>
        <div style="color:#888;text-align:center;margin-top:18px;">Aucune recette enregistrée.</div>
    <?php endif; ?>
</div>
</body>
</html>
