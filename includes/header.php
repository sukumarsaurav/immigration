<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - Canada Immigration Consultancy' : 'Canada Immigration Consultancy'; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="<?php echo getBaseURL(); ?>/assets/css/style.css">
    <?php if(isset($page_specific_css)): ?>
    <link rel="stylesheet" href="<?php echo getBaseURL(); ?>/assets/css/<?php echo $page_specific_css; ?>">
    <?php endif; ?>
    <meta name="description" content="<?php echo isset($page_meta_description) ? $page_meta_description : 'Professional immigration consultancy services for Canada. Get expert advice on visa applications, Express Entry, family sponsorship, and more.'; ?>">
    <meta name="keywords" content="<?php echo isset($page_meta_keywords) ? $page_meta_keywords : 'canada immigration, express entry, canadian visa, immigration consultant, study permit, work permit, family sponsorship'; ?>">
    <meta name="robots" content="<?php echo isset($page_robots) ? $page_robots : 'index, follow'; ?>">
    <meta property="og:title" content="<?php echo isset($page_og_title) ? $page_og_title : $page_title . ' | Canada Immigration Consultancy'; ?>">
    <meta property="og:description" content="<?php echo isset($page_og_description) ? $page_og_description : (isset($page_meta_description) ? $page_meta_description : 'Professional immigration consultancy services for Canada.'); ?>">
    <meta property="og:image" content="<?php echo isset($page_og_image) ? $page_og_image : getBaseURL() . '/assets/images/og-default.jpg'; ?>">
    <meta property="og:url" content="<?php echo getCurrentURL(); ?>">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo isset($page_twitter_title) ? $page_twitter_title : $page_title . ' | Canada Immigration Consultancy'; ?>">
    <meta name="twitter:description" content="<?php echo isset($page_twitter_description) ? $page_twitter_description : (isset($page_meta_description) ? $page_meta_description : 'Professional immigration consultancy services for Canada.'); ?>">
    <meta name="twitter:image" content="<?php echo isset($page_twitter_image) ? $page_twitter_image : getBaseURL() . '/assets/images/twitter-default.jpg'; ?>">
    <link rel="canonical" href="<?php echo isset($page_canonical) ? $page_canonical : getCurrentURL(); ?>">
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-light bg-white">
            <div class="container">
                <a class="navbar-brand" href="<?php echo getBaseURL(); ?>/">
                    <img src="<?php echo getBaseURL(); ?>/assets/images/logo.png" alt="Canada Immigration Consultancy" height="50">
                </a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarMain">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarMain">
                    <ul class="navbar-nav mr-auto">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="visaDropdown" role="button" data-toggle="dropdown">
                                Visa Types
                            </a>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="<?php echo getBaseURL(); ?>/visa_types/work_visa.php">Work Visa</a>
                                <a class="dropdown-item" href="<?php echo getBaseURL(); ?>/visa_types/study_visa.php">Study Visa</a>
                                <a class="dropdown-item" href="<?php echo getBaseURL(); ?>/visa_types/express_entry.php">Express Entry</a>
                                <a class="dropdown-item" href="<?php echo getBaseURL(); ?>/visa_types/family_sponsorship.php">Family Sponsorship</a>
                            </div>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="calculatorsDropdown" role="button" data-toggle="dropdown">
                                Assessment Tools
                            </a>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="<?php echo getBaseURL(); ?>/calculators/eligibility.php">Visa Eligibility</a>
                                <a class="dropdown-item" href="<?php echo getBaseURL(); ?>/calculators/crs_calculator.php">CRS Calculator</a>
                                <a class="dropdown-item" href="<?php echo getBaseURL(); ?>/calculators/study_permit.php">Study Permit</a>
                            </div>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="resourcesDropdown" role="button" data-toggle="dropdown">
                                Resources
                            </a>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="<?php echo getBaseURL(); ?>/resources/news.php">News</a>
                                <a class="dropdown-item" href="<?php echo getBaseURL(); ?>/resources/guides.php">Guides</a>
                                <a class="dropdown-item" href="<?php echo getBaseURL(); ?>/resources/faq.php">FAQ</a>
                                <a class="dropdown-item" href="<?php echo getBaseURL(); ?>/resources/blog.php">Blog</a>
                            </div>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo getBaseURL(); ?>/booking/schedule.php">Book Consultation</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo getBaseURL(); ?>/contact/contact_form.php">Contact Us</a>
                        </li>
                    </ul>
                    <div class="navbar-nav">
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <div class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown">
                                    <i class="fas fa-user-circle mr-1"></i> <?php echo $_SESSION['first_name']; ?>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <a class="dropdown-item" href="<?php echo getBaseURL(); ?>/user/dashboard.php">My Dashboard</a>
                                    <a class="dropdown-item" href="<?php echo getBaseURL(); ?>/booking/document_upload.php">My Documents</a>
                                    <a class="dropdown-item" href="<?php echo getBaseURL(); ?>/booking/track_application.php">Track Applications</a>
                                    <a class="dropdown-item" href="<?php echo getBaseURL(); ?>/user/profile.php">Profile Settings</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="<?php echo getBaseURL(); ?>/user/logout.php">Logout</a>
                                </div>
                            </div>
                        <?php else: ?>
                            <a class="btn btn-outline-primary mr-2" href="<?php echo getBaseURL(); ?>/user/login.php">Login</a>
                            <a class="btn btn-primary" href="<?php echo getBaseURL(); ?>/user/register.php">Register</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>
    </header>
</body>
</html> 