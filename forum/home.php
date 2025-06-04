<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


require_once 'includes/header.php';

// --- BEGIN FETCHING FORUM DATA --- 
// IMPORTANT: Ensure $db (database connection) is available from your config.php

// Placeholder for Featured Courses & Latest Tutorials (as per current focus)
if (!isset($featured_posts)) {
    $featured_posts = []; 
    // Example: 
    // $featured_posts = [
    //     ['id' => 1, 'title' => 'Introduction to Laravel', 'category' => 'PHP Frameworks', 'author_name' => 'Admin', 'author_avatar' => 'default.jpg', 'content' => 'Learn the basics of Laravel framework for modern PHP development.' ],
    // ];
}
if (!isset($latest_tutorials)) {
    $latest_tutorials = [];
    // Example:
    // $latest_tutorials = [
    //     ['id' => 1, 'title' => 'Getting Started with Docker', 'category' => 'DevOps', 'author_name' => 'ContainerGuy', 'author_avatar' => 'default.jpg', 'description' => 'A beginner-friendly tutorial on Docker fundamentals.', 'views' => 1200, 'likes' => 150],
    // ];
}

$hot_topics = [];
$categories = [];
$total_users = 0;
$total_posts = 0;
$conn = null; // Initialize $conn to null

try {
    $conn = getDbConnection(); // Get mysqli connection

    // Fetch Hot Topics
    // Fetch Hot Topics (Latest Discussions) from 'posts' table
    // User info from 'users' table
    // Replies count from 'comments' table
    $sql_hot_topics = 
        "SELECT p.post_id, p.title, p.content, p.created_at as topic_created_at, u.username AS author_name, u.profile_image AS author_avatar, " .
        "(SELECT COUNT(c.comment_id) FROM comments c WHERE c.post_id = p.post_id) AS replies " .
        "FROM posts p " .
        "JOIN users u ON p.user_id = u.user_id " .
        "ORDER BY p.created_at DESC LIMIT 3"; // Fetch latest 3 discussions
    $result_hot_topics = $conn->query($sql_hot_topics);
    if ($result_hot_topics) {
        while ($row = $result_hot_topics->fetch_assoc()) {
            $hot_topics[] = [
                'id' => $row['post_id'], // Changed from id to post_id
                'title' => htmlspecialchars($row['title']),
                'author_name' => htmlspecialchars($row['author_name']),
                // Assuming UPLOAD_URL and SITE_URL are correctly defined for images
                // The profile_image column in users table defaults to 'default.jpg'
                'author_avatar' => (!empty($row['author_avatar']) && $row['author_avatar'] !== 'default.jpg') ? UPLOAD_URL . htmlspecialchars($row['author_avatar']) : 'assets/img/default.jpg', // Path disesuaikan
                'replies' => $row['replies'],
                // 'views' => $row['views'], // 'views' column is not available in 'posts' table from schema
                'last_reply_time' => date('M d, Y', strtotime($row['topic_created_at'])),
                'excerpt' => substr(strip_tags((string)$row['content']), 0, 100) . '...'
            ];
        }
        $result_hot_topics->free();
    }

    // Fetch Topic Categories from 'topic_spaces' table
    // Count posts from 'posts' table related to each topic_space
    $sql_categories = 
        "SELECT ts.topic_id, ts.topic_name, COUNT(p.post_id) as post_count " .
        "FROM topic_spaces ts " .
        "LEFT JOIN posts p ON ts.topic_id = p.topic_id " .
        "GROUP BY ts.topic_id, ts.topic_name ORDER BY post_count DESC";
    $result_categories = $conn->query($sql_categories);
    if ($result_categories) {
        while ($row = $result_categories->fetch_assoc()) {
            $categories[] = [
                'id' => $row['topic_id'], // Changed from id to topic_id
                'name' => htmlspecialchars($row['topic_name']), // Changed from name to topic_name
                'post_count' => $row['post_count']
            ];
        }
        $result_categories->free();
    }

    // Fetch Community Stats
    $result_total_users = $conn->query("SELECT COUNT(*) as count FROM users");
    if ($result_total_users) {
        $row = $result_total_users->fetch_assoc();
        $total_users = (int)$row['count'];
        $result_total_users->free();
    }

    // For Community Stats: 'total_posts' currently counts the number of main topics.
    // If it should count all posts including replies, change the query to: SELECT COUNT(*) as count FROM posts
    // For Community Stats: 'total_posts' counts the number of main discussions from 'posts' table.
    $result_total_posts = $conn->query("SELECT COUNT(*) as count FROM posts"); 
    if ($result_total_posts) {
        $row = $result_total_posts->fetch_assoc();
        $total_posts = (int)$row['count'];
        $result_total_posts->free();
    }

} catch (Exception $e) { // Catch generic Exception for mysqli errors if any
    error_log('Database or other error on home.php: ' . $e->getMessage());
} finally {
    if ($conn) {
        closeDbConnection($conn);
    }
}

