<?php
include('../config/db.php');

if (isset($_POST['reg'])) {
    $n = mysqli_real_escape_string($conn, $_POST['name']);
    $e = mysqli_real_escape_string($conn, $_POST['email']);
    $p = password_hash($_POST['pass'], PASSWORD_BCRYPT);

    // Skills added during registration
    $teach_skill = mysqli_real_escape_string($conn, $_POST['teach_skill']);
    $learn_skill = mysqli_real_escape_string($conn, $_POST['learn_skill']);

    // Insert User
    $q = mysqli_prepare($conn, "INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($q, "sss", $n, $e, $p);

    if (mysqli_stmt_execute($q)) {
        $user_id = mysqli_insert_id($conn);

        // Get a default category (or create one)
        $cat = mysqli_fetch_assoc(mysqli_query($conn, "SELECT category_id FROM skill_categories LIMIT 1"));
        $category_id = $cat ? $cat['category_id'] : 1;

        // Insert skills into skills table
        $teach_result = mysqli_query($conn, "INSERT INTO skills (skill_name, category_id, status) VALUES ('$teach_skill', $category_id, 'pending')");
        $teach_skill_id = mysqli_insert_id($conn);

        $learn_result = mysqli_query($conn, "INSERT INTO skills (skill_name, category_id, status) VALUES ('$learn_skill', $category_id, 'pending')");
        $learn_skill_id = mysqli_insert_id($conn);

        // Insert into user_skills junction table
        if ($teach_skill_id) mysqli_query($conn, "INSERT INTO user_skills (user_id, skill_id, type, status) VALUES ($user_id, $teach_skill_id, 'teach', 'pending')");
        if ($learn_skill_id) mysqli_query($conn, "INSERT INTO user_skills (user_id, skill_id, type, status) VALUES ($user_id, $learn_skill_id, 'learn', 'approved')");

        header("Location: login.php?msg=success");
    } else {
        $error = "Email already exists!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join SkillSwap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-light">

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="text-center mb-4">
                    <h1 class="fw-bold text-gradient">SKILLSWAP</h1>
                    <p class="text-muted">Start your exchange journey today.</p>
                </div>

                <div class="card p-4 shadow border-0">
                    <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Full Name</label>
                            <input type="text" name="name" class="form-control" placeholder="John Doe" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="john@example.com" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Password</label>
                            <input type="password" name="pass" class="form-control" placeholder="••••••••" required>
                        </div>

                        <hr>
                        <p class="small text-primary fw-bold text-center">Your First Skills</p>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">I can teach:</label>
                            <input type="text" name="teach_skill" class="form-control" placeholder="e.g. Photoshop" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold">I want to learn:</label>
                            <input type="text" name="learn_skill" class="form-control" placeholder="e.g. Italian" required>
                        </div>

                        <button name="reg" class="btn btn-primary w-100 py-2">Create Account</button>

                        <div class="text-center mt-3">
                            <p class="small text-muted">Already have an account? <a href="login.php" class="text-decoration-none">Login here</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>