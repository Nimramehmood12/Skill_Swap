/*
 ============================================
 ADVANCED SQL QUERIES FOR SKILLSWAP
 ============================================
 
 1. JOIN QUERIES (Multiple tables)
 2. AGGREGATE FUNCTIONS (COUNT, AVG, SUM, MAX)
 3. SUBQUERIES (Nested SELECT)
 4. TRANSACTIONS (Multi-step operations)
 
 ============================================
*/

/*
 ============================================
 SECTION 1: QUERY BY USER ROLE
 ============================================
*/

-- 👤 USER QUERY: Get all my skills and matching teachers
SELECT 
    u.name as teacher_name,
    s.skill_name,
    sc.category_name,
    ROUND(AVG(r.rating), 2) as teacher_rating,
    COUNT(DISTINCT sess.session_id) as completed_sessions
FROM users u
JOIN user_skills us ON u.user_id = us.user_id
JOIN skills s ON us.skill_id = s.skill_id
JOIN skill_categories sc ON s.category_id = sc.category_id
LEFT JOIN sessions sess ON u.user_id = sess.receiver_id AND sess.status = 'completed'
LEFT JOIN reviews r ON sess.session_id = r.session_id
WHERE s.status = 'approved' AND us.type = 'teach'
    AND s.skill_id IN (
        SELECT skill_id FROM skills 
        WHERE skill_id IN (
            SELECT sk.skill_id FROM skills sk
            JOIN user_skills us2 ON sk.skill_id = us2.skill_id
            WHERE us2.user_id = 1 AND us2.type = 'learn'
        )
    )
GROUP BY u.user_id, u.name, s.skill_name, sc.category_name
ORDER BY teacher_rating DESC;

-- 👤 USER QUERY: Get my schedule for upcoming sessions
SELECT 
    s.session_id,
    CASE 
        WHEN s.requester_id = 1 THEN u_receiver.name
        ELSE u_requester.name
    END as partner_name,
    sk.skill_name,
    ss.proposed_date,
    ss.proposed_time,
    ss.status,
    s.status as session_status
FROM sessions s
JOIN users u_requester ON s.requester_id = u_requester.user_id
JOIN users u_receiver ON s.receiver_id = u_receiver.user_id
JOIN skills sk ON s.skill_id = sk.skill_id
LEFT JOIN session_schedules ss ON s.session_id = ss.session_id
WHERE (s.requester_id = 1 OR s.receiver_id = 1)
    AND (s.status = 'active' OR s.status = 'pending')
    AND (ss.proposed_date IS NULL OR ss.proposed_date >= CURDATE())
ORDER BY COALESCE(ss.proposed_date, s.created_at) ASC;

-- 🔍 MODERATOR QUERY: Pending skills to approve
SELECT 
    us.user_skill_id,
    u.name as user_name,
    s.skill_name,
    sc.category_name,
    us.type,
    us.created_at,
    us.status
FROM user_skills us
JOIN users u ON us.user_id = u.user_id
JOIN skills s ON us.skill_id = s.skill_id
JOIN skill_categories sc ON s.category_id = sc.category_id
WHERE us.status = 'pending' OR s.status = 'pending'
ORDER BY us.created_at ASC;

-- 👨‍💼 ADMIN QUERY: System Analytics
SELECT 
    'Total Users' as metric, COUNT(DISTINCT user_id) as value
FROM users
UNION ALL
SELECT 'Active Sessions', COUNT(*) FROM sessions WHERE status = 'active'
UNION ALL
SELECT 'Completed Sessions', COUNT(*) FROM sessions WHERE status = 'completed'
UNION ALL
SELECT 'Total Reviews', COUNT(*) FROM reviews
UNION ALL
SELECT 'Pending Skills', COUNT(*) FROM user_skills WHERE status = 'pending'
UNION ALL
SELECT 'Avg Rating', ROUND(AVG(rating), 2) FROM reviews;

/*
 ============================================
 SECTION 2: AGGREGATE FUNCTIONS
 ============================================
*/

-- Teacher Performance Metrics
SELECT 
    u.user_id,
    u.name,
    COUNT(DISTINCT s.session_id) as total_sessions_completed,
    AVG(r.rating) as average_rating,
    MAX(r.rating) as highest_rating,
    MIN(r.rating) as lowest_rating,
    COUNT(DISTINCT r.review_id) as total_reviews
FROM users u
LEFT JOIN sessions s ON u.user_id = s.receiver_id AND s.status = 'completed'
LEFT JOIN reviews r ON s.session_id = r.session_id
GROUP BY u.user_id, u.name
HAVING COUNT(DISTINCT s.session_id) > 0
ORDER BY average_rating DESC;

-- Skills by Category - Popularity
SELECT 
    sc.category_name,
    COUNT(DISTINCT s.skill_id) as total_skills,
    COUNT(DISTINCT us.user_id) as teachers,
    ROUND(AVG(r.rating), 2) as avg_category_rating
FROM skill_categories sc
LEFT JOIN skills s ON sc.category_id = s.category_id
LEFT JOIN user_skills us ON s.skill_id = us.skill_id AND us.type = 'teach'
LEFT JOIN sessions sess ON s.skill_id = sess.skill_id
LEFT JOIN reviews r ON sess.session_id = r.session_id
GROUP BY sc.category_id, sc.category_name
ORDER BY teachers DESC;

/*
 ============================================
 SECTION 3: SUBQUERIES
 ============================================
*/

-- Users with above-average ratings
SELECT 
    u.name,
    ROUND(AVG(r.rating), 2) as rating
