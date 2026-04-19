<?php
$node = $nodeMap[$currentId] ?? null;
if (!$node): ?>
    <p>Fout: node niet gevonden.</p>
<?php return; endif; ?>

<div class="grid">
    <?php foreach (['option_a', 'option_b'] as $optKey):
        $opt   = $node[$optKey];
        $isEnd = empty($opt['next_node_id']);
        if ($isEnd) {
            $href = BASE_URL . "?action=topic_complete&topic=$topicId";
        } else {
            $href = BASE_URL . "?action=topic_followup_nav&topic=$topicId&fnode={$opt['next_node_id']}&opt=$optKey";
        }
    ?>
        <a href="<?= $href ?>" class="card <?= $isEnd ? 'card--end' : '' ?>">
            <?php if (!empty($opt['image_url'])): ?>
                <img src="<?= htmlspecialchars($opt['image_url']) ?>" alt="">
            <?php endif; ?>
            <span><?= htmlspecialchars($opt['label']) ?></span>
            <?php if ($isEnd): ?>
                <span class="end-badge">✓ Klaar</span>
            <?php endif; ?>
        </a>
    <?php endforeach; ?>
</div>
