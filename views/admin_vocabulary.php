<style>
.vocab-header { display:flex; align-items:center; gap:16px; margin-bottom:20px; flex-wrap:wrap; }
.vocab-header h2 { margin:0; color:var(--color-primary); }
.vocab-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 16px;
    margin-bottom: 28px;
}
.vocab-card {
    background: white;
    border-radius: 14px;
    padding: 16px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.07);
    border-left: 5px solid #ddd;
    transition: border-color .2s;
}
.vocab-card.approved { border-left-color: var(--color-success); }
.vocab-label {
    font-weight: 700;
    font-size: 1rem;
    margin-bottom: 12px;
    color: #222;
    display: flex;
    align-items: center;
    gap: 8px;
}
.vocab-badge-ok {
    font-size: .7rem;
    background: var(--color-success);
    color: #fff;
    border-radius: 5px;
    padding: 2px 7px;
    font-weight: 600;
}
.vocab-del {
    margin-left: auto;
    font-size: .75rem;
    color: var(--color-danger);
    text-decoration: none;
    font-weight: normal;
}
.vocab-del:hover { text-decoration: underline; }
.img-row { display:flex; align-items:center; gap:8px; margin-bottom:10px; }
.img-thumb-wrap {
    flex-shrink:0; width:56px; height:56px;
    border:1px solid #ddd; border-radius:9px;
    background:#f8f4ff; display:flex; align-items:center;
    justify-content:center; overflow:hidden;
}
.img-thumb { width:46px; height:46px; object-fit:contain; }
.img-thumb-empty { color:#ccc; font-size:1.4rem; }
.img-url-input {
    flex:1; padding:7px 10px; font-size:.78rem;
    border:1px solid #ddd; border-radius:7px; outline:none;
    font-family:monospace; color:#555; background:white;
}
.img-url-input:focus { border-color: var(--color-accent); }
.approve-row {
    display:flex; align-items:center; gap:8px;
    font-size:.85rem; color:#555; margin-top:4px;
}
.action-bar {
    display:flex; gap:12px; justify-content:center;
    flex-wrap:wrap; padding:10px 0 20px;
}
.btn-save {
    padding:14px 36px; background:var(--color-success); color:white;
    border:none; border-radius:12px; font-size:1.1rem; font-weight:bold;
    cursor:pointer; transition:background .2s, transform .1s;
}
.btn-save:hover { background: var(--color-success-dk); }
.btn-save:active { transform: scale(.97); }
.saved-msg {
    display:none; background:#d5f5e3; color:#1e8449;
    border-radius:10px; padding:10px 18px; margin-bottom:16px; font-weight:bold;
}
.filter-bar { display:flex; gap:10px; margin-bottom:18px; flex-wrap:wrap; }
.filter-btn { padding:6px 16px; border-radius:20px; border:1px solid #ddd;
    background:#fff; cursor:pointer; font-size:.85rem; }
.filter-btn.active { background:var(--color-accent); color:#fff; border-color:var(--color-accent); }
</style>

<div class="vocab-header">
    <h2>Woordenschat</h2>
    <span style="font-size:.85rem; color:var(--color-text-muted);"><?= count($vocab) ?> opties</span>
    <a href="<?= BASE_URL ?>?action=admin" class="btn" style="margin-left:auto;">← Terug</a>
</div>

<div id="saved-msg" class="saved-msg">✓ Wijzigingen opgeslagen</div>

<?php if (empty($vocab)): ?>
    <div class="card-admin">
        <p style="color:var(--color-text-muted);">
            Nog geen opties. Start een AI-gesprek om opties te genereren — ze verschijnen hier automatisch.
        </p>
    </div>
<?php else: ?>

<div class="filter-bar">
    <button class="filter-btn active" onclick="filterVocab('all', this)">Alle</button>
    <button class="filter-btn" onclick="filterVocab('approved', this)">✅ Goedgekeurd</button>
    <button class="filter-btn" onclick="filterVocab('pending', this)">⏳ Nog niet</button>
    <button class="filter-btn" onclick="filterVocab('noimage', this)">🖼 Geen plaatje</button>
</div>

<form method="POST" action="<?= BASE_URL ?>?action=admin_save_vocabulary" id="vocab-form">
<div class="vocab-grid" id="vocab-grid">
<?php foreach ($vocab as $item):
    $vid      = (int)$item['id'];
    $label    = $item['label'];
    $imgUrl   = $item['image_url'] ?? '';
    $approved = (bool)$item['is_approved'];
    $inputId  = "vimg_$vid";
    $noImg    = !$imgUrl;
?>
<div class="vocab-card <?= $approved ? 'approved' : '' ?>"
     data-approved="<?= $approved ? '1' : '0' ?>"
     data-noimage="<?= $noImg ? '1' : '0' ?>">
    <div class="vocab-label">
        <?= htmlspecialchars($label) ?>
        <?php if ($approved): ?><span class="vocab-badge-ok">✓ OK</span><?php endif; ?>
        <a href="<?= BASE_URL ?>?action=admin_delete_vocab&id=<?= $vid ?>"
           class="vocab-del"
           onclick="return confirm('Verwijder \'<?= htmlspecialchars(addslashes($label)) ?>\' uit de woordenschat?')">
            Verwijderen
        </a>
    </div>

    <input type="hidden" name="vocab[<?= $vid ?>][label]" value="<?= htmlspecialchars($label) ?>">

    <div class="img-row">
        <div class="img-thumb-wrap" id="wrap_<?= $inputId ?>">
            <?php if ($imgUrl): ?>
                <img src="<?= htmlspecialchars($imgUrl) ?>" class="img-thumb"
                     onerror="this.style.opacity='0.15'">
            <?php else: ?>
                <div class="img-thumb-empty">?</div>
            <?php endif; ?>
        </div>
        <input type="text"
               id="<?= $inputId ?>"
               name="vocab[<?= $vid ?>][image_url]"
               class="img-url-input"
               value="<?= htmlspecialchars($imgUrl) ?>"
               placeholder="https://..."
               oninput="updateThumb('<?= $inputId ?>')">
        <button type="button" class="img-pick-btn" onclick="openPicker('<?= $inputId ?>')">🖼</button>
    </div>

    <div class="approve-row">
        <input type="checkbox"
               id="ok_<?= $vid ?>"
               name="vocab[<?= $vid ?>][is_approved]"
               value="1"
               <?= $approved ? 'checked' : '' ?>
               onchange="this.closest('.vocab-card').classList.toggle('approved', this.checked);
                         this.closest('.vocab-card').dataset.approved = this.checked ? '1' : '0'">
        <label for="ok_<?= $vid ?>">✅ Plaatje is goedgekeurd</label>
    </div>
</div>
<?php endforeach; ?>
</div>

<div class="action-bar">
    <a href="<?= BASE_URL ?>?action=admin" class="btn" style="padding:14px 28px; font-size:1rem;">← Terug</a>
    <button type="submit" class="btn-save">✓ Opslaan</button>
</div>
</form>

<?php endif; ?>

<script>
function updateThumb(inputId) {
    const input = document.getElementById(inputId);
    const wrap  = document.getElementById('wrap_' + inputId);
    const url   = input.value.trim();
    wrap.innerHTML = url
        ? '<img src="' + url + '" class="img-thumb" onerror="this.style.opacity=\'0.15\'">'
        : '<div class="img-thumb-empty">?</div>';
}

function filterVocab(filter, btn) {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.vocab-card').forEach(card => {
        let show = true;
        if (filter === 'approved') show = card.dataset.approved === '1';
        if (filter === 'pending')  show = card.dataset.approved === '0';
        if (filter === 'noimage')  show = card.dataset.noimage  === '1';
        card.style.display = show ? '' : 'none';
    });
}

if (window.location.hash === '#saved') {
    document.getElementById('saved-msg').style.display = 'block';
    setTimeout(() => document.getElementById('saved-msg').style.display = 'none', 3000);
    history.replaceState(null, '', window.location.pathname + window.location.search);
}
</script>

<?php include __DIR__ . '/_image_picker.php'; ?>