FROM users u
JOIN reviews r ON u.user_id = r.receiver_id
GROUP BY u.user_id, u.name
HAVING AVG(r.rating) > (
    SELECT AVG(rating) FROM reviews
);

-- Skills taught by top-rated teachers (rating > 4)
SELECT DISTINCT
    s.skill_name,
    u.name as teacher_name
FROM skills s
JOIN user_skills us ON s.skill_id = us.skill_id
JOIN users u ON us.user_id = u.user_id
WHERE u.user_id IN (
    SELECT receiver_id FROM reviews
    GROUP BY receiver_id
    HAVING AVG(rating) > 4
)
AND us.type = 'teach';

-- Users with no sessions yet
SELECT 
    u.name,
    u.email
FROM users u
WHERE u.user_id NOT IN (
    SELECT DISTINCT requester_id FROM sessions
    UNION
    SELECT DISTINCT receiver_id FROM sessions
)
AND u.role = 'user';

/*
 ============================================
 SECTION 4: TRANSACTIONS
 ============================================
*/

/*
TRANSACTION 1: Request a Skill Swap
- Insert session
- Check if skill is available
- Rollback if teacher is unavailable
*/

DELIMITER $$

CREATE PROCEDURE RequestSkillSwap(
    IN p_requester_id INT,
    IN p_receiver_id INT,
    IN p_skill_id INT,
    OUT p_session_id INT,
    OUT p_success BOOLEAN
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_success = FALSE;
    END;

    START TRANSACTION;

    -- Check if receiver teaches this skill
    IF NOT EXISTS (
        SELECT 1 FROM user_skills
        WHERE user_id = p_receiver_id 
        AND skill_id = p_skill_id
        AND type = 'teach'
        AND status = 'approved'
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Teacher does not offer this skill';
    END IF;

    -- Check if requester wants to learn this skill
    IF NOT EXISTS (
        SELECT 1 FROM user_skills
        WHERE user_id = p_requester_id
        AND skill_id = p_skill_id
        AND type = 'learn'
    ) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Skill not in learning list';
    END IF;

    -- Insert session
    INSERT INTO sessions (requester_id, receiver_id, skill_id, status)
    VALUES (p_requester_id, p_receiver_id, p_skill_id, 'pending');

    SET p_session_id = LAST_INSERT_ID();
    COMMIT;
    SET p_success = TRUE;
END$$

DELIMITER ;

/*
TRANSACTION 2: Complete Session & Add Review
- Update session to completed
- Insert review
- Award points
- Rollback if any step fails
*/

DELIMITER $$

CREATE PROCEDURE CompleteSessionWithReview(
    IN p_session_id INT,
    IN p_reviewer_id INT,
    IN p_rating INT,
    IN p_comment TEXT,
    OUT p_success BOOLEAN
)
BEGIN
    DECLARE v_receiver_id INT;
    DECLARE v_requester_id INT;
    DECLARE v_skill_id INT;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_success = FALSE;
    END;

    START TRANSACTION;

    -- Get session details
    SELECT receiver_id, requester_id, skill_id INTO v_receiver_id, v_requester_id, v_skill_id
    FROM sessions
    WHERE session_id = p_session_id;

    -- Validate rating
    IF p_rating < 1 OR p_rating > 5 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Rating must be between 1 and 5';
    END IF;

    -- Update session status to completed
    UPDATE sessions SET status = 'completed' WHERE session_id = p_session_id;

    -- Insert review
    INSERT INTO reviews (session_id, reviewer_id, receiver_id, rating, comment)
    VALUES (p_session_id, p_reviewer_id, 
            IF(p_reviewer_id = v_requester_id, v_receiver_id, v_requester_id),
            p_rating, p_comment);

    -- Award points to both users
    UPDATE users SET points = points + 10 WHERE user_id = v_requester_id;
    UPDATE users SET points = points + 10 WHERE user_id = v_receiver_id;

    -- Remove the skill from the requester's learning list
    DELETE FROM user_skills WHERE user_id = v_requester_id AND skill_id = v_skill_id AND type = 'learn';

    COMMIT;
    SET p_success = TRUE;
END$$

DELIMITER ;

/*
TRANSACTION 3: Schedule Session & Agree on Time
- Insert proposed schedule
- Update to agreed when both confirm
- Automatic status update
*/

DELIMITER $$

CREATE PROCEDURE ProposeSessionTime(
    IN p_session_id INT,
    IN p_proposed_by_id INT,
    IN p_date DATE,
    IN p_time TIME,
    OUT p_success BOOLEAN
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_success = FALSE;
    END;

    START TRANSACTION;

    -- Check date is in future
    IF p_date < CURDATE() THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cannot propose past dates';
    END IF;

    -- Insert proposed time
    INSERT INTO session_schedules (session_id, proposed_by_id, proposed_date, proposed_time, status)
    VALUES (p_session_id, p_proposed_by_id, p_date, p_time, 'proposed');

    COMMIT;
    SET p_success = TRUE;
END$$

DELIMITER ;

/*
TRANSACTION 4: Cleanup - Delete Completed Sessions After Reviews
- Only delete if session is completed and has review
- Check before deletion
*/

DELIMITER $$

CREATE PROCEDURE CleanupCompletedSessions()
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
    END;

    START TRANSACTION;

    DELETE FROM sessions
    WHERE session_id IN (
        SELECT DISTINCT s.session_id
        FROM sessions s
        JOIN reviews r ON s.session_id = r.session_id
        WHERE s.status = 'completed'
        AND DATE_ADD(s.updated_at, INTERVAL 30 DAY) < NOW()
    );

    COMMIT;
END$$

DELIMITER ;
