<h2>Beheer Onderwerpen</h2>
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
                <td style="padding: 15px; text-align: center;">
                    <a href="<?= BASE_URL ?>?topic=<?= $topic['id'] ?>&action=admin_topic_delete" style="color: red;" onclick="return confirm('Zeker weten?')">Verwijderen</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>