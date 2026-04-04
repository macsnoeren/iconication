<h2>Beheer Onderwerpen</h2>
<div style="display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap;">
    <a href="<?= BASE_URL ?>?action=admin_add_topic" class="btn" style="background:#2ecc71;">+ Nieuw Onderwerp</a>
    <a href="<?= BASE_URL ?>?action=admin_ai_generate" class="btn" style="background:#9b59b6;">🤖 AI Genereer Onderwerp</a>
</div>
<table style="width: 100%; background: white; border-collapse: collapse; border-radius: 10px; overflow: hidden;">
    <thead style="background: #2c3e50; color: white;">
        <tr>
            <th style="padding: 15px; text-align: left;">Naam</th>
            <th style="padding: 15px; text-align: center;">Acties</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($topics as $topic): ?>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 15px;"><?= htmlspecialchars($topic['name']) ?></td>
                <td style="padding: 15px; text-align: center; display: flex; gap: 10px; justify-content: center;">
                    <a href="<?= BASE_URL ?>?action=admin_topic_nodes&topic=<?= $topic['id'] ?>" class="btn" style="background:#3498db; padding: 8px 15px;">Nodes</a>
                    <a href="<?= BASE_URL ?>?topic=<?= $topic['id'] ?>&action=admin_topic_delete" style="color: red;" onclick="return confirm('Zeker weten?')">Verwijderen</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>