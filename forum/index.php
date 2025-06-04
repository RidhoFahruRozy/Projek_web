<?php
/**
 * Home page - displays posts from followed topics
 */
require_once 'config/config.php';
require_once 'includes/user.php';
require_once 'includes/topic.php';
require_once 'includes/post.php';

// Get posts from followed topics if user is logged in
// Otherwise, get latest posts from all topics
$posts = [];
$pageTitle = 'Latest Posts';

if (isLoggedIn()) {
    $posts = getPostsFromFollowedTopics($_SESSION['user_id'], 10, 0);
    $pageTitle = 'Your Feed';
    
    // If user doesn't follow any topics yet, get latest posts
    if (empty($posts)) {
        $sql = "SELECT p.*, u.username, u.profile_image, t.topic_name,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.post_id) as comments_count
                FROM posts p
                JOIN users u ON p.user_id = u.user_id
                JOIN topic_spaces t ON p.topic_id = t.topic_id
                ORDER BY p.created_at DESC
                LIMIT 10";
        $result = executeQuery($sql);
        
        while ($row = $result->fetch_assoc()) {
            $posts[] = $row;
        }
        
        $pageTitle = 'Latest Posts';
    }
} else {
    $sql = "SELECT p.*, u.username, u.profile_image, t.topic_name,
            (SELECT COUNT(*) FROM comments WHERE post_id = p.post_id) as comments_count
            FROM posts p
            JOIN users u ON p.user_id = u.user_id
            JOIN topic_spaces t ON p.topic_id = t.topic_id
            ORDER BY p.created_at DESC
            LIMIT 10";
    $result = executeQuery($sql);
    
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }
}

// Get popular topics
$popularTopics = [];
$sql = "SELECT t.*, COUNT(f.follow_id) as followers_count
        FROM topic_spaces t
        LEFT JOIN user_topic_follows f ON t.topic_id = f.topic_id
        GROUP BY t.topic_id
        ORDER BY followers_count DESC
        LIMIT 5";
$result = executeQuery($sql);

while ($row = $result->fetch_assoc()) {
    $popularTopics[] = $row;
}

// Include header
include 'includes/header.php';
?>

<div class="row">
    <!-- Main content -->
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><?php echo $pageTitle; ?></h2>
            <?php if (isLoggedIn()): ?>
                <a href="<?php echo SITE_URL; ?>/pages/create_post.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle me-1"></i> Create Post
                </a>
            <?php endif; ?>
        </div>
        
        <?php if (empty($posts)): ?>
            <div class="alert alert-info">
                <p>No posts found. <?php echo isLoggedIn() ? 'Follow some topics to see posts here.' : 'Please login to see personalized content.'; ?></p>
            </div>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <div class="card post-card">
                    <div class="card-header bg-white">
                        <div class="post-header">
                            <img src="<?php if (empty($post['profile_image'])) {echo 'assets/img/default.jpg'; } else { echo UPLOAD_URL . $post['profile_image']; } ?>" class="post-avatar">
                            <div>
                                <h5 class="mb-0"><?php echo $post['title']; ?></h5>
                                <div class="post-meta">
                                    <span>Posted by <a href="<?php echo SITE_URL; ?>/pages/profile.php?id=<?php echo $post['user_id']; ?>"><?php echo $post['username']; ?></a></span>
                                    <span class="mx-1">•</span>
                                    <span>in <a href="<?php echo SITE_URL; ?>/pages/topic.php?id=<?php echo $post['topic_id']; ?>"><?php echo $post['topic_name']; ?></a></span>
                                    <span class="mx-1">•</span>
                                    <span><?php echo date('M d, Y', strtotime($post['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="post-content">
                            <?php 
                            // Limit content preview to 200 characters
                            echo nl2br(substr($post['content'], 0, 200));
                            if (strlen($post['content']) > 200) {
                                echo '... <a href="' . SITE_URL . '/pages/post.php?id=' . $post['post_id'] . '">Read more</a>';
                            }
                            ?>
                            
                            <?php if ($post['media_url'] && $post['media_type'] === 'image'): ?>
                                <div class="mt-3">
                                    <img src="<?php echo UPLOAD_URL . $post['media_url']; ?>" alt="Post image" class="post-image">
                                </div>
                            <?php elseif ($post['media_url'] && $post['media_type'] === 'video'): ?>
                                <div class="mt-3">
                                    <video src="<?php echo UPLOAD_URL . $post['media_url']; ?>" controls class="post-video"></video>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="post-actions">
                            <div class="vote-buttons">
                                <?php 
                                $userVote = '';
                                if (isLoggedIn()) {
                                    $sql = "SELECT vote_type FROM votes 
                                            WHERE user_id = ? AND content_type = 'post' AND content_id = ?";
                                    $result = executePreparedStatement($sql, "ii", [$_SESSION['user_id'], $post['post_id']]);
                                    if ($result->num_rows > 0) {
                                        $userVote = $result->fetch_assoc()['vote_type'];
                                    }
                                }
                                ?>
                                <button class="btn btn-sm <?php echo $userVote === 'upvote' ? 'btn-success' : 'btn-outline-success'; ?> post-vote-btn" 
                                        id="post-<?php echo $post['post_id']; ?>-upvote"
                                        data-post-id="<?php echo $post['post_id']; ?>" 
                                        data-vote-type="upvote" 
                                        <?php echo !isLoggedIn() ? 'disabled' : ''; ?>>
                                    <i class="fas fa-arrow-up"></i>
                                </button>
                                
                                <span class="vote-count" id="post-<?php echo $post['post_id']; ?>-votes">
                                    <?php echo $post['upvotes'] - $post['downvotes']; ?>
                                </span>
                                
                                <button class="btn btn-sm <?php echo $userVote === 'downvote' ? 'btn-danger' : 'btn-outline-danger'; ?> post-vote-btn" 
                                        id="post-<?php echo $post['post_id']; ?>-downvote"
                                        data-post-id="<?php echo $post['post_id']; ?>" 
                                        data-vote-type="downvote" 
                                        <?php echo !isLoggedIn() ? 'disabled' : ''; ?>>
                                    <i class="fas fa-arrow-down"></i>
                                </button>
                            </div>
                            
                            <a href="<?php echo SITE_URL; ?>/pages/post.php?id=<?php echo $post['post_id']; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-comment me-1"></i> <?php echo $post['comments_count']; ?> Comments
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- About section -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">About <?php echo SITE_NAME; ?></h5>
            </div>
            <div class="card-body">
                <p>Welcome to our learning forum! This is a place to discuss, share knowledge, and ask questions about various learning topics.</p>
                <?php if (!isLoggedIn()): ?>
                    <div class="d-grid gap-2">
                        <a href="<?php echo SITE_URL; ?>/pages/login.php" class="btn btn-primary">Login</a>
                        <a href="<?php echo SITE_URL; ?>/pages/register.php" class="btn btn-outline-primary">Register</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Popular topics -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Popular Topics</h5>
            </div>
            <div class="card-body">
                <?php if (empty($popularTopics)): ?>
                    <p>No topics found.</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($popularTopics as $topic): ?>
                            <a href="<?php echo SITE_URL; ?>/pages/topic.php?id=<?php echo $topic['topic_id']; ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <?php echo $topic['topic_name']; ?>
                                <span class="badge bg-primary rounded-pill"><?php echo $topic['followers_count']; ?> followers</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-3">
                        <a href="<?php echo SITE_URL; ?>/pages/search.php" class="btn btn-outline-primary btn-sm w-100">View All Topics</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>
