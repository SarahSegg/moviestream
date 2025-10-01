<?php
require_once __DIR__.'/config.php';
require_once __DIR__.'/helpers.php';
require_once __DIR__.'/includes/db_connect.php';
require_once __DIR__.'/includes/notifications.php';

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($SITE_NAME) ?> - <?= e(ucfirst(str_replace(['.php', '_'], ['', ' '], $current_page))) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <a href="index.php" class="logo">
                    <i class="fas fa-play-circle"></i>
                    <span><?= e($SITE_NAME) ?></span>
                </a>
                
                <nav>
                    <ul>
                        <li><a href="index.php" class="<?= $current_page == 'index.php' ? 'active' : '' ?>"><i class="fas fa-home"></i> Home</a></li>
                        <li><a href="titles.php" class="<?= $current_page == 'titles.php' ? 'active' : '' ?>"><i class="fas fa-film"></i> Browse</a></li>
                        <li><a href="search.php" class="<?= $current_page == 'search.php' ? 'active' : '' ?>"><i class="fas fa-search"></i> Search</a></li>
                        
                        <?php if (is_logged_in()): ?>
                            <li><a href="add_rating.php" class="<?= $current_page == 'add_rating.php' ? 'active' : '' ?>"><i class="fas fa-star"></i> Rate</a></li>
                            <li><a href="add_watch.php" class="<?= $current_page == 'add_watch.php' ? 'active' : '' ?>"><i class="fas fa-history"></i> Log Watch</a></li>
                            <li><a href="users/profile.php"><i class="fas fa-user"></i> Profile</a></li>
                            <?php if (is_admin()): ?>
                                <li><a href="admin/"><i class="fas fa-cog"></i> Admin</a></li>
                            <?php endif; ?>
                            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        <?php else: ?>
                            <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <main class="container">
        <?php show_notifications(); ?>