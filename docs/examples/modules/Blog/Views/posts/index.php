<h1>Blog Posts</h1>

<div class="posts">
    <?php foreach($posts as $post): ?>
        <article class="post">
            <h2>
                <a href="/blog/posts/<?= $post->slug ?>">
                    <?= htmlspecialchars($post->title) ?>
                </a>
            </h2>
            <div class="post-meta">
                <span>Published: <?= $post->created_at ?></span>
            </div>
            <div class="post-excerpt">
                <?= htmlspecialchars(substr($post->content, 0, 200)) ?>...
            </div>
        </article>
    <?php endforeach; ?>
</div>
