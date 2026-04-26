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
$profile_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($profile_user_id == 0) {
    header("Location: browse_users.php");
    exit();
}

// Get user profile
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE user_id = $profile_user_id"));

if (!$user) {
    header("Location: browse_users.php");
    exit();
}

// Get user's teaching skills
$teaching = mysqli_query(
    $conn,
    "SELECT s.skill_name, sc.category_name FROM user_skills us
     JOIN skills s ON us.skill_id = s.skill_id
     JOIN skill_categories sc ON s.category_id = sc.category_id
     WHERE us.user_id = $profile_user_id AND us.type = 'teach'"
);

// Get user's learning skills
$learning = mysqli_query(
    $conn,
    "SELECT s.skill_name, sc.category_name FROM user_skills us
     JOIN skills s ON us.skill_id = s.skill_id
     JOIN skill_categories sc ON s.category_id = sc.category_id
     WHERE us.user_id = $profile_user_id AND us.type = 'learn'"
);

// Get user's reviews
$reviews = mysqli_query(
    $conn,
    "SELECT r.*, u.name as reviewer_name FROM reviews r
     JOIN users u ON r.reviewer_id = u.user_id
     WHERE r.receiver_id = $profile_user_id
     ORDER BY r.created_at DESC LIMIT 5"
);

// Get user stats
$rating = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT COALESCE(ROUND(AVG(rating), 1), 0) as avg_rating, COUNT(*) as total_reviews
     FROM reviews WHERE receiver_id = $profile_user_id"
));

include('../includes/header.php');
?>

<div class="container py-5 mt-5">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <!-- Profile Header -->
            <div class="card shadow-sm border-0 p-4 rounded-4 mb-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h2 class="fw-bold mb-1"><?php echo htmlspecialchars($user['name']); ?></h2>
                        <p class="text-muted">
                            <i class="fas fa-coins text-warning"></i>
                            <strong><?php echo $user['points']; ?> Points</strong>
                        </p>
                    </div>
                    <a href="browse_users.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>

                <!-- Rating -->
                <div class="mb-3">
                    <span class="h5">Rating:</span>
                    <div>
                        <?php
                        $avg = intval($rating['avg_rating']);
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $avg) {
                                echo '<i class="fas fa-star text-warning"></i>';
                            } else {
                                echo '<i class="far fa-star text-muted"></i>';
                            }
                        }
                        ?>
                        <span class="badge bg-info"><?php echo $rating['avg_rating']; ?>/5</span>
                        <small class="text-muted">(<?php echo $rating['total_reviews']; ?> reviews)</small>
                    </div>
                </div>

                <!-- Bio -->
                <?php if (!empty($user['bio'])): ?>
                    <div class="mb-3">
                        <h6 class="fw-bold">About</h6>
                        <p><?php echo htmlspecialchars($user['bio']); ?></p>
                    </div>
                <?php endif; ?>

                <!-- Actions -->
                <div class="d-flex gap-2 mb-3">
                    <a href="messages.php?to=<?php echo $profile_user_id; ?>" class="btn btn-primary">
                        <i class="fas fa-envelope"></i> Send Message
                    </a>
                    <?php if ($profile_user_id != $user_id && mysqli_num_rows($teaching) > 0): ?>
                        <a href="../sessions/request_session.php?rid=<?php echo $profile_user_id; ?>" class="btn btn-warning">
                            <i class="fas fa-handshake"></i> Request Swap
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Teaching Skills -->
            <div class="card shadow-sm border-0 p-4 rounded-4 mb-4">
                <h5 class="fw-bold mb-3">
                    <i class="fas fa-book text-primary"></i> Can Teach
                </h5>
                <?php if (mysqli_num_rows($teaching) > 0): ?>
                    <div class="row">
                        <?php while ($skill = mysqli_fetch_assoc($teaching)): ?>
                            <div class="col-md-6 mb-2">
                                <span class="badge bg-primary p-2">
                                    <?php echo htmlspecialchars($skill['skill_name']); ?>
                                    <br>
                                    <small><?php echo htmlspecialchars($skill['category_name']); ?></small>
                                </span>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No teaching skills yet.</p>
                <?php endif; ?>
            </div>

            <!-- Learning Skills -->
            <div class="card shadow-sm border-0 p-4 rounded-4 mb-4">
                <h5 class="fw-bold mb-3">
                    <i class="fas fa-graduation-cap text-info"></i> Wants to Learn
                </h5>
                <?php if (mysqli_num_rows($learning) > 0): ?>
                    <div class="row">
                        <?php while ($skill = mysqli_fetch_assoc($learning)): ?>
                            <div class="col-md-6 mb-2">
                                <span class="badge bg-info p-2">
                                    <?php echo htmlspecialchars($skill['skill_name']); ?>
                                    <br>
                                    <small><?php echo htmlspecialchars($skill['category_name']); ?></small>
                                </span>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No learning skills yet.</p>
                <?php endif; ?>
            </div>

            <!-- Recent Reviews -->
            <div class="card shadow-sm border-0 p-4 rounded-4">
                <h5 class="fw-bold mb-3">
                    <i class="fas fa-comments text-success"></i> Recent Reviews
                </h5>
                <?php if (mysqli_num_rows($reviews) > 0): ?>
                    <?php while ($review = mysqli_fetch_assoc($reviews)): ?>
                        <div class="card mb-3 p-3 border-1">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <strong><?php echo htmlspecialchars($review['reviewer_name']); ?></strong>
                                <div class="stars">
                                    <?php
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $review['rating']) {
                                            echo '<i class="fas fa-star text-warning"></i>';
                                        } else {
                                            echo '<i class="far fa-star text-muted"></i>';
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                            <p class="mb-2"><?php echo htmlspecialchars($review['comment']); ?></p>
                            <small class="text-muted">
                                <i class="fas fa-clock"></i>
                                <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                            </small>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-muted">No reviews yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>