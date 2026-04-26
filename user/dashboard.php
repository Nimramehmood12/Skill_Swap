<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('../config/db.php');
include('../database_functions.php');

// Redirect if NOT logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

include('../includes/header.php');

$user_id = $_SESSION['user_id'];

// Get user data
$user_res = mysqli_query($conn, "SELECT * FROM users WHERE user_id = $user_id");
if (!$user_res) {
    die("Error fetching user: " . mysqli_error($conn));
}
$user_data = mysqli_fetch_assoc($user_res);

// Matching Logic: Find users who teach what I want to learn
// Step 1: Get skills the current user wants to LEARN
$my_learn_skills = mysqli_query(
    $conn,
    "SELECT DISTINCT TRIM(s.skill_name) AS skill_name
     FROM user_skills us
     JOIN skills s ON us.skill_id = s.skill_id
     WHERE us.user_id = $user_id AND us.type = 'learn'"
);

if (!$my_learn_skills) {
    die("Error fetching learning skills: " . mysqli_error($conn));
}

$learn_skill_names = [];
while ($row = mysqli_fetch_assoc($my_learn_skills)) {
    $learn_skill_names[] = $row['skill_name'];
}

// Step 2: Find teachers who teach those skills
$matches = false;
$match_mode = 'exact';

if (!empty($learn_skill_names)) {
    $escaped_skill_names = array_map(function ($skill_name) use ($conn) {
        return "'" . mysqli_real_escape_string($conn, $skill_name) . "'";
    }, $learn_skill_names);

    $skills_list = implode(",", $escaped_skill_names);

    $sql = "SELECT DISTINCT u.user_id, u.name, s.skill_id, s.skill_name
            FROM users u
            JOIN user_skills us ON u.user_id = us.user_id
            JOIN skills s ON us.skill_id = s.skill_id
            WHERE s.skill_name IN ($skills_list)
            AND us.type = 'teach'
            AND u.user_id != $user_id
            ORDER BY u.name, s.skill_name";
    $matches = mysqli_query($conn, $sql);
    if (!$matches) {
        die("Error fetching matches: " . mysqli_error($conn));
    }
}

if (!$matches || mysqli_num_rows($matches) === 0) {
    $fallback_sql = "SELECT DISTINCT u.user_id, u.name, s.skill_id, s.skill_name
            FROM users u
            JOIN user_skills us ON u.user_id = us.user_id
            JOIN skills s ON us.skill_id = s.skill_id
            WHERE us.type = 'teach'
            AND u.user_id != $user_id
            ORDER BY u.name, s.skill_name";
    $matches = mysqli_query($conn, $fallback_sql);
    $match_mode = 'fallback';
    if (!$matches) {
        die("Error fetching fallback matches: " . mysqli_error($conn));
    }
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 rounded-4 mb-4 bg-white">
                <h4>Welcome, <?php echo htmlspecialchars($user_data['name']); ?>!</h4>
                <div class="badge bg-success mb-3 p-2 px-3"><?php echo $user_data['points']; ?> Points</div>

                <h6 class="fw-bold mt-3">Your Skills:</h6>
                <div class="mb-2">
                    <?php
                    $skills = mysqli_query(
                        $conn,
                        "SELECT s.skill_name, us.type FROM user_skills us
                         JOIN skills s ON us.skill_id = s.skill_id
                         WHERE us.user_id = $user_id"
                    );
                    while ($s = mysqli_fetch_assoc($skills)) {
                        $color = ($s['type'] == 'teach') ? 'primary' : 'info';
                        $label = ($s['type'] == 'teach') ? 'Teaching' : 'Learning';
                        echo "<span class='badge bg-$color me-1 mb-1' title='$label'>" . htmlspecialchars($s['skill_name']) . "</span>";
                    }
                    ?>
                </div>
                <hr>
                <a href="add_skill_v2.php" class="btn btn-sm btn-outline-secondary w-100 mb-2">
                    <i class="fas fa-edit"></i> Edit Skills
                </a>
                <a href="browse_users.php" class="btn btn-sm btn-info w-100">
                    <i class="fas fa-users"></i> Browse Users & Message
                </a>
            </div>
        </div>

        <div class="col-md-8">
            <h3 class="mb-4 fw-bold">Suggested Matches</h3>

            <?php if (empty($learn_skill_names)): ?>
                <div class="alert alert-warning">
                    <strong>⚠️ No Learning Skills Found</strong><br>
                    You haven't added any "Learning" skills yet. Add some first to see suggested matches!
                </div>
            <?php endif; ?>

            <?php if ($match_mode === 'fallback' && !empty($learn_skill_names)): ?>
                <div class="alert alert-info">
                    No exact matches found, so showing available teachers you can request.
                </div>
            <?php endif; ?>

            <div class="row">
                <?php if ($matches && mysqli_num_rows($matches) > 0): ?>
                    <?php while ($m = mysqli_fetch_assoc($matches)): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card h-100 shadow-sm border-0 profile-card p-2">
                                <div class="card-body">
                                    <h5 class="card-title fw-bold text-dark"><?php echo htmlspecialchars($m['name']); ?></h5>
                                    <p class="text-muted mb-3">Can teach you: <span class="text-primary fw-bold"><?php echo htmlspecialchars($m['skill_name']); ?></span></p>

                                    <a href="../sessions/request_session.php?rid=<?php echo $m['user_id']; ?>&skill_id=<?php echo $m['skill_id']; ?>" class="btn btn-warning btn-sm w-100 fw-bold">Request Swap</a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-info">
                            No teachers are available right now. Try again after other users approve teaching skills.
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>