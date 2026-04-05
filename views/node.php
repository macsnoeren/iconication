<div class="grid">
    <?php foreach ($options as $option):
        $isEnd = empty($option['next_node_id']);
        $href  = $isEnd ? BASE_URL : BASE_URL . "?topic=$topicId&node={$option['next_node_id']}";
    ?>
        <a href="<?= $href ?>" class="card <?= $isEnd ? 'card--end' : '' ?>">
            <img src="<?= htmlspecialchars($option['image_url']) ?>" alt="">
            <span><?= htmlspecialchars($option['label']) ?></span>
            <?php if ($isEnd): ?>
                <span class="end-badge">✓ Klaar</span>
            <?php endif; ?>
        </a>
    <?php endforeach; ?>
</div>