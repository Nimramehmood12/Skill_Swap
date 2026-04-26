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

// Handle messages from redirects
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'sent') {
        $msg = "Swap request sent successfully!";
    } elseif ($_GET['msg'] == 'already_exists') {
        $error = "You already have a pending request for this skill with this user.";
    } elseif ($_GET['msg'] == 'already_active') {
        $error = "You already have an active swap for this skill with this user.";
    } elseif ($_GET['msg'] == 'error') {
        $error = "An error occurred while sending the request.";
    }
}

// Handle Accept/Decline requests
if (isset($_GET['action']) && isset($_GET['session_id'])) {
    $action = $_GET['action'];
    $session_id = intval($_GET['session_id']);

    // Verify this session belongs to the user as receiver
    $verify = mysqli_query($conn, "SELECT * FROM sessions WHERE session_id=$session_id AND receiver_id=$user_id");

    if ($verify && mysqli_num_rows($verify) > 0) {
        if ($action == 'accept') {
            $update = mysqli_query($conn, "UPDATE sessions SET status='active' WHERE session_id=$session_id");
            if ($update) {
                $msg = "Request accepted! Swap is now active.";
            } else {
                $error = "Error accepting request.";
            }
        } elseif ($action == 'decline') {
            $update = mysqli_query($conn, "UPDATE sessions SET status='declined' WHERE session_id=$session_id");
            if ($update) {
                $msg = "Request declined.";
            } else {
                $error = "Error declining request.";
            }
        }
    } else {
        $error = "Invalid request or unauthorized action.";
    }
}

// Handle Cancel sent requests
if (isset($_GET['cancel']) && isset($_GET['session_id'])) {
    $session_id = intval($_GET['session_id']);

    // Verify this session belongs to the user as sender
    $verify = mysqli_query($conn, "SELECT * FROM sessions WHERE session_id=$session_id AND requester_id=$user_id AND status='pending'");

    if ($verify && mysqli_num_rows($verify) > 0) {
        $delete = mysqli_query($conn, "DELETE FROM sessions WHERE session_id=$session_id");
        if ($delete) {
            $msg = "Request cancelled.";
        } else {
            $error = "Error cancelling request.";
        }
    } else {
        $error = "Cannot cancel this request.";
    }
}

