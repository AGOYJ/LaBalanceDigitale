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

// Récupérer les ingrédients
$sql = 'SELECT i.id, i.nom, ri.quantite, i.prix_kg FROM recette_ingredients ri JOIN ingredients i ON ri.ingredient_id = i.id WHERE ri.recette_id = ?';
$ingredients = $pdo->prepare($sql);
$ingredients->execute([$id]);
$ingredients = $ingredients->fetchAll(PDO::FETCH_ASSOC);

// Traitement de la modification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_recette'])) {
    // Quantités
    if (isset($_POST['quantites']) && isset($_POST['ingredient_ids'])) {
        foreach ($_POST['ingredient_ids'] as $idx => $ing_id) {
            $qte = floatval($_POST['quantites'][$idx]);
            $stmt = $pdo->prepare('UPDATE recette_ingredients SET quantite = ? WHERE recette_id = ? AND ingredient_id = ?');
            $stmt->execute([$qte, $id, $ing_id]);
        }
    }
    // Mode opératoire
    if (isset($_POST['mode_operatoire'])) {
        $stmt = $pdo->prepare('UPDATE recettes SET mode_operatoire = ? WHERE id = ?');
        $stmt->execute([$_POST['mode_operatoire'], $id]);
    }
    // Prix de vente
    if (isset($_POST['prix_vente'])) {
        $stmt = $pdo->prepare('UPDATE recettes SET prix_vente = ? WHERE id = ?');
        $stmt->execute([floatval($_POST['prix_vente']), $id]);
    }
    header("Location: recette.php?id=$id");
    exit;
}

// Recalcul du coût total
$cout_total = 0;
foreach ($ingredients as $ing) {
    $qte = floatval($ing['quantite']);
    $prix_kg = floatval($ing['prix_kg']);
    $cout = ($qte / 1000) * $prix_kg;
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
        // Quantités multipliées
        document.querySelectorAll('.qte-mult').forEach(function(span) {
            const base = parseFloat(span.getAttribute('data-base'));
            span.textContent = (base * mult).toFixed(2);
        });
        // Coût par ingrédient
        document.querySelectorAll('tr[data-cout-base]').forEach(function(row) {
            const base = parseFloat(row.getAttribute('data-cout-base'));
            const coutCell = row.querySelector('td:last-child span');
            coutCell.textContent = (base * mult).toFixed(2).replace('.', ',') + ' €';
        });
        // Coût total
        let total = 0;
        document.querySelectorAll('tr[data-cout-base]').forEach(function(row) {
            const base = parseFloat(row.getAttribute('data-cout-base'));
            total += base * mult;
        });
        document.getElementById('cout-total').textContent = total.toFixed(2).replace('.', ',') + ' €';
        // Prix de vente
        const prixVenteInput = document.getElementById('prix_vente');
        const prixVenteBase = parseFloat(prixVenteInput.getAttribute('data-base')) || 0;
        const prixVenteMult = prixVenteInput.value ? parseFloat(prixVenteInput.value) * mult : prixVenteBase * mult;
        document.getElementById('prix-vente-affichage').textContent = prixVenteMult.toFixed(2).replace('.', ',') + ' €';
        updateBenefice(prixVenteMult, total);
    }
    function updateBenefice(prixVenteMult = null, cout = null) {
        if (prixVenteMult === null) {
            const mult = parseFloat(document.getElementById('mult').value) || 1;
            const prixVenteInput = document.getElementById('prix_vente');
            const prixVenteBase = parseFloat(prixVenteInput.getAttribute('data-base')) || 0;
            prixVenteMult = prixVenteInput.value ? parseFloat(prixVenteInput.value) * mult : prixVenteBase * mult;
            cout = parseFloat(document.getElementById('cout-total').textContent.replace(',', '.')) || 0;
        }
        const benef = prixVenteMult - cout;
        document.getElementById('benefice-block').textContent = prixVenteMult > 0
            ? 'Bénéfice : ' + benef.toFixed(2).replace('.', ',') + ' €'
            : '';
    }
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('mult').addEventListener('input', updateQuantities);
        document.getElementById('prix_vente').addEventListener('input', updateQuantities);
        updateQuantities();
    });
    </script>
