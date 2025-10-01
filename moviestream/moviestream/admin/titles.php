<?php
require_once __DIR__.'/../header.php';

require_login();
if (!is_admin()) {
    flash("Access denied. Admin privileges required.", "error");
    redirect('index.php');
}

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$title_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($action === 'delete' && $title_id > 0) {
    // Delete title (with proper error handling)
    try {
        $pdo->beginTransaction();
        
        // Delete related records first
        $pdo->exec("DELETE FROM ratings WHERE title_id = $title_id");
        $pdo->exec("DELETE FROM watch_events WHERE title_id = $title_id");
        $pdo->exec("DELETE FROM title_genres WHERE title_id = $title_id");
        $pdo->exec("DELETE FROM title_platforms WHERE title_id = $title_id");
        $pdo->exec("DELETE FROM episodes WHERE show_id = $title_id");
        
        // Delete the title
        $stmt = $pdo->prepare("DELETE FROM titles WHERE title_id = ?");
        $stmt->execute([$title_id]);
        
        $pdo->commit();
        flash("Title deleted successfully!", "success");
    } catch (Exception $e) {
        $pdo->rollBack();
        flash("Error deleting title: " . $e->getMessage(), "error");
    }
    
    redirect('titles.php');
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $type = $_POST['type'] ?? 'movie';
    $release_date = $_POST['release_date'] ?? '';
    $status = $_POST['status'] ?? 'released';
    $runtime_min = isset($_POST['runtime_min']) ? (int)$_POST['runtime_min'] : null;
    $seasons = isset($_POST['seasons']) ? (int)$_POST['seasons'] : null;
    $description = trim($_POST['description'] ?? '');
    
    if (empty($name)) {
        flash("Title name is required", "error");
    } else {
        if ($title_id > 0) {
            // Update existing title
            $stmt = $pdo->prepare("
                UPDATE titles SET name = ?, type = ?, release_date = ?, status = ?, 
                runtime_min = ?, seasons = ?, description = ?
                WHERE title_id = ?
            ");
            $stmt->execute([$name, $type, $release_date, $status, $runtime_min, $seasons, $description, $title_id]);
            flash("Title updated successfully!", "success");
        } else {
            // Insert new title
            $stmt = $pdo->prepare("
                INSERT INTO titles (name, type, release_date, status, runtime_min, seasons, description, added_date)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$name, $type, $release_date, $status, $runtime_min, $seasons, $description]);
            $title_id = $pdo->lastInsertId();
            flash("Title added successfully!", "success");
        }
        redirect('titles.php');
    }
}

$genres = db_all($pdo, "SELECT genre_id, name FROM genres ORDER BY name");
$platforms = db_all($pdo, "SELECT platform_id, name FROM platforms ORDER BY name");

