<?php
require_once __DIR__.'/header.php';

require_login();

$ok = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = get_user_id();
    $title_id = (int)($_POST['title_id'] ?? 0);
    $rating = (float)($_POST['rating'] ?? 0);
    
    if ($title_id > 0 && $rating >= 0 && $rating <= 10) {
        // Check if user already rated this title
        $existing_rating = db_one($pdo, 
            "SELECT rating_id FROM ratings WHERE user_id = ? AND title_id = ?", 
            [$user_id, $title_id]
        );
        
        if ($existing_rating) {
            // Update existing rating
            $st = $pdo->prepare("UPDATE ratings SET rating = ?, rated_at = NOW() WHERE rating_id = ?");
            $st->execute([$rating, $existing_rating['rating_id']]);
            flash("Rating updated successfully!", "success");
        } else {
            // Insert new rating
            $st = $pdo->prepare("INSERT INTO ratings (user_id, title_id, rating, rated_at) VALUES (?, ?, ?, NOW())");
            $st->execute([$user_id, $title_id, $rating]);
            flash("Rating submitted successfully!", "success");
        }
        
        redirect("title.php?id=" . $title_id);
    } else {
        $ok = "Please fill all fields correctly.";
    }
}

$titles = db_all($pdo, "SELECT title_id, name FROM titles ORDER BY name");
$title_id = isset($_GET['title_id']) ? (int)$_GET['title_id'] : 0;
?>

<div class="page-header">
    <h1 class="page-title">Add Rating</h1>
    <a href="titles.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Titles
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Rate a Title</h2>
        <i class="fas fa-star" style="color: var(--warning); font-size: 1.5rem;"></i>
    </div>

    <?php if ($ok): ?>
        <div class="notification notification-error">
            <i class="fas fa-exclamation-circle"></i>
            <p><?= e($ok) ?></p>
        </div>
    <?php endif; ?>

    <form method="post">
        <div class="form-group">
            <label class="form-label">Title</label>
            <select name="title_id" class="form-control form-select" required>
                <option value="">-- Select a Title --</option>
                <?php foreach($titles as $t): ?>
                    <option value="<?= $t['title_id'] ?>" <?= $title_id === $t['title_id'] ? 'selected' : '' ?>>
                        <?= e($t['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Your Rating (0-10)</label>
            <input type="range" name="rating" min="0" max="10" step="0.5" value="5" 
                   class="form-control rating-slider" oninput="updateRatingDisplay(this.value)">
            <output id="rating-output">5</output>
            <div class="rating-stars" id="rating-stars" style="margin-top: 0.5rem;">
                <?= get_rating_stars(5) ?>
            </div>
        </div>

        <button type="submit" class="btn">
            <i class="fas fa-save"></i> Submit Rating
        </button>
    </form>
</div>

<script>
function updateRatingDisplay(value) {
    document.getElementById('rating-output').textContent = value;
    
    const starsContainer = document.getElementById('rating-stars');
    const fullStars = Math.floor(value);
    const hasHalfStar = value % 1 >= 0.5;
    
    let starsHTML = '';
    for (let i = 0; i < fullStars; i++) {
        starsHTML += '<i class="fas fa-star"></i>';
    }
    if (hasHalfStar) {
        starsHTML += '<i class="fas fa-star-half-alt"></i>';
    }
    for (let i = fullStars + (hasHalfStar ? 1 : 0); i < 10; i++) {
        starsHTML += '<i class="far fa-star"></i>';
    }
    
    starsContainer.innerHTML = starsHTML;
}
</script>

<?php require_once __DIR__.'/footer.php'; ?>