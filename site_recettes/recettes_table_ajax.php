<table>
    <tr style="background:#eee;font-weight:bold;">
        <td>Nom</td>
        <td>Date de création</td>
        <td>Actions</td>
    </tr>
    <?php foreach ($recettes as $recette): ?>
    <tr>
        <td><?= htmlspecialchars($recette['titre']) ?></td>
        <td><?= htmlspecialchars($recette['date_creation']) ?></td>
        <td><a href="recette.php?id=<?= $recette['id'] ?>" class="btn-view">Détails</a></td>
    </tr>
    <?php endforeach; ?>
</table>

<?php if (empty($recettes)): ?>
    <div style="color:#888;text-align:center;padding: 14px 16px;">Aucune recette enregistrée.</div>
<?php endif; ?>
