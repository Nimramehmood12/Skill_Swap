<?php
// 1. Check session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('../config/db.php');
include('../database_functions.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if (!isset($_GET['rid']) || empty($_GET['rid'])) {
    header("Location: ../index.php");
    exit();
}

$requester_id = $_SESSION['user_id'];
$receiver_id = intval($_GET['rid']);
$selected_skill_id = isset($_GET['skill_id']) ? intval($_GET['skill_id']) : 0;

// Prevent requesting a swap with yourself
if ($requester_id === $receiver_id) {
    header("Location: ../index.php?msg=self_request");
    exit();
}

// Verify receiver exists
$receiver_check = mysqli_query($conn, "SELECT user_id FROM users WHERE user_id='$receiver_id'");
if (!$receiver_check || mysqli_num_rows($receiver_check) == 0) {
    header("Location: ../index.php?msg=user_not_found");
    exit();
}

// Use the selected skill from the dashboard when available.
// Fallback to the receiver's first approved teaching skill if no skill was passed.
$skill_id = 0;

if ($selected_skill_id > 0) {
    $skill_check = mysqli_query($conn, "SELECT s.skill_id FROM skills s
        JOIN user_skills us ON s.skill_id = us.skill_id
        WHERE s.skill_id='$selected_skill_id'
        AND us.user_id='$receiver_id'
        AND us.type='teach'
        LIMIT 1");

    if ($skill_check && mysqli_num_rows($skill_check) > 0) {
        $skill = mysqli_fetch_assoc($skill_check);
        $skill_id = (int)$skill['skill_id'];
    }
}

if ($skill_id == 0) {
    $get_skill = mysqli_query(
        $conn,
        "SELECT DISTINCT s.skill_id FROM user_skills us
         JOIN skills s ON us.skill_id = s.skill_id
         WHERE us.user_id='$receiver_id' AND us.type='teach' LIMIT 1"
    );

    if ($get_skill && mysqli_num_rows($get_skill) > 0) {
        $skill = mysqli_fetch_assoc($get_skill);
        $skill_id = (int)$skill['skill_id'];
    }
}

if ($skill_id > 0) {
    // Check if request already exists
    $check = mysqli_query($conn, "SELECT * FROM sessions WHERE requester_id='$requester_id' AND receiver_id='$receiver_id' AND skill_id='$skill_id' AND status='pending'");
    $check_active = mysqli_query($conn, "SELECT * FROM sessions WHERE requester_id='$requester_id' AND receiver_id='$receiver_id' AND skill_id='$skill_id' AND status='active'");

    if (mysqli_num_rows($check) == 0) {
        if (mysqli_num_rows($check_active) > 0) {
            header("Location: manage_sessions.php?msg=already_active");
            exit();
        }
        $sql = "INSERT INTO sessions (requester_id, receiver_id, skill_id, status) VALUES ('$requester_id', '$receiver_id', '$skill_id', 'pending')";
        if (mysqli_query($conn, $sql)) {
            header("Location: manage_sessions.php?msg=sent");
            exit();
        } else {
            header("Location: ../index.php?msg=error");
            exit();
        }
    } else {
        header("Location: manage_sessions.php?msg=already_exists");
        exit();
    }
} else {
    header("Location: ../index.php?msg=no_skill");
    exit();
}
