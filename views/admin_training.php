<style>
.training-card {
    background: white;
    border-radius: 14px;
    padding: 24px 28px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
}
.training-card h2 { margin: 0 0 6px; color: #2c3e50; font-size: 1.4rem; }
.training-card p  { margin: 0 0 20px; color: #888; font-size: 0.9rem; }
.topic-row {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 14px 0;
    border-bottom: 1px solid #f0f0f0;
}
.topic-row:last-child { border-bottom: none; }
.topic-check {
    margin-top: 3px;
    width: 20px; height: 20px;
    flex-shrink: 0;
    accent-color: #9b59b6;
    cursor: pointer;
}
.topic-info { flex: 1; }
.topic-name { font-weight: bold; color: #2c3e50; margin-bottom: 6px; }
.topic-name span { font-size: 0.78rem; font-weight: normal; color: #aaa; margin-left: 8px; }
.topic-notes {
    width: 100%;
    box-sizing: border-box;
    padding: 7px 10px;
    font-size: 0.88rem;
    border: 1px solid #ddd;
    border-radius: 8px;
    outline: none;
    resize: vertical;
    min-height: 52px;
    font-family: inherit;
    color: #444;
    display: none;
}
.topic-notes:focus { border-color: #9b59b6; }
.topic-notes.visible { display: block; }
.action-bar {
    display: flex;
    gap: 14px;
    justify-content: flex-start;
    margin-top: 20px;
    flex-wrap: wrap;
}
.btn-save {
    padding: 13px 32px;
    background: #27ae60;
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 1.05rem;
    font-weight: bold;
    cursor: pointer;
    transition: background 0.2s;
}
.btn-save:hover { background: #219a52; }
.info-box {
    background: #f0e8ff;
    border: 1px solid #d7b8f7;
    border-radius: 12px;
    padding: 16px 20px;
    margin-bottom: 24px;
    font-size: 0.9rem;
    color: #5b2c6f;
    line-height: 1.6;
}
.info-box code {
    background: #e8d5f5;
    padding: 2px 7px;
    border-radius: 5px;
    font-size: 0.85rem;
}
.saved-msg {
    display: none;
    background: #d5f5e3;
    color: #1e8449;
    border-radius: 10px;
    padding: 10px 18px;
    margin-bottom: 16px;
    font-weight: bold;
}
</style>

<div id="saved-msg" class="saved-msg">✓ Trainingsvoorbeelden opgeslagen</div>

<div class="info-box">
    <strong>Hoe werkt het?</strong><br>
    Selecteer bestaande topics die als voorbeeld voor de AI dienen.
    De worker leert hiervan de gewenste stijl en structuur.<br><br>
    Na het opslaan, run je de worker eenmalig met:<br>
    <code>python worker.py --tune</code><br><br>
    Dit maakt een nieuw Ollama-model <code>iconication-aac</code> aan dat de worker daarna automatisch gebruikt.
</div>

<div class="training-card">
    <h2>Trainingsvoorbeelden</h2>
    <p>Vink topics aan die als voorbeeld voor de AI moeten dienen. Voeg optioneel een notitie toe.</p>

    <form method="POST" action="<?= BASE_URL ?>?action=admin_save_training" id="training-form">
        <?php foreach ($topics as $topic):
            $isSelected = !empty($topic['example_id']);
        ?>
        <div class="topic-row">
            <input type="checkbox"
                   class="topic-check"
                   name="example_topics[]"
                   value="<?= $topic['id'] ?>"
                   id="chk_<?= $topic['id'] ?>"
                   <?= $isSelected ? 'checked' : '' ?>
                   onchange="toggleNotes(<?= $topic['id'] ?>, this.checked)">
            <div class="topic-info">
                <label for="chk_<?= $topic['id'] ?>" class="topic-name">
                    <?= htmlspecialchars($topic['name']) ?>
                    <span>ID <?= $topic['id'] ?></span>
                </label>
                <textarea
                    class="topic-notes <?= $isSelected ? 'visible' : '' ?>"
                    id="notes_<?= $topic['id'] ?>"
                    name="notes[<?= $topic['id'] ?>]"
                    placeholder="Optionele notitie (bijv. 'goede structuur voor pijnklachten')"
                ><?= htmlspecialchars($topic['notes'] ?? '') ?></textarea>
            </div>
        </div>
        <?php endforeach; ?>

        <div class="action-bar">
            <button type="submit" class="btn-save">✓ Opslaan</button>
            <a href="<?= BASE_URL ?>?action=admin" class="btn" style="background:#95a5a6; padding:13px 28px;">← Terug</a>
        </div>
    </form>
</div>

<script>
function toggleNotes(topicId, show) {
    const el = document.getElementById('notes_' + topicId);
    el.classList.toggle('visible', show);
}

if (window.location.hash === '#saved') {
    document.getElementById('saved-msg').style.display = 'block';
    setTimeout(() => document.getElementById('saved-msg').style.display = 'none', 3000);
    history.replaceState(null, '', window.location.pathname + window.location.search);
}
</script>
