<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
$id = intval($_GET['id'] ?? 0);
if (!$id) {
    echo '<p style="color:red;text-align:center;">Recette introuvable.</p>';
    exit;
}
// Récupérer la recette
$stmt = $pdo->prepare('SELECT * FROM recettes WHERE id = ?');
$stmt->execute([$id]);
$recette = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$recette) {
    echo '<p style="color:red;text-align:center;">Recette introuvable.</p>';
    exit;
}
// Récupérer les ingrédients avec prix_kg
$sql = 'SELECT i.nom, ri.quantite, ri.unite, i.prix_kg FROM recette_ingredients ri JOIN ingredients i ON ri.ingredient_id = i.id WHERE ri.recette_id = ?';
$ingredients = $pdo->prepare($sql);
$ingredients->execute([$id]);
$ingredients = $ingredients->fetchAll(PDO::FETCH_ASSOC);
// Calcul du coût de fabrication
$cout_total = 0;
foreach ($ingredients as $ing) {
    $qte = floatval($ing['quantite']);
    $prix_kg = floatval($ing['prix_kg']);
    $unite = strtolower(trim($ing['unite']));
    $cout = 0;
    if ($prix_kg > 0) {
        if ($unite === 'g' || $unite === 'gramme' || $unite === 'grammes') {
            $cout = ($qte / 1000) * $prix_kg;
        } elseif ($unite === 'kg' || $unite === 'kilogramme' || $unite === 'kilogrammes') {
            $cout = $qte * $prix_kg;
        } elseif ($unite === 'l' || $unite === 'litre' || $unite === 'litres') {
            $cout = $qte * $prix_kg; // suppose prix_kg = prix/L pour liquides
        } else {
            $cout = $qte * $prix_kg;
        }
    }
    $cout_total += $cout;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($recette['titre']) ?> - Détail de la recette</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script>
    function updateQuantities() {
        const mult = parseFloat(document.getElementById('mult').value) || 1;
        document.querySelectorAll('.qte').forEach(function(input) {
            const base = parseFloat(input.getAttribute('data-base'));
            input.textContent = (base * mult).toFixed(2);
        });
        // Mettre à jour le coût total
        let total = 0;
        document.querySelectorAll('tr[data-cout-base]').forEach(function(row) {
            const base = parseFloat(row.getAttribute('data-cout-base'));
            total += base * mult;
        });
        document.getElementById('cout-total').textContent = total.toFixed(2).replace('.', ',') + ' €';
        updateBenefice();
    }
    function updateBenefice() {
        const prixVente = parseFloat(document.getElementById('prix_vente').value) || 0;
        const cout = parseFloat(document.getElementById('cout-total').textContent.replace(',', '.')) || 0;
        const mult = parseFloat(document.getElementById('mult').value) || 1;
        const benef = prixVente - cout;
        document.getElementById('benefice-block').textContent = prixVente > 0 ? 'Bénéfice : ' + benef.toFixed(2).replace('.', ',') + ' €' : '';
    }
    document.getElementById('prix_vente').addEventListener('input', updateBenefice);
    window.onload = function() { updateBenefice(); };
    </script>
</head>
<body>
<nav class="menu">
    <a href="index.php">Toutes les recettes</a> |
    <a href="ingredients.php">Liste des ingrédients</a>
</nav>
<?php
// Suppression de la recette si demandé
if (isset($_POST['delete_recette']) && $_POST['delete_recette'] == $recette['id']) {
    // Supprimer les liaisons ingrédients
    $del1 = $pdo->prepare('DELETE FROM recette_ingredients WHERE recette_id = ?');
    $del1->execute([$recette['id']]);
    // Supprimer la recette
    $del2 = $pdo->prepare('DELETE FROM recettes WHERE id = ?');
    $del2->execute([$recette['id']]);
    header('Location: recettes.php?deleted=1');
    exit;
}
?>
<div class="recette-box">
    <a href="recettes.php" class="btn-primary" style="margin-bottom:18px;">← Retour à la liste des recettes</a>
    <a href="modifier_recette.php?id=<?= $recette['id'] ?>" class="btn-primary" style="margin-left:10px;background:#43a047;">✏️ Modifier</a>
    <form method="post" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette recette ? Cette action est irréversible.');" style="display:inline-block;margin-left:10px;">
        <input type="hidden" name="delete_recette" value="<?= $recette['id'] ?>">
        <button type="submit" class="btn-suppr">🗑️ Supprimer</button>
    </form>
    <h1><?= htmlspecialchars($recette['titre']) ?></h1>
    <label>Multiplicateur :</label>
    <input type="number" id="mult" value="1" min="0.1" step="0.1" style="width:80px;" onchange="updateQuantities()"> <span>x les quantités</span>
    <h2>Ingrédients</h2>
    <table class="ingredients-table">
        <tr style="background:#eee;font-weight:bold;">
            <td>Nom</td>
            <td>Quantité</td>
            <td>Unité</td>
            <td>Prix/kg (€)</td>
            <td>Coût</td>
        </tr>
        <?php foreach ($ingredients as $ing):
            $qte = floatval($ing['quantite']);
            $prix_kg = floatval($ing['prix_kg']);
            $unite = strtolower(trim($ing['unite']));
            $cout = 0;
            if ($prix_kg > 0) {
                if ($unite === 'g' || $unite === 'gramme' || $unite === 'grammes') {
                    $cout = ($qte / 1000) * $prix_kg;
                } elseif ($unite === 'kg' || $unite === 'kilogramme' || $unite === 'kilogrammes') {
                    $cout = $qte * $prix_kg;
                } elseif ($unite === 'l' || $unite === 'litre' || $unite === 'litres') {
                    $cout = $qte * $prix_kg;
                } else {
                    $cout = $qte * $prix_kg;
                }
            }
        ?>
        <tr data-cout-base="<?= htmlspecialchars($cout) ?>">
            <td><?= htmlspecialchars($ing['nom']) ?></td>
            <td><span class="qte" data-base="<?= htmlspecialchars($ing['quantite']) ?>"><?= htmlspecialchars($ing['quantite']) ?></span></td>
            <td><?= htmlspecialchars($ing['unite']) ?></td>
            <td><?= number_format($ing['prix_kg'], 6, ',', ' ') ?></td>
            <td><span><?= number_format($cout, 2, ',', ' ') ?> €</span></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <div style="margin-bottom:10px;font-weight:bold;font-size:1.08em;color:#333;">
        Prix de vente : <span id="prix-vente-affichage"><?= isset($recette['prix_vente']) ? number_format($recette['prix_vente'], 2, ',', ' ') : '0,00' ?> €</span>
    </div>
    <div style="margin-bottom:18px;font-weight:bold;font-size:1.15em;color:#1976d2;">
        Coût de fabrication total : <span id="cout-total"><?= number_format($cout_total, 2, ',', ' ') ?> €</span>
    </div>
    <div id="benefice-block" style="margin-bottom:18px;font-weight:bold;font-size:1.1em;color:#43a047;">
        <?php
        if (isset($recette['prix_vente']) && $recette['prix_vente'] > 0) {
            $benefice = $recette['prix_vente'] - $cout_total;
            echo 'Bénéfice : ' . number_format($benefice, 2, ',', ' ') . ' €';
        }
        ?>
    </div>
    <h2>Mode opératoire</h2>
    <div style="white-space:pre-line; background:#f8f8f8; border-radius:6px; padding:12px;"> <?= htmlspecialchars($recette['mode_operatoire']) ?> </div>
</div>
</body>
</html>
