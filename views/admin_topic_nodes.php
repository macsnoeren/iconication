<h2>Nodes voor "<?= htmlspecialchars($topic['name']) ?>"</h2>
<div style="margin-bottom: 20px; display: flex; gap: 10px;">
    <a href="<?= BASE_URL ?>?action=admin_add_node&topic=<?= $topic['id'] ?>" class="btn" style="background:#2ecc71;">+ Nieuwe Node</a>
    <a href="<?= BASE_URL ?>?action=admin" class="btn" style="background:#95a5a6;">Terug naar Onderwerpen</a>
</div>

<table style="width: 100%; background: white; border-collapse: collapse; border-radius: 10px; overflow: hidden;">
    <thead style="background: #2c3e50; color: white;">
        <tr>
            <th style="padding: 15px; text-align: left;">Node ID</th>
            <th style="padding: 15px; text-align: left;">Optie A</th>
            <th style="padding: 15px; text-align: left;">Optie B</th>
            <th style="padding: 15px; text-align: center;">Acties</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($nodes as $node): 
            $stmtOptions = $this->db->prepare("SELECT label FROM options WHERE node_id = ? ORDER BY id ASC");
            $stmtOptions->execute([$node['id']]);
            $options = $stmtOptions->fetchAll(\PDO::FETCH_COLUMN);
        ?>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 15px;"><?= $node['id'] ?> <?= ($node['id'] == $topic['root_node_id']) ? '(Root)' : '' ?></td>
                <td style="padding: 15px;"><?= htmlspecialchars($options[0] ?? 'N/A') ?></td>
                <td style="padding: 15px;"><?= htmlspecialchars($options[1] ?? 'N/A') ?></td>
                <td style="padding: 15px; text-align: center; display: flex; gap: 10px; justify-content: center;">
                    <a href="<?= BASE_URL ?>?action=admin_edit_node&topic=<?= $topic['id'] ?>&node=<?= $node['id'] ?>" class="btn" style="background:#3498db; padding: 8px 15px;">Bewerken</a>
                    <a href="<?= BASE_URL ?>?action=admin_delete_node&topic=<?= $topic['id'] ?>&node=<?= $node['id'] ?>" style="color: red;" onclick="return confirm('Weet je zeker dat je deze node wilt verwijderen? Alle bijbehorende opties worden ook verwijderd.')">Verwijderen</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>