<?php
/*
 ============================================
 SKILLSWAP DATABASE OPERATIONS
 Backend CRUD & Transaction Functions
 ============================================
*/

include('config/db.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
 ============================================
 SECTION 1: CATEGORY FUNCTIONS
 ============================================
*/

function getAllCategories()
{
    global $conn;
    $query = "SELECT * FROM skill_categories ORDER BY category_name";
    return mysqli_query($conn, $query);
}

function getCategoryById($category_id)
{
    global $conn;
    $category_id = intval($category_id);
    $query = "SELECT * FROM skill_categories WHERE category_id = $category_id";
    return mysqli_fetch_assoc(mysqli_query($conn, $query));
}

function addCategory($category_name, $description = '')
{
    global $conn;
    $category_name = mysqli_real_escape_string($conn, $category_name);
    $description = mysqli_real_escape_string($conn, $description);

    $query = "INSERT INTO skill_categories (category_name, description) 
              VALUES ('$category_name', '$description')";

    if (mysqli_query($conn, $query)) {
        return array('success' => true, 'message' => 'Category added');
    } else {
        return array('success' => false, 'message' => mysqli_error($conn));
    }
}

/*
 ============================================
 SECTION 2: SKILL FUNCTIONS
 ============================================
*/

function addSkill($skill_name, $category_id)
{
    global $conn;
    $skill_name = mysqli_real_escape_string($conn, $skill_name);
    $category_id = intval($category_id);

    $query = "INSERT INTO skills (skill_name, category_id, status) 
              VALUES ('$skill_name', $category_id, 'approved')";

    if (mysqli_query($conn, $query)) {
        return array('success' => true, 'skill_id' => mysqli_insert_id($conn));
    } else {
        return array('success' => false, 'message' => mysqli_error($conn));
    }
}

function getSkillsByCategory($category_id)
{
    global $conn;
    $category_id = intval($category_id);

    $query = "SELECT s.*, sc.category_name 
              FROM skills s
              JOIN skill_categories sc ON s.category_id = sc.category_id
              WHERE s.category_id = $category_id
              ORDER BY s.skill_name";

    return mysqli_query($conn, $query);
}

function getApprovedSkillsByCategory($category_id)
{
    global $conn;
    $category_id = intval($category_id);

    $query = "SELECT s.skill_id, s.skill_name, sc.category_name,
                     COUNT(us.user_id) as teacher_count,
                     ROUND(AVG(r.rating), 2) as avg_rating
              FROM skills s
              JOIN skill_categories sc ON s.category_id = sc.category_id
              LEFT JOIN user_skills us ON s.skill_id = us.skill_id AND us.type = 'teach'
              LEFT JOIN sessions sess ON s.skill_id = sess.skill_id
              LEFT JOIN reviews r ON sess.session_id = r.session_id
              WHERE s.category_id = $category_id
              GROUP BY s.skill_id
              ORDER BY teacher_count DESC";

    return mysqli_query($conn, $query);
}

/*
 ============================================
 SECTION 3: USER SKILL FUNCTIONS
 ============================================
*/

function addUserSkill($user_id, $skill_id, $type)
{
    global $conn;

    $user_id = intval($user_id);
    $skill_id = intval($skill_id);
    $type = mysqli_real_escape_string($conn, $type);

    $query = "INSERT INTO user_skills (user_id, skill_id, type, status) 
              VALUES ($user_id, $skill_id, '$type', 'approved')";

    if (mysqli_query($conn, $query)) {
        return array('success' => true);
    } else {
        return array('success' => false, 'message' => mysqli_error($conn));
    }
}

function getUserSkills($user_id)
{
    global $conn;
    $user_id = intval($user_id);

    $query = "SELECT us.*, s.skill_name, sc.category_name
              FROM user_skills us
              JOIN skills s ON us.skill_id = s.skill_id
              JOIN skill_categories sc ON s.category_id = sc.category_id
              WHERE us.user_id = $user_id
              ORDER BY us.type, s.skill_name";

    return mysqli_query($conn, $query);
}

function approveUserSkill($user_skill_id)
{
    global $conn;
    $user_skill_id = intval($user_skill_id);

    $query = "UPDATE user_skills SET status = 'approved' WHERE user_skill_id = $user_skill_id";

    return mysqli_query($conn, $query);
}

function deleteUserSkill($user_skill_id)
{
    global $conn;
    $user_skill_id = intval($user_skill_id);

    $query = "DELETE FROM user_skills WHERE user_skill_id = $user_skill_id";

    return mysqli_query($conn, $query);
}

/*
 ============================================
 SECTION 4: SESSION FUNCTIONS
 ============================================
*/

function getTeachersForSkill($skill_id)
{
    global $conn;
    $skill_id = intval($skill_id);

    $query = "SELECT DISTINCT
                u.user_id,
                u.name,
                ROUND(AVG(r.rating), 2) as avg_rating,
                COUNT(DISTINCT sess.session_id) as completed_sessions
              FROM users u
              JOIN user_skills us ON u.user_id = us.user_id
              JOIN skills s ON us.skill_id = s.skill_id
              LEFT JOIN sessions sess ON u.user_id = sess.receiver_id AND sess.status = 'completed'
              LEFT JOIN reviews r ON sess.session_id = r.session_id
              WHERE s.skill_id = $skill_id 
              AND us.type = 'teach'
              GROUP BY u.user_id, u.name
              ORDER BY avg_rating DESC";

    return mysqli_query($conn, $query);
}

function requestSkillSwap($requester_id, $receiver_id, $skill_id)
{
    global $conn;

    $requester_id = intval($requester_id);
    $receiver_id = intval($receiver_id);
    $skill_id = intval($skill_id);

    // Start transaction
    mysqli_begin_transaction($conn);

    try {
        // Verify receiver teaches this skill
        $check_teach = mysqli_query(
            $conn,
            "SELECT * FROM user_skills 
             WHERE user_id = $receiver_id AND skill_id = $skill_id AND type = 'teach'"
        );

        if (!$check_teach || mysqli_num_rows($check_teach) == 0) {
            throw new Exception("Teacher does not offer this skill");
        }

        // Verify requester wants to learn this skill
        $check_learn = mysqli_query(
            $conn,
            "SELECT * FROM user_skills 
             WHERE user_id = $requester_id AND skill_id = $skill_id AND type = 'learn'"
        );

        if (!$check_learn || mysqli_num_rows($check_learn) == 0) {
            throw new Exception("Skill not in your learning list");
        }

        // Create session
        $insert = mysqli_query(
            $conn,
            "INSERT INTO sessions (requester_id, receiver_id, skill_id, status) 
             VALUES ($requester_id, $receiver_id, $skill_id, 'pending')"
        );

        if (!$insert) {
            throw new Exception(mysqli_error($conn));
        }

        $session_id = mysqli_insert_id($conn);

        mysqli_commit($conn);

        return array('success' => true, 'session_id' => $session_id);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        return array('success' => false, 'message' => $e->getMessage());
    }
}

function getUserSessions($user_id, $type = 'all')
{
    global $conn;
    $user_id = intval($user_id);

    $status_filter = "";
    if ($type == 'pending') {
        $status_filter = "AND s.status = 'pending'";
    } elseif ($type == 'active') {
        $status_filter = "AND s.status = 'active'";
    } elseif ($type == 'completed') {
        $status_filter = "AND s.status = 'completed'";
    }

    $query = "SELECT s.*, 
                     u_req.name as requester_name,
                     u_rec.name as receiver_name,
                     sk.skill_name,
                     sc.category_name
              FROM sessions s
              JOIN users u_req ON s.requester_id = u_req.user_id
              JOIN users u_rec ON s.receiver_id = u_rec.user_id
              JOIN skills sk ON s.skill_id = sk.skill_id
              JOIN skill_categories sc ON sk.category_id = sc.category_id
              WHERE (s.requester_id = $user_id OR s.receiver_id = $user_id)
              $status_filter
              ORDER BY s.created_at DESC";

    return mysqli_query($conn, $query);
}

/*
 ============================================
 SECTION 5: SESSION SCHEDULE FUNCTIONS
 ============================================
*/

function proposeSessionTime($session_id, $proposed_by_id, $proposed_date, $proposed_time)
{
    global $conn;

    $session_id = intval($session_id);
    $proposed_by_id = intval($proposed_by_id);
    $proposed_date = mysqli_real_escape_string($conn, $proposed_date);
    $proposed_time = mysqli_real_escape_string($conn, $proposed_time);

    // Validate date is future
    if ($proposed_date < date('Y-m-d')) {
        return array('success' => false, 'message' => 'Cannot propose past dates');
    }

    mysqli_begin_transaction($conn);

    try {
        $insert = mysqli_query(
            $conn,
            "INSERT INTO session_schedules (session_id, proposed_by_id, proposed_date, proposed_time, status)
             VALUES ($session_id, $proposed_by_id, '$proposed_date', '$proposed_time', 'proposed')"
        );

        if (!$insert) {
            throw new Exception(mysqli_error($conn));
        }

        mysqli_commit($conn);

        return array('success' => true);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        return array('success' => false, 'message' => $e->getMessage());
    }
}

function getSessionSchedules($session_id)
{
    global $conn;
    $session_id = intval($session_id);

    $query = "SELECT ss.*, u.name as proposed_by_name
              FROM session_schedules ss
              JOIN users u ON ss.proposed_by_id = u.user_id
              WHERE ss.session_id = $session_id
              ORDER BY ss.created_at DESC";

    return mysqli_query($conn, $query);
}

/*
 ============================================
 SECTION 6: REVIEW FUNCTIONS
 ============================================
*/

function addReview($session_id, $reviewer_id, $rating, $comment = '')
{
    global $conn;

    $session_id = intval($session_id);
    $reviewer_id = intval($reviewer_id);
    $rating = intval($rating);
    $comment = mysqli_real_escape_string($conn, $comment);

    // Validate rating
    if ($rating < 1 || $rating > 5) {
        return array('success' => false, 'message' => 'Rating must be between 1 and 5');
    }

    mysqli_begin_transaction($conn);

    try {
        // Check if reviewer already left a review for this session
        $check_review = mysqli_query($conn, "SELECT review_id FROM reviews WHERE session_id = $session_id AND reviewer_id = $reviewer_id");
        if (mysqli_num_rows($check_review) > 0) {
            throw new Exception("You have already reviewed this session.");
        }

        // Get session details
        $sess = mysqli_fetch_assoc(mysqli_query(
            $conn,
            "SELECT requester_id, receiver_id, skill_id FROM sessions WHERE session_id = $session_id"
        ));

        if (!$sess) {
            throw new Exception("Session not found");
        }

        // Determine receiver
        $receiver_id = ($reviewer_id == $sess['requester_id']) ? $sess['receiver_id'] : $sess['requester_id'];

        // Insert review
        $insert = mysqli_query(
            $conn,
            "INSERT INTO reviews (session_id, reviewer_id, receiver_id, rating, comment)
             VALUES ($session_id, $reviewer_id, $receiver_id, $rating, '$comment')"
        );

        if (!$insert) {
            throw new Exception(mysqli_error($conn));
        }

        // Update session to completed
        mysqli_query($conn, "UPDATE sessions SET status = 'completed' WHERE session_id = $session_id");

        // Award points to both users
        mysqli_query($conn, "UPDATE users SET points = points + 10 WHERE user_id = " . $sess['requester_id']);
        mysqli_query($conn, "UPDATE users SET points = points + 10 WHERE user_id = " . $sess['receiver_id']);

        // Remove the learned skill from the requester's "learn" list
        mysqli_query($conn, "DELETE FROM user_skills WHERE user_id = " . $sess['requester_id'] . " AND skill_id = " . $sess['skill_id'] . " AND type = 'learn'");

        mysqli_commit($conn);

        return array('success' => true);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        return array('success' => false, 'message' => $e->getMessage());
    }
}

function getUserRating($user_id)
{
    global $conn;
    $user_id = intval($user_id);

    $query = "SELECT 
                ROUND(AVG(rating), 2) as avg_rating,
                COUNT(*) as total_reviews,
                MAX(rating) as highest,
                MIN(rating) as lowest
              FROM reviews
              WHERE receiver_id = $user_id";

    return mysqli_fetch_assoc(mysqli_query($conn, $query));
}

function getSessionReviews($session_id)
{
    global $conn;
    $session_id = intval($session_id);

    $query = "SELECT r.*, u.name as reviewer_name
              FROM reviews r
              JOIN users u ON r.reviewer_id = u.user_id
              WHERE r.session_id = $session_id";

    return mysqli_query($conn, $query);
}

/*
 ============================================
 SECTION 7: MESSAGE FUNCTIONS
 ============================================
*/

function sendMessage($sender_id, $receiver_id, $message)
{
    global $conn;

    $sender_id = intval($sender_id);
    $receiver_id = intval($receiver_id);
    $message = mysqli_real_escape_string($conn, $message);

    $query = "INSERT INTO messages (sender_id, receiver_id, message)
              VALUES ($sender_id, $receiver_id, '$message')";

    if (mysqli_query($conn, $query)) {
        return array('success' => true);
    } else {
        return array('success' => false, 'message' => mysqli_error($conn));
    }
}

function getChatHistory($user_id, $other_user_id)
{
    global $conn;

    $user_id = intval($user_id);
    $other_user_id = intval($other_user_id);

    $query = "SELECT m.*, 
                     u_sender.name as sender_name,
                     u_receiver.name as receiver_name
              FROM messages m
              JOIN users u_sender ON m.sender_id = u_sender.user_id
              JOIN users u_receiver ON m.receiver_id = u_receiver.user_id
              WHERE (m.sender_id = $user_id AND m.receiver_id = $other_user_id)
              OR (m.sender_id = $other_user_id AND m.receiver_id = $user_id)
              ORDER BY m.created_at ASC";

    return mysqli_query($conn, $query);
}

/*
 ============================================
 SECTION 8: ANALYTICS FUNCTIONS
 ============================================
*/

function getTeacherPerformance($user_id)
{
    global $conn;
    $user_id = intval($user_id);

    $query = "SELECT 
                COUNT(DISTINCT s.session_id) as total_completed,
                ROUND(AVG(r.rating), 2) as avg_rating,
                COUNT(DISTINCT r.review_id) as total_reviews
              FROM users u
              LEFT JOIN sessions s ON u.user_id = s.receiver_id AND s.status = 'completed'
              LEFT JOIN reviews r ON s.session_id = r.session_id
              WHERE u.user_id = $user_id";

    return mysqli_fetch_assoc(mysqli_query($conn, $query));
}

function getSkillsPopularity()
{
    global $conn;

    $query = "SELECT 
                sc.category_name,
                COUNT(DISTINCT s.skill_id) as skill_count,
                COUNT(DISTINCT us.user_id) as teacher_count,
                ROUND(AVG(r.rating), 2) as avg_rating
              FROM skill_categories sc
              LEFT JOIN skills s ON sc.category_id = s.category_id
              LEFT JOIN user_skills us ON s.skill_id = us.skill_id AND us.type = 'teach'
              LEFT JOIN sessions sess ON s.skill_id = sess.skill_id
              LEFT JOIN reviews r ON sess.session_id = r.session_id
              GROUP BY sc.category_id, sc.category_name
              ORDER BY teacher_count DESC";

    return mysqli_query($conn, $query);
}
