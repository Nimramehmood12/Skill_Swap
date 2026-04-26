<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('../config/db.php');

// Redirect if NOT logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = "";
$error = "";

// Add new skill
if (isset($_POST['add_skill'])) {
    $skill_name = mysqli_real_escape_string($conn, $_POST['skill_name']);
    $skill_type = mysqli_real_escape_string($conn, $_POST['skill_type']);

    if (!empty($skill_name)) {
        // Get default category
        $cat = mysqli_fetch_assoc(mysqli_query($conn, "SELECT category_id FROM skill_categories LIMIT 1"));
        $category_id = $cat ? $cat['category_id'] : 1;

        // Check if user already has this skill type
        $check = mysqli_query($conn, "SELECT us.user_skill_id FROM user_skills us JOIN skills s ON us.skill_id = s.skill_id WHERE us.user_id=$user_id AND s.skill_name='$skill_name' AND us.type='$skill_type'");
        if (mysqli_num_rows($check) > 0) {
            $error = "You already have this skill listed!";
        } else {
            // Insert skill
            $skill_insert = mysqli_query($conn, "INSERT INTO skills (skill_name, category_id, status) VALUES ('$skill_name', $category_id, 'approved')");
            if ($skill_insert) {
                $skill_id = mysqli_insert_id($conn);
                // Insert user_skill
                $user_skill_insert = mysqli_query($conn, "INSERT INTO user_skills (user_id, skill_id, type, status) VALUES ($user_id, $skill_id, '$skill_type', 'approved')");
                if ($user_skill_insert) {
                    $msg = "Skill added successfully!";
                } else {
                    $error = "Error adding skill. Please try again.";
                }
            } else {
                $error = "Error adding skill. Please try again.";
            }
        }
    } else {
        $error = "Please enter a skill name!";
    }
}

// Delete skill
if (isset($_GET['delete'])) {
    $user_skill_id = intval($_GET['delete']);
    $delete = mysqli_query($conn, "DELETE FROM user_skills WHERE user_skill_id=$user_skill_id AND user_id=$user_id");
    if ($delete) {
        $msg = "Skill removed successfully!";
    }
}

// Get user's current skills
$skills = mysqli_query($conn, "SELECT us.user_skill_id, us.type, us.status, s.skill_name FROM user_skills us JOIN skills s ON us.skill_id = s.skill_id WHERE us.user_id=$user_id ORDER BY us.type DESC, us.user_skill_id DESC");

include('../includes/header.php');
?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow-sm border-0 p-4 rounded-4">
                <h2 class="fw-bold mb-4">Manage Your Skills</h2>

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

                <!-- Add New Skill Form -->
                <div class="card bg-light border-0 p-3 mb-4">
                    <h5 class="fw-bold mb-3">Add a New Skill</h5>
                    <form method="POST" class="row g-2">
                        <div class="col-md-6">
                            <input type="text" name="skill_name" class="form-control" placeholder="e.g. Photography, Spanish, Piano" required>
                        </div>
                        <div class="col-md-4">
                            <select name="skill_type" class="form-select" required>
                                <option value="">Select Type</option>
                                <option value="teach">I can teach this</option>
                                <option value="learn">I want to learn this</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" name="add_skill" class="btn btn-primary w-100">Add</button>
                        </div>
                    </form>
                </div>

                <!-- Current Skills -->
                <h5 class="fw-bold mb-3">Your Skills</h5>

                <?php if (mysqli_num_rows($skills) > 0): ?>
                    <div class="row">
                        <?php while ($skill = mysqli_fetch_assoc($skills)): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card h-100 p-3 border-2 <?php echo ($skill['type'] == 'teach') ? 'border-primary' : 'border-info'; ?>">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($skill['skill_name']); ?></h6>
                                            <div class="d-flex gap-2">
                                                <span class="badge bg-<?php echo ($skill['type'] == 'teach') ? 'primary' : 'info'; ?>">
                                                    <?php echo ($skill['type'] == 'teach') ? 'Teaching' : 'Learning'; ?>
                                                </span>
                                                <span class="badge bg-<?php
                                                                        if ($skill['status'] == 'approved') echo 'success';
                                                                        elseif ($skill['status'] == 'pending') echo 'warning';
                                                                        else echo 'secondary';
                                                                        ?>">
                                                    <?php echo ucfirst($skill['status']); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <a href="add_skill.php?delete=<?php echo $skill['user_skill_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Remove this skill?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No skills added yet. Add your first skill above!
                    </div>
                <?php endif; ?>

                <hr class="my-4">
                <div class="text-center">
                    <a href="dashboard.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>