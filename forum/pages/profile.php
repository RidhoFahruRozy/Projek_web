<?php
/**
 * User profile page
 */
require_once '../config/config.php';
require_once '../includes/user.php';
require_once '../includes/topic.php';
require_once '../includes/post.php';

// Get user ID (current user or specified user)
$userId = isset($_GET['id']) ? (int)$_GET['id'] : (isLoggedIn() ? $_SESSION['user_id'] : null);

if (!$userId) {
    addError("User not found");
    redirect('../index.php');
}

// Get user details
$user = getUserById($userId);

if (!$user) {
    addError("User not found");
    redirect('../index.php');
}

// Check if this is the current user's profile
$isOwnProfile = isLoggedIn() && $userId == $_SESSION['user_id'];

// Handle profile update form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isOwnProfile) {
    $bio = sanitizeInput($_POST['bio']);
    
    // Check if profile image is uploaded
    $profileImage = null;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Upload profile image
        $file = $_FILES['profile_image'];
        
        // Check file type
        $fileType = $file['type'];
        if (!in_array($fileType, ALLOWED_IMAGE_TYPES)) {
            addError("Invalid file type. Only JPEG, PNG, and GIF are allowed.");
        } 
        // Check file size
        elseif ($file['size'] > MAX_FILE_SIZE) {
            addError("File is too large (max 5MB)");
        } 
        else {
            // Generate unique filename
            $fileName = uniqid() . '_' . basename($file['name']);
            $targetPath = UPLOAD_DIR . $fileName;
            
            // Move uploaded file to target directory
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $profileImage = $fileName;
            } else {
                addError("Failed to upload profile image");
            }
        }
    }
    
    // Update profile
    if (updateUserProfile($userId, $bio, $profileImage)) {
        // Refresh user data
        $user = getUserById($userId);
    }
}

// Get user's posts
$userPosts = [];
$sql = "SELECT p.*, t.topic_name,
        (SELECT COUNT(*) FROM comments WHERE post_id = p.post_id) as comments_count
        FROM posts p
        JOIN topic_spaces t ON p.topic_id = t.topic_id
        WHERE p.user_id = ?
        ORDER BY p.created_at DESC
        LIMIT 10";
$result = executePreparedStatement($sql, "i", [$userId]);

while ($row = $result->fetch_assoc()) {
    $userPosts[] = $row;
}

// Get user's followed topics
$followedTopics = getFollowedTopics($userId, 5, 0);

// Include header
include '../includes/header.php';
?>

