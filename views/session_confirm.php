<div class="panel">
    <div class="panel__icon">💬</div>
    <h2 class="panel__title">Dit bedoel je?</h2>

    <div class="panel__message">
        <?= htmlspecialchars($suggestedMessage ?: $sentence) ?>
    </div>

    <div class="panel__actions">
        <form method="POST" action="<?= BASE_URL ?>?action=session_confirm">
            <input type="hidden" name="sentence" value="<?= htmlspecialchars($suggestedMessage ?: $sentence) ?>">
            <button type="submit" class="card card--leaf" style="min-height:120px;">
                <span class="card__icon">✅</span>
                <span class="card__label">Ja, dit!</span>
            </button>
        </form>

        <form method="POST" action="<?= BASE_URL ?>?action=session_back">
            <button type="submit" class="card" style="min-height:120px;">
                <span class="card__icon">↩</span>
                <span class="card__label">Nee, terug</span>
            </button>
        </form>
    </div>
</div>
