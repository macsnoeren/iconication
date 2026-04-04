<style>
.ai-form-card {
    background: white;
    border-radius: 16px;
    padding: 40px;
    max-width: 600px;
    margin: 40px auto;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}
.ai-form-card h2 {
    margin: 0 0 8px;
    color: #2c3e50;
    font-size: 1.8rem;
}
.ai-form-card p.subtitle {
    color: #666;
    margin: 0 0 30px;
}
.ai-field { margin-bottom: 16px; }
.ai-field label { display: block; font-weight: bold; color: #444; margin-bottom: 6px; font-size: 0.95rem; }
.ai-field input[type="text"] {
    width: 100%;
    box-sizing: border-box;
    padding: 14px 18px;
    font-size: 1.1rem;
    border: 2px solid #ddd;
    border-radius: 12px;
    outline: none;
    transition: border-color 0.2s;
}
.ai-field input[type="text"]:focus { border-color: #9b59b6; }
.ai-field textarea {
    width: 100%;
    box-sizing: border-box;
    padding: 14px 18px;
    font-size: 1rem;
    border: 2px solid #ddd;
    border-radius: 12px;
    outline: none;
    resize: vertical;
    min-height: 90px;
    font-family: inherit;
    transition: border-color 0.2s;
}
.ai-field textarea:focus { border-color: #9b59b6; }
.ai-submit {
    width: 100%;
    padding: 16px;
    background: #9b59b6;
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 1.15rem;
    font-weight: bold;
    cursor: pointer;
    transition: background 0.2s, transform 0.1s;
}
.ai-submit:hover { background: #8e44ad; }
.ai-submit:active { transform: scale(0.98); }
.ai-submit:disabled { background: #bbb; cursor: not-allowed; }
.ai-error {
    background: #fde8e8;
    border: 1px solid #e74c3c;
    color: #c0392b;
    border-radius: 10px;
    padding: 14px 18px;
    margin-bottom: 24px;
}
.ai-examples {
    margin-top: 28px;
    padding-top: 24px;
    border-top: 1px solid #eee;
}
.ai-examples p { color: #888; margin: 0 0 12px; font-size: 0.95rem; }
.ai-examples .chips { display: flex; flex-wrap: wrap; gap: 8px; }
.ai-examples .chip {
    background: #f0e8ff;
    color: #7d3c98;
    border: 1px solid #d7b8f7;
    border-radius: 20px;
    padding: 6px 14px;
    font-size: 0.9rem;
    cursor: pointer;
    transition: background 0.15s;
}
.ai-examples .chip:hover { background: #e0caff; }
.ai-loading {
    display: none;
    align-items: center;
    gap: 12px;
    color: #9b59b6;
    margin-top: 20px;
    font-size: 1rem;
}
.spinner {
    width: 24px; height: 24px;
    border: 3px solid #e8d5f5;
    border-top-color: #9b59b6;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>

<div class="ai-form-card">
    <h2>🤖 AI Onderwerp Genereren</h2>
    <p class="subtitle">Voer een onderwerp in en de AI maakt automatisch een decision tree.</p>

    <?php if (!empty($_SESSION['ai_error'])): ?>
        <div class="ai-error"><?= htmlspecialchars($_SESSION['ai_error']) ?></div>
        <?php unset($_SESSION['ai_error']); ?>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>?action=admin_ai_queue" id="ai-form">
        <div class="ai-field">
            <label for="topic-input">Onderwerp</label>
            <input
                type="text"
                name="topic"
                id="topic-input"
                placeholder="bijv. Hoofdpijn, Honger, Misselijk..."
                maxlength="200"
                required
                autofocus
            >
        </div>
        <div class="ai-field">
            <label for="goal-input">Wat wil je bereiken? <span style="font-weight:normal; color:#888;">(optioneel)</span></label>
            <textarea
                name="goal"
                id="goal-input"
                maxlength="500"
                placeholder="bijv. Achterhalen waar de pijn zit en hoe erg het is, zodat de begeleider gerichte hulp kan bieden."
            ></textarea>
        </div>
        <button type="submit" id="submit-btn" class="ai-submit">✨ Genereer Decision Tree</button>
        <div class="ai-loading" id="ai-loading">
            <div class="spinner"></div>
            <span>Job aanmaken...</span>
        </div>
    </form>

    <div class="ai-examples">
        <p>Voorbeelden:</p>
        <div class="chips">
            <?php foreach (['Hoofdpijn', 'Honger', 'Pijn', 'Misselijk', 'Moe', 'Koud', 'Angst', 'Toilet'] as $ex): ?>
                <span class="chip" onclick="document.getElementById('topic-input').value='<?= $ex ?>'"><?= $ex ?></span>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<p style="text-align:center; margin-top: 20px;">
    <a href="<?= BASE_URL ?>?action=admin" class="btn" style="background:#555;">← Terug naar beheer</a>
</p>

<script>
document.getElementById('ai-form').addEventListener('submit', function () {
    document.getElementById('submit-btn').disabled = true;
    document.getElementById('ai-loading').style.display = 'flex';
});
</script>
