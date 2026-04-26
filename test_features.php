<?php
include('config/db.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    die("Please login first to test features.");
}

$user_id = $_SESSION['user_id'];
echo "<h2>Feature Test Dashboard</h2>";
echo "<hr>";

// Test 1: Check if user has any active sessions
echo "<h3>1️⃣ Active Sessions</h3>";
$active = mysqli_query($conn, "SELECT * FROM sessions WHERE (requester_id=$user_id OR receiver_id=$user_id) AND status='active'");
if (mysqli_num_rows($active) > 0) {
    echo "✅ You have active sessions: " . mysqli_num_rows($active) . "<br>";
    while ($s = mysqli_fetch_assoc($active)) {
        echo "  - Session ID: {$s['session_id']}<br>";
        echo "  - <a href='sessions/session_schedule.php?session_id={$s['session_id']}' class='btn btn-sm btn-primary'>Schedule</a> ";
        echo "  - <a href='sessions/review_session.php?session_id={$s['session_id']}' class='btn btn-sm btn-warning'>Review</a><br>";
    }
} else {
    echo "❌ No active sessions. First accept a swap request to create an active session.<br>";
}

echo "<hr>";

// Test 2: Check if user has other users to message
echo "<h3>2️⃣ Messages</h3>";
$other_users = mysqli_query($conn, "SELECT DISTINCT u.user_id, u.name FROM users u WHERE u.user_id != $user_id LIMIT 3");
if (mysqli_num_rows($other_users) > 0) {
    echo "✅ Users you can message:<br>";
    while ($u = mysqli_fetch_assoc($other_users)) {
        echo "  - <a href='user/messages.php?to={$u['user_id']}' class='btn btn-sm btn-info'>Message {$u['name']}</a><br>";
    }
} else {
    echo "❌ No other users found.<br>";
}

echo "<hr>";

// Test 3: Check sessions that need scheduling
echo "<h3>3️⃣ Sessions Needing Schedule</h3>";
$pending_schedule = mysqli_query($conn, "SELECT s.session_id, u.name, sk.skill_name FROM sessions s 
    JOIN users u ON CASE WHEN s.requester_id=$user_id THEN s.receiver_id ELSE s.requester_id END = u.user_id
    JOIN skills sk ON s.skill_id=sk.skill_id
    WHERE s.status='active' AND (s.requester_id=$user_id OR s.receiver_id=$user_id)");

if (mysqli_num_rows($pending_schedule) > 0) {
    echo "✅ Sessions to schedule:<br>";
    while ($p = mysqli_fetch_assoc($pending_schedule)) {
        echo "  - {$p['name']} - {$p['skill_name']} ";
        echo "  <a href='sessions/session_schedule.php?session_id={$p['session_id']}' class='btn btn-sm btn-primary'>Schedule Now</a><br>";
    }
} else {
    echo "❌ No active sessions to schedule.<br>";
}

echo "<hr>";

// Test 4: Check sessions ready for review
echo "<h3>4️⃣ Sessions Needing Reviews</h3>";
$ready_review = mysqli_query($conn, "SELECT s.session_id, u.name, sk.skill_name FROM sessions s 
    JOIN users u ON CASE WHEN s.requester_id=$user_id THEN s.receiver_id ELSE s.requester_id END = u.user_id
    JOIN skills sk ON s.skill_id=sk.skill_id
    WHERE s.status='completed' AND (s.requester_id=$user_id OR s.receiver_id=$user_id)
    AND NOT EXISTS (SELECT 1 FROM reviews WHERE session_id=s.session_id AND reviewer_id=$user_id)");

if (mysqli_num_rows($ready_review) > 0) {
    echo "✅ Sessions ready for review:<br>";
    while ($r = mysqli_fetch_assoc($ready_review)) {
        echo "  - {$r['name']} - {$r['skill_name']} ";
        echo "  <a href='sessions/review_session.php?session_id={$r['session_id']}' class='btn btn-sm btn-warning'>Review Now</a><br>";
    }
} else {
    echo "❌ No completed sessions to review. Complete active sessions first.<br>";
}

echo "<hr>";

// Test 5: Database connectivity
echo "<h3>5️⃣ Database Status</h3>";
$tables_check = mysqli_query($conn, "SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema='skillswap_db'");
$count = mysqli_fetch_assoc($tables_check);
echo "✅ Database tables: " . $count['count'] . "<br>";

// Check key tables
$key_tables = ['users', 'skills', 'user_skills', 'sessions', 'session_schedules', 'reviews', 'messages', 'skill_categories'];
foreach ($key_tables as $table) {
    $check = mysqli_query($conn, "SELECT 1 FROM $table LIMIT 1");
    $status = ($check) ? "✅" : "❌";
    echo "$status Table: $table<br>";
}

echo "<hr>";
echo "<a href='user/dashboard.php' class='btn btn-secondary'>Back to Dashboard</a>";
?>

<style>
    body {
        font-family: Arial;
        padding: 20px;
    }

    h2 {
        color: #333;
    }

    h3 {
        color: #666;
        margin-top: 20px;
    }

    .btn {
        display: inline-block;
        padding: 8px 12px;
        margin: 5px 0;
        text-decoration: none;
        border-radius: 4px;
        color: white;
    }

    .btn-primary {
        background-color: #007bff;
    }

    .btn-warning {
        background-color: #ffc107;
    }

    .btn-info {
        background-color: #17a2b8;
    }

    .btn-secondary {
        background-color: #6c757d;
    }

    .btn:hover {
        opacity: 0.8;
    }
</style>