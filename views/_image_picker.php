<!-- ════════════════════════════════════════════════════════════════════
     Herbruikbare Image Picker Modal (met ARASAAC zoeken)
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
    max-width: 900px;
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

/* Tabs */
.picker-tabs {
    display: flex;
    gap: 4px;
    margin-bottom: 16px;
    border-bottom: 2px solid #eee;
}
.picker-tab {
    padding: 8px 18px;
    border: none;
    background: none;
    font-size: 0.95rem;
    cursor: pointer;
    color: #888;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    font-weight: bold;
    transition: color 0.15s;
}
.picker-tab.active { color: #3498db; border-bottom-color: #3498db; }
.picker-tab:hover { color: #2c3e50; }

/* ARASAAC zoekbalk */
.arasaac-search-row {
    display: flex;
    gap: 8px;
    margin-bottom: 14px;
}
.arasaac-search-input {
    flex: 1;
    padding: 10px 14px;
    font-size: 1rem;
    border: 2px solid #ddd;
    border-radius: 10px;
    outline: none;
}
.arasaac-search-input:focus { border-color: #3498db; }
.arasaac-search-btn {
    padding: 10px 18px;
    background: #3498db;
    color: white;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-weight: bold;
    transition: background 0.15s;
}
.arasaac-search-btn:hover { background: #2980b9; }
.arasaac-search-btn:disabled { background: #aaa; cursor: not-allowed; }

/* Bibliotheek filter */
.picker-filter {
    width: 100%;
    box-sizing: border-box;
    padding: 10px 14px;
    font-size: 1rem;
    border: 2px solid #ddd;
    border-radius: 10px;
    outline: none;
    margin-bottom: 14px;
}
.picker-filter:focus { border-color: #3498db; }

.picker-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
    gap: 12px;
    max-height: 52vh;
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
.picker-item img { width: 64px; height: 64px; object-fit: contain; display: block; margin: 0 auto 6px; }
.picker-item-label { font-size: 0.68rem; color: #666; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.picker-empty { color: #aaa; text-align: center; padding: 40px; grid-column: 1/-1; font-size: 0.9rem; }
.picker-spinner { text-align: center; padding: 40px; grid-column: 1/-1; color: #3498db; }
</style>

<div id="imgPickerModal">
    <div class="picker-content">
        <div class="picker-header">
            <h3>Selecteer afbeelding</h3>
            <button type="button" class="picker-close-btn" onclick="closePicker()">Sluiten</button>
        </div>

        <div class="picker-tabs">
            <button type="button" class="picker-tab active" onclick="switchTab('arasaac')">🔍 ARASAAC zoeken</button>
            <button type="button" class="picker-tab" onclick="switchTab('library')">📚 Bibliotheek</button>
        </div>

        <!-- Tab: ARASAAC zoeken -->
        <div id="pickerTabArasaac">
            <div class="arasaac-search-row">
                <input type="text" id="arasaacQuery" class="arasaac-search-input"
                       placeholder="Zoek pictogram (bijv. eten, pijn, blij)..."
                       onkeydown="if(event.key==='Enter'){event.preventDefault();searchArasaac()}">
                <button type="button" class="arasaac-search-btn" id="arasaacBtn" onclick="searchArasaac()">Zoek</button>
            </div>
            <div id="arasaacGrid" class="picker-grid">
                <div class="picker-empty">Voer een zoekterm in om ARASAAC pictogrammen te zoeken.</div>
            </div>
        </div>

        <!-- Tab: Bibliotheek -->
        <div id="pickerTabLibrary" style="display:none">
            <input type="text" id="libraryFilter" class="picker-filter"
                   placeholder="Filter op naam of URL..." oninput="filterLibrary()">
            <div id="libraryGrid" class="picker-grid">
                <div class="picker-spinner">Laden...</div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    let targetInputId = null;
    let libraryImages = null;  // null = nog niet geladen
    let extraImages   = typeof pickerExtraImages !== 'undefined' ? pickerExtraImages : [];

    // ── Tab wisselen ──────────────────────────────────────────────────
    window.switchTab = function (tab) {
        document.querySelectorAll('.picker-tab').forEach(b => b.classList.remove('active'));
        event.target.classList.add('active');
        document.getElementById('pickerTabArasaac').style.display = tab === 'arasaac' ? '' : 'none';
        document.getElementById('pickerTabLibrary').style.display  = tab === 'library'  ? '' : 'none';

        if (tab === 'library' && libraryImages === null) {
            loadLibrary();
        }
    };

    // ── Opener / sluit ────────────────────────────────────────────────
    window.openPicker = function (inputId) {
        targetInputId = inputId;
        document.getElementById('imgPickerModal').style.display = 'block';
        // Begin altijd op de ARASAAC tab en reset
        document.querySelectorAll('.picker-tab').forEach((b,i) => b.classList.toggle('active', i===0));
        document.getElementById('pickerTabArasaac').style.display = '';
        document.getElementById('pickerTabLibrary').style.display  = 'none';
        document.getElementById('arasaacQuery').value = '';
        document.getElementById('arasaacGrid').innerHTML =
            '<div class="picker-empty">Voer een zoekterm in om ARASAAC pictogrammen te zoeken.</div>';
        document.getElementById('arasaacQuery').focus();
    };

    window.closePicker = function () {
        document.getElementById('imgPickerModal').style.display = 'none';
    };

    // ── ARASAAC zoeken ────────────────────────────────────────────────
    window.searchArasaac = async function () {
        const query = document.getElementById('arasaacQuery').value.trim();
        if (!query) return;

        const btn  = document.getElementById('arasaacBtn');
        const grid = document.getElementById('arasaacGrid');
        btn.disabled = true;
        btn.textContent = '...';
        grid.innerHTML = '<div class="picker-spinner">Zoeken in ARASAAC...</div>';

        try {
            const resp = await fetch(
                `https://api.arasaac.org/v1/pictograms/nl/search/${encodeURIComponent(query)}`
            );
            const results = resp.ok ? await resp.json() : [];

            if (!results.length) {
                grid.innerHTML = '<div class="picker-empty">Geen pictogrammen gevonden voor "' + query + '".</div>';
                return;
            }

            grid.innerHTML = results.slice(0, 40).map(p => {
                const url   = `https://api.arasaac.org/v1/pictograms/${p._id}`;
                const label = (p.keywords?.[0]?.keyword) || p._id;
                return pickerItemHtml(url, label);
            }).join('');
        } catch (e) {
            grid.innerHTML = '<div class="picker-empty">Fout bij verbinding met ARASAAC.</div>';
        } finally {
            btn.disabled = false;
            btn.textContent = 'Zoek';
        }
    };

    // ── Bibliotheek laden en filteren ─────────────────────────────────
    function loadLibrary() {
        const grid = document.getElementById('libraryGrid');
        grid.innerHTML = '<div class="picker-spinner">Laden...</div>';

        fetch('<?= BASE_URL ?>?action=admin_get_images')
            .then(r => r.json())
            .then(urls => {
                // Combineer: extra (AI) bovenaan + DB
                const seen = new Set();
                libraryImages = [...extraImages, ...urls].filter(u => {
                    if (!u || seen.has(u)) return false;
                    seen.add(u);
                    return true;
                });
                renderLibrary(libraryImages);
            })
            .catch(() => {
                libraryImages = [...extraImages];
                renderLibrary(libraryImages);
            });
    }

    window.filterLibrary = function () {
        if (!libraryImages) return;
        const term = document.getElementById('libraryFilter').value.toLowerCase();
        renderLibrary(libraryImages.filter(u => u.toLowerCase().includes(term)));
    };

    function renderLibrary(images) {
        const grid = document.getElementById('libraryGrid');
        if (!images.length) {
            grid.innerHTML = '<div class="picker-empty">Geen afbeeldingen gevonden.</div>';
            return;
        }
        grid.innerHTML = images.map(url => {
            const name = url.split('/').pop().split('?')[0];
            return pickerItemHtml(url, name);
        }).join('');
    }

    // ── Gedeelde helpers ──────────────────────────────────────────────
    function pickerItemHtml(url, label) {
        const safe = url.replace(/'/g, "\\'");
        return `<div class="picker-item" onclick="selectPickerImage('${safe}')">
            <img src="${url}" onerror="this.style.opacity='0.15'" loading="lazy">
            <div class="picker-item-label">${label}</div>
        </div>`;
    }

    window.selectPickerImage = function (url) {
        const input = document.getElementById(targetInputId);
        if (input) {
            input.value = url;
            input.dispatchEvent(new Event('input'));
        }
        closePicker();
    };

    document.getElementById('imgPickerModal').addEventListener('click', function (e) {
        if (e.target === this) closePicker();
    });
})();
</script>
