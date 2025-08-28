<table>
    <tr style="background:#eee;font-weight:bold;">
        <td>Nom</td>
        <td>Prix au kg (€)</td>
        <td>Prix au g (€)</td>
        <td>Actions</td>
    </tr>
    <?php foreach ($ingredients as $ing):?>
        <tr>
            <td><?= htmlspecialchars($ing['nom']) ?></td>
            <td>
                <form method="post">
                    <input type="hidden" name="id" value="<?= $ing['id'] ?>">
                    <input class="form-input" type="number" step="0.001" name="prix_kg" value="<?= htmlspecialchars($ing['prix_kg']) ?>" style="width: 100px;">
                    <button type="submit" name="edit_ingredient" class="btn-view">💾</button>
                </form>
            </td>
            <td><?= number_format($ing['prix_kg']/1000, 5, ',', ' ') ?></td>
            <td>
                <form method="post" style="display:inline;" onsubmit="return confirm('Supprimer cet ingrédient ?');">
                    <input type="hidden" name="id" value="<?= $ing['id'] ?>">
                    <button type="submit" name="delete_ingredient" class="btn-primary" style="background: #e9311dff;">🗑️</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php if (empty($ingredients)): ?>
    <div style="color:#888;text-align:center;padding: 14px 16px;">Aucun ingrédient enregistré.</div>
<?php endif; ?>