// Get requests sent TO me
$incoming = mysqli_query($conn, "SELECT s.*, u.name as requester_name, u.user_id as requester_id, sk.skill_name 
                                FROM sessions s 
                                JOIN users u ON s.requester_id = u.user_id 
                                JOIN skills sk ON s.skill_id = sk.skill_id 
                                WHERE s.receiver_id = '$user_id' AND s.status='pending' ORDER BY s.session_id DESC");

// Get requests sent BY me
$outgoing = mysqli_query($conn, "SELECT s.*, u.name as receiver_name, u.user_id as receiver_id, sk.skill_name 
                                FROM sessions s 
                                JOIN users u ON s.receiver_id = u.user_id 
                                JOIN skills sk ON s.skill_id = sk.skill_id 
                                WHERE s.requester_id = '$user_id' AND s.status='pending' ORDER BY s.session_id DESC");

// Get active swaps (I'm the receiver)
$active_receive = mysqli_query($conn, "SELECT s.*, u.name as requester_name, u.user_id as requester_id, sk.skill_name 
                                FROM sessions s 
                                JOIN users u ON s.requester_id = u.user_id 
                                JOIN skills sk ON s.skill_id = sk.skill_id 
                                WHERE s.receiver_id = '$user_id' AND s.status='active' ORDER BY s.session_id DESC");

// Get active swaps (I'm the sender)
$active_send = mysqli_query($conn, "SELECT s.*, u.name as receiver_name, u.user_id as receiver_id, sk.skill_name 
                                FROM sessions s 
                                JOIN users u ON s.receiver_id = u.user_id 
                                JOIN skills sk ON s.skill_id = sk.skill_id 
                                WHERE s.requester_id = '$user_id' AND s.status='active' ORDER BY s.session_id DESC");

// Get completed swaps (I'm the receiver)
$completed_receive = mysqli_query($conn, "SELECT s.*, u.name as requester_name, u.user_id as requester_id, sk.skill_name 
                                FROM sessions s 
                                JOIN users u ON s.requester_id = u.user_id 
                                JOIN skills sk ON s.skill_id = sk.skill_id 
                                WHERE s.receiver_id = '$user_id' AND s.status='completed' ORDER BY s.session_id DESC");

// Get completed swaps (I'm the sender)
$completed_send = mysqli_query($conn, "SELECT s.*, u.name as receiver_name, u.user_id as receiver_id, sk.skill_name 
                                FROM sessions s 
                                JOIN users u ON s.receiver_id = u.user_id 
                                JOIN skills sk ON s.skill_id = sk.skill_id 
                                WHERE s.requester_id = '$user_id' AND s.status='completed' ORDER BY s.session_id DESC");

include('../includes/header.php');
?>

<div class="container mt-5 pt-5">
    <h2 class="fw-bold mb-4">My Swap Requests</h2>

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

    <!-- PENDING REQUESTS TAB -->
    <div class="row mb-5">
        <div class="col-md-6">
            <div class="card shadow-sm p-4">
                <h4 class="text-primary">
                    <i class="fas fa-inbox"></i> Incoming Requests
                </h4>
                <hr>
                <?php
                if ($incoming && mysqli_num_rows($incoming) > 0) {
                    while ($row = mysqli_fetch_assoc($incoming)): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3 p-3 border rounded bg-light">
                            <div>
                                <strong><?php echo htmlspecialchars($row['requester_name']); ?></strong> wants to learn
                                <span class="badge bg-info"><?php echo htmlspecialchars($row['skill_name']); ?></span>
                                <br>
                                <small class="text-muted">Status: <strong><?php echo ucfirst($row['status']); ?></strong></small>
                            </div>
                            <div class="d-flex gap-2">
                                <?php if ($row['status'] == 'pending'): ?>
                                    <a href="manage_sessions.php?action=accept&session_id=<?php echo $row['session_id']; ?>" class="btn btn-success btn-sm" title="Accept this swap request">
                                        <i class="fas fa-check"></i> Accept
                                    </a>
                                    <a href="manage_sessions.php?action=decline&session_id=<?php echo $row['session_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Decline this request?');" title="Decline this swap request">
                                        <i class="fas fa-times"></i> Decline
                                    </a>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><?php echo ucfirst($row['status']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                <?php endwhile;
                } else {
                    echo '<div class="alert alert-info">No incoming requests yet.</div>';
                }
                ?>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm p-4">
                <h4 class="text-warning">
                    <i class="fas fa-paper-plane"></i> Sent Requests
                </h4>
                <hr>
                <?php
                if ($outgoing && mysqli_num_rows($outgoing) > 0) {
                    while ($row = mysqli_fetch_assoc($outgoing)): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3 p-3 border rounded bg-light">
                            <div>
                                Sent to <strong><?php echo htmlspecialchars($row['receiver_name']); ?></strong>
                                for <strong><?php echo htmlspecialchars($row['skill_name']); ?></strong>
                                <br>
                                <small class="text-muted">Status: <strong><?php echo ucfirst($row['status']); ?></strong></small>
                            </div>
                            <div class="d-flex gap-2">
                                <?php if ($row['status'] == 'pending'): ?>
                                    <a href="manage_sessions.php?cancel=true&session_id=<?php echo $row['session_id']; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Cancel this request?');" title="Cancel this swap request">
                                        <i class="fas fa-ban"></i> Cancel
                                    </a>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><?php echo ucfirst($row['status']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                <?php endwhile;
                } else {
                    echo '<div class="alert alert-info">No sent requests yet.</div>';
                }
                ?>
            </div>
        </div>
    </div>

    <!-- ACTIVE SWAPS TAB -->
    <h3 class="fw-bold mb-3 mt-4">
        <i class="fas fa-handshake text-success"></i> Active Swaps
    </h3>
    <div class="row mb-5">
        <div class="col-md-6">
            <div class="card shadow-sm p-4 border-success border-2">
                <h5 class="text-success">Teaching Active Swaps</h5>
                <hr>
                <?php
                if ($active_receive && mysqli_num_rows($active_receive) > 0) {
                    while ($row = mysqli_fetch_assoc($active_receive)): ?>
                        <div class="p-3 border rounded bg-light mb-3">
                            <div>
                                <strong><?php echo htmlspecialchars($row['requester_name']); ?></strong> is learning
                                <span class="badge bg-success"><?php echo htmlspecialchars($row['skill_name']); ?></span>
                                <br>
                                <small class="text-muted">Status: <strong class="text-success">Active</strong></small>
                            </div>
                            <div class="mt-2 d-flex gap-2">
                                <a href="session_schedule.php?session_id=<?php echo $row['session_id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-calendar"></i> Schedule
                                </a>
                                <a href="../user/messages.php?session_id=<?php echo $row['session_id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-comments"></i> Message
                                </a>
                                <a href="review_session.php?session_id=<?php echo $row['session_id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Are you sure you want to end this entire course? Make sure you have completed all your planned classes first!');">
                                    <i class="fas fa-check-circle"></i> Complete & Review
                                </a>
                            </div>
                        </div>
                <?php endwhile;
                } else {
                    echo '<div class="alert alert-info">No active teaching swaps yet.</div>';
                }
                ?>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm p-4 border-success border-2">
                <h5 class="text-success">Learning Active Swaps</h5>
                <hr>
                <?php
                if ($active_send && mysqli_num_rows($active_send) > 0) {
                    while ($row = mysqli_fetch_assoc($active_send)): ?>
                        <div class="p-3 border rounded bg-light mb-3">
                            <div>
                                <strong><?php echo htmlspecialchars($row['receiver_name']); ?></strong> is teaching you
                                <span class="badge bg-success"><?php echo htmlspecialchars($row['skill_name']); ?></span>
                                <br>
                                <small class="text-muted">Status: <strong class="text-success">Active</strong></small>
                            </div>
                            <div class="mt-2 d-flex gap-2">
                                <a href="session_schedule.php?session_id=<?php echo $row['session_id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-calendar"></i> Schedule
                                </a>
                                <a href="../user/messages.php?session_id=<?php echo $row['session_id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-comments"></i> Message
                                </a>
                                <a href="review_session.php?session_id=<?php echo $row['session_id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Are you sure you want to end this entire course? Make sure you have completed all your planned classes first!');">
                                    <i class="fas fa-check-circle"></i> Complete & Review
                                </a>
                            </div>
                        </div>
                <?php endwhile;
                } else {
                    echo '<div class="alert alert-info">No active learning swaps yet.</div>';
                }
                ?>
            </div>
        </div>
    </div>

    <!-- COMPLETED SWAPS TAB -->
    <h3 class="fw-bold mb-3 mt-4">
        <i class="fas fa-star text-warning"></i> Completed Swaps
    </h3>
    <div class="row mb-5">
        <div class="col-md-6">
            <div class="card shadow-sm p-4 border-warning border-2">
                <h5 class="text-warning">Completed Teaching Swaps</h5>
                <hr>
                <?php
                if ($completed_receive && mysqli_num_rows($completed_receive) > 0) {
                    while ($row = mysqli_fetch_assoc($completed_receive)): ?>
                        <div class="p-3 border rounded bg-light mb-3">
                            <div>
                                <strong><?php echo htmlspecialchars($row['requester_name']); ?></strong> learned
                                <span class="badge bg-warning text-dark"><?php echo htmlspecialchars($row['skill_name']); ?></span>
                                <br>
                                <small class="text-muted">Status: <strong class="text-warning">Completed</strong></small>
                            </div>
                            <div class="mt-2 d-flex gap-2">
                                <a href="../user/messages.php?session_id=<?php echo $row['session_id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-comments"></i> Message
                                </a>
                                <a href="review_session.php?session_id=<?php echo $row['session_id']; ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-star"></i> Review
                                </a>
                            </div>
                        </div>
                <?php endwhile;
                } else {
                    echo '<div class="alert alert-info">No completed teaching swaps yet.</div>';
                }
                ?>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm p-4 border-warning border-2">
                <h5 class="text-warning">Completed Learning Swaps</h5>
                <hr>
                <?php
                if ($completed_send && mysqli_num_rows($completed_send) > 0) {
                    while ($row = mysqli_fetch_assoc($completed_send)): ?>
                        <div class="p-3 border rounded bg-light mb-3">
                            <div>
                                <strong><?php echo htmlspecialchars($row['receiver_name']); ?></strong> taught you
                                <span class="badge bg-warning text-dark"><?php echo htmlspecialchars($row['skill_name']); ?></span>
                                <br>
                                <small class="text-muted">Status: <strong class="text-warning">Completed</strong></small>
                            </div>
                            <div class="mt-2 d-flex gap-2">
                                <a href="../user/messages.php?session_id=<?php echo $row['session_id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-comments"></i> Message
                                </a>
                                <a href="review_session.php?session_id=<?php echo $row['session_id']; ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-star"></i> Review
                                </a>
                            </div>
                        </div>
                <?php endwhile;
                } else {
                    echo '<div class="alert alert-info">No completed learning swaps yet.</div>';
                }
                ?>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <a href="../user/dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>