<?php
require_once __DIR__.'/header.php';

// Check if user is logged in, if not redirect to home page
if (!is_logged_in()) {
    $_SESSION['redirect_to'] = 'dashboard.php';
    // Redirect to home page instead of non-existent login.php
    header('Location: index.php');
    exit;
}

$user_id = get_user_id();

// Get user's personal dashboard statistics with error handling
try {
    $user_stats = db_one($pdo, "
        SELECT 
            COALESCE((SELECT COUNT(*) FROM ratings WHERE user_id = ?), 0) as total_ratings,
            COALESCE((SELECT COUNT(DISTINCT title_id) FROM ratings WHERE user_id = ?), 0) as rated_titles,
            COALESCE((SELECT COUNT(*) FROM watch_events WHERE user_id = ?), 0) as total_watch_sessions,
            COALESCE((SELECT SUM(seconds_watched) FROM watch_events WHERE user_id = ?), 0) as total_watch_seconds,
            COALESCE((SELECT ROUND(AVG(rating), 2) FROM ratings WHERE user_id = ?), 0) as avg_rating,
            COALESCE((SELECT COUNT(*) FROM titles), 0) as total_titles_available
    ", [$user_id, $user_id, $user_id, $user_id, $user_id]);
} catch (Exception $e) {
    $user_stats = [
        'total_ratings' => 0,
        'rated_titles' => 0,
        'total_watch_sessions' => 0,
        'total_watch_seconds' => 0,
        'avg_rating' => 0,
        'total_titles_available' => 0
    ];
}

// Get user's recent activity with error handling
try {
    $recent_activity = db_all($pdo, "
        (SELECT 'rating' as type, title_id, rating as value, rated_at as date, NULL as seconds_watched
         FROM ratings WHERE user_id = ?)
        UNION ALL
        (SELECT 'watch' as type, title_id, NULL as value, watched_at as date, seconds_watched
         FROM watch_events WHERE user_id = ?)
        ORDER BY date DESC
        LIMIT 10
    ", [$user_id, $user_id]);
} catch (Exception $e) {
    $recent_activity = [];
}

// Get user's top rated titles
try {
    $top_rated = db_all($pdo, "
        SELECT t.title_id, t.name, t.type, r.rating, r.rated_at
        FROM ratings r
        JOIN titles t ON t.title_id = r.title_id
        WHERE r.user_id = ?
        ORDER BY r.rating DESC, r.rated_at DESC
        LIMIT 5
    ", [$user_id]);
} catch (Exception $e) {
    $top_rated = [];
}

// Get recently watched titles
try {
    $recently_watched = db_all($pdo, "
        SELECT DISTINCT t.title_id, t.name, t.type, MAX(we.watched_at) as last_watched
        FROM watch_events we
        JOIN titles t ON t.title_id = we.title_id
        WHERE we.user_id = ?
        GROUP BY t.title_id, t.name, t.type
        ORDER BY last_watched DESC
        LIMIT 5
    ", [$user_id]);
} catch (Exception $e) {
    $recently_watched = [];
}

// Get watch time by genre
try {
    $genre_watch_time = db_all($pdo, "
        SELECT g.name as genre, COALESCE(SUM(we.seconds_watched), 0) as total_seconds
        FROM watch_events we
        JOIN titles t ON t.title_id = we.title_id
        JOIN title_genres tg ON tg.title_id = t.title_id
        JOIN genres g ON g.genre_id = tg.genre_id
        WHERE we.user_id = ?
        GROUP BY g.genre_id, g.name
        ORDER BY total_seconds DESC
        LIMIT 8
    ", [$user_id]);
} catch (Exception $e) {
    $genre_watch_time = [];
}

// Get personalized recommendations based on user's ratings
try {
    $recommendations = db_all($pdo, "
        SELECT DISTINCT t.title_id, t.name, t.type, t.release_date,
               COALESCE((SELECT ROUND(AVG(r2.rating), 2) FROM ratings r2 WHERE r2.title_id = t.title_id), 0) as avg_rating,
               COALESCE((SELECT COUNT(r3.rating) FROM ratings r3 WHERE r3.title_id = t.title_id), 0) as rating_count
        FROM titles t
        JOIN title_genres tg ON tg.title_id = t.title_id
        WHERE tg.genre_id IN (
            SELECT DISTINCT tg2.genre_id
            FROM ratings r
            JOIN title_genres tg2 ON tg2.title_id = r.title_id
            WHERE r.user_id = ? AND r.rating >= 7
        )
        AND t.title_id NOT IN (
            SELECT COALESCE(title_id, 0) FROM ratings WHERE user_id = ?
        )
        ORDER BY avg_rating DESC, rating_count DESC
        LIMIT 6
    ", [$user_id, $user_id]);
} catch (Exception $e) {
    $recommendations = [];
}

// If no recommendations based on genre, show popular titles
if (empty($recommendations)) {
    try {
        $recommendations = db_all($pdo, "
            SELECT t.title_id, t.name, t.type, t.release_date,
                   COALESCE(ROUND(AVG(r.rating), 2), 0) as avg_rating,
                   COALESCE(COUNT(r.rating), 0) as rating_count
            FROM titles t
            LEFT JOIN ratings r ON r.title_id = t.title_id
            WHERE t.title_id NOT IN (
                SELECT COALESCE(title_id, 0) FROM ratings WHERE user_id = ?
            )
            GROUP BY t.title_id, t.name, t.type, t.release_date
            HAVING rating_count >= 3
            ORDER BY avg_rating DESC, rating_count DESC
            LIMIT 6
        ", [$user_id]);
    } catch (Exception $e) {
        $recommendations = [];
    }
}

// Get user's watch progress with error handling
try {
    $watch_progress_result = db_one($pdo, "
        SELECT 
            COALESCE(ROUND(COUNT(DISTINCT title_id) * 100.0 / NULLIF((SELECT COUNT(*) FROM titles), 0), 2), 0) as completion_percentage,
            COALESCE(COUNT(DISTINCT title_id), 0) as watched_titles,
            COALESCE((SELECT COUNT(*) FROM titles), 0) as total_titles
        FROM watch_events 
        WHERE user_id = ? AND completed = 1
    ", [$user_id]);
} catch (Exception $e) {
    $watch_progress_result = null;
}

// Use default values if no progress data
$watch_progress = [
    'completion_percentage' => $watch_progress_result['completion_percentage'] ?? 0,
    'watched_titles' => $watch_progress_result['watched_titles'] ?? 0,
    'total_titles' => $watch_progress_result['total_titles'] ?? 0
];

// Get daily streak with simplified query
try {
    $current_streak_result = db_one($pdo, "
        SELECT COUNT(DISTINCT DATE(watched_at)) as streak_days
        FROM watch_events 
        WHERE user_id = ? 
        AND watched_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ", [$user_id]);
    $current_streak = $current_streak_result ? (int)$current_streak_result['streak_days'] : 0;
} catch (Exception $e) {
    $current_streak = 0;
}

// Ensure all required variables are set
$user_stats = array_merge([
    'total_ratings' => 0,
    'rated_titles' => 0,
    'total_watch_sessions' => 0,
    'total_watch_seconds' => 0,
    'avg_rating' => 0,
    'total_titles_available' => 0
], (array)$user_stats);

?>

<div class="page-header">
    <h1 class="page-title">Your Dashboard</h1>
    <div class="header-actions">
        <a href="titles.php" class="btn">
            <i class="fas fa-film"></i> Browse Titles
        </a>
        <a href="search.php" class="btn btn-secondary">
            <i class="fas fa-search"></i> Search
        </a>
    </div>
</div>

<!-- Personal Statistics -->
<div class="grid grid-4">
    <div class="kpi-card">
        <div class="kpi-value"><?= (int)$user_stats['total_ratings'] ?></div>
        <div class="kpi-label">Ratings Given</div>
        <i class="fas fa-star" style="position: absolute; bottom: 1rem; right: 1rem; opacity: 0.3; font-size: 2rem;"></i>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-value"><?= (int)$user_stats['rated_titles'] ?></div>
        <div class="kpi-label">Titles Rated</div>
        <i class="fas fa-tv" style="position: absolute; bottom: 1rem; right: 1rem; opacity: 0.3; font-size: 2rem;"></i>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-value"><?= round($user_stats['total_watch_seconds'] / 3600, 1) ?></div>
        <div class="kpi-label">Watch Hours</div>
        <i class="fas fa-clock" style="position: absolute; bottom: 1rem; right: 1rem; opacity: 0.3; font-size: 2rem;"></i>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-value"><?= e($user_stats['avg_rating']) ?></div>
        <div class="kpi-label">Avg Rating</div>
        <i class="fas fa-chart-line" style="position: absolute; bottom: 1rem; right: 1rem; opacity: 0.3; font-size: 2rem;"></i>
    </div>
</div>

<div class="grid grid-2">
    <!-- Recent Activity -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <i class="fas fa-history"></i> Recent Activity
            </h2>
            <?php if (!empty($recent_activity)): ?>
                <a href="users/ratings.php" class="btn btn-sm">View All</a>
            <?php endif; ?>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Title</th>
                        <th>Details</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recent_activity)): ?>
                        <?php foreach($recent_activity as $activity): ?>
                            <?php
                            $title = db_one($pdo, "SELECT name, type FROM titles WHERE title_id = ?", [$activity['title_id']]);
                            if (!$title) continue;
                            ?>
                            <tr>
                                <td>
                                    <span class="badge <?= $activity['type'] === 'rating' ? 'badge-warning' : 'badge-primary' ?>">
                                        <?= e(ucfirst($activity['type'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="title.php?id=<?= $activity['title_id'] ?>" class="text-decoration-none">
                                        <?= e($title['name']) ?>
                                    </a>
                                </td>
                                <td>
                                    <?php if ($activity['type'] === 'rating'): ?>
                                        <div class="rating-stars">
                                            <?= get_rating_stars($activity['value']) ?>
                                        </div>
                                        <small class="text-muted"><?= e($activity['value']) ?>/10</small>
                                    <?php else: ?>
                                        <i class="fas fa-clock text-muted"></i>
                                        <small class="text-muted"><?= round(($activity['seconds_watched'] ?? 0) / 60, 1) ?> min</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="text-muted"><?= format_date($activity['date'] ?? '') ?></small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center p-3">
                                <p class="text-muted">No recent activity yet.</p>
                                <a href="titles.php" class="btn btn-sm mt-2">Start Exploring</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Watch Progress & Streak -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <i class="fas fa-trophy"></i> Your Progress
            </h2>
        </div>
        <div class="p-3">
            <!-- Completion Progress -->
            <div class="mb-4">
                <div class="flex justify-between items-center mb-2">
                    <span class="font-semibold">Collection Progress</span>
                    <span class="text-muted" style="font-size: 0.875rem;"><?= (int)$watch_progress['watched_titles'] ?> / <?= (int)$watch_progress['total_titles'] ?></span>
                </div>
                
                <div class="text-center mt-1">
                    <small class="text-muted"><?= number_format((float)$watch_progress['completion_percentage'], 1) ?>% complete</small>
                </div>
            </div>

            <!-- Current Streak -->
            <div class="mb-4">
                <div class="flex justify-between items-center mb-2">
                    <span class="font-semibold">Current Streak</span>
                    <span class="text-muted" style="font-size: 0.875rem;">Last 7 days</span>
                </div>
                <div class="text-center p-3">
                    <div style="font-size: 3rem; font-weight: bold; color: var(--warning); line-height: 1;">
                        <?= (int)$current_streak ?>
                    </div>
                    <small class="text-muted">
                        <?php if ($current_streak > 0): ?>
                            Keep going! 🔥
                        <?php else: ?>
                            Start your streak today!
                        <?php endif; ?>
                    </small>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-2" style="gap: 1rem;">
                <div class="text-center p-3" style="background: var(--light-2); border-radius: var(--border-radius-sm);">
                    <div style="font-weight: bold; font-size: 1.125rem;"><?= (int)$user_stats['total_watch_sessions'] ?></div>
                    <small class="text-muted">Watch Sessions</small>
                </div>
                <div class="text-center p-3" style="background: var(--light-2); border-radius: var(--border-radius-sm);">
                    <div style="font-weight: bold; font-size: 1.125rem;"><?= (int)$user_stats['rated_titles'] ?></div>
                    <small class="text-muted">Rated Titles</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Top Rated & Recently Watched -->
<div class="grid grid-2">
    <!-- Top Rated Titles -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <i class="fas fa-crown"></i> Your Top Rated
            </h2>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Your Rating</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($top_rated)): ?>
                        <?php foreach($top_rated as $title): ?>
                            <tr>
                                <td>
                                    <a href="title.php?id=<?= $title['title_id'] ?>" style="font-weight: 600;">
                                        <?= e($title['name']) ?>
                                    </a>
                                    <br>
                                    <small class="text-muted"><?= e(ucfirst($title['type'] ?? '')) ?></small>
                                </td>
                                <td>
                                    <div class="rating-stars">
                                        <?= get_rating_stars($title['rating'] ?? 0) ?>
                                    </div>
                                    <span style="font-weight: bold;"><?= e($title['rating'] ?? 0) ?>/10</span>
                                </td>
                                <td>
                                    <small class="text-muted"><?= format_date($title['rated_at'] ?? '') ?></small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="text-center p-4">
                                <p class="text-muted">You haven't rated any titles yet.</p>
                                <a href="titles.php" class="btn btn-sm mt-2">Rate Some Titles</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recently Watched -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <i class="fas fa-eye"></i> Recently Watched
            </h2>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Last Watched</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recently_watched)): ?>
                        <?php foreach($recently_watched as $title): ?>
                            <tr>
                                <td>
                                    <a href="title.php?id=<?= $title['title_id'] ?>" style="font-weight: 600;">
                                        <?= e($title['name']) ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge badge-primary"><?= e(ucfirst($title['type'] ?? '')) ?></span>
                                </td>
                                <td>
                                    <small class="text-muted"><?= format_date($title['last_watched'] ?? '') ?></small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="text-center p-4">
                                <p class="text-muted">You haven't watched anything yet.</p>
                                <a href="titles.php" class="btn btn-sm mt-2">Start Watching</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Personalized Recommendations -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <i class="fas fa-lightbulb"></i> Recommended For You
        </h2>
        <small class="text-muted">Based on your ratings</small>
    </div>
    <div class="grid grid-3">
        <?php if (!empty($recommendations)): ?>
            <?php foreach($recommendations as $title): ?>
                <div class="title-card">
                    <img src="assets/images/placeholder-poster.jpg" alt="<?= e($title['name']) ?>" class="title-poster">
                    <div class="title-info">
                        <h3 class="title-name"><?= e($title['name']) ?></h3>
                        <div class="title-meta">
                            <span class="badge badge-primary"><?= e($title['type']) ?></span>
                            <span class="text-muted"><?= format_date($title['release_date'] ?? '') ?></span>
                        </div>
                        <?php if ($title['avg_rating'] > 0): ?>
                            <div class="rating-stars">
                                <?= get_rating_stars($title['avg_rating']) ?>
                            </div>
                            <small class="text-muted"><?= e($title['avg_rating']) ?> (<?= (int)($title['rating_count'] ?? 0) ?> ratings)</small>
                        <?php endif; ?>
                        <div class="title-actions">
                            <a href="title.php?id=<?= $title['title_id'] ?>" class="btn btn-sm">View</a>
                            <a href="add_rating.php?title_id=<?= $title['title_id'] ?>" class="btn btn-sm btn-outline">Rate</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 2rem;">
                <p class="text-muted">Rate more titles to get personalized recommendations!</p>
                <a href="titles.php" class="btn mt-2">Explore Titles</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Genre Watch Time -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <i class="fas fa-chart-pie"></i> Your Watch Time by Genre
        </h2>
    </div>
    <div class="grid grid-2">
        <div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Genre</th>
                            <th>Watch Time</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $total_genre_seconds = array_sum(array_column($genre_watch_time, 'total_seconds'));
                        ?>
                        <?php if (!empty($genre_watch_time) && $total_genre_seconds > 0): ?>
                            <?php foreach($genre_watch_time as $genre): ?>
                                <?php
                                $percentage = $total_genre_seconds > 0 ? round(($genre['total_seconds'] / $total_genre_seconds) * 100, 1) : 0;
                                ?>
                                <tr>
                                    <td><?= e($genre['genre']) ?></td>
                                    <td><?= round(($genre['total_seconds'] ?? 0) / 3600, 1) ?>h</td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <div style="background: var(--light-3); border-radius: 10px; height: 6px; flex: 1;">
                                                <div style="background: var(--primary); height: 100%; width: <?= $percentage ?>%; border-radius: 10px;"></div>
                                            </div>
                                            <small class="text-muted"><?= $percentage ?>%</small>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center p-3">
                                    <p class="text-muted">No genre data available yet.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="flex items-center justify-center">
            <?php if ($total_genre_seconds > 0): ?>
                <div style="text-align: center;">
                    <div style="font-size: 3rem; color: var(--primary);">
                        <i class="fas fa-film"></i>
                    </div>
                    <h3 class="mt-2"><?= round($total_genre_seconds / 3600, 1) ?> Total Hours</h3>
                    <p class="text-muted">Across <?= count($genre_watch_time) ?> genres</p>
                </div>
            <?php else: ?>
                <div class="text-center">
                    <p class="text-muted">No genre data available yet.</p>
                    <a href="titles.php" class="btn btn-sm mt-2">Start Watching</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <i class="fas fa-bolt"></i> Quick Actions
        </h2>
    </div>
    <div class="grid grid-4">
        <a href="add_rating.php" class="btn btn-secondary">
            <i class="fas fa-star"></i> Rate a Title
        </a>
        <a href="add_watch.php" class="btn btn-secondary">
            <i class="fas fa-history"></i> Log Watch
        </a>
        <a href="search.php" class="btn btn-secondary">
            <i class="fas fa-search"></i> Search Titles
        </a>
        <a href="users/profile.php" class="btn btn-secondary">
            <i class="fas fa-user"></i> Your Profile
        </a>
    </div>
</div>

<?php require_once __DIR__.'/footer.php'; ?>