?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informatika Hub - Learn, Share, and Connect</title>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Favicon -->
    <link rel="shortcut icon" type="image/png" href="assets/images/favicon.png">
</head>
<body>
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-overlay">
            <div class="container">
                <div class="hero-content">
                    <h1 class="display-4 fw-bold">Welcome to Informatika Hub</h1>
                    <p class="lead mb-4">Learn, Share, and Connect with the Community</p>
                    <div class="hero-buttons">
                        <a href="<?php echo SITE_URL; ?>/Tutorial/course.php" class="btn btn-primary btn-lg me-3">
                            <i class="fas fa-book me-2"></i>Start Learning
                        </a>
                        <a href="index.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-comments me-2"></i>Join Discussion
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Courses Section -->
    <section id="featured-courses" class="py-5">
        <div class="container">
            <div class="section-header text-center mb-5">
                <h2 class="display-5 fw-bold">Featured Courses</h2>
                <p class="lead">Explore our latest and most popular courses</p>
            </div>
            
            <div class="row g-4">
                <?php foreach ($featured_posts as $post): ?>
                    <div class="col-md-4">
                        <div class="card course-card h-100">
                            <img src="<?php echo SITE_URL; ?>/assets/images/default.jpg" 
                                 class="card-img-top" alt="<?php echo $post['title']; ?>">
                            <div class="card-body">
                                <div class="course-category mb-2">
                                    <span class="badge bg-primary">
                                        <?php echo $post['category']; ?>
                                    </span>
                                </div>
                                <h5 class="card-title"><?php echo $post['title']; ?></h5>
                                <p class="card-text">
                                    <?php echo substr(strip_tags($post['content']), 0, 100) . '...'; ?>
                                </p>
                                <div class="course-meta d-flex justify-content-between align-items-center">
                                    <div class="author">
                                        <img src="<?php echo SITE_URL; ?>/assets/images/explore/person.png" 
                                             class="avatar-sm" alt="Author">
                                        <span><?php echo $post['author_name']; ?></span>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress" style="width: 100%">
                                            <span>100%</span>
                                        </div>
                                    </div>
                                </div>
                                <a href="<?php echo SITE_URL; ?>/Tutorial/course.php?id=<?php echo $post['id']; ?>" 
                                   class="stretched-link"></a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Hot Topics Section -->
    <section id="hot-topics" class="py-5 bg-light">
        <div class="container">
            <div class="section-header text-center mb-5">
                <h2 class="display-5 fw-bold">Hot Topics</h2>
                <p class="lead">Join the most active discussions</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h4 class="mb-0">Latest Discussions</h4>
                                <a href="index.php" class="btn btn-outline-primary btn-sm">
                                    View All
                                </a>
                            </div>
                            
                            <div class="discussion-list">
                                <?php foreach ($hot_topics as $topic): ?>
                                    <div class="discussion-item d-flex align-items-start mb-4">
                                        <div class="discussion-avatar me-3">
                                            <img src="<?php echo htmlspecialchars($topic['author_avatar']); ?>" 
                                                 class="avatar-md" alt="<?php echo htmlspecialchars($topic['author_name']); ?>">
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="mb-1">
                                                <a href="<?php echo SITE_URL; ?>/Forum/post.php?id=<?php echo $topic['post_id']; ?>" 
                                                   class="text-decoration-none">
                                                    <?php echo $topic['title']; ?>
                                                </a>
                                            </h5>
                                            <div class="discussion-meta">
                                             <span class="badge bg-secondary me-2">
                                                 <i class="fas fa-comments me-1"></i>
                                                 <?php echo htmlspecialchars($topic['replies']); ?> Replies
                                             </span>
                                             <!-- Views are not displayed as data is unavailable -->
                                         </div>
                                            <p class="mt-2 mb-0">
                                                <?php echo htmlspecialchars($topic['excerpt']); ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="mb-4">Topic Categories</h4>
                            <div class="topic-categories">
                                <?php foreach ($categories as $category): ?>
                                    <a href="<?php echo SITE_URL; ?>/pages/topic.php?id=<?php echo $category['id']; ?>" 
                                       class="category-item d-flex align-items-center mb-3">
                                        <div class="category-icon me-2">
                                            <i class="fas fa-<?php echo getIconForCategory($category['name']); ?>"></i>
                                        </div>
                                        <span><?php echo $category['name']; ?></span>
                                        <span class="badge bg-primary ms-auto">
                                            <?php echo $category['post_count']; ?> posts
                                        </span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Latest Tutorials Section -->
    <section id="latest-tutorials" class="py-5">
        <div class="container">
            <div class="section-header text-center mb-5">
                <h2 class="display-5 fw-bold">Latest Tutorials</h2>
                <p class="lead">Fresh tutorials from our expert instructors</p>
            </div>
            
            <div class="row g-4">
                <?php foreach ($latest_tutorials as $tutorial): ?>
                    <div class="col-md-4">
                        <div class="card tutorial-card h-100">
                            <div class="tutorial-thumbnail">
                                <img src="<?php echo SITE_URL; ?>/assets/images/default.jpg" 
                                      class="card-img-top" alt="<?php echo $tutorial['title']; ?>">
                                <div class="tutorial-badge">
                                    <span class="badge bg-success">
                                        <?php echo $tutorial['difficulty'] ?? 'New'; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $tutorial['title']; ?></h5>
                                <p class="card-text">
                                    <?php echo $tutorial['content'] ? substr(strip_tags($tutorial['content']), 0, 100) . '...' : 'No description available'; ?>
                                </p>
                                <div class="tutorial-meta d-flex justify-content-between align-items-center">
                                    <div class="author">
                                        <img src="<?php echo SITE_URL; ?>/assets/images/explore/person.png" 
                                             class="avatar-sm" alt="Author">
                                        <span><?php echo $tutorial['author_name']; ?></span>
                                    </div>
                                    <div class="tutorial-stats">
                                        <span class="me-3">
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo $tutorial['duration']; ?>
                                        </span>
                                        <span>
                                            <i class="fas fa-star me-1"></i>
                                            <?php echo $tutorial['rating']; ?>
                                        </span>
                                    </div>
                                </div>
                                <a href="<?php echo SITE_URL; ?>/Tutorial/course.php?id=<?php echo $tutorial['id']; ?>" 
                                   class="stretched-link"></a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Community Stats Section -->
    <section id="community-stats" class="py-5 community-stats-section bg-light">
        <div class="container">
            <div class="section-header text-center mb-5">
                <h2 class="display-5 fw-bold">Community Stats</h2>
                <p class="lead">See how our community is growing</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="stat-item">
                        <div class="stat-icon bg-primary">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="mb-0"><?php echo formatNumber($total_users); ?></h3>
                            <span class="text-muted">Members</span>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="stat-item">
                        <div class="stat-icon bg-success">
                            <i class="fas fa-comments"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="mb-0"><?php echo formatNumber($total_posts); ?></h3>
                            <span class="text-muted">Posts</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php require_once 'includes/footer.php'; ?>
</body>
</html>
