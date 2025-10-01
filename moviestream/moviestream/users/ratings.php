<?php
require_once __DIR__.'/../header.php';

require_login();

$user_id = get_user_id();
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get user's ratings
$ratings = db_all($pdo, "
    SELECT r.rating_id, r.title_id, r.rating, r.rated_at,
           t.name as title_name, t.type as title_type
    FROM ratings r
    JOIN titles t ON t.title_id = r.title_id
    WHERE r.user_id = ?
    ORDER BY r.rated_at DESC
    LIMIT $limit OFFSET $offset
", [$user_id]);

// Get total count for pagination
$total_ratings = db_one($pdo, "SELECT COUNT(*) as total FROM ratings WHERE user_id = ?", [$user_id])['total'] ?? 0;
$total_pages = ceil($total_ratings / $limit);
?>

<div class="page-header">
    <h1 class="page-title">Your Ratings</h1>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Your Movie & Show Ratings</h2>
        <span class="badge badge-primary"><?= (int)$total_ratings ?> ratings</span>
    </div>

    <?php if (!empty($ratings)): ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Your Rating</th>
                        <th>Date Rated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($ratings as $rating): ?>
                        <tr>
                            <td>
                                <a href="title.php?id=<?= $rating['title_id'] ?>">
                                    <?= e($rating['title_name']) ?>
                                </a>
                            </td>
                            <td>
                                <span class="badge badge-primary"><?= e($rating['title_type']) ?></span>
                            </td>
                            <td>
                                <div class="rating-stars">
                                    <?= get_rating_stars($rating['rating']) ?>
                                </div>
                                <?= e($rating['rating']) ?>/10
                            </td>
                            <td><?= format_date($rating['rated_at']) ?></td>
                            <td>
                                <a href="add_rating.php?title_id=<?= $rating['title_id'] ?>" class="btn btn-sm">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <button class="btn btn-sm btn-danger" onclick="deleteRating(<?= $rating['rating_id'] ?>)">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
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
    <?php else: ?>
        <p class="note">You haven't rated any titles yet.</p>
        <a href="titles.php" class="btn">Browse Titles to Rate</a>
    <?php endif; ?>
</div>

<script>
function deleteRating(ratingId) {
    if (confirm('Are you sure you want to delete this rating?')) {
        fetch('../api/ratings.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ rating_id: ratingId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Rating deleted successfully!', 'success');
                location.reload();
            } else {
                showNotification('Error deleting rating: ' + data.message, 'error');
            }
        })
        .catch(error => {
            showNotification('Error deleting rating', 'error');
        });
    }
}
</script>

<?php require_once __DIR__.'/../footer.php'; ?>