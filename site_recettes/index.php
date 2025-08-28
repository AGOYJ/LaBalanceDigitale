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

// Si requête AJAX, retourner uniquement le tableau HTML
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    ob_start();
    include 'recettes_table_ajax.php';
    echo ob_get_clean();
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Recettes</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="page-box">

        <div class="nav">
            <h1>Les recettes</h1>
            <a href="ingredients.php">Liste des ingrédients</a>
        </div>

        <div class="form-box">
            <a href="ajouter_recette.php" class="btn-primary">+ Ajouter une recette</a>
        </div>

        <form class="form-box" method="get" action="index.php">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Rechercher une recette..." autocomplete="off">
        </form>

        <div class="table-container" id="recettes-table-container">
            <?php include 'recettes_table_ajax.php'; ?>
        </div>
        
        <script>
        const searchInput = document.querySelector('input[name="search"]');
        const tableContainer = document.getElementById('recettes-table-container');
        searchInput.addEventListener('input', function() {
            const value = this.value;
            const xhr = new XMLHttpRequest();
            xhr.open('GET', 'index.php?search=' + encodeURIComponent(value), true);
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
