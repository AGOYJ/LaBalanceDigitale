<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
// Récupérer la liste des ingrédients existants
$stmt = $pdo->query('SELECT id, nom, prix_kg FROM ingredients ORDER BY nom');
$ingredients_existants = $stmt->fetchAll(PDO::FETCH_ASSOC);
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre'] ?? '');
    $mode_operatoire = trim($_POST['mode_operatoire'] ?? '');
    $prix_vente = isset($_POST['prix_vente']) ? floatval($_POST['prix_vente']) : 0;
    $ingredients_existants = $_POST['ingredients_existants'] ?? [];
    $nouveaux_noms = $_POST['nouveaux_noms'] ?? [];
    $nouveaux_prix = $_POST['nouveaux_prix'] ?? [];
    $quantites = $_POST['quantites'] ?? [];
    $unites = $_POST['unites'] ?? [];
    if ($titre && $mode_operatoire && (!empty($ingredients_existants) || !empty($nouveaux_noms))) {
        $stmt = $pdo->prepare('INSERT INTO recettes (titre, description, mode_operatoire, utilisateur_id, prix_vente) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$titre, '', $mode_operatoire, $_SESSION['user_id'], $prix_vente]);
        $recette_id = $pdo->lastInsertId();
        $idx = 0;
        foreach ($quantites as $i => $qte) {
            $qte = floatval($qte);
            $unite = trim($unites[$i] ?? '');
            $ingredient_id = null;
            if (!empty($nouveaux_noms[$i])) {
                $nom_ingredient = trim($nouveaux_noms[$i]);
                $prix_kg = isset($nouveaux_prix[$i]) ? floatval($nouveaux_prix[$i]) : 0;
                // Vérifier si l'ingrédient existe déjà
                $stmtIng = $pdo->prepare('SELECT id FROM ingredients WHERE nom = ?');
                $stmtIng->execute([$nom_ingredient]);
                $rowIng = $stmtIng->fetch(PDO::FETCH_ASSOC);
                if ($rowIng) {
                    $ingredient_id = $rowIng['id'];
                } else {
                    $stmtIng = $pdo->prepare('INSERT INTO ingredients (nom, prix_kg) VALUES (?, ?)');
                    $stmtIng->execute([$nom_ingredient, $prix_kg]);
                    $ingredient_id = $pdo->lastInsertId();
                }
            } elseif (!empty($ingredients_existants[$i])) {
                $ingredient_id = intval($ingredients_existants[$i]);
            }
            if ($ingredient_id) {
                $stmtLink = $pdo->prepare('INSERT INTO recette_ingredients (recette_id, ingredient_id, quantite, unite) VALUES (?, ?, ?, ?)');
                $stmtLink->execute([$recette_id, $ingredient_id, $qte, $unite]);
            }
        }
        header('Location: recettes.php');
        exit;
    } else {
        $error = 'Veuillez remplir tous les champs obligatoires.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter une recette</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script>
    // Ajout dynamique d'ingrédients avec autocomplétion
    function addIngredientField() {
        const container = document.getElementById('ingredients-list');
        const idx = container.children.length;
        const div = document.createElement('div');
        div.className = 'ingredient-row';
        div.innerHTML = `
            <input type="text" name="ingredients_existants_nom[]" list="ingredients-list-datalist" placeholder="Chercher ingrédient existant..." style="width:28%">
            <input type="hidden" name="ingredients_existants[]">
            <label style="margin:0 6px;">
                <input type="checkbox" onchange="toggleNouveau(this, ${idx})"> Nouveau
            </label>
            <input type="text" name="nouveaux_noms[]" placeholder="Nom ingrédient" style="width:18%;display:none;">
            <input type="number" step="0.000001" name="nouveaux_prix[]" placeholder="Prix au kg (€)" style="width:14%;display:none;">
            <input type="number" step="0.01" name="quantites[]" placeholder="Quantité" required style="width:12%"> 
            <input type="text" name="unites[]" placeholder="Unité" required style="width:10%"> 
            <button type='button' class='btn-suppr' onclick='this.parentNode.remove()'>Supprimer</button>
        `;
        // Ajout d'un événement pour remplir le champ hidden avec l'id de l'ingrédient sélectionné
        setTimeout(() => {
            const inputNom = div.querySelector('input[list="ingredients-list-datalist"]');
            const inputId = div.querySelector('input[name="ingredients_existants[]"]');
            inputNom.addEventListener('input', function() {
                const val = this.value;
                const option = Array.from(document.getElementById('ingredients-list-datalist').options).find(opt => opt.value === val);
                inputId.value = option ? option.getAttribute('data-id') : '';
            });
        }, 0);
        container.appendChild(div);
    }
    function toggleNouveau(checkbox, idx) {
        const row = checkbox.closest('.ingredient-row');
        const inputAuto = row.querySelector('input[list="ingredients-list-datalist"]');
        const inputNom = row.querySelector('input[name="nouveaux_noms[]"]');
        const inputPrix = row.querySelector('input[name="nouveaux_prix[]"]');
        if (checkbox.checked) {
            inputAuto.disabled = true;
            inputNom.style.display = '';
            inputPrix.style.display = '';
            inputNom.required = true;
            inputPrix.required = true;
        } else {
            inputAuto.disabled = false;
            inputNom.style.display = 'none';
            inputPrix.style.display = 'none';
            inputNom.required = false;
            inputPrix.required = false;
        }
    }
    window.onload = function() { addIngredientField(); };
    </script>
    <datalist id="ingredients-list-datalist">
        <?php foreach ($ingredients_existants as $ing): ?>
            <option value="<?= htmlspecialchars($ing['nom']) ?>" data-id="<?= $ing['id'] ?>" data-prix="<?= $ing['prix_kg'] ?>">
                <?= htmlspecialchars($ing['nom']) ?>
            </option>
        <?php endforeach; ?>
    </datalist>
</head>
<body>
<nav class="menu">
    <a href="dashboard.php">Accueil</a> |
    <a href="recettes.php">Toutes les recettes</a> |
    <a href="ingredients.php">Liste des ingrédients</a> |
    <a href="index.php?logout=1">Déconnexion</a>
</nav>
<div class="recette-box" style="max-width:700px;">
    <a href="recettes.php" style="display:inline-block;background:#1976d2;color:#fff;padding:8px 18px;border-radius:6px;text-decoration:none;font-weight:bold;margin-bottom:18px;">← Retour à la liste des recettes</a>
    <h1 style="color:#1976d2;text-align:center;margin-bottom:24px;">Ajouter une recette</h1>
    <?php if ($error): ?>
        <div style="color:#e74c3c;background:#fff0f0;border:1px solid #e74c3c;border-radius:6px;padding:10px;margin-bottom:18px;text-align:center;font-weight:500;"> <?= htmlspecialchars($error) ?> </div>
    <?php endif; ?>
    <form method="post" style="max-width:520px;margin:auto;">
        <label style="font-weight:500;">Titre de la recette :</label>
        <input type="text" name="titre" required><br>
        <label style="font-weight:500;">Prix de vente (€) :</label>
        <input type="number" step="0.01" min="0" name="prix_vente" required><br>
        <label style="font-weight:500;">Ingrédients :</label>
        <div id="ingredients-list"></div>
        <button type="button" onclick="addIngredientField()" style="background:#1976d2;color:#fff;border:none;border-radius:6px;padding:8px 18px;margin:10px 0 18px 0;cursor:pointer;font-weight:bold;">+ Ajouter un ingrédient</button><br>
        <label style="font-weight:500;">Mode opératoire :</label>
        <textarea name="mode_operatoire" required style="width:100%;height:100px;border-radius:6px;border:1px solid #b0b8d1;padding:10px;"></textarea><br>
        <button type="submit" style="background:#43a047;color:#fff;border:none;border-radius:8px;padding:12px 0;width:100%;font-size:1.1em;font-weight:bold;cursor:pointer;margin-top:8px;box-shadow:0 2px 8px #b0b8d1;">Enregistrer la recette</button>
    </form>
</div>
</body>
</html>
