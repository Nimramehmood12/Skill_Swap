<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('../config/db.php');

// 1. Redirect if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: ../user/dashboard.php");
    exit();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 2. Use trim() to remove accidental spaces from inputs
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = trim($_POST['pass']);

    // 3. Fetch user by email
    $query = "SELECT * FROM users WHERE email='$email'";
    $result = mysqli_query($conn, $query);

    if ($row = mysqli_fetch_assoc($result)) {
        // 4. Compare password with hashed password using password_verify()
        if (password_verify($password, $row['password'])) {

            // 5. Success! Set session variables
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['user_name'] = $row['name'];
            $_SESSION['role'] = $row['role'];

            header("Location: ../user/dashboard.php");
            exit();
        } else {
            $error = "Invalid password! Please check your caps lock.";
        }
    } else {
        $error = "No account found with that email.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | SkillSwap</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .login-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .text-primary {
            color: #0d6efd !important;
        }
    </style>
</head>

<body>

    <div class="container py-5 mt-5">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="text-center mb-4">
                    <h2 class="fw-bold text-primary">WELCOME BACK</h2>
                    <p class="text-muted">Log in to manage your skill swaps.</p>
                </div>

                <div class="card login-card p-4 bg-white">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger small py-2 mb-3 text-center"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
                        <div class="alert alert-success small py-2 mb-3 text-center">Account created! Login below.</div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="name@email.com" required autocomplete="email">
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold">Password</label>
                            <input type="password" name="pass" class="form-control" placeholder="••••••••" required autocomplete="current-password">
                        </div>

                        <button type="submit" class="btn btn-warning w-100 py-2 mb-3 fw-bold shadow-sm">Login</button>

                        <div class="text-center">
                            <p class="small text-muted mb-0">Don't have an account?</p>
                            <a href="register.php" class="text-decoration-none small fw-bold text-primary">Create Account</a>
                        </div>
                    </form>
                </div>

                <div class="text-center mt-4">
                    <a href="../index.php" class="text-muted small text-decoration-none">
                        <i class="fas fa-arrow-left me-1"></i> Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>