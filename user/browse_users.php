<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('../config/db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : "";

// Get all users except current user, with their ratings
if (!empty($search)) {
    $query = "SELECT u.user_id, u.name, u.bio, u.points,
                     COALESCE(ROUND(AVG(r.rating), 1), 0) as avg_rating,
                     COUNT(DISTINCT r.review_id) as total_reviews
              FROM users u
              LEFT JOIN reviews r ON u.user_id = r.receiver_id
              WHERE u.user_id != $user_id AND (u.name LIKE '%$search%' OR u.bio LIKE '%$search%')
              GROUP BY u.user_id, u.name, u.bio, u.points
              ORDER BY u.name";
} else {
    $query = "SELECT u.user_id, u.name, u.bio, u.points,
                     COALESCE(ROUND(AVG(r.rating), 1), 0) as avg_rating,
                     COUNT(DISTINCT r.review_id) as total_reviews
              FROM users u
              LEFT JOIN reviews r ON u.user_id = r.receiver_id
              WHERE u.user_id != $user_id
              GROUP BY u.user_id, u.name, u.bio, u.points
              ORDER BY u.points DESC";
}

$users = mysqli_query($conn, $query);

include('../includes/header.php');
?>

<div class="container py-5 mt-5">
    <div class="row">
        <div class="col-md-10 mx-auto">
            <div class="card shadow-sm border-0 p-4 rounded-4">
                <h2 class="fw-bold mb-4">
                    <i class="fas fa-users"></i> Browse Users
                </h2>

                <!-- Search Bar -->
                <div class="card bg-light border-0 p-3 mb-4">
                    <form method="GET" class="d-flex gap-2">
                        <input type="text" name="search" class="form-control" placeholder="Search by name or bio..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <?php if (!empty($search)): ?>
                            <a href="browse_users.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Users Grid -->
                <?php if (mysqli_num_rows($users) > 0): ?>
                    <div class="row">
                        <?php while ($u = mysqli_fetch_assoc($users)): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card h-100 shadow-sm border-0 p-3">
                                    <div class="card-body">
                                        <!-- User Name & Rating -->
                                        <h5 class="fw-bold mb-2"><?php echo htmlspecialchars($u['name']); ?></h5>

                                        <!-- Stars -->
                                        <div class="mb-2">
                                            <?php
                                            $rating = intval($u['avg_rating']);
                                            for ($i = 1; $i <= 5; $i++) {
                                                if ($i <= $rating) {
                                                    echo '<i class="fas fa-star text-warning"></i>';
                                                } else {
                                                    echo '<i class="far fa-star text-muted"></i>';
                                                }
                                            }
                                            ?>
                                            <small class="text-muted">
                                                <?php echo $u['avg_rating']; ?>
                                                (<?php echo $u['total_reviews']; ?> reviews)
                                            </small>
                                        </div>

                                        <!-- Bio -->
                                        <?php if (!empty($u['bio'])): ?>
                                            <p class="text-muted small mb-2">
                                                <?php echo htmlspecialchars(substr($u['bio'], 0, 100)); ?>...
                                            </p>
                                        <?php endif; ?>

                                        <!-- Points Badge -->
                                        <div class="mb-3">
                                            <span class="badge bg-success">
                                                <i class="fas fa-coins"></i> <?php echo $u['points']; ?> Points
                                            </span>
                                        </div>

                                        <!-- Action Buttons -->
                                        <div class="d-flex gap-2">
                                            <a href="messages.php?to=<?php echo $u['user_id']; ?>" class="btn btn-sm btn-primary flex-grow-1">
                                                <i class="fas fa-envelope"></i> Message
                                            </a>
                                            <a href="view_profile.php?user_id=<?php echo $u['user_id']; ?>" class="btn btn-sm btn-outline-secondary flex-grow-1">
                                                <i class="fas fa-user"></i> Profile
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle"></i> No users found.
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