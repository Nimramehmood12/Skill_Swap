<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('../config/db.php');

if(!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$uid = $_SESSION['user_id'];
$msg = "";
$error = "";

if(isset($_POST['update'])){
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $bio = mysqli_real_escape_string($conn, $_POST['bio']);
    $update = mysqli_query($conn, "UPDATE users SET name='$name', bio='$bio' WHERE user_id=$uid");
    if($update) {
        $_SESSION['user_name'] = $name;
        $msg = "Profile updated successfully!";
    } else {
        $error = "Error updating profile. Please try again.";
    }
}

$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE user_id=$uid"));
include('../includes/header.php');
?>
<div class="container py-5 mt-5">
    <div class="col-md-6 mx-auto card shadow-sm border-0 p-4 rounded-4">
        <h3 class="fw-bold mb-4">
            <i class="fas fa-user-edit"></i> Edit Profile
        </h3>
        
        <?php if(!empty($msg)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if(!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-bold">Full Name</label>
                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label fw-bold">Bio / About Me</label>
                <textarea name="bio" class="form-control" rows="4" placeholder="Tell others about yourself..."><?php echo htmlspecialchars($user['bio']); ?></textarea>
            </div>
            
            <div class="mb-3">
                <label class="form-label fw-bold">Email</label>
                <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                <small class="text-muted">Email cannot be changed</small>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Points</label>
                <input type="text" class="form-control" value="<?php echo $user['points']; ?>" disabled>
                <small class="text-muted">Earn points by completing swaps</small>
            </div>
            
            <button type="submit" name="update" class="btn btn-primary w-100 fw-bold py-2">
                <i class="fas fa-save"></i> Save Changes
            </button>
        </form>

        <hr class="my-4">
        <div class="text-center">
            <a href="../user/dashboard.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>