<?php
$page_title = "Manage Applications";
$is_admin_page = true;
include '../includes/functions.php';
include '../includes/db_connect.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isUserType('admin')) {
    header("Location: ../user/login.php?redirect=admin/applications.php");
    exit();
}

// Set default filters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$visa_type = isset($_GET['visa_type']) ? $_GET['visa_type'] : '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 15;
$offset = ($page - 1) * $records_per_page;

// Build query
$query = "SELECT a.application_id, a.reference_number, a.status, a.created_at, a.last_updated, 
                 u.first_name, u.last_name, u.email, 
                 v.visa_name 
          FROM applications a
          JOIN users u ON a.user_id = u.user_id
          JOIN visa_types v ON a.visa_id = v.visa_id
          WHERE 1=1";

$count_query = "SELECT COUNT(*) as total FROM applications a 
                JOIN users u ON a.user_id = u.user_id
                JOIN visa_types v ON a.visa_id = v.visa_id
                WHERE 1=1";

$params = array();
$types = "";

// Add filters to query
if (!empty($status_filter)) {
    $query .= " AND a.status = ?";
    $count_query .= " AND a.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($search)) {
    $search_term = "%$search%";
    $query .= " AND (a.reference_number LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
    $count_query .= " AND (a.reference_number LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ssss";
}

if (!empty($date_from)) {
    $query .= " AND a.created_at >= ?";
    $count_query .= " AND a.created_at >= ?";
    $params[] = $date_from . " 00:00:00";
    $types .= "s";
}

if (!empty($date_to)) {
    $query .= " AND a.created_at <= ?";
    $count_query .= " AND a.created_at <= ?";
    $params[] = $date_to . " 23:59:59";
    $types .= "s";
}

if (!empty($visa_type)) {
    $query .= " AND a.visa_id = ?";
    $count_query .= " AND a.visa_id = ?";
    $params[] = $visa_type;
    $types .= "i";
}

// Get total records for pagination
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Add pagination to query
$query .= " ORDER BY a.last_updated DESC LIMIT ?, ?";
$params[] = $offset;
$params[] = $records_per_page;
$types .= "ii";

// Execute query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$applications = $stmt->get_result();

// Get all visa types for filter dropdown
$visa_types = $conn->query("SELECT visa_id, visa_name FROM visa_types ORDER BY visa_name");

// Process actions
$errors = array();
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'update_status':
            $application_id = $_POST['application_id'];
            $new_status = $_POST['status'];
            $notes = trim($_POST['notes']);
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Update application status
                $update_stmt = $conn->prepare("UPDATE applications SET status = ?, last_updated = NOW() WHERE application_id = ?");
                $update_stmt->bind_param("si", $new_status, $application_id);
                $update_stmt->execute();
                
                // Add to status history
                $history_stmt = $conn->prepare("INSERT INTO application_status_history (application_id, status, notes, updated_by) VALUES (?, ?, ?, ?)");
                $history_stmt->bind_param("issi", $application_id, $new_status, $notes, $_SESSION['user_id']);
                $history_stmt->execute();
                
                // Get application details for email
                $app_stmt = $conn->prepare("SELECT a.reference_number, u.email, u.first_name, u.last_name, v.visa_name 
                                           FROM applications a 
                                           JOIN users u ON a.user_id = u.user_id 
                                           JOIN visa_types v ON a.visa_id = v.visa_id 
                                           WHERE a.application_id = ?");
                $app_stmt->bind_param("i", $application_id);
                $app_stmt->execute();
                $app_details = $app_stmt->get_result()->fetch_assoc();
                
                // Send email notification
                $template_key = 'application_status_update';
                $email_data = array(
                    'name' => $app_details['first_name'] . ' ' . $app_details['last_name'],
                    'email' => $app_details['email'],
                    'reference_number' => $app_details['reference_number'],
                    'visa_type' => $app_details['visa_name'],
                    'status' => ucfirst(str_replace('_', ' ', $new_status)),
                    'notes' => $notes
                );
                
                sendTemplateEmail($app_details['email'], $template_key, $email_data);
                
                // Log the activity
                logActivity($_SESSION['user_id'], "Updated application #$application_id status to $new_status");
                
                // Commit transaction
                $conn->commit();
                
                $success_message = "Application status updated successfully. Email notification sent to client.";
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollback();
                $errors[] = "Error updating application: " . $e->getMessage();
            }
            
            // Refresh applications list
            header("Location: applications.php?status=$status_filter&search=$search&date_from=$date_from&date_to=$date_to&visa_type=$visa_type&page=$page");
            exit();
            break;
    }
}

