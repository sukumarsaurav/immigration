<?php
$page_title = "My Dashboard";
include '../includes/functions.php';
include '../includes/db_connect.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: login.php?redirect=dashboard.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user's applications
$applications_query = "SELECT a.application_id, a.visa_id, a.status, a.created_at, a.last_updated, v.visa_name 
                      FROM applications a 
                      JOIN visa_types v ON a.visa_id = v.visa_id 
                      WHERE a.user_id = ? 
                      ORDER BY a.last_updated DESC";
$stmt = $conn->prepare($applications_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$applications = $stmt->get_result();

// Get user's upcoming appointments
$appointments_query = "SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.status, a.notes, c.first_name, c.last_name 
                      FROM appointments a 
                      LEFT JOIN users c ON a.consultant_id = c.user_id 
                      WHERE a.user_id = ? AND a.appointment_date >= CURDATE() 
                      ORDER BY a.appointment_date ASC, a.appointment_time ASC";
$stmt = $conn->prepare($appointments_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$appointments = $stmt->get_result();

// Get user's recent documents
$documents_query = "SELECT d.document_id, d.document_name, d.upload_date, a.application_id, v.visa_name 
                   FROM documents d 
                   JOIN applications a ON d.application_id = a.application_id 
                   JOIN visa_types v ON a.visa_id = v.visa_id 
                   WHERE a.user_id = ? 
                   ORDER BY d.upload_date DESC 
                   LIMIT 5";
$stmt = $conn->prepare($documents_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$documents = $stmt->get_result();

include '../includes/header.php';
?>

<main class="dashboard-page py-5">
    <div class="container">
        <div class="row mb-4">
            <div class="col-md-8">
                <h1>Welcome, <?php echo $_SESSION['first_name']; ?>!</h1>
                <p class="lead">Manage your immigration applications and services from your personal dashboard.</p>
            </div>
            <div class="col-md-4 text-md-right">
                <a href="<?php echo getBaseURL(); ?>/booking/schedule.php" class="btn btn-primary">
                    <i class="fas fa-calendar-plus mr-2"></i>Book Consultation
                </a>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-8">
                <!-- Applications Section -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h2 class="h5 mb-0">My Applications</h2>
                            <a href="<?php echo getBaseURL(); ?>/visa_types/" class="btn btn-sm btn-outline-primary">Start New Application</a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if ($applications->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Visa Type</th>
                                            <th>Status</th>
                                            <th>Last Updated</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($app = $applications->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($app['visa_name']); ?></td>
                                                <td>
                                                    <?php 
                                                    $status_class = '';
                                                    switch($app['status']) {
                                                        case 'pending': $status_class = 'warning'; break;
                                                        case 'in_progress': $status_class = 'info'; break;
                                                        case 'approved': $status_class = 'success'; break;
                                                        case 'rejected': $status_class = 'danger'; break;
                                                        default: $status_class = 'secondary';
                                                    }
                                                    ?>
                                                    <span class="badge badge-<?php echo $status_class; ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $app['status'])); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo formatDate($app['last_updated']); ?></td>
                                                <td>
                                                    <a href="<?php echo getBaseURL(); ?>/booking/track_application.php?id=<?php echo $app['application_id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                                    <a href="<?php echo getBaseURL(); ?>/booking/document_upload.php?id=<?php echo $app['application_id']; ?>" class="btn btn-sm btn-outline-secondary">Documents</a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <img src="<?php echo getBaseURL(); ?>/assets/images/no-applications.svg" alt="No Applications" class="img-fluid mb-3" style="max-width: 150px;">
                                <h3 class="h5">No Applications Yet</h3>
                                <p class="text-muted">Start your immigration journey by exploring our visa options.</p>
                                <a href="<?php echo getBaseURL(); ?>/visa_types/" class="btn btn-primary">Explore Visa Options</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Documents Section -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h2 class="h5 mb-0">Recent Documents</h2>
                            <a href="<?php echo getBaseURL(); ?>/booking/document_upload.php" class="btn btn-sm btn-outline-primary">Manage Documents</a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if ($documents->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Document Name</th>
                                            <th>Application</th>
                                            <th>Uploaded</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($doc = $documents->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($doc['document_name']); ?></td>
                                                <td><?php echo htmlspecialchars($doc['visa_name']); ?></td>
                                                <td><?php echo formatDate($doc['upload_date']); ?></td>
                                                <td>
                                                    <a href="<?php echo getBaseURL(); ?>/uploads/documents/<?php echo $doc['document_id']; ?>" target="_blank" class="btn btn-sm btn-outline-info">View</a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="p-4 text-center">
                                <p class="text-muted mb-0">No documents uploaded yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <!-- Upcoming Appointments -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h2 class="h5 mb-0">Upcoming Appointments</h2>
                            <a href="<?php echo getBaseURL(); ?>/booking/schedule.php" class="btn btn-sm btn-outline-primary">Book</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if ($appointments->num_rows > 0): ?>
                            <ul class="list-group list-group-flush">
                                <?php while ($apt = $appointments->fetch_assoc()): ?>
                                    <li class="list-group-item px-0">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1"><?php echo date('l, M j, Y', strtotime($apt['appointment_date'])); ?></h6>
                                                <p class="mb-0 text-muted">
                                                    <i class="far fa-clock mr-1"></i> <?php echo date('g:i A', strtotime($apt['appointment_time'])); ?>
                                                    <span class="mx-2">|</span>
                                                    <i class="far fa-user mr-1"></i> <?php echo $apt['first_name'] . ' ' . $apt['last_name']; ?>
                                                </p>
                                            </div>
                                            <span class="badge badge-<?php echo $apt['status'] == 'confirmed' ? 'success' : 'warning'; ?>">
                                                <?php echo ucfirst($apt['status']); ?>
                                            </span>
                                        </div>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <img src="<?php echo getBaseURL(); ?>/assets/images/calendar-icon.svg" alt="No Appointments" class="img-fluid mb-3" style="max-width: 80px;">
                                <p class="text-muted mb-3">No upcoming appointments scheduled.</p>
                                <a href="<?php echo getBaseURL(); ?>/booking/schedule.php" class="btn btn-sm btn-primary">Schedule Consultation</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-white">
                        <h2 class="h5 mb-0">Quick Links</h2>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <a href="<?php echo getBaseURL(); ?>/calculators/eligibility.php" class="list-group-item list-group-item-action d-flex align-items-center">
                                <i class="fas fa-calculator text-primary mr-3"></i>
                                <div>
                                    <h6 class="mb-0">Check Your Eligibility</h6>
                                    <small class="text-muted">Find out which visa you qualify for</small>
                                </div>
                            </a>
                            <a href="<?php echo getBaseURL(); ?>/calculators/crs_calculator.php" class="list-group-item list-group-item-action d-flex align-items-center">
                                <i class="fas fa-chart-bar text-primary mr-3"></i>
                                <div>
                                    <h6 class="mb-0">Calculate CRS Score</h6>
                                    <small class="text-muted">Express Entry points calculator</small>
                                </div>
                            </a>
                            <a href="<?php echo getBaseURL(); ?>/resources/guides.php" class="list-group-item list-group-item-action d-flex align-items-center">
                                <i class="fas fa-book text-primary mr-3"></i>
                                <div>
                                    <h6 class="mb-0">Immigration Guides</h6>
                                    <small class="text-muted">Step-by-step application guides</small>
                                </div>
                            </a>
                            <a href="<?php echo getBaseURL(); ?>/resources/faq.php" class="list-group-item list-group-item-action d-flex align-items-center">
                                <i class="fas fa-question-circle text-primary mr-3"></i>
                                <div>
                                    <h6 class="mb-0">FAQ</h6>
                                    <small class="text-muted">Answers to common questions</small>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?> 