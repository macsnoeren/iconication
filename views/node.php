<div class="grid">
    <?php foreach ($options as $option):
        $isEnd = empty($option['next_node_id']);
        if ($isEnd) {
            $href = BASE_URL . "?action=topic_complete&topic=$topicId&opt={$option['id']}";
        } else {
            $href = BASE_URL . "?topic=$topicId&node={$option['next_node_id']}&opt={$option['id']}";
        }
    ?>
        <a href="<?= $href ?>" class="card <?= $isEnd ? 'card--end' : '' ?>">
            <img src="<?= htmlspecialchars($option['image_url'] ?? '') ?>" alt="">
            <span><?= htmlspecialchars($option['label']) ?></span>
            <?php if ($isEnd): ?>
                <span class="end-badge">✓ Klaar</span>
            <?php endif; ?>
        </a>
    <?php endforeach; ?>
</div>