<style>
    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
    .modal-content { background: white; margin: 5% auto; padding: 20px; width: 80%; max-width: 800px; border-radius: 15px; max-height: 80vh; overflow-y: auto; }
    .image-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 15px; margin-top: 20px; }
    .image-item { border: 2px solid #ddd; padding: 5px; cursor: pointer; border-radius: 10px; text-align: center; transition: transform 0.1s; }
    .image-item:hover { border-color: #3498db; transform: scale(1.05); }
    .image-item img { max-width: 100%; height: 80px; object-fit: contain; }
    .search-box { width: 100%; padding: 12px; font-size: 1rem; border: 1px solid #ccc; border-radius: 8px; box-sizing: border-box; }
    .select-btn { background: #3498db; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer; margin-top: 5px; font-size: 0.8rem; }
</style>

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
                <div style="display: flex; gap: 5px; margin-bottom: 15px;">
                    <input type="text" id="img_0" name="option_image[]" value="<?= htmlspecialchars($options[0]['image_url'] ?? '') ?>" required
                           style="flex: 1; padding:10px; border: 1px solid #ccc; border-radius: 5px;">
                    <button type="button" class="btn" style="padding: 5px 10px; background: #3498db;" onclick="openImagePicker('img_0')">🔍</button>
                </div>
                
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
                <div style="display: flex; gap: 5px; margin-bottom: 15px;">
                    <input type="text" id="img_1" name="option_image[]" value="<?= htmlspecialchars($options[1]['image_url'] ?? '') ?>" required
                           style="flex: 1; padding:10px; border: 1px solid #ccc; border-radius: 5px;">
                    <button type="button" class="btn" style="padding: 5px 10px; background: #3498db;" onclick="openImagePicker('img_1')">🔍</button>
                </div>
                
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

<!-- Image Picker Modal -->
<div id="imageModal" class="modal">
    <div class="modal-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3>Selecteer een bestaande afbeelding</h3>
            <button type="button" class="btn" onclick="closeImagePicker()" style="background: #e74c3c;">Sluiten</button>
        </div>
        <input type="text" id="imageSearch" class="search-box" placeholder="Zoek op URL of naam..." oninput="filterImages()">
        <div id="imageLibrary" class="image-grid">
            <!-- Images will be loaded here -->
        </div>
    </div>
</div>

<script>
let targetInputId = null;
let allImages = [];

async function openImagePicker(inputId) {
    targetInputId = inputId;
    document.getElementById('imageModal').style.display = 'block';
    
    if (allImages.length === 0) {
        const response = await fetch('<?= BASE_URL ?>?action=admin_get_images');
        allImages = await response.json();
    }
    renderImages(allImages);
}

function closeImagePicker() {
    document.getElementById('imageModal').style.display = 'none';
}

function renderImages(images) {
    const library = document.getElementById('imageLibrary');
    library.innerHTML = '';
    images.forEach(url => {
        const div = document.createElement('div');
        div.className = 'image-item';
        div.onclick = () => selectImage(url);
        div.innerHTML = `<img src="${url}" onerror="this.src='https://via.placeholder.com/100?text=Error'"><div style="font-size: 0.7rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${url.split('/').pop()}</div>`;
        library.appendChild(div);
    });
}

function filterImages() {
    const term = document.getElementById('imageSearch').value.toLowerCase();
    const filtered = allImages.filter(url => url.toLowerCase().includes(term));
    renderImages(filtered);
}

function selectImage(url) {
    document.getElementById(targetInputId).value = url;
    closeImagePicker();
}

// Sluit modal bij klikken buiten content
window.onclick = function(event) {
    const modal = document.getElementById('imageModal');
    if (event.target == modal) closeImagePicker();
}
</script>