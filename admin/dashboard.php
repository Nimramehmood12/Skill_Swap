<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('../config/db.php');

if(!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if($_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$msg = "";
$error = "";

// Handle Delete User
if(isset($_GET['delete_user'])) {
    $user_id = (int)$_GET['delete_user'];
    $delete = mysqli_query($conn, "DELETE FROM users WHERE user_id=$user_id");
    if($delete) {
        $msg = "User deleted successfully!";
    } else {
        $error = "Error deleting user.";
    }
}

// Handle Role Change
if(isset($_POST['change_role'])) {
    $user_id = (int)$_POST['user_id'];
    $new_role = mysqli_real_escape_string($conn, $_POST['new_role']);
    $update = mysqli_query($conn, "UPDATE users SET role='$new_role' WHERE user_id=$user_id");
    if($update) {
        $msg = "User role updated successfully!";
    } else {
        $error = "Error updating user role.";
    }
}

// Get statistics
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users"))['count'];
$total_skills = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM skills"))['count'];
$approved_skills = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM skills WHERE status='approved'"))['count'];
$pending_skills = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM skills WHERE status='pending'"))['count'];
$total_sessions = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM sessions"))['count'];
$active_sessions = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM sessions WHERE status='active'"))['count'];

// Get all users
$users = mysqli_query($conn, "SELECT * FROM users ORDER BY user_id DESC");

include('../includes/header.php');
?>

<div class="container-fluid py-5 mt-5">
    <h2 class="mb-4 fw-bold">
        <i class="fas fa-lock"></i> Admin Dashboard
    </h2>

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

    <!-- Statistics Row -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm p-3">
                <h6 class="text-muted mb-2"><i class="fas fa-users"></i> Total Users</h6>
                <h2 class="fw-bold text-primary"><?php echo $total_users; ?></h2>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm p-3">
                <h6 class="text-muted mb-2"><i class="fas fa-star"></i> Total Skills</h6>
                <h2 class="fw-bold text-warning"><?php echo $total_skills; ?></h2>
                <small class="text-muted"><?php echo $approved_skills; ?> approved, <?php echo $pending_skills; ?> pending</small>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm p-3">
                <h6 class="text-muted mb-2"><i class="fas fa-handshake"></i> Total Sessions</h6>
                <h2 class="fw-bold text-success"><?php echo $total_sessions; ?></h2>
                <small class="text-muted"><?php echo $active_sessions; ?> active</small>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm p-3">
                <h6 class="text-muted mb-2"><i class="fas fa-cogs"></i> Management</h6>
                <a href="/projecttrial3/moderator/approve_skills.php" class="btn btn-sm btn-outline-primary w-100">Moderate Skills</a>
            </div>
        </div>
    </div>

    <!-- Users Management -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light p-3">
            <h5 class="mb-0">
                <i class="fas fa-users-cog"></i> User Management
            </h5>
        </div>
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Points</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if($users && mysqli_num_rows($users) > 0) {
                    while($user = mysqli_fetch_assoc($users)): 
                        $userId = $user['user_id'] ?? ($user['id'] ?? null);
                        if($userId === null) continue;
                    ?>
                        <tr>
                            <td><?php echo $userId; ?></td>
                            <td><strong><?php echo htmlspecialchars($user['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="user_id" value="<?php echo $userId; ?>">
                                    <select name="new_role" class="form-select form-select-sm d-inline w-auto" onchange="this.form.submit();">
                                        <option value="user" <?php echo ($user['role'] == 'user') ? 'selected' : ''; ?>>User</option>
                                        <option value="moderator" <?php echo ($user['role'] == 'moderator') ? 'selected' : ''; ?>>Moderator</option>
                                        <option value="admin" <?php echo ($user['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                    <button type="submit" name="change_role" class="d-none"></button>
                                </form>
                            </td>
                            <td><?php echo $user['points']; ?></td>
                            <td>
                                <a href="dashboard.php?delete_user=<?php echo $userId; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure? This will delete the user and all their data!');">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                    <?php endwhile;
                } else {
                    echo '<tr><td colspan="6" class="text-center text-muted py-4">No users found</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php include('../includes/footer.php'); ?>
