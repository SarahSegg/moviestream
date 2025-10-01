<?php
require_once __DIR__.'/header.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$type = isset($_GET['type']) ? $_GET['type'] : '';
$genre_id = isset($_GET['genre_id']) ? (int)$_GET['genre_id'] : 0;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

$genres = db_all($pdo, "SELECT genre_id, name FROM genres ORDER BY name");

if (!empty($q)) {
    $sql = "
        SELECT t.title_id, t.name, t.type, t.release_date, t.description,
               ROUND(AVG(r.rating), 2) as avg_rating,
               COUNT(r.rating) as rating_count
        FROM titles t
        LEFT JOIN ratings r ON r.title_id = t.title_id
        LEFT JOIN title_genres tg ON tg.title_id = t.title_id
        WHERE t.name LIKE ? OR t.description LIKE ?
    ";
    
    $params = ["%$q%", "%$q%"];
    $where = [];
    
    if ($type) {
        $where[] = "t.type = ?";
        $params[] = $type;
    }
    if ($genre_id > 0) {
        $where[] = "tg.genre_id = ?";
        $params[] = $genre_id;
    }
    
    if (!empty($where)) {
        $sql .= " AND " . implode(" AND ", $where);
    }
    
    $sql .= " GROUP BY t.title_id, t.name, t.type, t.release_date, t.description";
    $sql .= " ORDER BY t.name LIMIT $limit OFFSET $offset";
    
    $results = db_all($pdo, $sql, $params);
    
    // Count total results
    $count_sql = "
        SELECT COUNT(DISTINCT t.title_id) as total
        FROM titles t
        LEFT JOIN title_genres tg ON tg.title_id = t.title_id
        WHERE t.name LIKE ? OR t.description LIKE ?
    ";
    $count_params = ["%$q%", "%$q%"];
    
    if ($type) {
        $count_sql .= " AND t.type = ?";
        $count_params[] = $type;
    }
    if ($genre_id > 0) {
        $count_sql .= " AND tg.genre_id = ?";
        $count_params[] = $genre_id;
    }
    
    $total_result = db_one($pdo, $count_sql, $count_params);
    $total_results = $total_result['total'] ?? 0;
    $total_pages = ceil($total_results / $limit);
} else {
    $results = [];
    $total_results = 0;
    $total_pages = 0;
}
?>

<div class="page-header">
    <h1 class="page-title">Search Titles</h1>
</div>

<div class="card">
    <form method="get" class="search-form">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="search" name="q" value="<?= e($q) ?>" 
                   class="form-control live-search" placeholder="Search titles..." 
                   oninput="performSearch(this.value)">
        </div>
        
        <div class="search-filter" style="margin-top: 1rem;">
            <div class="form-group">
                <label class="form-label">Type</label>
                <select name="type" class="form-control form-select" onchange="applyFilters()">
                    <option value="">All Types</option>
                    <option value="movie" <?= $type === 'movie' ? 'selected' : '' ?>>Movie</option>
                    <option value="show" <?= $type === 'show' ? 'selected' : '' ?>>Show</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Genre</label>
                <select name="genre_id" class="form-control form-select" onchange="applyFilters()">
                    <option value="0">All Genres</option>
                    <?php foreach($genres as $genre): ?>
                        <option value="<?= $genre['genre_id'] ?>" <?= $genre_id === $genre['genre_id'] ? 'selected' : '' ?>>
                            <?= e($genre['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group" style="align-self: end;">
                <button type="submit" class="btn">Search</button>
                <a href="search.php" class="btn btn-secondary">Clear</a>
            </div>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <?php if (!empty($q)): ?>
                Search Results for "<?= e($q) ?>" (<?= $total_results ?> found)
            <?php else: ?>
                Search Results
            <?php endif; ?>
        </h2>
    </div>
    
    <div id="search-results">
        <?php if (!empty($q)): ?>
            <?php if (!empty($results)): ?>
                <div class="grid">
                    <?php foreach($results as $title): ?>
                        <div class="title-card">
                            <img src="assets/images/placeholder-poster.jpg" alt="<?= e($title['name']) ?>" class="title-poster">
                            <div class="title-info">
                                <h3 class="title-name"><?= e($title['name']) ?></h3>
                                <div class="title-meta">
                                    <span class="badge badge-primary"><?= e($title['type']) ?></span>
                                    <span><?= format_date($title['release_date']) ?></span>
                                </div>
                                <?php if ($title['description']): ?>
                                    <p class="title-description"><?= e(substr($title['description'], 0, 100)) ?>...</p>
                                <?php endif; ?>
                                <?php if ($title['avg_rating']): ?>
                                    <div class="rating-stars">
                                        <?= get_rating_stars($title['avg_rating']) ?>
                                        <span class="rating-value"><?= e($title['avg_rating']) ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="title-actions">
                                    <a href="title.php?id=<?= $title['title_id'] ?>" class="btn btn-sm">View</a>
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
                <p class="note">No results found for "<?= e($q) ?>".</p>
            <?php endif; ?>
        <?php else: ?>
            <p class="note">Enter a search term to find titles.</p>
        <?php endif; ?>
    </div>
</div>

<script>
function performSearch(query) {
    // This would be implemented with AJAX in a real application
    // For now, we'll just submit the form when the user presses Enter
}

function applyFilters() {
    document.querySelector('.search-form').submit();
}
</script>

<?php require_once __DIR__.'/footer.php'; ?>