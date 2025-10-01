<?php
require_once __DIR__.'/../header.php';

require_login();
if (!is_admin()) {
    flash("Access denied. Admin privileges required.", "error");
    redirect('index.php');
}

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($action === 'delete' && $user_id > 0) {
    // Prevent admin from deleting themselves
    if ($user_id === get_user_id()) {
        flash("You cannot delete your own account.", "error");
        redirect('users.php');
    }
    
    try {
        $pdo->beginTransaction();
        
        // Delete user-related records
        $pdo->exec("DELETE FROM ratings WHERE user_id = $user_id");
        $pdo->exec("DELETE FROM watch_events WHERE user_id = $user_id");
        
        // Delete the user
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        $pdo->commit();
        flash("User deleted successfully!", "success");
    } catch (Exception $e) {
        $pdo->rollBack();
        flash("Error deleting user: " . $e->getMessage(), "error");
    }
    
    redirect('users.php');
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;
    
    if (empty($username) || empty($email)) {
        flash("Username and email are required", "error");
    } else {
        if ($user_id > 0) {
            // Update existing user
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    UPDATE users SET username = ?, email = ?, password = ?, is_admin = ?
                    WHERE user_id = ?
                ");
                $stmt->execute([$username, $email, $hashed_password, $is_admin, $user_id]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE users SET username = ?, email = ?, is_admin = ?
                    WHERE user_id = ?
                ");
                $stmt->execute([$username, $email, $is_admin, $user_id]);
            }
            flash("User updated successfully!", "success");
        } else {
            // Insert new user
            if (empty($password)) {
                flash("Password is required for new users", "error");
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    INSERT INTO users (username, email, password, is_admin, created_at)
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$username, $email, $hashed_password, $is_admin]);
                $user_id = $pdo->lastInsertId();
                flash("User added successfully!", "success");
            }
        }
        
        if (!isset($_POST['password']) || !empty($_POST['password'])) {
            redirect('users.php');
        }
    }
}

if ($action === 'edit' || $action === 'add') {
    $user = null;
    if ($user_id > 0) {
        $user = db_one($pdo, "SELECT * FROM users WHERE user_id = ?", [$user_id]);
    }
    ?>
    
    <div class="page-header">
        <h1 class="page-title"><?= $user_id > 0 ? 'Edit User' : 'Add New User' ?></h1>
        <a href="users.php" class="btn btn-secondary">Back to Users</a>
    </div>
    
    <div class="card">
        <form method="post">
            <div class="grid" style="grid-template-columns: 1fr 1fr;">
                <div class="form-group">
                    <label class="form-label">Username *</label>
                    <input type="text" name="username" class="form-control" 
                           value="<?= e($user['username'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-control" 
                           value="<?= e($user['email'] ?? '') ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Password <?= $user_id > 0 ? '(leave blank to keep current)' : '*' ?></label>
                <input type="password" name="password" class="form-control" 
                       <?= $user_id > 0 ? '' : 'required' ?>>
            </div>
            
            <div class="form-group">
                <label class="checkbox-group">
                    <input type="checkbox" name="is_admin" value="1"
                        <?= ($user['is_admin'] ?? 0) ? 'checked' : '' ?>>
                    <span>Administrator</span>
                </label>
            </div>
            
            <button type="submit" class="btn">Save User</button>
        </form>
    </div>
    
    <?php
} else {
    // List users
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;
    
    $users = db_all($pdo, "
        SELECT u.*,
               COUNT(r.rating_id) as rating_count,
               COUNT(we.watch_id) as watch_count
        FROM users u
        LEFT JOIN ratings r ON r.user_id = u.user_id
        LEFT JOIN watch_events we ON we.user_id = u.user_id
        GROUP BY u.user_id
        ORDER BY u.created_at DESC
        LIMIT $limit OFFSET $offset
    ");
    
    $total_users = db_one($pdo, "SELECT COUNT(*) as total FROM users")['total'] ?? 0;
    $total_pages = ceil($total_users / $limit);
    ?>
    
    <div class="page-header">
        <h1 class="page-title">Manage Users</h1>
        <a href="users.php?action=add" class="btn">Add New User</a>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">All Users (<?= $total_users ?> total)</h2>
        </div>
        
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Ratings</th>
                        <th>Watch Events</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($users as $user): ?>
                    <tr>
                        <td>
                            <strong><?= e($user['username']) ?></strong>
                            <?php if ($user['user_id'] == get_user_id()): ?>
                                <span class="badge badge-success">You</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($user['email']) ?></td>
                        <td>
                            <?php if ($user['is_admin']): ?>
                                <span class="badge badge-warning">Admin</span>
                            <?php else: ?>
                                <span class="badge badge-primary">User</span>
                            <?php endif; ?>
                        </td>
                        <td><?= (int)$user['rating_count'] ?></td>
                        <td><?= (int)$user['watch_count'] ?></td>
                        <td><?= format_date($user['created_at']) ?></td>
                        <td>
                            <a href="users.php?action=edit&id=<?= $user['user_id'] ?>" class="btn btn-sm">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <?php if ($user['user_id'] != get_user_id()): ?>
                                <a href="users.php?action=delete&id=<?= $user['user_id'] ?>" 
                                   class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Are you sure you want to delete this user?')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            <?php endif; ?>
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