<!-- ════════════════════════════════════════════════════════════════════
     Herbruikbare Image Picker Modal
     Gebruik:
       1. Include deze file in de view
       2. Optioneel: definieer $pickerExtraImages = ['url1','url2',...] vóór include
       3. Voeg naast elk img-url-input toe:
            <button type="button" class="img-pick-btn" onclick="openPicker('inputId')">🖼</button>
     ════════════════════════════════════════════════════════════════════ -->
<style>
.img-pick-btn {
    padding: 7px 10px;
    background: #3498db;
    color: white;
    border: none;
    border-radius: 7px;
    cursor: pointer;
    font-size: 1rem;
    flex-shrink: 0;
    transition: background 0.15s;
}
.img-pick-btn:hover { background: #2980b9; }

/* Modal */
#imgPickerModal {
    display: none;
    position: fixed;
    z-index: 2000;
    inset: 0;
    background: rgba(0,0,0,0.55);
    overflow-y: auto;
}
.picker-content {
    background: white;
    margin: 40px auto;
    padding: 24px;
    width: 90%;
    max-width: 860px;
    border-radius: 18px;
    box-shadow: 0 8px 40px rgba(0,0,0,0.2);
}
.picker-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    gap: 12px;
}
.picker-header h3 { margin: 0; color: #2c3e50; }
.picker-close-btn {
    padding: 8px 16px;
    background: #e74c3c;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: bold;
}
.picker-close-btn:hover { background: #c0392b; }
.picker-search {
    width: 100%;
    box-sizing: border-box;
    padding: 11px 16px;
    font-size: 1rem;
    border: 2px solid #ddd;
    border-radius: 10px;
    outline: none;
    margin-bottom: 16px;
}
.picker-search:focus { border-color: #3498db; }
.picker-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
    gap: 12px;
    max-height: 55vh;
    overflow-y: auto;
}
.picker-item {
    border: 2px solid #eee;
    border-radius: 10px;
    padding: 8px 6px;
    cursor: pointer;
    text-align: center;
    transition: border-color 0.15s, transform 0.1s;
    background: #fafafa;
}
.picker-item:hover { border-color: #3498db; transform: scale(1.04); }
.picker-item img {
    width: 64px; height: 64px;
    object-fit: contain;
    display: block;
    margin: 0 auto 6px;
}
.picker-item-label {
    font-size: 0.7rem;
    color: #666;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.picker-empty { color: #aaa; text-align: center; padding: 40px; grid-column: 1/-1; }
</style>

<div id="imgPickerModal">
    <div class="picker-content">
        <div class="picker-header">
            <h3>Selecteer afbeelding</h3>
            <button type="button" class="picker-close-btn" onclick="closePicker()">Sluiten</button>
        </div>
        <input type="text" id="pickerSearch" class="picker-search"
               placeholder="Zoek op naam of URL..." oninput="filterPicker()">
        <div id="pickerGrid" class="picker-grid"></div>
    </div>
</div>

<script>
(function () {
    let targetInputId = null;
    let allImages     = [];   // geladen van API
    let extraImages   = typeof pickerExtraImages !== 'undefined' ? pickerExtraImages : [];

    window.openPicker = function (inputId) {
        targetInputId = inputId;
        document.getElementById('pickerSearch').value = '';
        document.getElementById('imgPickerModal').style.display = 'block';

        if (allImages.length > 0) {
            renderPicker(merged());
            return;
        }
        // Laad bestaande DB-afbeeldingen
        fetch('<?= BASE_URL ?>?action=admin_get_images')
            .then(r => r.json())
            .then(urls => {
                allImages = urls;
                renderPicker(merged());
            })
            .catch(() => renderPicker(merged()));
    };

    window.closePicker = function () {
        document.getElementById('imgPickerModal').style.display = 'none';
    };

    window.filterPicker = function () {
        const term = document.getElementById('pickerSearch').value.toLowerCase();
        renderPicker(merged().filter(url => url.toLowerCase().includes(term)));
    };

    function merged() {
        // Combineer DB-afbeeldingen + extra (AI-gevonden), dedupliceert op URL
        const seen = new Set();
        return [...extraImages, ...allImages].filter(url => {
            if (!url || seen.has(url)) return false;
            seen.add(url);
            return true;
        });
    }

    function renderPicker(images) {
        const grid = document.getElementById('pickerGrid');
        if (!images.length) {
            grid.innerHTML = '<div class="picker-empty">Geen afbeeldingen gevonden</div>';
            return;
        }
        grid.innerHTML = images.map(url => {
            const name = url.split('/').pop().split('?')[0];
            return `<div class="picker-item" onclick="selectPickerImage('${url.replace(/'/g, "\\'")}')">
                <img src="${url}" onerror="this.style.opacity='0.15'" loading="lazy">
                <div class="picker-item-label">${name}</div>
            </div>`;
        }).join('');
    }

    window.selectPickerImage = function (url) {
        const input = document.getElementById(targetInputId);
        if (input) {
            input.value = url;
            input.dispatchEvent(new Event('input')); // triggert updateThumb
        }
        closePicker();
    };

    // Sluit bij klik buiten de modal
    document.getElementById('imgPickerModal').addEventListener('click', function (e) {
        if (e.target === this) closePicker();
    });
})();
</script>
