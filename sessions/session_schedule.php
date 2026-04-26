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

// Verify user is part of this session
$session_check = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT * FROM sessions WHERE session_id = $session_id AND (requester_id = $user_id OR receiver_id = $user_id)"
));

if (!$session_check) {
    header("Location: ../sessions/manage_sessions.php");
    exit();
}

// Propose a time
if (isset($_POST['propose_time'])) {
    $proposed_date = mysqli_real_escape_string($conn, $_POST['proposed_date']);
    $proposed_time = mysqli_real_escape_string($conn, $_POST['proposed_time']);

    $result = proposeSessionTime($session_id, $user_id, $proposed_date, $proposed_time);
    if ($result['success']) {
        $msg = "Time proposed successfully!";
    } else {
        $error = $result['message'];
    }
}

// Agree on proposed time
if (isset($_GET['agree'])) {
    $schedule_id = intval($_GET['agree']);
    $update = mysqli_query($conn, "UPDATE session_schedules SET status = 'agreed' WHERE schedule_id = $schedule_id");
    if ($update) {
        $msg = "Time agreed!";
    }
}

// Mark specific class as completed
if (isset($_GET['complete_class'])) {
    $schedule_id = intval($_GET['complete_class']);
    $update = mysqli_query($conn, "UPDATE session_schedules SET status = 'completed' WHERE schedule_id = $schedule_id AND session_id = $session_id");
    if ($update) {
        $msg = "Class marked as completed!";
    }
}

// Get session details
$session_details = mysqli_fetch_assoc(mysqli_query(
    $conn,
    "SELECT s.*, u_req.name as requester_name, u_rec.name as receiver_name,
            sk.skill_name, sc.category_name
    FROM sessions s
    JOIN users u_req ON s.requester_id = u_req.user_id
    JOIN users u_rec ON s.receiver_id = u_rec.user_id
    JOIN skills sk ON s.skill_id = sk.skill_id
    JOIN skill_categories sc ON sk.category_id = sc.category_id
    WHERE s.session_id = $session_id"
));

// Get proposed schedules
$schedules = getSessionSchedules($session_id);

include('../includes/header.php');
?>

<div class="container py-5 mt-5">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow-sm border-0 p-4 rounded-4">
                <h2 class="fw-bold mb-4">
                    <i class="fas fa-calendar"></i> Schedule Session
                </h2>

                <!-- Session Info -->
                <div class="alert alert-info mb-4">
                    <strong><?php echo htmlspecialchars($session_details['requester_name']); ?></strong> wants to learn
                    <strong><?php echo htmlspecialchars($session_details['skill_name']); ?></strong>
                    from <strong><?php echo htmlspecialchars($session_details['receiver_name']); ?></strong>
                    <br>
                    <small class="text-muted">Category: <?php echo htmlspecialchars($session_details['category_name']); ?></small>
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

                <!-- Propose Time Form -->
                <div class="card bg-light border-0 p-3 mb-4">
                    <h5 class="fw-bold mb-3">Propose a Date & Time</h5>
                    <form method="POST" class="row g-2">
                        <div class="col-md-5">
                            <label class="form-label small">Date</label>
                            <input type="date" name="proposed_date" class="form-control" required
                                min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small">Time</label>
                            <input type="time" name="proposed_time" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">&nbsp;</label>
                            <button type="submit" name="propose_time" class="btn btn-primary w-100">
                                <i class="fas fa-clock"></i> Propose
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Proposed Times -->
                <h5 class="fw-bold mb-3">Proposed Times</h5>

                <?php if ($schedules && mysqli_num_rows($schedules) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-4">
                            <thead class="table-light">
                                <tr>
                                    <th>Proposed By</th>
                                    <th>Date & Time</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($schedule = mysqli_fetch_assoc($schedules)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($schedule['proposed_by_name']); ?></td>
                                        <td>
                                            <strong><?php echo date('M d, Y', strtotime($schedule['proposed_date'])); ?></strong>
                                            at
                                            <strong><?php echo date('h:i A', strtotime($schedule['proposed_time'])); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php
                                                                    if ($schedule['status'] == 'agreed') echo 'success';
                                                                    elseif ($schedule['status'] == 'completed') echo 'primary';
                                                                    elseif ($schedule['status'] == 'proposed') echo 'warning';
                                                                    else echo 'secondary';
                                                                    ?>">
                                                <?php echo ucfirst($schedule['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($schedule['status'] == 'proposed' && $schedule['proposed_by_id'] != $user_id): ?>
                                                <a href="session_schedule.php?session_id=<?php echo $session_id; ?>&agree=<?php echo $schedule['schedule_id']; ?>"
                                                    class="btn btn-sm btn-success">
                                                    <i class="fas fa-check"></i> Agree
                                                </a>
                                            <?php elseif ($schedule['status'] == 'agreed'): ?>
                                                <a href="session_schedule.php?session_id=<?php echo $session_id; ?>&complete_class=<?php echo $schedule['schedule_id']; ?>"
                                                    class="btn btn-sm btn-info" onclick="return confirm('Mark this specific class as completed?');">
                                                    <i class="fas fa-check-double"></i> Mark Done
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted small">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mb-4">
                        No times proposed yet. Propose a time above!
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

<?php include('../includes/footer.php'); ?>