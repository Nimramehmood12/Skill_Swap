<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('../config/db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$uid = $_SESSION['user_id'];
$session_id = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
$other_id = isset($_GET['to']) ? (int)$_GET['to'] : 0;

$error = "";
$msg_status = "";
$session_context = null;
$session_options = null;

if ($session_id > 0) {
    $session_query = mysqli_query($conn, "SELECT s.session_id, s.requester_id, s.receiver_id, s.status, sk.skill_name,
        u_req.name as requester_name, u_rec.name as receiver_name
        FROM sessions s
        JOIN skills sk ON s.skill_id = sk.skill_id
        JOIN users u_req ON s.requester_id = u_req.user_id
        JOIN users u_rec ON s.receiver_id = u_rec.user_id
        WHERE s.session_id = $session_id AND (s.requester_id = $uid OR s.receiver_id = $uid)");

    if (!$session_query || mysqli_num_rows($session_query) == 0) {
        header("Location: ../sessions/manage_sessions.php");
        exit();
    }

    $session_context = mysqli_fetch_assoc($session_query);
    $other_id = ($session_context['requester_id'] == $uid) ? (int)$session_context['receiver_id'] : (int)$session_context['requester_id'];
}

if ($other_id == 0) {
    header("Location: ../user/dashboard.php");
    exit();
}

$other_user = mysqli_query($conn, "SELECT user_id, name, email FROM users WHERE user_id=$other_id");
if (!$other_user || mysqli_num_rows($other_user) == 0) {
    header("Location: ../user/dashboard.php");
    exit();
}
$other_user_data = mysqli_fetch_assoc($other_user);

$session_options = mysqli_query($conn, "SELECT s.session_id, s.status, s.created_at, sk.skill_name
    FROM sessions s
    JOIN skills sk ON s.skill_id = sk.skill_id
    WHERE ((s.requester_id = $uid AND s.receiver_id = $other_id)
        OR (s.requester_id = $other_id AND s.receiver_id = $uid))
    ORDER BY s.created_at DESC");

if (isset($_POST['send_msg'])) {
    $msg = mysqli_real_escape_string($conn, $_POST['message']);
    $insert = mysqli_query($conn, "INSERT INTO messages (sender_id, receiver_id, message) VALUES ($uid, $other_id, '$msg')");
    if (!$insert) {
        $error = "Error sending message. Please try again.";
    } else {
        $msg_status = "✓ Message sent successfully!";
    }
}

$chat_history = mysqli_query($conn, "SELECT m.*, u_sender.name as sender_name, u_receiver.name as receiver_name
    FROM messages m
    LEFT JOIN users u_sender ON m.sender_id = u_sender.user_id
    LEFT JOIN users u_receiver ON m.receiver_id = u_receiver.user_id
    WHERE (m.sender_id=$uid AND m.receiver_id=$other_id) OR (m.sender_id=$other_id AND m.receiver_id=$uid)
    ORDER BY m.created_at ASC");

include('../includes/header.php');
?>
<div class="container py-5 mt-5">
    <div class="col-md-8 mx-auto">
        <div class="card shadow border-0 h-100">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">
                        <i class="fas fa-comments"></i>
                        <?php echo htmlspecialchars($other_user_data['name']); ?>
                    </h5>
                    <?php if (!empty($session_context)): ?>
                        <small class="text-white-50 d-block">
                            Swap session: <?php echo htmlspecialchars($session_context['skill_name']); ?>
                            (<?php echo htmlspecialchars(ucfirst($session_context['status'])); ?>)
                        </small>
                    <?php else: ?>
                        <small class="text-white-50 d-block">
                            General chat. Pick a swap session below to keep the conversation tied to the correct exchange.
                        </small>
                    <?php endif; ?>
                </div>
                <a href="<?php echo !empty($session_context) ? '../sessions/manage_sessions.php' : '../user/dashboard.php'; ?>" class="btn btn-sm btn-light">Back</a>
            </div>

            <div class="card-body">
                <?php if (empty($session_context)): ?>
                    <div class="alert alert-info">
                        <strong>Session selector</strong><br>
                        Choose the swap session you want to discuss or rate. This keeps the message thread linked to the correct skill exchange.
                    </div>

                    <?php if ($session_options && mysqli_num_rows($session_options) > 0): ?>
                        <div class="card bg-light border-0 p-3 mb-4">
                            <h6 class="fw-bold mb-3">Swap sessions with <?php echo htmlspecialchars($other_user_data['name']); ?></h6>
                            <?php while ($session = mysqli_fetch_assoc($session_options)): ?>
                                <div class="d-flex justify-content-between align-items-center p-3 border rounded bg-white mb-2">
                                    <div>
                                        <strong><?php echo htmlspecialchars($session['skill_name']); ?></strong>
                                        <span class="badge bg-secondary ms-2"><?php echo htmlspecialchars(ucfirst($session['status'])); ?></span>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo date('M d, Y', strtotime($session['created_at'])); ?>
                                        </small>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a href="messages.php?to=<?php echo $other_id; ?>&session_id=<?php echo $session['session_id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-comments"></i> Open
                                        </a>
                                        <?php if ($session['status'] === 'completed'): ?>
                                            <a href="../sessions/review_session.php?session_id=<?php echo $session['session_id']; ?>" class="btn btn-sm btn-warning">
                                                <i class="fas fa-star"></i> Rate
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            No swap sessions found with this user yet. Start a swap from the dashboard or session manager first.
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <div class="fw-bold">Session details</div>
                            <small class="text-muted">
                                Skill: <?php echo htmlspecialchars($session_context['skill_name']); ?> |
                                Status: <?php echo htmlspecialchars(ucfirst($session_context['status'])); ?>
                            </small>
                        </div>
                        <?php if ($session_context['status'] === 'completed'): ?>
                            <a href="../sessions/review_session.php?session_id=<?php echo $session_context['session_id']; ?>" class="btn btn-warning btn-sm">
                                <i class="fas fa-star"></i> Rate this swap
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="card shadow-sm border-0 mt-3">
                    <div class="card-body overflow-auto" style="height: 400px;">
                        <?php
                        if ($chat_history && mysqli_num_rows($chat_history) > 0) {
                            while ($m = mysqli_fetch_assoc($chat_history)):
                        ?>
                                <div class="mb-3 <?php echo ($m['sender_id'] == $uid) ? 'text-end' : ''; ?>">
                                    <span class="p-2 rounded d-inline-block <?php echo ($m['sender_id'] == $uid) ? 'bg-primary text-white' : 'bg-light'; ?>">
                                        <small><?php echo htmlspecialchars($m['message']); ?></small>
                                        <br>
                                        <small class="<?php echo ($m['sender_id'] == $uid) ? 'text-white-50' : 'text-muted'; ?>">
                                            <?php echo date('h:i A', strtotime($m['created_at'])); ?>
                                        </small>
                                    </span>
                                </div>
                        <?php
                            endwhile;
                        } else {
                            echo '<div class="alert alert-info text-center">No messages yet. Start the conversation!</div>';
                        }
                        ?>
                    </div>

                    <div class="card-footer bg-white">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger alert-sm mb-2"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <?php if (!empty($msg_status)): ?>
                            <div class="alert alert-success alert-sm mb-2"><?php echo $msg_status; ?></div>
                        <?php endif; ?>

                        <form method="POST" class="d-flex gap-2">
                            <input type="text" name="message" class="form-control" placeholder="Type a message..." required autocomplete="off">
                            <button type="submit" name="send_msg" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Send
                            </button>
                        </form>
                    </div>
                </div>

                <?php if (!empty($session_context) && $session_context['status'] === 'completed'): ?>
                    <div class="mt-3 text-end">
                        <a href="../sessions/review_session.php?session_id=<?php echo $session_context['session_id']; ?>" class="btn btn-outline-warning">
                            <i class="fas fa-star"></i> Rate this completed swap
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include('../includes/footer.php'); ?>
