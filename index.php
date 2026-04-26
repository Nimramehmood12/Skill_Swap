<?php 
include('config/db.php'); 
include('includes/header.php'); 
?>

<section id="home" class="hero-section text-center d-flex align-items-center" style="min-height: 90vh;">
    <div class="container">
        <h1 class="display-1 fw-bold mb-4">Learn Anything.<br><span class="text-warning">Teach What You Love.</span></h1>
        <p class="lead mb-5 fs-4 opacity-75">Join the ultimate peer-to-peer skill exchange. <br>Trade your expertise for new knowledge, 100% free.</p>
        <?php if(!isset($_SESSION['user_id'])): ?>
            <div class="d-flex justify-content-center gap-3">
                <a href="auth/register.php" class="btn btn-warning btn-lg px-5 py-3 fw-bold shadow-lg">Start Swapping Now</a>
                <a href="#how" class="btn btn-outline-light btn-lg px-5 py-3">How it Works</a>
            </div>
        <?php else: ?>
            <a href="user/dashboard.php" class="btn btn-warning btn-lg px-5 py-3 fw-bold shadow">Go to My Dashboard</a>
        <?php endif; ?>
    </div>
</section>

<section id="skills" class="py-5 mt-5">
    <div class="container">
        <div class="text-center mb-5">
            <h6 class="text-primary fw-bold text-uppercase">Marketplace</h6>
            <h2 class="display-5 fw-bold">Available Skills to Learn</h2>
        </div>
        
        <div class="row g-4">
            <?php
            $sql = "SELECT s.skill_name, s.skill_id, us.user_id, u.name as teacher_name FROM skills s JOIN user_skills us ON s.skill_id = us.skill_id JOIN users u ON us.user_id = u.user_id 
                    WHERE us.type = 'teach' LIMIT 6";
            
            $result = mysqli_query($conn, $sql);

            if ($result && mysqli_num_rows($result) > 0) {
                while($row = mysqli_fetch_assoc($result)) {
                    ?>
                    <div class="col-md-4">
                        <div class="card h-100 border-0 shadow-sm p-4 text-center skill-card">
                            <div class="icon-circle bg-light mb-3 mx-auto">
                                <i class="fas fa-graduation-cap text-primary fa-2x"></i>
                            </div>
                            <h4 class="fw-bold"><?php echo htmlspecialchars($row['skill_name']); ?></h4>
                            <p class="text-muted">By <?php echo htmlspecialchars($row['teacher_name']); ?></p>
                            
                            <a href="sessions/request_session.php?rid=<?php echo $row['user_id']; ?>&skill_id=<?php echo $row['skill_id']; ?>" class="btn btn-outline-primary rounded-pill px-4">
                                Request Swap
                            </a>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo '<div class="col-12 text-center py-5">
                        <div class="alert alert-secondary d-inline-block">No skills approved yet. Be the first to add one!</div>
                      </div>';
            }
            ?>
        </div>
    </div>
</section>

<section id="about" class="py-5 bg-white">
    <div class="container py-5">
        <div class="row align-items-center">
            <div class="col-md-6">
                <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" class="img-fluid rounded-5 shadow" alt="About">
            </div>
            <div class="col-md-6 ps-md-5 mt-4 mt-md-0">
                <h6 class="text-primary fw-bold text-uppercase">Our Mission</h6>
                <h2 class="display-5 fw-bold mb-4">Empowering people through Skill Sharing</h2>
                <p class="text-muted fs-5">SkillSwap is built on the idea that everyone has something to teach and something to learn. We remove the middleman and the money, leaving only pure human connection and knowledge.</p>
                <div class="mt-4">
                    <div class="d-flex mb-3">
                        <i class="fas fa-check-circle text-success fa-2x me-3"></i>
                        <p class="mb-0 fw-bold">Zero Cost - Knowledge should be free.</p>
                    </div>
                    <div class="d-flex mb-3">
                        <i class="fas fa-check-circle text-success fa-2x me-3"></i>
                        <p class="mb-0 fw-bold">Verified Profiles - Trust the expert.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="how" class="py-5 bg-light text-center">
    <div class="container py-5">
        <h2 class="display-5 fw-bold mb-5">How It Works</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="p-4">
                    <span class="display-4 fw-bold text-warning mb-3 d-block">01</span>
                    <h3>Create Profile</h3>
                    <p class="text-muted">List your skills and what you're dying to learn.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-4 border-start border-end">
                    <span class="display-4 fw-bold text-warning mb-3 d-block">02</span>
                    <h3>Find Match</h3>
                    <p class="text-muted">We suggest users with complementary skills to yours.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-4">
                    <span class="display-4 fw-bold text-warning mb-3 d-block">03</span>
                    <h3>Trade Skills</h3>
                    <p class="text-muted">Chat, schedule, and start your exchange journey.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="contact" class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 card shadow-lg border-0 rounded-5 overflow-hidden p-0">
                <div class="row g-0">
                    <div class="col-md-5 bg-dark text-white p-5 d-flex flex-column justify-content-center">
                        <h3 class="fw-bold mb-4">Contact Info</h3>
                        <p><i class="fas fa-envelope me-2 text-warning"></i> k240807@nu.edu.pk</p>
                        <p><i class="fas fa-map-marker-alt me-2 text-warning"></i> 123 Swap Street, Karachi</p>
                    </div>
                    <div class="col-md-7 p-5 bg-white">
                        <h3 class="fw-bold mb-4">Send a Message</h3>
                        <form>
                            <input type="text" class="form-control mb-3" placeholder="Full Name">
                            <input type="email" class="form-control mb-3" placeholder="Email">
                            <textarea class="form-control mb-3" rows="4" placeholder="Your Message"></textarea>
                            <button type="button" class="btn btn-warning w-100 py-3 fw-bold">Submit</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include('includes/footer.php'); ?>