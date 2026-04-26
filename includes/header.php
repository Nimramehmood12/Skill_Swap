<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkillSwap | Peer-to-Peer Learning</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/projecttrial3/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow">
        <div class="container">
            <a class="navbar-brand fw-bold fs-3" href="/projecttrial3/index.php">SKILL<span class="text-warning">SWAP</span></a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    
                    <li class="nav-item"><a class="nav-link" href="/projecttrial3/index.php#home">Home</a></li>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item"><a class="nav-link" href="/projecttrial3/user/dashboard.php">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="/projecttrial3/sessions/manage_sessions.php">My Swaps</a></li>
                        <li class="nav-item"><a class="nav-link" href="/projecttrial3/user/messages.php">Messages</a></li>

                        <?php if ($_SESSION['role'] == 'admin'): ?>
                            <li class="nav-item"><a class="nav-link text-warning" href="/projecttrial3/admin/dashboard.php">Admin</a></li>
                        <?php endif; ?>
                        
                        <?php if ($_SESSION['role'] == 'moderator' || $_SESSION['role'] == 'admin'): ?>
                            <li class="nav-item"><a class="nav-link text-info" href="/projecttrial3/moderator/approve_skills.php">Verify</a></li>
                        <?php endif; ?>

                        <li class="nav-item dropdown ms-lg-3">
                            <a class="nav-link dropdown-toggle btn btn-outline-warning px-3 text-white" href="#" id="userDrop" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle me-1"></i> Hi, <?php echo $_SESSION['user_name']; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDrop">
                                <li><a class="dropdown-item" href="/projecttrial3/user/profile.php"><i class="fas fa-cog me-2"></i>Profile Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="/projecttrial3/auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </li>

                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="/projecttrial3/index.php#skills">Browse</a></li>
                        <li class="nav-item"><a class="nav-link" href="/projecttrial3/index.php#about">About</a></li>
                        <li class="nav-item"><a class="nav-link" href="/projecttrial3/index.php#contact">Contact</a></li>
                        <li class="nav-item ms-lg-3">
                            <a class="btn btn-warning fw-bold px-4 rounded-pill" href="/projecttrial3/auth/login.php">Login</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>