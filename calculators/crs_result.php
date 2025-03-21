<?php
$page_title = "CRS Score Results";
$page_specific_css = "calculator.css";
include '../includes/functions.php';
include '../includes/db_connect.php';

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: crs_calculator.php");
    exit();
}

// Process form data
$core_human_capital = 0;
$spouse_factors = 0;
$skill_transferability = 0;
$additional_points = 0;

// Core/Human Capital Factors
$age = isset($_POST['age']) ? intval($_POST['age']) : 0;
$education = isset($_POST['education']) ? intval($_POST['education']) : 0;
$first_language = 0;

if (isset($_POST['first_reading']) && isset($_POST['first_writing']) && 
    isset($_POST['first_speaking']) && isset($_POST['first_listening'])) {
    $first_language = intval($_POST['first_reading']) + intval($_POST['first_writing']) + 
                     intval($_POST['first_speaking']) + intval($_POST['first_listening']);
}

$second_language = 0;
if (isset($_POST['second_reading']) && isset($_POST['second_writing']) && 
    isset($_POST['second_speaking']) && isset($_POST['second_listening'])) {
    $second_language = intval($_POST['second_reading']) + intval($_POST['second_writing']) + 
                      intval($_POST['second_speaking']) + intval($_POST['second_listening']);
}

$experience = isset($_POST['experience']) ? intval($_POST['experience']) : 0;

// Calculate core human capital points
$core_human_capital = $age + $education + $first_language + $second_language + $experience;

// Spouse factors
$has_spouse = isset($_POST['has_spouse']) && $_POST['has_spouse'] == 'yes';
if ($has_spouse) {
    $spouse_education = isset($_POST['spouse_education']) ? intval($_POST['spouse_education']) : 0;
    $spouse_language = 0;
    
    if (isset($_POST['spouse_reading']) && isset($_POST['spouse_writing']) && 
        isset($_POST['spouse_speaking']) && isset($_POST['spouse_listening'])) {
        $spouse_language = intval($_POST['spouse_reading']) + intval($_POST['spouse_writing']) + 
                          intval($_POST['spouse_speaking']) + intval($_POST['spouse_listening']);
    }
    
    $spouse_experience = isset($_POST['spouse_experience']) ? intval($_POST['spouse_experience']) : 0;
    
    $spouse_factors = $spouse_education + $spouse_language + $spouse_experience;
}

// Skill Transferability Factors
// Education + Language
$education_language = 0;
if ($education >= 120 && $first_language >= 64) { // Bachelor's degree or higher and CLB 7 or higher
    $education_language = 50;
} elseif ($education >= 120 && $first_language >= 32) { // Bachelor's degree or higher and CLB 5 or higher
    $education_language = 25;
}

// Education + Experience
$education_experience = 0;
if ($education >= 120 && $experience >= 53) { // Bachelor's degree or higher and 2+ years experience
    $education_experience = 50;
} elseif ($education >= 120 && $experience >= 40) { // Bachelor's degree or higher and 1+ year experience
    $education_experience = 25;
}

// Foreign Work Experience + Language
$foreign_experience_language = 0;
if (isset($_POST['foreign_experience']) && intval($_POST['foreign_experience']) >= 3 && $first_language >= 64) {
    $foreign_experience_language = 50;
} elseif (isset($_POST['foreign_experience']) && intval($_POST['foreign_experience']) >= 1 && $first_language >= 32) {
    $foreign_experience_language = 25;
}

// Canadian Work Experience + Foreign Work Experience
$canadian_foreign_experience = 0;
if ($experience >= 53 && isset($_POST['foreign_experience']) && intval($_POST['foreign_experience']) >= 3) {
    $canadian_foreign_experience = 50;
} elseif ($experience >= 40 && isset($_POST['foreign_experience']) && intval($_POST['foreign_experience']) >= 1) {
    $canadian_foreign_experience = 25;
}

// Calculate skill transferability points (max 100)
$skill_transferability = min(100, $education_language + $education_experience + 
                           $foreign_experience_language + $canadian_foreign_experience);

// Additional Points
if (isset($_POST['provincial_nomination']) && $_POST['provincial_nomination'] == 'yes') {
    $additional_points += 600;
}

if (isset($_POST['job_offer']) && $_POST['job_offer'] == 'yes') {
    $job_offer_noc = isset($_POST['job_offer_noc']) ? $_POST['job_offer_noc'] : '';
    if ($job_offer_noc == '00') {
        $additional_points += 200;
    } else {
        $additional_points += 50;
    }
}

