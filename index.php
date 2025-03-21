<?php 
try {
    include 'includes/header.php';
?>

<main>
    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1>Your Gateway to Canadian Immigration</h1>
            <p>Professional guidance for all your Canadian visa and immigration needs</p>
            <div class="cta-buttons">
                <a href="booking/schedule.php" class="btn btn-primary">Book a Consultation</a>
                <a href="visa_types/express_entry.php" class="btn btn-secondary">Explore Visa Options</a>
            </div>
        </div>
    </section>

    <!-- Services Overview -->
    <section class="services-overview">
        <div class="container">
            <h2>Our Immigration Services</h2>
            <div class="services-grid">
                <div class="service-card">
                    <img src="assets/images/work-visa.jpg" alt="Work Visa">
                    <h3>Work Visas</h3>
                    <p>Comprehensive assistance for skilled workers, temporary workers, and business immigrants.</p>
                    <a href="visa_types/work_visa.php">Learn More</a>
                </div>
                <div class="service-card">
                    <img src="assets/images/study-visa.jpg" alt="Study Visa">
                    <h3>Study Permits</h3>
                    <p>Guidance for international students seeking education opportunities in Canada.</p>
                    <a href="visa_types/study_visa.php">Learn More</a>
                </div>
                <div class="service-card">
                    <img src="assets/images/express-entry.jpg" alt="Express Entry">
                    <h3>Express Entry</h3>
                    <p>Navigate the Express Entry system for skilled immigrants with our expert assistance.</p>
                    <a href="visa_types/express_entry.php">Learn More</a>
                </div>
                <div class="service-card">
                    <img src="assets/images/family-sponsorship.jpg" alt="Family Sponsorship">
                    <h3>Family Sponsorship</h3>
                    <p>Reunite with your family members through Canada's family sponsorship programs.</p>
                    <a href="visa_types/family_sponsorship.php">Learn More</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Assessment Tools -->
    <section class="assessment-tools">
        <div class="container">
            <h2>Free Immigration Assessment Tools</h2>
            <div class="tools-grid">
                <div class="tool-card">
                    <h3>Visa Eligibility Calculator</h3>
                    <p>Find out which Canadian immigration programs you may qualify for.</p>
                    <a href="calculators/eligibility.php" class="btn">Check Eligibility</a>
                </div>
                <div class="tool-card">
                    <h3>CRS Score Calculator</h3>
                    <p>Calculate your Comprehensive Ranking System score for Express Entry.</p>
                    <a href="calculators/crs_calculator.php" class="btn">Calculate Score</a>
                </div>
                <div class="tool-card">
                    <h3>Study Permit Requirements</h3>
                    <p>Verify if you meet the requirements for a Canadian study permit.</p>
                    <a href="calculators/study_permit.php" class="btn">Check Requirements</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Success Stories -->
    <section class="success-stories">
        <div class="container">
            <h2>Success Stories</h2>
            <div class="testimonials-slider">
                <?php 
                try {
                    include 'includes/testimonials_slider.php';
                } catch (Throwable $e) {
                    error_log("Error including testimonials: " . $e->getMessage());
                    echo '<div class="alert alert-info">Success stories coming soon!</div>';
                }
                ?>
            </div>
            <a href="success_stories.php" class="btn btn-outline">View All Success Stories</a>
        </div>
    </section>

    <!-- Latest News & Blog -->
    <section class="latest-news">
        <div class="container">
            <h2>Latest Immigration News & Updates</h2>
            <div class="news-grid">
                <?php 
                try {
                    include 'includes/latest_news.php';
                } catch (Throwable $e) {
                    error_log("Error including latest news: " . $e->getMessage());
                    echo '<div class="alert alert-info">News updates coming soon!</div>';
                }
                ?>
            </div>
            <a href="resources/news.php" class="btn btn-outline">View All News</a>
        </div>
    </section>
</main>

<?php 
    include 'includes/footer.php';
} catch (Throwable $e) {
    // Simple error display if things go wrong before error handler loads
    echo '<h1>An error occurred</h1>';
    echo '<p>Please try again later. If the problem persists, contact the administrator.</p>';
    
    // Log the error
    error_log("Uncaught exception in index.php: " . $e->getMessage());
}
?> 