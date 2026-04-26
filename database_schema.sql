/*
 ============================================
 SKILLSWAP DATABASE SCHEMA - BCNF NORMALIZED
 ============================================
 
 Entities (BCNF):
 1. User - stores user profile
 2. Category - skill categories
 3. Skill - skills with category reference
 4. UserSkill - M:N relationship (user offers/learns skills)
 5. Session - skill swap requests
 6. SessionSchedule - proposed times for sessions
 7. Review - feedback with ratings
 8. Message - messages between users
 
 ============================================
 STEP 1: DROP EXISTING TABLES (if needed)
 ============================================
*/

DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS session_schedules;
DROP TABLE IF EXISTS sessions;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS user_skills;
DROP TABLE IF EXISTS skills;
DROP TABLE IF EXISTS skill_categories;
DROP TABLE IF EXISTS users;

/*
 ============================================
 STEP 2: CREATE NORMALIZED TABLES (BCNF)
 ============================================
*/

-- ✅ TABLE 1: User
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    bio TEXT,
    points INT DEFAULT 0,
    role ENUM('user', 'moderator', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ✅ TABLE 2: Category (Skill Categories)
CREATE TABLE skill_categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(50) UNIQUE NOT NULL,
    description VARCHAR(200)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ✅ TABLE 3: Skill (BCNF: no transitive dependency on category)
CREATE TABLE skills (
    skill_id INT PRIMARY KEY AUTO_INCREMENT,
    skill_name VARCHAR(100) NOT NULL,
    category_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES skill_categories(category_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ✅ TABLE 4: UserSkill (M:N relationship - user & skill)
CREATE TABLE user_skills (
    user_skill_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    skill_id INT NOT NULL,
    type ENUM('teach', 'learn') NOT NULL,
    status ENUM('pending', 'approved') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills(skill_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_skill (user_id, skill_id, type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ✅ TABLE 5: Session (Skill swap request)
CREATE TABLE sessions (
    session_id INT PRIMARY KEY AUTO_INCREMENT,
    requester_id INT NOT NULL,
    receiver_id INT NOT NULL,
    skill_id INT NOT NULL,
    status ENUM('pending', 'active', 'completed', 'declined', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (requester_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills(skill_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ✅ TABLE 6: SessionSchedule (Proposed times for sessions)
CREATE TABLE session_schedules (
    schedule_id INT PRIMARY KEY AUTO_INCREMENT,
    session_id INT NOT NULL,
    proposed_by_id INT NOT NULL,
    proposed_date DATE NOT NULL,
    proposed_time TIME NOT NULL,
    status ENUM('proposed', 'agreed', 'completed', 'cancelled') DEFAULT 'proposed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES sessions(session_id) ON DELETE CASCADE,
    FOREIGN KEY (proposed_by_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ✅ TABLE 7: Review (Feedback with star ratings)
CREATE TABLE reviews (
    review_id INT PRIMARY KEY AUTO_INCREMENT,
    session_id INT NOT NULL,
    reviewer_id INT NOT NULL,
    receiver_id INT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES sessions(session_id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_session_review (session_id, reviewer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ✅ TABLE 8: Message (Chat messages)
CREATE TABLE messages (
    message_id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/*
 ============================================
 STEP 3: CREATE INDEXES (Performance)
 ============================================
*/

CREATE INDEX idx_user_email ON users(email);
CREATE INDEX idx_skill_category ON skills(category_id);
CREATE INDEX idx_user_skill_user ON user_skills(user_id);
CREATE INDEX idx_user_skill_skill ON user_skills(skill_id);
CREATE INDEX idx_session_requester ON sessions(requester_id);
CREATE INDEX idx_session_receiver ON sessions(receiver_id);
CREATE INDEX idx_session_skill ON sessions(skill_id);
CREATE INDEX idx_schedule_session ON session_schedules(session_id);
CREATE INDEX idx_review_session ON reviews(session_id);
CREATE INDEX idx_message_sender ON messages(sender_id);
CREATE INDEX idx_message_receiver ON messages(receiver_id);

/*
 ============================================
 STEP 4: CREATE VIEWS (For Common Queries)
 ============================================
*/

-- ✅ VIEW 1: Active Approved Skills by Category
CREATE VIEW active_skills AS
SELECT 
    s.skill_id,
    s.skill_name,
    sc.category_name,
    COUNT(us.user_id) as total_teachers
FROM skills s
JOIN skill_categories sc ON s.category_id = sc.category_id
LEFT JOIN user_skills us ON s.skill_id = us.skill_id AND us.type = 'teach' AND us.status = 'approved'
WHERE s.status = 'approved'
GROUP BY s.skill_id, s.skill_name, sc.category_name;

-- ✅ VIEW 2: User Profile with Skills & Ratings
CREATE VIEW user_profile_view AS
SELECT 
    u.user_id,
    u.name,
    u.email,
    u.bio,
    u.points,
    u.role,
    GROUP_CONCAT(DISTINCT CASE WHEN us.type = 'teach' THEN s.skill_name END) as teaches,
    GROUP_CONCAT(DISTINCT CASE WHEN us.type = 'learn' THEN s.skill_name END) as learns,
    ROUND(AVG(r.rating), 2) as avg_rating,
    COUNT(DISTINCT r.review_id) as review_count
FROM users u
LEFT JOIN user_skills us ON u.user_id = us.user_id
LEFT JOIN skills s ON us.skill_id = s.skill_id
LEFT JOIN reviews r ON u.user_id = r.receiver_id
GROUP BY u.user_id, u.name, u.email, u.bio, u.points, u.role;

-- ✅ VIEW 3: Available Sessions for Booking
CREATE VIEW available_sessions AS
SELECT 
    s.session_id,
    u_requester.name as requester_name,
    u_receiver.name as receiver_name,
    sk.skill_name,
    sc.category_name,
    s.status,
    s.created_at
FROM sessions s
JOIN users u_requester ON s.requester_id = u_requester.user_id
JOIN users u_receiver ON s.receiver_id = u_receiver.user_id
JOIN skills sk ON s.skill_id = sk.skill_id
JOIN skill_categories sc ON sk.category_id = sc.category_id
WHERE s.status IN ('pending', 'active');

-- ✅ VIEW 4: Teacher Performance (Rating & Sessions)
CREATE VIEW teacher_performance AS
SELECT 
    u.user_id,
    u.name,
    sc.category_name,
    COUNT(DISTINCT s.session_id) as total_sessions,
    SUM(CASE WHEN s.status = 'completed' THEN 1 ELSE 0 END) as completed_sessions,
    ROUND(AVG(r.rating), 2) as avg_rating
FROM users u
LEFT JOIN user_skills us ON u.user_id = us.user_id AND us.type = 'teach'
LEFT JOIN skills s ON us.skill_id = s.skill_id
LEFT JOIN skill_categories sc ON s.category_id = sc.category_id
LEFT JOIN sessions sess ON (u.user_id = sess.receiver_id AND sess.status = 'completed')
LEFT JOIN reviews r ON sess.session_id = r.session_id
GROUP BY u.user_id, u.name, sc.category_name;

/*
 ============================================
 STEP 5: INSERT SAMPLE DATA (Categories)
 ============================================
*/

INSERT INTO skill_categories (category_name, description) VALUES
('Languages', 'Learn foreign languages'),
('Technology', 'Programming and tech skills'),
('Arts', 'Drawing, painting, music'),
('Business', 'Professional and business skills'),
('Sports', 'Physical activities and sports'),
('Academic', 'Academic subjects and tutoring');
