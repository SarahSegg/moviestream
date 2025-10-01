<?php
require_once __DIR__.'/includes/db_connect.php';
require_once __DIR__.'/helpers.php';
require_once __DIR__.'/includes/auth.php';
require_once __DIR__.'/includes/notifications.php';

// If user is already logged in, redirect to appropriate page
if (is_logged_in()) {
    if (is_admin()) {
        header('Location: admin/');
    } else {
        header('Location: dashboard.php');
    }
    exit;
}

$error = '';
$username = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Clean old login attempts periodically
    if (mt_rand(1, 10) === 1) {
        clean_old_attempts();
    }
    
    // Validate CSRF token
    if (!validate_csrf_token($csrf_token)) {
        $error = "Invalid security token. Please try again.";
    }
    // Validate input
    elseif (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    }
    // Check for brute force
    elseif (check_brute_force($username)) {
        $error = "Too many failed login attempts. Please try again in 2 hours.";
        record_login_attempt($username, false);
    }
    else {
        // Attempt authentication
        $user = authenticate_user($username, $password);
        
        if ($user) {
            // Login successful
            login($user['user_id'], $user['username'], (bool)$user['is_admin']);
            record_login_attempt($username, true);
            
            // Redirect to intended page or default
            $redirect_to = $_SESSION['redirect_to'] ?? (is_admin() ? 'admin/' : 'dashboard.php');
            unset($_SESSION['redirect_to']);
            
            flash("Welcome back, " . e($user['username']) . "!", "success");
            header('Location: ' . $redirect_to);
            exit;
        } else {
            // Login failed
            $error = "Invalid username or password.";
            record_login_attempt($username, false);
        }
    }
}

// Generate CSRF token for the form
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MovieStream</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem;
        }
        
        .login-card {
            background: white;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-lg);
            padding: 3rem;
            width: 100%;
            max-width: 400px;
            position: relative;
            overflow: hidden;
        }
        
        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
        }
        
        .login-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-logo .logo {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }
        
        .login-logo h1 {
            color: var(--dark);
            margin-bottom: 0.5rem;
        }
        
        .login-logo p {
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark);
        }
        
        .form-control {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid var(--gray-lighter);
            border-radius: var(--border-radius-sm);
            font-size: 1rem;
            transition: var(--transition);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }
        
        .btn-login {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            border-radius: var(--border-radius-sm);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .login-links {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--light-3);
        }
        
        .login-links a {
            color: var(--primary);
            text-decoration: none;
            transition: var(--transition);
        }
        
        .login-links a:hover {
            color: var(--primary-dark);
        }
        
        .demo-accounts {
            background: var(--light-2);
            border-radius: var(--border-radius-sm);
            padding: 1.5rem;
            margin-top: 2rem;
        }
        
        .demo-accounts h3 {
            font-size: 1rem;
            margin-bottom: 1rem;
            color: var(--dark);
        }
        
        .demo-account {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--light-3);
        }
        
        .demo-account:last-child {
            border-bottom: none;
        }
        
        .demo-account .role {
            font-size: 0.875rem;
            color: var(--gray);
        }
        
        .demo-account .credentials {
            font-family: monospace;
            font-size: 0.875rem;
            color: var(--dark);
        }
        
        .error-message {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            padding: 1rem;
            border-radius: var(--border-radius-sm);
            border-left: 4px solid var(--danger);
            margin-bottom: 1.5rem;
        }
        
        @media (max-width: 480px) {
            .login-card {
                padding: 2rem;
            }
            
            .login-container {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-logo">
                <div class="logo">
                    <i class="fas fa-play-circle"></i>
                </div>
                <h1>MovieStream</h1>
                <p>Sign in to your account</p>
            </div>

            <?php if ($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <?php
            // Show flash messages if any
            $flash = get_flash();
            if ($flash): ?>
                <div class="notification notification-<?= e($flash['type']) ?>">
                    <i class="fas fa-<?= $flash['type'] === 'success' ? 'check' : 'info' ?>-circle"></i>
                    <p><?= e($flash['message']) ?></p>
                </div>
            <?php endif; ?>

            <form method="post" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                
                <div class="form-group">
                    <label class="form-label" for="username">
                        <i class="fas fa-user"></i> Username or Email
                    </label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           class="form-control" 
                           value="<?= e($username) ?>" 
                           placeholder="Enter your username or email"
                           required
                           autofocus>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-control" 
                           placeholder="Enter your password"
                           required>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>

            <div class="login-links">
                <p>Don't have an account? <a href="../index.php">Go to homepage</a></p>
            </div>

            <!-- Demo accounts for testing -->
            <div class="demo-accounts">
                <h3><i class="fas fa-vial"></i> Demo Accounts</h3>
                
                <div class="demo-account">
                    <div>
                        <strong>Admin User</strong>
                        <div class="role">Full access</div>
                    </div>
                    <div class="credentials">
                        admin / admin123
                    </div>
                </div>
                
                <div class="demo-account">
                    <div>
                        <strong>Regular User</strong>
                        <div class="role">Basic access</div>
                    </div>
                    <div class="credentials">
                        user1 / user123
                    </div>
                </div>
                
                <div style="margin-top: 1rem; font-size: 0.875rem; color: var(--gray); text-align: center;">
                    <i class="fas fa-info-circle"></i> These are sample accounts for testing
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('loginForm');
            const usernameInput = document.getElementById('username');
            const passwordInput = document.getElementById('password');
            
            // Simple form validation
            loginForm.addEventListener('submit', function(e) {
                let valid = true;
                
                // Clear previous errors
                document.querySelectorAll('.error').forEach(el => el.remove());
                
                // Validate username
                if (!usernameInput.value.trim()) {
                    showError(usernameInput, 'Username is required');
                    valid = false;
                }
                
                // Validate password
                if (!passwordInput.value) {
                    showError(passwordInput, 'Password is required');
                    valid = false;
                }
                
                if (!valid) {
                    e.preventDefault();
                }
            });
            
            function showError(input, message) {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-message';
                errorDiv.style.marginTop = '0.5rem';
                errorDiv.style.marginBottom = '0';
                errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
                
                input.parentNode.appendChild(errorDiv);
                input.focus();
            }
            
            // Clear error when user starts typing
            [usernameInput, passwordInput].forEach(input => {
                input.addEventListener('input', function() {
                    const error = this.parentNode.querySelector('.error-message');
                    if (error) {
                        error.remove();
                    }
                });
            });
            
            // Auto-fill demo accounts for easier testing
            const urlParams = new URLSearchParams(window.location.search);
            const demo = urlParams.get('demo');
            
            if (demo === 'admin') {
                usernameInput.value = 'admin';
                passwordInput.value = 'admin123';
            } else if (demo === 'user') {
                usernameInput.value = 'user1';
                passwordInput.value = 'user123';
            }
        });
    </script>
</body>
</html>