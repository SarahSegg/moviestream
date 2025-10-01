<?php
require_once __DIR__.'/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$title = db_one($pdo, "SELECT * FROM titles WHERE title_id=?", [$id]);

if (!$title) {
    echo "<div class='card'><p>Title not found.</p></div>";
    require_once __DIR__.'/footer.php';
    exit;
}

$genres = db_all($pdo, "
    SELECT g.name FROM title_genres tg 
    JOIN genres g ON g.genre_id=tg.genre_id
    WHERE tg.title_id=? ORDER BY g.name", [$id]
);

$platforms = db_all($pdo, "
    SELECT p.name FROM title_platforms tp 
    JOIN platforms p ON p.platform_id=tp.platform_id
    WHERE tp.title_id=? ORDER BY p.name", [$id]
);

$avg_rating = db_one($pdo, "
    SELECT ROUND(AVG(rating),2) as avg, COUNT(*) as n 
    FROM ratings WHERE title_id=?", [$id]
);

$recentRatings = db_all($pdo, "
    SELECT user_id, rating, rated_at 
    FROM ratings WHERE title_id=? 
    ORDER BY rated_at DESC LIMIT 10", [$id]
);

$episodes = [];
if ($title['type'] === 'show') {
    $episodes = db_all($pdo, "
        SELECT episode_id, season_number, episode_number, name, air_date, runtime_min
        FROM episodes WHERE show_id=? 
        ORDER BY season_number, episode_number", [$id]
    );
}

// Check if user has rated this title
$user_rating = null;
if (is_logged_in()) {
    $user_rating = db_one($pdo, "
        SELECT rating, rated_at 
        FROM ratings 
        WHERE title_id=? AND user_id=?", [$id, get_user_id()]
    );
}
?>

<div class="title-card" style="flex-direction: row; margin-bottom: 2rem;">
    <img src="assets/images/placeholder-poster.jpg" alt="<?= e($title['name']) ?>" class="title-poster" style="width: 200px; height: 300px;">
    <div class="title-info">
        <div class="title-header">
            <div>
                <h1 class="title-name"><?= e($title['name']) ?></h1>
                <div class="title-meta">
                    <span class="badge badge-primary"><?= e($title['type']) ?></span>
                    <span>Released: <?= format_date($title['release_date']) ?></span>
                    <span>Status: <?= e($title['status']) ?></span>
                </div>
            </div>
            <?php if ($avg_rating && $avg_rating['n'] > 0): ?>
                <div style="text-align: center;">
                    <div class="rating-stars" style="font-size: 1.5rem;">
                        <?= get_rating_stars($avg_rating['avg']) ?>
                    </div>
                    <div style="font-size: 1.2rem; font-weight: bold;">
                        <?= e($avg_rating['avg']) ?> / 10
                    </div>
                    <small>(<?= (int)$avg_rating['n'] ?> ratings)</small>
                </div>
            <?php endif; ?>
        </div>

        <div class="title-meta">
            <?php if (!empty($genres)): ?>
                <div>
                    <strong>Genres:</strong> <?= e(implode(', ', array_column($genres, 'name'))) ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($platforms)): ?>
                <div>
                    <strong>Platforms:</strong> <?= e(implode(', ', array_column($platforms, 'name'))) ?>
                </div>
            <?php endif; ?>
            <?php if ($title['type'] === 'movie' && $title['runtime_min']): ?>
                <div>
                    <strong>Runtime:</strong> <?= format_runtime($title['runtime_min']) ?>
                </div>
            <?php elseif ($title['type'] === 'show' && $title['seasons']): ?>
                <div>
                    <strong>Seasons:</strong> <?= (int)$title['seasons'] ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($title['description']): ?>
            <div class="title-description">
                <p><?= e($title['description']) ?></p>
            </div>
        <?php endif; ?>

        <div class="title-actions">
            <a href="add_rating.php?title_id=<?= $id ?>" class="btn">
                <i class="fas fa-star"></i> Rate This Title
            </a>
            <a href="add_watch.php?title_id=<?= $id ?>" class="btn btn-secondary">
                <i class="fas fa-history"></i> Log Watch
            </a>
            <?php if (is_admin()): ?>
                <a href="admin/titles.php?edit=<?= $id ?>" class="btn btn-outline">
                    <i class="fas fa-edit"></i> Edit
                </a>
            <?php endif; ?>
        </div>

        <?php if ($user_rating): ?>
            <div class="notification notification-info" style="margin-top: 1rem;">
                <i class="fas fa-info-circle"></i>
                <p>You rated this <?= e($user_rating['rating']) ?>/10 on <?= format_date($user_rating['rated_at']) ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($episodes): ?>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Episodes</h2>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Season</th>
                        <th>Episode</th>
                        <th>Name</th>
                        <th>Air Date</th>
                        <th>Runtime</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($episodes as $episode): ?>
                        <tr>
                            <td><?= (int)$episode['season_number'] ?></td>
                            <td><?= (int)$episode['episode_number'] ?></td>
                            <td><?= e($episode['name']) ?></td>
                            <td><?= format_date($episode['air_date']) ?></td>
                            <td><?= format_runtime($episode['runtime_min']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<div class="grid" style="grid-template-columns: 1fr 1fr;">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Recent Ratings</h2>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Rating</th>
                        <th>When</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recentRatings)): ?>
                        <?php foreach($recentRatings as $rating): ?>
                            <tr>
                                <td>User #<?= (int)$rating['user_id'] ?></td>
                                <td>
                                    <div class="rating-stars">
                                        <?= get_rating_stars($rating['rating']) ?>
                                    </div>
                                    <?= e($rating['rating']) ?>
                                </td>
                                <td><?= format_date($rating['rated_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="note">No ratings yet</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Watch Statistics</h2>
        </div>
        <?php
        $watch_stats = db_one($pdo, "
            SELECT COUNT(*) as total_views,
                   SUM(seconds_watched) as total_seconds,
                   SUM(CASE WHEN completed = 1 THEN 1 ELSE 0 END) as completions
            FROM watch_events 
            WHERE title_id=?", [$id]
        );
        ?>
        <div style="padding: 1rem;">
            <p><strong>Total Views:</strong> <?= (int)$watch_stats['total_views'] ?></p>
            <p><strong>Total Watch Time:</strong> <?= round($watch_stats['total_seconds'] / 3600, 1) ?> hours</p>
            <p><strong>Completions:</strong> <?= (int)$watch_stats['completions'] ?></p>
            <?php if ($watch_stats['total_views'] > 0): ?>
                <p><strong>Completion Rate:</strong> <?= round(($watch_stats['completions'] / $watch_stats['total_views']) * 100, 1) ?>%</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__.'/footer.php'; ?>