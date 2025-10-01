<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MovieStream - Modern Streaming Platform</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3a0ca3;
            --accent: #f72585;
            --success: #4cc9f0;
            --warning: #f9c74f;
            --danger: #f94144;
            --dark: #212529;
            --light: #f8f9fa;
            --gray: #6c757d;
            --gray-light: #ced4da;
            --border-radius: 12px;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7ff;
            color: var(--dark);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header Styles */
        header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 1.5rem 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.8rem;
            font-weight: 700;
        }

        .logo i {
            font-size: 2.2rem;
        }

        nav ul {
            display: flex;
            list-style: none;
            gap: 1.5rem;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 30px;
            transition: var(--transition);
        }

        nav a:hover, nav a.active {
            background: rgba(255, 255, 255, 0.15);
        }

        /* Main Content */
        main {
            padding: 2rem 0;
        }

        .page-header {
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title {
            font-size: 2rem;
            color: var(--dark);
            font-weight: 700;
        }

        /* Card Styles */
        .card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: var(--transition);
        }

        .card:hover {
            box-shadow: 0 6px 24px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.2rem;
            padding-bottom: 0.8rem;
            border-bottom: 1px solid var(--gray-light);
        }

        .card-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--dark);
        }

        /* Grid Layout */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        /* KPI Cards */
        .kpi-card {
            text-align: center;
            padding: 1.5rem;
        }

        .kpi-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .kpi-label {
            color: var(--gray);
            font-weight: 500;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 1.2rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark);
        }

        .form-control {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid var(--gray-light);
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
        }

        .form-select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%236c757d' viewBox='0 0 16 16'%3E%3Cpath d='M8 12L2 6h12L8 12z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 16px;
            appearance: none;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
        }

        /* Button Styles */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.8rem 1.5rem;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
        }

        .btn:hover {
            background-color: var(--secondary);
            transform: translateY(-2px);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }

        .btn-secondary {
            background-color: var(--gray);
        }

        .btn-success {
            background-color: var(--success);
        }

        .btn-danger {
            background-color: var(--danger);
        }

        /* Table Styles */
        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--gray-light);
        }

        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: var(--dark);
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        /* Rating Stars */
        .rating-stars {
            display: flex;
            gap: 0.2rem;
            color: var(--warning);
        }

        /* Footer */
        footer {
            background-color: var(--dark);
            color: white;
            padding: 2rem 0;
            margin-top: 3rem;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }
            
            nav ul {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .grid {
                grid-template-columns: 1fr;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .footer-content {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
        }

        /* Notification */
        .notification {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .notification-success {
            background-color: rgba(76, 201, 240, 0.15);
            color: var(--success);
            border-left: 4px solid var(--success);
        }

        .notification-error {
            background-color: rgba(249, 65, 68, 0.15);
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }

        /* Search & Filter */
        .search-filter {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 0.35rem 0.65rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-primary {
            background-color: rgba(67, 97, 238, 0.15);
            color: var(--primary);
        }

        .badge-success {
            background-color: rgba(76, 201, 240, 0.15);
            color: var(--success);
        }

        /* Title Card */
        .title-card {
            display: flex;
            gap: 1.5rem;
            padding: 1.5rem;
        }

        .title-poster {
            width: 150px;
            height: 225px;
            border-radius: var(--border-radius);
            object-fit: cover;
            box-shadow: var(--shadow);
        }

        .title-info {
            flex: 1;
        }

        .title-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .title-name {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .title-meta {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            color: var(--gray);
        }

        .title-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <i class="fas fa-play-circle"></i>
                    <span>MovieStream</span>
                </div>
                <nav>
                    <ul>
                        <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                        <li><a href="titles.php"><i class="fas fa-film"></i> Browse</a></li>
                        <li><a href="add_rating.php" class="active"><i class="fas fa-star"></i> Rate</a></li>
                        <li><a href="add_watch.php"><i class="fas fa-history"></i> Log Watch</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <main class="container">
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

            <form method="post">
                <div class="form-group">
                    <label class="form-label">Title</label>
                    <select name="title_id" class="form-control form-select" required>
                        <option value="">-- Select a Title --</option>
                        <option value="1">The Dark Knight</option>
                        <option value="2">Breaking Bad</option>
                        <option value="3">Inception</option>
                        <option value="4">Stranger Things</option>
                        <option value="5">The Shawshank Redemption</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">User ID</label>
                    <input type="number" name="user_id" min="1" step="1" class="form-control" required 
                           placeholder="Enter user ID (1-5)">
                    <small style="color: var(--gray); display: block; margin-top: 0.5rem;">
                        <i class="fas fa-info-circle"></i> User IDs 1–5 exist in the seed data
                    </small>
                </div>

                <div class="form-group">
                    <label class="form-label">Rating (0-10)</label>
                    <input type="range" name="rating" min="0" max="10" step="0.5" value="5" 
                           oninput="this.nextElementSibling.value = this.value" class="form-control">
                    <output>5</output>
                    <div class="rating-stars" style="margin-top: 0.5rem;">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="far fa-star"></i>
                        <i class="far fa-star"></i>
                        <i class="far fa-star"></i>
                        <i class="far fa-star"></i>
                        <i class="far fa-star"></i>
                    </div>
                </div>

                <button type="submit" class="btn">
                    <i class="fas fa-save"></i> Submit Rating
                </button>
            </form>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Recent Ratings</h2>
                <i class="fas fa-history" style="color: var(--primary);"></i>
            </div>
            
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>User</th>
                            <th>Rating</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>The Dark Knight</td>
                            <td>#3</td>
                            <td>
                                <div class="rating-stars">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star-half-alt"></i>
                                </div>
                                9.5
                            </td>
                            <td>2 hours ago</td>
                        </tr>
                        <tr>
                            <td>Breaking Bad</td>
                            <td>#1</td>
                            <td>
                                <div class="rating-stars">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                </div>
                                10.0
                            </td>
                            <td>Yesterday</td>
                        </tr>
                        <tr>
                            <td>Inception</td>
                            <td>#5</td>
                            <td>
                                <div class="rating-stars">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="far fa-star"></i>
                                </div>
                                9.0
                            </td>
                            <td>2 days ago</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <div class="footer-content">
                <p>MovieStream &copy; 2023 - Demo app for learning PHP + MySQL</p>
                <div>
                    <a href="#" style="color: white; margin-right: 1rem;"><i class="fab fa-github"></i></a>
                    <a href="#" style="color: white; margin-right: 1rem;"><i class="fab fa-twitter"></i></a>
                    <a href="#" style="color: white;"><i class="fab fa-facebook"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Interactive rating stars
        document.addEventListener('DOMContentLoaded', function() {
            const ratingSlider = document.querySelector('input[type="range"]');
            const ratingOutput = document.querySelector('output');
            const ratingStars = document.querySelectorAll('.rating-stars .fa-star, .rating-stars .fa-star-half-alt');
            
            ratingSlider.addEventListener('input', function() {
                const value = parseFloat(this.value);
                ratingOutput.textContent = value;
                
                // Update star display
                ratingStars.forEach((star, index) => {
                    if (index < Math.floor(value)) {
                        star.classList.add('fas');
                        star.classList.remove('far', 'fa-star-half-alt');
                    } else if (index < value) {
                        star.classList.remove('fas', 'far');
                        star.classList.add('fa-star-half-alt');
                    } else {
                        star.classList.remove('fas', 'fa-star-half-alt');
                        star.classList.add('far');
                    }
                });
            });
            
            // Trigger initial update
            ratingSlider.dispatchEvent(new Event('input'));
        });
    </script>
</body>
</html>