<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('../config/db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SESSION['role'] != 'moderator' && $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$msg = "";
$error = "";

// Handle Approval/Rejection
if (isset($_GET['action']) && isset($_GET['id'])) {
    $user_skill_id = (int)$_GET['id'];
    if ($_GET['action'] == 'approve') {
        $update = mysqli_query($conn, "UPDATE user_skills SET status='approved' WHERE user_skill_id=$user_skill_id");
        if ($update) {
            $msg = "Skill approved successfully!";
        } else {
            $error = "Error approving skill.";
        }
    } elseif ($_GET['action'] == 'reject') {
        $delete = mysqli_query($conn, "DELETE FROM user_skills WHERE user_skill_id=$user_skill_id");
        if ($delete) {
            $msg = "Skill rejected and removed.";
        } else {
            $error = "Error rejecting skill.";
        }
    }
}

$pending = mysqli_query($conn, "SELECT us.*, u.name, s.skill_name FROM user_skills us JOIN users u ON us.user_id=u.user_id JOIN skills s ON us.skill_id=s.skill_id WHERE us.status='pending' ORDER BY us.user_skill_id DESC");

// Get approved skills count
$approved = mysqli_query($conn, "SELECT COUNT(*) as count FROM skills WHERE status='approved'");
$approved_count = mysqli_fetch_assoc($approved)['count'];

// Get total users count
$users = mysqli_query($conn, "SELECT COUNT(*) as count FROM users");
$users_count = mysqli_fetch_assoc($users)['count'];

include('../includes/header.php');
?>
<div class="container py-5 mt-5">
    <h2 class="mb-4 fw-bold">
        <i class="fas fa-tasks"></i> Skill Moderation Queue
    </h2>

    <?php if (!empty($msg)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm p-3">
                <h6 class="text-muted">Total Users</h6>
                <h2 class="fw-bold text-primary"><?php echo $users_count; ?></h2>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm p-3">
                <h6 class="text-muted">Approved Skills</h6>
                <h2 class="fw-bold text-success"><?php echo $approved_count; ?></h2>
            </div>
        </div>
    </div>

    <!-- Pending Skills Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light p-3">
            <h5 class="mb-0">
                <i class="fas fa-hourglass-half"></i> Pending Skills for Review
                <?php
                if ($pending && mysqli_num_rows($pending) > 0) {
                    echo '<span class="badge bg-warning ms-2">' . mysqli_num_rows($pending) . '</span>';
                }
                ?>
            </h5>
        </div>
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>User</th>
                    <th>Skill</th>
                    <th>Type</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($pending && mysqli_num_rows($pending) > 0) {
                    while ($row = mysqli_fetch_assoc($pending)): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['skill_name']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo ($row['type'] == 'teach') ? 'primary' : 'info'; ?>">
                                    <?php echo ucfirst($row['type']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="approve_skills.php?action=approve&id=<?php echo $row['user_skill_id']; ?>" class="btn btn-success btn-sm" title="Approve this skill">
                                    <i class="fas fa-check"></i> Approve
                                </a>
                                <a href="approve_skills.php?action=reject&id=<?php echo $row['user_skill_id']; ?>" class="btn btn-danger btn-sm" title="Reject this skill" onclick="return confirm('Are you sure?');">
                                    <i class="fas fa-times"></i> Reject
                                </a>
                            </td>
                        </tr>
                <?php endwhile;
                } else {
                    echo '<tr><td colspan="4" class="text-center text-muted py-4">No pending skills to review!</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
</div>