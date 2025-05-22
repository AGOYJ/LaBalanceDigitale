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
// Récupérer les ingrédients existants
$stmt = $pdo->query('SELECT id, nom, prix_kg FROM ingredients ORDER BY nom');
$ingredients_existants = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Récupérer les ingrédients de la recette
$sql = 'SELECT ri.id, i.nom, i.id as ingredient_id, ri.quantite, ri.unite, i.prix_kg FROM recette_ingredients ri JOIN ingredients i ON ri.ingredient_id = i.id WHERE ri.recette_id = ?';
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$ingredients_recette = $stmt->fetchAll(PDO::FETCH_ASSOC);
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre'] ?? '');
    $mode_operatoire = trim($_POST['mode_operatoire'] ?? '');
    $prix_vente = isset($_POST['prix_vente']) ? floatval($_POST['prix_vente']) : 0;
    $ingredients_existants_post = $_POST['ingredients_existants'] ?? [];
    $nouveaux_noms = $_POST['nouveaux_noms'] ?? [];
    $nouveaux_prix = $_POST['nouveaux_prix'] ?? [];
    $quantites = $_POST['quantites'] ?? [];
    $unites = $_POST['unites'] ?? [];
    if ($titre && $mode_operatoire && (!empty($ingredients_existants_post) || !empty($nouveaux_noms))) {
        $stmt = $pdo->prepare('UPDATE recettes SET titre=?, mode_operatoire=?, prix_vente=? WHERE id=?');
        $stmt->execute([$titre, $mode_operatoire, $prix_vente, $id]);
        // Supprimer les anciens ingrédients de la recette
        $pdo->prepare('DELETE FROM recette_ingredients WHERE recette_id=?')->execute([$id]);
        // Réinsérer les ingrédients
        foreach ($quantites as $i => $qte) {
            $qte = floatval($qte);
            $unite = trim($unites[$i] ?? '');
            $ingredient_id = null;
            if (!empty($nouveaux_noms[$i])) {
                $nom_ingredient = trim($nouveaux_noms[$i]);
                $prix_kg = isset($nouveaux_prix[$i]) ? floatval($nouveaux_prix[$i]) : 0;
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
            } elseif (!empty($ingredients_existants_post[$i])) {
                $ingredient_id = intval($ingredients_existants_post[$i]);
            }
            if ($ingredient_id) {
                $stmtLink = $pdo->prepare('INSERT INTO recette_ingredients (recette_id, ingredient_id, quantite, unite) VALUES (?, ?, ?, ?)');
                $stmtLink->execute([$id, $ingredient_id, $qte, $unite]);
            }
        }
        header('Location: recette.php?id=' . $id);
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
    <title>Modifier une recette</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script>
    function addIngredientField(nom = '', quantite = '', unite = '', ingr_id = '', prix_kg = '', isNew = false) {
        const container = document.getElementById('ingredients-list');
        const idx = container.children.length;
        const div = document.createElement('div');
        div.className = 'ingredient-row';
        div.innerHTML = `
            <input type="text" name="ingredients_existants_nom[]" list="ingredients-list-datalist" placeholder="Chercher ingrédient existant..." style="width:28%">
            <input type="hidden" name="ingredients_existants[]">
            <label style="margin:0 6px;">
                <input type="checkbox" onchange="toggleNouveau(this, ${idx})" ${isNew ? 'checked' : ''}> Nouveau
            </label>
            <input type="text" name="nouveaux_noms[]" placeholder="Nom ingrédient" value="${isNew ? nom : ''}" style="width:18%;${isNew ? '' : 'display:none;'}">
            <input type="number" step="0.000001" name="nouveaux_prix[]" placeholder="Prix au kg (€)" value="${isNew ? prix_kg : ''}" style="width:14%;${isNew ? '' : 'display:none;'}">
            <input type="number" step="0.01" name="quantites[]" placeholder="Quantité" value="${quantite}" required style="width:12%"> 
            <input type="text" name="unites[]" placeholder="Unité" value="${unite}" required style="width:10%"> 
            <button type='button' class='btn-suppr' onclick='this.parentNode.remove()'>Supprimer</button>
        `;
        // Préremplir le champ texte si ingrédient existant
        if (!isNew && ingr_id) {
            const ingr = <?php echo json_encode($ingredients_existants); ?>.find(i => i.id == ingr_id);
            if (ingr) {
                setTimeout(() => {
                    div.querySelector('input[list="ingredients-list-datalist"]').value = ingr.nom;
                    div.querySelector('input[name="ingredients_existants[]"]').value = ingr.id;
                }, 0);
            }
        }
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
        const selects = row.querySelector('select[name="ingredients_existants[]"]');
        const inputNom = row.querySelector('input[name="nouveaux_noms[]"]');
        const inputPrix = row.querySelector('input[name="nouveaux_prix[]"]');
        if (checkbox.checked) {
            selects.disabled = true;
            inputNom.style.display = '';
            inputPrix.style.display = '';
            inputNom.required = true;
            inputPrix.required = true;
        } else {
            selects.disabled = false;
            inputNom.style.display = 'none';
            inputPrix.style.display = 'none';
            inputNom.required = false;
            inputPrix.required = false;
        }
    }
    window.onload = function() {
        <?php foreach ($ingredients_recette as $ing): ?>
            addIngredientField(
                <?= json_encode($ing['nom']) ?>,
                <?= json_encode($ing['quantite']) ?>,
                <?= json_encode($ing['unite']) ?>,
                <?= json_encode($ing['ingredient_id']) ?>,
                <?= json_encode($ing['prix_kg']) ?>,
                false
            );
        <?php endforeach; ?>
        if (<?= count($ingredients_recette) ?> === 0) addIngredientField();
    };
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
    <a href="recette.php?id=<?= $id ?>" style="display:inline-block;background:#1976d2;color:#fff;padding:8px 18px;border-radius:6px;text-decoration:none;font-weight:bold;margin-bottom:18px;">← Retour à la fiche recette</a>
    <h1 style="color:#1976d2;text-align:center;margin-bottom:24px;">Modifier la recette</h1>
    <?php if ($error): ?>
        <div style="color:#e74c3c;background:#fff0f0;border:1px solid #e74c3c;border-radius:6px;padding:10px;margin-bottom:18px;text-align:center;font-weight:500;"> <?= htmlspecialchars($error) ?> </div>
    <?php endif; ?>
    <form method="post" style="max-width:520px;margin:auto;">
        <label style="font-weight:500;">Titre de la recette :</label>
        <input type="text" name="titre" value="<?= htmlspecialchars($recette['titre']) ?>" required><br>
        <label style="font-weight:500;">Prix de vente (€) :</label>
        <input type="number" step="0.01" min="0" name="prix_vente" value="<?= htmlspecialchars($recette['prix_vente']) ?>" required><br>
        <label style="font-weight:500;">Ingrédients :</label>
        <div id="ingredients-list"></div>
        <button type="button" onclick="addIngredientField()" style="background:#1976d2;color:#fff;border:none;border-radius:6px;padding:8px 18px;margin:10px 0 18px 0;cursor:pointer;font-weight:bold;">+ Ajouter un ingrédient</button><br>
        <label style="font-weight:500;">Mode opératoire :</label>
        <textarea name="mode_operatoire" required style="width:100%;height:100px;border-radius:6px;border:1px solid #b0b8d1;padding:10px;"><?= htmlspecialchars($recette['mode_operatoire']) ?></textarea><br>
        <button type="submit" style="background:#43a047;color:#fff;border:none;border-radius:8px;padding:12px 0;width:100%;font-size:1.1em;font-weight:bold;cursor:pointer;margin-top:8px;box-shadow:0 2px 8px #b0b8d1;">Enregistrer les modifications</button>
    </form>
</div>
</body>
</html>
