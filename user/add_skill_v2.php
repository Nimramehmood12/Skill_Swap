<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('../config/db.php');
include('../database_functions.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = "";
$error = "";

// Add new skill with category
if (isset($_POST['add_skill'])) {
    $skill_name = mysqli_real_escape_string($conn, $_POST['skill_name']);
    $category_id = intval($_POST['category_id']);
    $skill_type = mysqli_real_escape_string($conn, $_POST['skill_type']);

    if (!empty($skill_name) && $category_id > 0) {
        // First, check if skill exists, if not create it
        $check_skill = mysqli_query(
            $conn,
            "SELECT skill_id FROM skills WHERE skill_name='$skill_name' AND category_id=$category_id LIMIT 1"
        );

        $skill_id = 0;
        if (mysqli_num_rows($check_skill) > 0) {
            $skill = mysqli_fetch_assoc($check_skill);
            $skill_id = $skill['skill_id'];
        } else {
            // Create new skill
            $add_result = addSkill($skill_name, $category_id);
            if ($add_result['success']) {
                $skill_id = $add_result['skill_id'];
            } else {
                $error = "Error creating skill: " . $add_result['message'];
            }
        }

        if ($skill_id > 0) {
            // Now add user skill
            $add_user_skill = addUserSkill($user_id, $skill_id, $skill_type);
            if ($add_user_skill['success']) {
                $msg = "Skill added successfully!";
            } else {
                $error = "Error adding skill to your profile";
            }
        }
    } else {
        $error = "Please select category and enter skill name!";
    }
}

// Delete skill
if (isset($_GET['delete'])) {
    $user_skill_id = intval($_GET['delete']);
    if (deleteUserSkill($user_skill_id)) {
        $msg = "Skill removed successfully!";
    } else {
        $error = "Error removing skill";
    }
}

// Get user's current skills
$user_skills = getUserSkills($user_id);

// Get all categories
$categories = getAllCategories();

include('../includes/header.php');
?>

<div class="container py-5 mt-5">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow-sm border-0 p-4 rounded-4">
                <h2 class="fw-bold mb-4">
                    <i class="fas fa-star"></i> Manage Your Skills
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

                <!-- Add New Skill Form -->
                <div class="card bg-light border-0 p-3 mb-4">
                    <h5 class="fw-bold mb-3">Add a New Skill</h5>
                    <form method="POST" class="row g-2">
                        <div class="col-md-4">
                            <select name="category_id" class="form-select" required>
                                <option value="">Select Category</option>
                                <?php
                                if ($categories) {
                                    while ($cat = mysqli_fetch_assoc($categories)) {
                                        echo "<option value='" . $cat['category_id'] . "'>" . htmlspecialchars($cat['category_name']) . "</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="text" name="skill_name" class="form-control" placeholder="e.g. Photography" required>
                        </div>
                        <div class="col-md-3">
                            <select name="skill_type" class="form-select" required>
                                <option value="">Select Type</option>
                                <option value="teach">I can teach this</option>
                                <option value="learn">I want to learn this</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <button type="submit" name="add_skill" class="btn btn-primary w-100">Add</button>
                        </div>
                    </form>
                </div>

                <!-- Current Skills -->
                <h5 class="fw-bold mb-3">Your Skills</h5>

                <?php if ($user_skills && mysqli_num_rows($user_skills) > 0): ?>
                    <div class="row">
                        <?php while ($skill = mysqli_fetch_assoc($user_skills)): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card h-100 p-3 border-2 <?php echo ($skill['type'] == 'teach') ? 'border-primary' : 'border-info'; ?>">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($skill['skill_name']); ?></h6>
                                            <p class="mb-2 small text-muted">
                                                <i class="fas fa-tag"></i>
                                                <?php echo htmlspecialchars($skill['category_name']); ?>
                                            </p>
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