if ($action === 'edit' || $action === 'add') {
    $title = null;
    if ($title_id > 0) {
        $title = db_one($pdo, "SELECT * FROM titles WHERE title_id = ?", [$title_id]);
    }
    
    $title_genres = [];
    $title_platforms = [];
    if ($title_id > 0) {
        $title_genres = db_all($pdo, "SELECT genre_id FROM title_genres WHERE title_id = ?", [$title_id]);
        $title_platforms = db_all($pdo, "SELECT platform_id FROM title_platforms WHERE title_id = ?", [$title_id]);
    }
    ?>
    
    <div class="page-header">
        <h1 class="page-title"><?= $title_id > 0 ? 'Edit Title' : 'Add New Title' ?></h1>
        <a href="titles.php" class="btn btn-secondary">Back to Titles</a>
    </div>
    
    <div class="card">
        <form method="post">
            <div class="grid" style="grid-template-columns: 1fr 1fr;">
                <div class="form-group">
                    <label class="form-label">Title Name *</label>
                    <input type="text" name="name" class="form-control" 
                           value="<?= e($title['name'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Type *</label>
                    <select name="type" class="form-control form-select" required>
                        <option value="movie" <?= ($title['type'] ?? 'movie') === 'movie' ? 'selected' : '' ?>>Movie</option>
                        <option value="show" <?= ($title['type'] ?? '') === 'show' ? 'selected' : '' ?>>TV Show</option>
                    </select>
                </div>
            </div>
            
            <div class="grid" style="grid-template-columns: 1fr 1fr;">
                <div class="form-group">
                    <label class="form-label">Release Date</label>
                    <input type="date" name="release_date" class="form-control" 
                           value="<?= e($title['release_date'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control form-select">
                        <option value="released" <?= ($title['status'] ?? 'released') === 'released' ? 'selected' : '' ?>>Released</option>
                        <option value="upcoming" <?= ($title['status'] ?? '') === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
                        <option value="cancelled" <?= ($title['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
            </div>
            
            <div class="grid" style="grid-template-columns: 1fr 1fr;">
                <div class="form-group" id="runtime-field">
                    <label class="form-label">Runtime (minutes)</label>
                    <input type="number" name="runtime_min" class="form-control" 
                           value="<?= e($title['runtime_min'] ?? '') ?>" min="1">
                </div>
                
                <div class="form-group" id="seasons-field" style="display: none;">
                    <label class="form-label">Seasons</label>
                    <input type="number" name="seasons" class="form-control" 
                           value="<?= e($title['seasons'] ?? '') ?>" min="1">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="4"><?= e($title['description'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Genres</label>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 0.5rem;">
                    <?php foreach($genres as $genre): ?>
                        <label class="checkbox-group">
                            <input type="checkbox" name="genres[]" value="<?= $genre['genre_id'] ?>"
                                <?= in_array($genre['genre_id'], array_column($title_genres, 'genre_id')) ? 'checked' : '' ?>>
                            <span><?= e($genre['name']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Platforms</label>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 0.5rem;">
                    <?php foreach($platforms as $platform): ?>
                        <label class="checkbox-group">
                            <input type="checkbox" name="platforms[]" value="<?= $platform['platform_id'] ?>"
                                <?= in_array($platform['platform_id'], array_column($title_platforms, 'platform_id')) ? 'checked' : '' ?>>
                            <span><?= e($platform['name']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <button type="submit" class="btn">Save Title</button>
        </form>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const typeSelect = document.querySelector('select[name="type"]');
        const runtimeField = document.getElementById('runtime-field');
        const seasonsField = document.getElementById('seasons-field');
        
        function updateFields() {
            if (typeSelect.value === 'show') {
                runtimeField.style.display = 'none';
                seasonsField.style.display = 'block';
            } else {
                runtimeField.style.display = 'block';
                seasonsField.style.display = 'none';
            }
        }
        
        typeSelect.addEventListener('change', updateFields);
        updateFields(); // Initial call
    });
    </script>
    
    <?php
} else {
    // List titles
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;
    
    $titles = db_all($pdo, "
        SELECT t.*, 
               COUNT(r.rating) as rating_count,
               ROUND(AVG(r.rating), 2) as avg_rating
        FROM titles t
        LEFT JOIN ratings r ON r.title_id = t.title_id
        GROUP BY t.title_id
        ORDER BY t.added_date DESC
        LIMIT $limit OFFSET $offset
    ");
    
    $total_titles = db_one($pdo, "SELECT COUNT(*) as total FROM titles")['total'] ?? 0;
    $total_pages = ceil($total_titles / $limit);
    ?>
    
    <div class="page-header">
        <h1 class="page-title">Manage Titles</h1>
        <a href="titles.php?action=add" class="btn">Add New Title</a>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">All Titles (<?= $total_titles ?> total)</h2>
        </div>
        
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Release Date</th>
                        <th>Ratings</th>
                        <th>Avg Rating</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($titles as $title): ?>
                    <tr>
                        <td>
                            <a href="../title.php?id=<?= $title['title_id'] ?>">
                                <?= e($title['name']) ?>
                            </a>
                        </td>
                        <td>
                            <span class="badge badge-primary"><?= e($title['type']) ?></span>
                        </td>
                        <td><?= format_date($title['release_date']) ?></td>
                        <td><?= (int)$title['rating_count'] ?></td>
                        <td>
                            <?php if ($title['avg_rating']): ?>
                                <div class="rating-stars">
                                    <?= get_rating_stars($title['avg_rating']) ?>
                                </div>
                                <?= e($title['avg_rating']) ?>
                            <?php else: ?>
                                <span class="note">No ratings</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="titles.php?action=edit&id=<?= $title['title_id'] ?>" class="btn btn-sm">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="titles.php?action=delete&id=<?= $title['title_id'] ?>" 
                               class="btn btn-sm btn-danger" 
                               onclick="return confirm('Are you sure you want to delete this title?')">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>">&laquo;</a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="current"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?page=<?= $i ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?>">&raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

require_once __DIR__.'/../footer.php';
?>