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
$session_id = isset($_GET['session_id']) ? intval($_GET['session_id']) : 0;
$msg = "";
$error = "";

// Get session details (allow access when session is active or completed so reviewer can submit review)
$session_check = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT s.*, u_req.name as requester_name, u_rec.name as receiver_name,
            sk.skill_name, sc.category_name
    FROM sessions s
    JOIN users u_req ON s.requester_id = u_req.user_id
    JOIN users u_rec ON s.receiver_id = u_rec.user_id
    JOIN skills sk ON s.skill_id = sk.skill_id
    JOIN skill_categories sc ON sk.category_id = sc.category_id
    WHERE s.session_id = $session_id AND (s.requester_id = $user_id OR s.receiver_id = $user_id) AND s.status IN ('active','completed')"
));

if (!$session_check) {
    header("Location: ../sessions/manage_sessions.php");
    exit();
}

// Check if user already reviewed
$reviewed = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT review_id FROM reviews WHERE session_id = $session_id AND reviewer_id = $user_id"
));

// Submit review
if (isset($_POST['submit_review'])) {
    $rating = intval($_POST['rating']);
    $comment = mysqli_real_escape_string($conn, $_POST['comment']);

    if ($rating < 1 || $rating > 5) {
        $error = "Please select a rating between 1-5";
    } elseif (empty($comment)) {
        $error = "Please add a comment";
    } else {
        $result = addReview($session_id, $user_id, $rating, $comment);
        if ($result['success']) {
            $msg = "Review submitted successfully!";
            $reviewed = true; // Update the check
        } else {
            $error = $result['message'];
        }
    }
}

// Determine who the reviewer is reviewing
$reviewing_user_id = ($session_check['requester_id'] == $user_id) ? $session_check['receiver_id'] : $session_check['requester_id'];
$reviewing_user_name = ($session_check['requester_id'] == $user_id) ? $session_check['receiver_name'] : $session_check['requester_name'];

// Get existing reviews
/** @var mysqli_result|false $reviews */
$reviews = getSessionReviews($session_id);

include('../includes/header.php');
?>

<div class="container py-5 mt-5">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow-sm border-0 p-4 rounded-4">
                <h2 class="fw-bold mb-4">
                    <i class="fas fa-star"></i> Session Review & Rating
                </h2>

                <!-- Session Info -->
                <div class="alert alert-info mb-4">
                    <strong>Skill:</strong> <?php echo htmlspecialchars($session_check['skill_name']); ?>
                    (<?php echo htmlspecialchars($session_check['category_name']); ?>)
                    <br>
                    <strong>Completed On:</strong> <?php echo date('M d, Y', strtotime($session_check['created_at'])); ?>
                </div>

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

                <?php if (!$reviewed): ?>
                    <!-- Review Form -->
                    <div class="card bg-light border-0 p-4 mb-4">
                        <h5 class="fw-bold mb-4">
                            Rate your experience with <strong><?php echo htmlspecialchars($reviewing_user_name); ?></strong>
                        </h5>

                        <form method="POST">
                            <!-- Star Rating -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">Your Rating (1-5 stars)</label>
                                <div class="star-rating">
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" required>
                                        <label for="star<?php echo $i; ?>" class="star-label">
                                            <i class="fas fa-star"></i>
                                        </label>
                                    <?php endfor; ?>
                                </div>
                            </div>

                            <!-- Comment -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">Your Comment</label>
                                <textarea name="comment" class="form-control" rows="4" placeholder="Share your experience..." required></textarea>
                            </div>

                            <button type="submit" name="submit_review" class="btn btn-primary btn-lg">
                                <i class="fas fa-paper-plane"></i> Submit Review
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="alert alert-success mb-4">
                        <i class="fas fa-check-circle"></i> You have already submitted your review for this session.
                    </div>
                <?php endif; ?>

                <!-- Display Reviews -->
                <h5 class="fw-bold mb-3">Session Reviews</h5>

                <?php if ($reviews instanceof mysqli_result && mysqli_num_rows($reviews) > 0): ?>
                    <?php while ($review = mysqli_fetch_assoc($reviews)): ?>
                        <div class="card mb-3 border-1">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($review['reviewer_name']); ?></h6>
                                    <div class="stars">
                                        <?php
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $review['rating']) {
                                                echo '<i class="fas fa-star" style="color: #ffc107;"></i>';
                                            } else {
                                                echo '<i class="far fa-star" style="color: #ccc;"></i>';
                                            }
                                        }
                                        ?>
                                    </div>
                                </div>
                                <p class="card-text"><?php echo htmlspecialchars($review['comment']); ?></p>
                                <small class="text-muted">
                                    <i class="fas fa-clock"></i>
                                    <?php echo date('M d, Y h:i A', strtotime($review['created_at'])); ?>
                                </small>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="alert alert-info">
                        No reviews yet for this session.
                    </div>
                <?php endif; ?>

                <hr class="my-4">
                <div class="text-center">
                    <a href="../sessions/manage_sessions.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to My Swaps
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .star-rating {
        font-size: 2.5rem;
        display: flex;
        flex-direction: row-reverse;
        justify-content: flex-end;
        gap: 0.5rem;
    }

    .star-rating input {
        display: none;
    }

    .star-label {
        cursor: pointer;
        color: #ccc;
        transition: color 0.2s;
    }

    .star-rating input:checked~.star-label,
    .star-label:hover,
    .star-label:hover~.star-label {
        color: #ffc107;
    }
</style>

<?php include('../includes/footer.php'); ?>