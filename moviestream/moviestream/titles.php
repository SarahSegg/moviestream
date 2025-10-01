<?php
require_once __DIR__.'/header.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$type = isset($_GET['type']) && in_array($_GET['type'], ['movie','show']) ? $_GET['type'] : '';
$genre_id = isset($_GET['genre_id']) ? (int)$_GET['genre_id'] : 0;
$platform_id = isset($_GET['platform_id']) ? (int)$_GET['platform_id'] : 0;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$genres = db_all($pdo, "SELECT genre_id, name FROM genres ORDER BY name");
$platforms = db_all($pdo, "SELECT platform_id, name FROM platforms ORDER BY name");

$sql = "
    SELECT t.title_id, t.name, t.type, t.release_date, t.status, t.runtime_min, t.seasons,
           ROUND(AVG(r.rating), 2) as avg_rating,
           COUNT(r.rating) as rating_count
    FROM titles t
    LEFT JOIN ratings r ON r.title_id = t.title_id
    LEFT JOIN title_genres tg ON tg.title_id = t.title_id
    LEFT JOIN title_platforms tp ON tp.title_id = t.title_id
    WHERE 1=1
";
$params = [];
$where = [];

if ($q !== '') { 
    $where[] = " t.name LIKE ?";
    $params[] = "%$q%";
}
if ($type !== '') { 
    $where[] = " t.type = ?";
    $params[] = $type;
}
if ($genre_id > 0) { 
    $where[] = " tg.genre_id = ?";
    $params[] = $genre_id;
}
if ($platform_id > 0) { 
    $where[] = " tp.platform_id = ?";
    $params[] = $platform_id;
}

if (!empty($where)) {
    $sql .= " AND " . implode(" AND ", $where);
}

$sql .= " GROUP BY t.title_id, t.name, t.type, t.release_date, t.status, t.runtime_min, t.seasons";
$sql .= " ORDER BY t.name LIMIT $limit OFFSET $offset";

$rows = db_all($pdo, $sql, $params);

// Get total count for pagination
$count_sql = "
    SELECT COUNT(DISTINCT t.title_id) as total
    FROM titles t
    LEFT JOIN title_genres tg ON tg.title_id = t.title_id
    LEFT JOIN title_platforms tp ON tp.title_id = t.title_id
    WHERE 1=1
";
if (!empty($where)) {
    $count_sql .= " AND " . implode(" AND ", $where);
}
$total_result = db_one($pdo, $count_sql, $params);
$total_titles = $total_result['total'] ?? 0;
$total_pages = ceil($total_titles / $limit);
?>

<div class="page-header">
    <h1 class="page-title">Browse Titles</h1>
</div>

<div class="card">
    <form method="get" class="search-filter">
        <div class="form-group">
            <label class="form-label">Search by name</label>
            <input type="text" name="q" value="<?= e($q) ?>" class="form-control" placeholder="Search titles...">
        </div>

        <div class="form-group">
            <label class="form-label">Type</label>
            <select name="type" class="form-control form-select">
                <option value="">(any type)</option>
                <option value="movie" <?= $type==='movie'?'selected':'' ?>>Movie</option>
                <option value="show"  <?= $type==='show'?'selected':'' ?>>Show</option>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Genre</label>
            <select name="genre_id" class="form-control form-select">
                <option value="0">(any genre)</option>
                <?php foreach($genres as $g): ?>
                    <option value="<?= $g['genre_id'] ?>" <?= $genre_id===$g['genre_id']?'selected':'' ?>>
                        <?= e($g['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Platform</label>
            <select name="platform_id" class="form-control form-select">
                <option value="0">(any platform)</option>
                <?php foreach($platforms as $p): ?>
                    <option value="<?= $p['platform_id'] ?>" <?= $platform_id===$p['platform_id']?'selected':'' ?>>
                        <?= e($p['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" style="align-self: end;">
            <button type="submit" class="btn">Apply Filters</button>
            <a href="titles.php" class="btn btn-secondary">Clear</a>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Titles (<?= $total_titles ?> found)</h2>
    </div>
    
    <?php if (count($rows) > 0): ?>
        <div class="grid">
            <?php foreach($rows as $title): ?>
                <div class="title-card">
                    <img src="assets/images/placeholder-poster.jpg" alt="<?= e($title['name']) ?>" class="title-poster">
                    <div class="title-info">
                        <h3 class="title-name"><?= e($title['name']) ?></h3>
                        <div class="title-meta">
                            <span class="badge badge-primary"><?= e($title['type']) ?></span>
                            <span><?= format_date($title['release_date']) ?></span>
                        </div>
                        <?php if ($title['avg_rating']): ?>
                            <div class="rating-stars">
                                <?= get_rating_stars($title['avg_rating']) ?>
                                <span class="rating-value"><?= e($title['avg_rating']) ?></span>
                                <small>(<?= (int)$title['rating_count'] ?> ratings)</small>
                            </div>
                        <?php else: ?>
                            <p class="note">No ratings yet</p>
                        <?php endif; ?>
                        <div class="title-actions">
                            <a href="title.php?id=<?= $title['title_id'] ?>" class="btn btn-sm">View Details</a>
                            <a href="add_rating.php?title_id=<?= $title['title_id'] ?>" class="btn btn-sm btn-outline">Rate</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">&laquo;</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="current"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">&raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <p class="note">No titles found matching your criteria.</p>
    <?php endif; ?>
</div>

<?php require_once __DIR__.'/footer.php'; ?>