if (isset($_POST['canadian_education']) && $_POST['canadian_education'] == 'yes') {
    $canadian_education_level = isset($_POST['canadian_education_level']) ? $_POST['canadian_education_level'] : '';
    if ($canadian_education_level == 'one_or_two_year') {
        $additional_points += 15;
    } elseif ($canadian_education_level == 'three_year') {
        $additional_points += 30;
    }
}

if (isset($_POST['french_proficiency']) && $_POST['french_proficiency'] == 'yes') {
    $english_proficiency = isset($_POST['english_proficiency']) && $_POST['english_proficiency'] == 'yes';
    if ($english_proficiency) {
        $additional_points += 50;
    } else {
        $additional_points += 25;
    }
}

if (isset($_POST['sibling_in_canada']) && $_POST['sibling_in_canada'] == 'yes') {
    $additional_points += 15;
}

// Calculate total CRS score
$total_score = $core_human_capital + $spouse_factors + $skill_transferability + $additional_points;

// Save result to database if user is logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $save_result_query = "INSERT INTO crs_results (user_id, score, core_human_capital, spouse_factors, skill_transferability, additional_points, created_at) 
                         VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($save_result_query);
    $stmt->bind_param("iiiiii", $user_id, $total_score, $core_human_capital, $spouse_factors, $skill_transferability, $additional_points);
    $stmt->execute();
    $result_id = $stmt->insert_id;
}

include '../includes/header.php';
?>

<main class="calculator-result-page py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h1 class="h3 mb-0">Your CRS Score Results</h1>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <div class="score-circle">
                                <div class="score-number"><?php echo $total_score; ?></div>
                                <div class="score-label">Total Points</div>
                            </div>
                            
                            <p class="lead mt-3">
                                <?php if ($total_score >= 470): ?>
                                    <span class="text-success">Your score is competitive for Express Entry!</span>
                                <?php elseif ($total_score >= 440): ?>
                                    <span class="text-warning">Your score is close to recent draw cutoffs.</span>
                                <?php else: ?>
                                    <span class="text-danger">Your score is below recent draw cutoffs.</span>
                                <?php endif; ?>
                            </p>
                            
                            <p>The most recent Express Entry draw cutoff was 471 points on <?php echo date('F j, Y', strtotime('-2 weeks')); ?>.</p>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <h2 class="h5 mb-0">Score Breakdown</h2>
                                    </div>
                                    <div class="card-body p-0">
                                        <ul class="list-group list-group-flush">
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                Core/Human Capital Factors
                                                <span class="badge badge-primary badge-pill"><?php echo $core_human_capital; ?></span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                Spouse Factors
                                                <span class="badge badge-primary badge-pill"><?php echo $spouse_factors; ?></span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                Skill Transferability
                                                <span class="badge badge-primary badge-pill"><?php echo $skill_transferability; ?></span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                Additional Points
                                                <span class="badge badge-primary badge-pill"><?php echo $additional_points; ?></span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-header bg-light">
                                        <h2 class="h5 mb-0">What's Next?</h2>
                                    </div>
                                    <div class="card-body">
                                        <p>Based on your score of <strong><?php echo $total_score; ?></strong>, here are your recommended next steps:</p>
                                        
                                        <ul class="fa-ul">
                                            <?php if ($total_score >= 470): ?>
                                                <li><span class="fa-li"><i class="fas fa-check-circle text-success"></i></span>Your score is competitive for Express Entry!</li>
                                                <li><span class="fa-li"><i class="fas fa-arrow-right"></i></span>Consider creating an Express Entry profile</li>
                                                <li><span class="fa-li"><i class="fas fa-arrow-right"></i></span>Prepare your supporting documents</li>
                                            <?php else: ?>
                                                <li><span class="fa-li"><i class="fas fa-exclamation-circle text-warning"></i></span>Your score may need improvement</li>
                                                <li><span class="fa-li"><i class="fas fa-arrow-right"></i></span>Improve language test scores</li>
                                                <li><span class="fa-li"><i class="fas fa-arrow-right"></i></span>Gain more work experience</li>
                                                <li><span class="fa-li"><i class="fas fa-arrow-right"></i></span>Consider provincial nomination programs</li>
                                            <?php endif; ?>
                                            <li><span class="fa-li"><i class="fas fa-arrow-right"></i></span>Book a consultation with our experts</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-3">
                            <a href="crs_calculator.php" class="btn btn-outline-primary mr-2">Recalculate Score</a>
                            <a href="<?php echo getBaseURL(); ?>/booking/schedule.php" class="btn btn-primary">Book a Consultation</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?> 