</head>
<body>
    <div class="page-box">
        <div class="nav">
            <h1><?= htmlspecialchars($recette['titre']) ?></h1>
            <a href="recettes.php">Liste des recettes</a>
        </div>
        <div class="form-box">
            <a href="recettes.php" class="btn-secondary">← Retour à la liste des recettes</a>
            <button type="submit" formaction="?delete=<?= $recette['id'] ?>" class="btn-primary" style="background:#e74c3c;" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette recette ? Cette action est irréversible.');">🗑️ Supprimer</button>
        </div>
        <div class="form-box">
            <label>Multiplicateur :</label>
            <input type="number" id="mult" value="1" min="0.1" step="0.1" style="width:80px;"> <span>x les quantités</span>
        </div>

        <form method="post">
            <input type="hidden" name="update_recette" value="1">
            
            <div class="add-recette-form-box">
                <h2>Ingrédients</h2>
                <table class="ingredients-table">
                    <tr style="background:#eee;font-weight:bold;">
                        <td>Nom</td>
                        <td>Quantité (g)</td>
                        <td>Quantité x facteur</td>
                        <td>Prix/kg (€)</td>
                        <td>Coût</td>
                    </tr>
                    <?php foreach ($ingredients as $idx => $ing):
                        $qte = floatval($ing['quantite']);
                        $prix_kg = floatval($ing['prix_kg']);
                        $cout = ($qte / 1000) * $prix_kg;
                    ?>
                    <tr data-cout-base="<?= htmlspecialchars($cout) ?>">
                        <td><?= htmlspecialchars($ing['nom']) ?></td>
                        <td>
                            <input type="number" name="quantites[]" value="<?= htmlspecialchars($ing['quantite']) ?>" step="0.01" style="width: 120px;">
                            <input type="hidden" name="ingredient_ids[]" value="<?= htmlspecialchars($ing['id']) ?>">
                        </td>
                        <td>
                            <span class="qte-mult" data-base="<?= htmlspecialchars($ing['quantite']) ?>"><?= htmlspecialchars($ing['quantite']) ?></span> g
                        </td>
                        <td><?= number_format($ing['prix_kg'], 6, ',', ' ') ?></td>
                        <td><span><?= number_format($cout, 2, ',', ' ') ?> €</span></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <div class="add-recette-form-box">
                <h2>Coût de fabrication et prix de vente</h2>
                <div>
                    Coût de fabrication total : <span id="cout-total"><?= number_format($cout_total, 2, ',', ' ') ?> €</span>
                </div>
                <div>
                    Prix de vente :
                    <input type="number" id="prix_vente" name="prix_vente" data-base="<?= isset($recette['prix_vente']) ? htmlspecialchars($recette['prix_vente']) : '0' ?>" value="<?= isset($recette['prix_vente']) ? htmlspecialchars($recette['prix_vente']) : '0' ?>" min="0" step="0.01" style="width:100px;">
                    <span id="prix-vente-affichage"><?= isset($recette['prix_vente']) ? number_format($recette['prix_vente'], 2, ',', ' ') : '0,00' ?> €</span>
                </div>
                <div id="benefice-block"></div>
            </div>
            <div class="add-recette-form-box">
                <h2>Mode opératoire</h2>
                <textarea name="mode_operatoire" style="width:100%;height:100px;border-radius:6px;border:1px solid #b0b8d1;padding:10px;margin-bottom:18px;"><?= htmlspecialchars($recette['mode_operatoire']) ?></textarea>
            </div>
            <button type="submit" class="btn-primary" style="width:100%;font-size:1.1em;">Enregistrer les modifications</button>
        </form>
    </div>
</body>
</html>