include 'includes/admin_header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $page_title; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
        <li class="breadcrumb-item active"><?php echo $page_title; ?></li>
    </ol>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success">
            <?php echo $success_message; ?>
        </div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-filter me-1"></i>
            Filter Applications
        </div>
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Name, email, or reference">
                </div>
                
                <div class="col-md-2">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Statuses</option>
                        <option value="draft" <?php echo $status_filter == 'draft' ? 'selected' : ''; ?>>Draft</option>
                        <option value="submitted" <?php echo $status_filter == 'submitted' ? 'selected' : ''; ?>>Submitted</option>
                        <option value="in_progress" <?php echo $status_filter == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="processing" <?php echo $status_filter == 'processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="review" <?php echo $status_filter == 'review' ? 'selected' : ''; ?>>Under Review</option>
                        <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="visa_type" class="form-label">Visa Type</label>
                    <select class="form-select" id="visa_type" name="visa_type">
                        <option value="">All Visa Types</option>
                        <?php while ($visa = $visa_types->fetch_assoc()): ?>
                            <option value="<?php echo $visa['visa_id']; ?>" <?php echo $visa_type == $visa['visa_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($visa['visa_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="date_from" class="form-label">Date From</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                </div>
                
                <div class="col-md-2">
                    <label for="date_to" class="form-label">Date To</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                </div>
                
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-file-alt me-1"></i>
                Applications
            </div>
            <div>
                <span class="badge bg-primary"><?php echo $total_records; ?> Total</span>
            </div>
        </div>
        <div class="card-body">
            <?php if ($applications->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Applicant</th>
                                <th>Visa Type</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <th>Last Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($app = $applications->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($app['reference_number']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($app['email']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($app['visa_name']); ?></td>
                                    <td>
                                        <?php 
                                        $status_class = '';
                                        switch($app['status']) {
                                            case 'draft': $status_class = 'secondary'; break;
                                            case 'submitted': $status_class = 'primary'; break;
                                            case 'in_progress': 
                                            case 'processing': 
                                            case 'review': $status_class = 'info'; break;
                                            case 'approved': $status_class = 'success'; break;
                                            case 'rejected': $status_class = 'danger'; break;
                                            default: $status_class = 'secondary';
                                        }
                                        ?>
                                        <span class="badge bg-<?php echo $status_class; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $app['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDate($app['created_at']); ?></td>
                                    <td><?php echo formatDate($app['last_updated']); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="application_details.php?id=<?php echo $app['application_id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#statusModal<?php echo $app['application_id']; ?>">Update Status</button>
                                        </div>
                                        
                                        <!-- Status Update Modal -->
                                        <div class="modal fade" id="statusModal<?php echo $app['application_id']; ?>" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="post">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="application_id" value="<?php echo $app['application_id']; ?>">
                                                        
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="statusModalLabel">Update Application Status</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label for="status<?php echo $app['application_id']; ?>" class="form-label">Status</label>
                                                                <select class="form-select" id="status<?php echo $app['application_id']; ?>" name="status" required>
                                                                    <option value="draft" <?php echo $app['status'] == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                                                    <option value="submitted" <?php echo $app['status'] == 'submitted' ? 'selected' : ''; ?>>Submitted</option>
                                                                    <option value="in_progress" <?php echo $app['status'] == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                                                    <option value="processing" <?php echo $app['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                                                    <option value="review" <?php echo $app['status'] == 'review' ? 'selected' : ''; ?>>Under Review</option>
                                                                    <option value="approved" <?php echo $app['status'] == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                                                    <option value="rejected" <?php echo $app['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                                                </select>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label for="notes<?php echo $app['application_id']; ?>" class="form-label">Notes (will be sent to applicant)</label>
                                                                <textarea class="form-control" id="notes<?php echo $app['application_id']; ?>" name="notes" rows="3"></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-primary">Update Status</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page-1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo $search; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>&visa_type=<?php echo $visa_type; ?>">Previous</a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&search=<?php echo $search; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>&visa_type=<?php echo $visa_type; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page+1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo $search; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>&visa_type=<?php echo $visa_type; ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>
                    No applications found matching your criteria.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?> 