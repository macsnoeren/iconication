<div class="grid">
    <?php foreach ($options as $option): ?>
        <a href="<?= $option['next_node_id'] ? "/topic/$topicId/node/{$option['next_node_id']}" : "/" ?>" class="card">
            <img src="<?= htmlspecialchars($option['image_url']) ?>" alt="">
            <span><?= htmlspecialchars($option['label']) ?></span>
        </a>
    <?php endforeach; ?>
</div>