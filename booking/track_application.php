<?php
$page_title = "Track Your Applications";
$page_specific_css = "tracking.css";
include '../includes/functions.php';
include '../includes/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../user/login.php?redirect=booking/track_application.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user's applications
$stmt = $conn->prepare("SELECT a.application_id, a.visa_id, a.status, a.created_at, a.last_updated, 
                        v.visa_name, v.processing_time 
                        FROM applications a 
                        JOIN visa_types v ON a.visa_id = v.visa_id 
                        WHERE a.user_id = ? 
                        ORDER BY a.last_updated DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$applications = $stmt->get_result();

// Get application details if an application is selected
$application_details = null;
$application_timeline = null;
$application_documents = null;

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $application_id = $_GET['id'];
    
    // Get application details
    $stmt = $conn->prepare("SELECT a.*, v.visa_name, v.processing_time, v.requirements 
                           FROM applications a 
                           JOIN visa_types v ON a.visa_id = v.visa_id 
                           WHERE a.application_id = ? AND a.user_id = ?");
    $stmt->bind_param("ii", $application_id, $user_id);
    $stmt->execute();
    $application_details = $stmt->get_result()->fetch_assoc();
    
    if ($application_details) {
        // Get application timeline
        $stmt = $conn->prepare("SELECT * FROM application_status_history 
                               WHERE application_id = ? 
                               ORDER BY status_date DESC");
        $stmt->bind_param("i", $application_id);
        $stmt->execute();
        $application_timeline = $stmt->get_result();
        
        // Get application documents
        $stmt = $conn->prepare("SELECT * FROM documents 
                               WHERE application_id = ? 
                               ORDER BY upload_date DESC");
        $stmt->bind_param("i", $application_id);
        $stmt->execute();
        $application_documents = $stmt->get_result();
    }
}

include '../includes/header.php';
?>

<main class="tracking-page py-5">
    <div class="container">
        <h1>Track Your Applications</h1>
        <p class="lead mb-4">Monitor the status and progress of your immigration applications.</p>
        
        <?php if ($applications->num_rows > 0): ?>
            <div class="row">
                <div class="col-md-4">
                    <div class="card shadow mb-4">
                        <div class="card-header bg-white">
                            <h2 class="h5 mb-0">Your Applications</h2>
                        </div>
                        <div class="list-group list-group-flush">
                            <?php while ($app = $applications->fetch_assoc()): ?>
                                <a href="?id=<?php echo $app['application_id']; ?>" class="list-group-item list-group-item-action <?php echo (isset($_GET['id']) && $_GET['id'] == $app['application_id']) ? 'active' : ''; ?>">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1"><?php echo $app['visa_name']; ?></h5>
                                        <small><?php echo getStatusBadge($app['status']); ?></small>
                                    </div>
                                    <p class="mb-1">Application #<?php echo $app['application_id']; ?></p>
                                    <small>Last updated: <?php echo date('M j, Y', strtotime($app['last_updated'])); ?></small>
                                </a>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <?php if ($application_details): ?>
                        <div class="card shadow mb-4">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h2 class="h5 mb-0"><?php echo $application_details['visa_name']; ?> Application</h2>
                                <span class="badge badge-pill <?php echo getStatusClass($application_details['status']); ?>"><?php echo ucfirst($application_details['status']); ?></span>
                            </div>
                            <div class="card-body">
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <p><strong>Application ID:</strong> #<?php echo $application_details['application_id']; ?></p>
                                        <p><strong>Submitted:</strong> <?php echo date('F j, Y', strtotime($application_details['created_at'])); ?></p>
                                        <p><strong>Last Updated:</strong> <?php echo date('F j, Y', strtotime($application_details['last_updated'])); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Estimated Processing Time:</strong> <?php echo $application_details['processing_time']; ?></p>
                                        <p><strong>Current Stage:</strong> <?php echo getCurrentStage($application_details['status']); ?></p>
                                        <?php if (!empty($application_details['decision_date'])): ?>
                                            <p><strong>Decision Date:</strong> <?php echo date('F j, Y', strtotime($application_details['decision_date'])); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Progress Bar -->
                                <div class="progress-tracker mb-4">
                                    <div class="progress">
                                        <div class="progress-bar" role="progressbar" style="width: <?php echo getProgressPercentage($application_details['status']); ?>%" aria-valuenow="<?php echo getProgressPercentage($application_details['status']); ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <div class="progress-labels d-flex justify-content-between mt-2">
                                        <span>Submitted</span>
                                        <span>Processing</span>
                                        <span>Review</span>
                                        <span>Decision</span>
                                    </div>
                                </div>
                                
                                <!-- Application Notes -->
                                <?php if (!empty($application_details['notes'])): ?>
                                    <div class="alert alert-info">
                                        <h3 class="h6"><i class="fas fa-info-circle mr-2"></i>Notes:</h3>
                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($application_details['notes'])); ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Action Buttons -->
                                <div class="text-right">
                                    <a href="<?php echo getBaseURL(); ?>/booking/document_upload.php?application_id=<?php echo $application_details['application_id']; ?>" class="btn btn-outline-primary">
                                        <i class="fas fa-file-upload mr-1"></i> Upload Documents
                                    </a>
                                    <a href="<?php echo getBaseURL(); ?>/booking/schedule.php" class="btn btn-primary">
                                        <i class="fas fa-calendar-alt mr-1"></i> Schedule Consultation
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Timeline -->
                        <div class="card shadow mb-4">
                            <div class="card-header bg-white">
                                <h3 class="h5 mb-0">Application Timeline</h3>
                            </div>
                            <div class="card-body">
                                <?php if ($application_timeline && $application_timeline->num_rows > 0): ?>
                                    <div class="timeline">
                                        <?php while ($event = $application_timeline->fetch_assoc()): ?>
                                            <div class="timeline-item">
                                                <div class="timeline-marker"></div>
                                                <div class="timeline-content">
                                                    <h3 class="timeline-title"><?php echo ucfirst($event['status']); ?></h3>
                                                    <p class="timeline-date"><?php echo date('F j, Y', strtotime($event['status_date'])); ?></p>
                                                    <?php if (!empty($event['notes'])): ?>
                                                        <p class="timeline-text"><?php echo nl2br(htmlspecialchars($event['notes'])); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-center text-muted my-4">No timeline events available for this application.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Documents -->
                        <div class="card shadow">
                            <div class="card-header bg-white">
                                <h3 class="h5 mb-0">Application Documents</h3>
                            </div>
                            <div class="card-body">
                                <?php if ($application_documents && $application_documents->num_rows > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Document</th>
                                                    <th>Uploaded</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($doc = $application_documents->fetch_assoc()): ?>
                                                    <tr>
                                                        <td>
                                                            <i class="<?php echo getDocumentIcon($doc['document_name']); ?> mr-2"></i>
                                                            <?php echo $doc['document_name']; ?>
                                                        </td>
                                                        <td><?php echo date('M j, Y', strtotime($doc['upload_date'])); ?></td>
                                                        <td>
                                                            <span class="badge badge-<?php echo $doc['status'] == 'approved' ? 'success' : ($doc['status'] == 'rejected' ? 'danger' : 'warning'); ?>">
                                                                <?php echo ucfirst($doc['status']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <a href="<?php echo getBaseURL(); ?>/uploads/<?php echo $doc['file_path']; ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <?php if ($doc['status'] == 'rejected'): ?>
                                                                <a href="<?php echo getBaseURL(); ?>/booking/document_upload.php?application_id=<?php echo $application_details['application_id']; ?>&replace=<?php echo $doc['document_id']; ?>" class="btn btn-sm btn-outline-danger">
                                                                    <i class="fas fa-sync-alt"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <img src="<?php echo getBaseURL(); ?>/assets/images/document-icon.svg" alt="No Documents" class="img-fluid mb-3" style="max-width: 80px;">
                                        <p class="text-muted mb-3">No documents have been uploaded for this application.</p>
                                        <a href="<?php echo getBaseURL(); ?>/booking/document_upload.php?application_id=<?php echo $application_details['application_id']; ?>" class="btn btn-primary">
                                            <i class="fas fa-file-upload mr-1"></i> Upload Documents
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="card shadow">
                            <div class="card-body text-center py-5">
                                <img src="<?php echo getBaseURL(); ?>/assets/images/select-application.svg" alt="Select Application" class="img-fluid mb-4" style="max-width: 200px;">
                                <h3>Track Your Application Progress</h3>
                                <p class="text-muted mb-4">Select an application from the list to view its details and current status.</p>
                                <p>Need to start a new application? <a href="<?php echo getBaseURL(); ?>/booking/schedule.php">Book a consultation</a> with our immigration experts.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="card shadow">
                <div class="card-body text-center py-5">
                    <img src="<?php echo getBaseURL(); ?>/assets/images/no-applications.svg" alt="No Applications" class="img-fluid mb-4" style="max-width: 200px;">
                    <h2>No Applications Found</h2>
                    <p class="text-muted mb-4">You don't have any active immigration applications at the moment.</p>
                    <div>
                        <a href="<?php echo getBaseURL(); ?>/booking/schedule.php" class="btn btn-primary">
                            <i class="fas fa-calendar-alt mr-1"></i> Book a Consultation
                        </a>
                        <a href="<?php echo getBaseURL(); ?>/calculators/eligibility.php" class="btn btn-outline-primary ml-2">
                            <i class="fas fa-calculator mr-1"></i> Check Your Eligibility
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php
// Helper functions for the tracking page
function getStatusBadge($status) {
    $class = getStatusClass($status);
    return '<span class="badge badge-pill ' . $class . '">' . ucfirst($status) . '</span>';
}

function getStatusClass($status) {
    switch ($status) {
        case 'submitted':
            return 'badge-info';
        case 'in_progress':
        case 'processing':
            return 'badge-primary';
        case 'review':
            return 'badge-warning';
        case 'approved':
            return 'badge-success';
        case 'rejected':
            return 'badge-danger';
        default:
            return 'badge-secondary';
    }
}

function getCurrentStage($status) {
    switch ($status) {
        case 'submitted':
            return 'Initial Processing';
        case 'in_progress':
        case 'processing':
            return 'Document Verification';
        case 'review':
            return 'Final Review';
        case 'approved':
            return 'Application Approved';
        case 'rejected':
            return 'Application Rejected';
        default:
            return 'Unknown';
    }
}

function getProgressPercentage($status) {
    switch ($status) {
        case 'submitted':
            return 25;
        case 'in_progress':
        case 'processing':
            return 50;
        case 'review':
            return 75;
        case 'approved':
        case 'rejected':
            return 100;
        default:
            return 0;
    }
}

function getDocumentIcon($document_name) {
    $extension = pathinfo($document_name, PATHINFO_EXTENSION);
    
    switch (strtolower($extension)) {
        case 'pdf':
            return 'far fa-file-pdf';
        case 'doc':
        case 'docx':
            return 'far fa-file-word';
        case 'xls':
        case 'xlsx':
            return 'far fa-file-excel';
        case 'jpg':
        case 'jpeg':
        case 'png':
            return 'far fa-file-image';
        default:
            return 'far fa-file';
    }
}
?>

<?php include '../includes/footer.php'; ?> 