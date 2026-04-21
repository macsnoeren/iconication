<style>
.guess-panel {
    max-width: 520px;
    margin: 0 auto;
    text-align: center;
    padding: 20px 16px;
}
.guess-label {
    font-size: .85rem;
    font-weight: 600;
    color: var(--color-text-muted);
    text-transform: uppercase;
    letter-spacing: .06em;
    margin-bottom: 18px;
}
.guess-bubble {
    background: var(--color-accent);
    color: #fff;
    border-radius: 20px;
    padding: 28px 32px;
    font-size: 1.45rem;
    font-weight: 700;
    line-height: 1.35;
    margin-bottom: 36px;
    box-shadow: 0 4px 20px rgba(0,0,0,.15);
}
.guess-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}
.guess-btn {
    border: none;
    border-radius: 18px;
    padding: 28px 16px;
    font-size: 1.1rem;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    transition: transform .1s, filter .15s;
}
.guess-btn:active { transform: scale(.96); }
.guess-btn__icon { font-size: 2rem; }
.guess-btn--yes {
    background: var(--color-success);
    color: #fff;
}
.guess-btn--yes:hover  { filter: brightness(.93); }
.guess-btn--no  {
    background: #f0f0f0;
    color: #333;
}
.guess-btn--no:hover   { background: #e2e2e2; }
</style>

<div class="guess-panel">
    <div class="guess-label">Bedoel je dit?</div>

    <div class="guess-bubble">
        <?= htmlspecialchars($guess) ?>
    </div>

    <div class="guess-actions">
        <form method="POST" action="<?= BASE_URL ?>?action=session_guess_confirm">
            <input type="hidden" name="sentence" value="<?= htmlspecialchars($guess) ?>">
            <button type="submit" class="guess-btn guess-btn--yes">
                <span class="guess-btn__icon">✅</span>
                Ja, dat klopt!
            </button>
        </form>

        <form method="POST" action="<?= BASE_URL ?>?action=session_guess_reject">
            <button type="submit" class="guess-btn guess-btn--no">
                <span class="guess-btn__icon">🔍</span>
                Nee, verder zoeken
            </button>
        </form>
    </div>
</div>
