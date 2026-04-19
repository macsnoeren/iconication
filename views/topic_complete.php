<?php
$historyItems = $state['history'] ?? [];
$probs        = $state['intent_probabilities'] ?? [];
arsort($probs);
?>
<div style="display:flex; flex-direction:column; align-items:center; justify-content:center; gap:28px; padding:40px 20px; text-align:center;">

    <div style="font-size:4rem;">✅</div>
    <h2 style="font-size:2rem; color:#2c3e50; margin:0;">Bericht begrepen</h2>

    <?php if ($topIntent): ?>
        <div style="background:#eafaf1; border:2px solid #27ae60; border-radius:16px; padding:16px 28px; font-size:1.4rem; color:#1e8449;">
            💬 <?= htmlspecialchars($topIntent) ?>
        </div>
    <?php endif; ?>

    <?php if ($historyItems): ?>
        <div style="background:white; border-radius:14px; border:1px solid #ddd; padding:16px 24px; text-align:left; min-width:260px; max-width:480px; width:100%;">
            <div style="font-weight:bold; color:#555; margin-bottom:10px; font-size:0.95rem;">Gekozen pad:</div>
            <?php foreach ($historyItems as $i => $h): ?>
                <div style="display:flex; align-items:center; gap:8px; padding:6px 0; border-bottom:1px solid #f0f0f0; font-size:1.1rem;">
                    <span style="color:#aaa; font-size:0.85rem;"><?= $i + 1 ?>.</span>
                    <?= htmlspecialchars($h['label']) ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div style="display:flex; flex-direction:column; gap:12px; width:100%; max-width:360px;">
        <a href="<?= BASE_URL ?>?topic=<?= $topicId ?>"
           style="background:#3498db; color:white; padding:18px; border-radius:14px; text-decoration:none; font-size:1.3rem; font-weight:bold;">
            🔄 Opnieuw beginnen
        </a>
        <a href="<?= BASE_URL ?>?action=topic_followup&topic=<?= $topicId ?>"
           style="background:#9b59b6; color:white; padding:18px; border-radius:14px; text-decoration:none; font-size:1.3rem; font-weight:bold;">
            🤖 Meer zeggen (AI vervolg)
        </a>
        <a href="<?= BASE_URL ?>"
           style="background:#95a5a6; color:white; padding:14px; border-radius:14px; text-decoration:none; font-size:1.1rem;">
            🏠 Terug naar overzicht
        </a>
    </div>
</div>
