<div style="max-width: 600px; margin: 50px auto; background: white; padding: 20px; border-radius: 15px; border: 1px solid #ddd;">
    <h2><?= $topic['id'] ? 'Onderwerp Bewerken' : 'Nieuw Onderwerp Aanmaken' ?></h2>
    <form method="POST" action="<?= BASE_URL ?>?action=admin_save_topic<?= $topic['id'] ? '&topic=' . $topic['id'] : '' ?>">
        <label for="name" style="display: block; margin-bottom: 5px; font-weight: bold;">Naam Onderwerp:</label>
        <input type="text" id="name" name="name" value="<?= htmlspecialchars($topic['name']) ?>" required 
               style="width:100%; padding:10px; margin-bottom:15px; border: 1px solid #ccc; border-radius: 5px;"><br>
        
        <label for="root_node_id" style="display: block; margin-bottom: 5px; font-weight: bold;">Root Node ID (optioneel):</label>
        <input type="number" id="root_node_id" name="root_node_id" value="<?= htmlspecialchars((string)$topic['root_node_id']) ?>" 
               style="width:100%; padding:10px; margin-bottom:15px; border: 1px solid #ccc; border-radius: 5px;"><br>

        <button type="submit" class="btn" style="width:100%; border:none; cursor:pointer; background:#2ecc71;">Opslaan</button>
        <a href="<?= BASE_URL ?>?action=admin" class="btn" style="width:100%; border:none; cursor:pointer; background:#95a5a6; margin-top: 10px; display: block; text-align: center;">Annuleren</a>
    </form>
</div>