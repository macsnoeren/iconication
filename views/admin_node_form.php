<div style="max-width: 800px; margin: 50px auto; background: white; padding: 20px; border-radius: 15px; border: 1px solid #ddd;">
    <h2><?= $node['id'] ? 'Node Bewerken (ID: ' . $node['id'] . ')' : 'Nieuwe Node Aanmaken' ?></h2>
    <form method="POST" action="<?= BASE_URL ?>?action=admin_save_node&topic=<?= $node['topic_id'] ?><?= $node['id'] ? '&node=' . $node['id'] : '' ?>">
        
        <div style="display: flex; gap: 20px; margin-bottom: 20px;">
            <div style="flex: 1 1 0; min-width: 0; padding: 15px; border: 1px solid #eee; border-radius: 10px; background: #fafafa; box-sizing: border-box;">
                <h3 style="margin-top: 0;">Optie 1</h3>
                <input type="hidden" name="option_id[]" value="<?= htmlspecialchars((string)($options[0]['id'] ?? '')) ?>">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Label:</label>
                <input type="text" name="option_label[]" value="<?= htmlspecialchars($options[0]['label'] ?? '') ?>" required
                       style="width:100%; box-sizing: border-box; padding:10px; margin-bottom:15px; border: 1px solid #ccc; border-radius: 5px;">
                
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Afbeelding URL:</label>
                <input type="text" name="option_image[]" value="<?= htmlspecialchars($options[0]['image_url'] ?? '') ?>" required
                       style="width:100%; box-sizing: border-box; padding:10px; margin-bottom:15px; border: 1px solid #ccc; border-radius: 5px;">
                
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Volgende Node ID:</label>
                <input type="number" name="option_next_node[]" value="<?= htmlspecialchars((string)($options[0]['next_node_id'] ?? '')) ?>"
                       style="width:100%; box-sizing: border-box; padding:10px; border: 1px solid #ccc; border-radius: 5px;">
            </div>

            <div style="flex: 1 1 0; min-width: 0; padding: 15px; border: 1px solid #eee; border-radius: 10px; background: #fafafa; box-sizing: border-box;">
                <h3 style="margin-top: 0;">Optie 2</h3>
                <input type="hidden" name="option_id[]" value="<?= htmlspecialchars((string)($options[1]['id'] ?? '')) ?>">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Label:</label>
                <input type="text" name="option_label[]" value="<?= htmlspecialchars($options[1]['label'] ?? '') ?>" required
                       style="width:100%; box-sizing: border-box; padding:10px; margin-bottom:15px; border: 1px solid #ccc; border-radius: 5px;">
                
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Afbeelding URL:</label>
                <input type="text" name="option_image[]" value="<?= htmlspecialchars($options[1]['image_url'] ?? '') ?>" required
                       style="width:100%; box-sizing: border-box; padding:10px; margin-bottom:15px; border: 1px solid #ccc; border-radius: 5px;">
                
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Volgende Node ID:</label>
                <input type="number" name="option_next_node[]" value="<?= htmlspecialchars((string)($options[1]['next_node_id'] ?? '')) ?>"
                       style="width:100%; box-sizing: border-box; padding:10px; border: 1px solid #ccc; border-radius: 5px;">
            </div>
        </div>

        <button type="submit" class="btn" style="width:100%; border:none; cursor:pointer; background:#2ecc71; margin-top: 20px;">Opslaan</button>
        <a href="<?= BASE_URL ?>?action=admin_topic_nodes&topic=<?= $node['topic_id'] ?>" class="btn" 
           style="width:100%; border:none; cursor:pointer; background:#95a5a6; margin-top: 10px; display: block; text-align: center;">Annuleren</a>
    </form>
</div>