<div class="row">
    <!-- Main content -->
    <div class="col-lg-8">
        <!-- Profile header -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row align-items-center">
                    <img src="<?php echo (!empty($user['profile_image']) ? UPLOAD_URL . $user['profile_image'] : '../assets/img/default.jpg'); ?>" class="profile-avatar">
                    <div class="profile-info">
                        <h2 class="mb-0"><?php echo $user['username']; ?></h2>
                        <p class="text-muted">
                            <?php echo $user['is_moderator'] ? '<span class="badge bg-primary me-2">Moderator</span>' : ''; ?>
                            Member since <?php echo date('M Y', strtotime($user['created_at'])); ?>
                        </p>
                        <p><?php echo !empty($user['bio']) ? $user['bio'] : 'No bio available.'; ?></p>
                        
                        <?php if ($isOwnProfile): ?>
                            <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                <i class="fas fa-edit me-1"></i> Edit Profile
                            </button>
                        <?php elseif (isLoggedIn() && isModerator()): ?>
                            <a href="<?php echo SITE_URL; ?>/pages/toggle_moderator.php?id=<?php echo $userId; ?>" 
                               class="btn btn-outline-<?php echo $user['is_moderator'] ? 'danger' : 'success'; ?> btn-sm"
                               onclick="return confirm('Are you sure you want to <?php echo $user['is_moderator'] ? 'remove moderator status from' : 'make'; ?> <?php echo $user['username']; ?> <?php echo $user['is_moderator'] ? '' : 'a moderator'; ?>?');">
                                <i class="fas fa-<?php echo $user['is_moderator'] ? 'user-minus' : 'user-shield'; ?> me-1"></i> 
                                <?php echo $user['is_moderator'] ? 'Remove Moderator Status' : 'Make Moderator'; ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- User's posts -->
        <h4 class="mb-3">Posts by <?php echo $user['username']; ?></h4>
        
        <?php if (empty($userPosts)): ?>
            <div class="alert alert-info">
                <p class="mb-0">No posts yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($userPosts as $post): ?>
                <div class="card post-card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><a href="<?php echo SITE_URL; ?>/pages/post.php?id=<?php echo $post['post_id']; ?>"><?php echo $post['title']; ?></a></h5>
                        <div class="post-meta">
                            <span>in <a href="<?php echo SITE_URL; ?>/pages/topic.php?id=<?php echo $post['topic_id']; ?>"><?php echo $post['topic_name']; ?></a></span>
                            <span class="mx-1">•</span>
                            <span><?php echo date('M d, Y', strtotime($post['created_at'])); ?></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="post-content">
                            <?php 
                            // Limit content preview to 150 characters
                            echo nl2br(substr($post['content'], 0, 150));
                            if (strlen($post['content']) > 150) {
                                echo '... <a href="' . SITE_URL . '/pages/post.php?id=' . $post['post_id'] . '">Read more</a>';
                            }
                            ?>
                        </div>
                        
                        <div class="post-actions">
                            <div>
                                <span class="text-muted me-3">
                                    <i class="fas fa-arrow-up me-1"></i> <?php echo $post['upvotes']; ?> upvotes
                                </span>
                                <span class="text-muted">
                                    <i class="fas fa-comment me-1"></i> <?php echo $post['comments_count']; ?> comments
                                </span>
                            </div>
                            
                            <a href="<?php echo SITE_URL; ?>/pages/post.php?id=<?php echo $post['post_id']; ?>" class="btn btn-sm btn-outline-primary">
                                View Post
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (count($userPosts) >= 10): ?>
                <div class="text-center mt-3">
                    <a href="<?php echo SITE_URL; ?>/pages/user_posts.php?id=<?php echo $userId; ?>" class="btn btn-outline-primary">
                        View All Posts
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Followed topics -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Topics <?php echo $user['username']; ?> Follows</h5>
            </div>
            <div class="card-body">
                <?php if (empty($followedTopics)): ?>
                    <p class="mb-0">Not following any topics yet.</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($followedTopics as $topic): ?>
                            <a href="<?php echo SITE_URL; ?>/pages/topic.php?id=<?php echo $topic['topic_id']; ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <?php echo $topic['topic_name']; ?>
                                <span class="badge bg-primary rounded-pill"><?php echo $topic['followers_count']; ?> followers</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if (count($followedTopics) >= 5): ?>
                        <div class="mt-3">
                            <a href="<?php echo SITE_URL; ?>/pages/user_topics.php?id=<?php echo $userId; ?>" class="btn btn-outline-primary btn-sm w-100">
                                View All Followed Topics
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- User stats -->
        <?php
        // Get user stats
        $sql = "SELECT 
                (SELECT COUNT(*) FROM posts WHERE user_id = ?) as posts_count,
                (SELECT COUNT(*) FROM comments WHERE user_id = ?) as comments_count,
                (SELECT COUNT(*) FROM user_topic_follows WHERE user_id = ?) as following_count";
        $result = executePreparedStatement($sql, "iii", [$userId, $userId, $userId]);
        $stats = $result->fetch_assoc();
        ?>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">User Stats</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-4">
                        <h4><?php echo $stats['posts_count']; ?></h4>
                        <p class="text-muted mb-0">Posts</p>
                    </div>
                    <div class="col-4">
                        <h4><?php echo $stats['comments_count']; ?></h4>
                        <p class="text-muted mb-0">Comments</p>
                    </div>
                    <div class="col-4">
                        <h4><?php echo $stats['following_count']; ?></h4>
                        <p class="text-muted mb-0">Following</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<?php if ($isOwnProfile): ?>
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editProfileModalLabel">Edit Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="<?php echo SITE_URL; ?>/pages/profile.php" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="profile_image" class="form-label">Profile Image</label>
                        <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/jpeg,image/png,image/gif">
                        <div class="form-text">Supported formats: JPEG, PNG, GIF (max 5MB)</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="bio" class="form-label">Bio</label>
                        <textarea class="form-control" id="bio" name="bio" rows="4"><?php echo $user['bio']; ?></textarea>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
// Include footer
include '../includes/footer.php';
?>
