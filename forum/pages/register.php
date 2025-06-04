<?php
/**
 * Register page
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/forum/config/config.php';
require_once '../includes/user.php';

// Check if user is already logged in
if (isLoggedIn()) {
    redirect('../index.php');
}

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Validate password match
    if ($password !== $confirmPassword) {
        addError("Passwords do not match");
    } else {
        if (registerUser($username, $email, $password)) {
            redirect('login.php');
        }
    }
}

// Include header
include '../includes/header.php';
?>

<div class="row">
    <div class="col-md-6 mx-auto">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-user-plus me-2"></i> Register</h4>
            </div>
            <div class="card-body">
                <form action="<?php echo SITE_URL; ?>/pages/register.php" method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                        <div class="form-text">Choose a unique username (3-20 characters)</div>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="form-text">Password must be at least 8 characters</div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus me-1"></i> Register
                        </button>
                        <p class="text-center mt-3">
                            Already have an account? <a href="<?php echo SITE_URL; ?>/pages/login.php">Login</a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include '../includes/footer.php';
?>
