<?php
$page_title = "Manage Appointments";
$is_admin_page = true;
include '../includes/functions.php';
include '../includes/db_connect.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isUserType('admin')) {
    header("Location: ../user/login.php?redirect=admin/appointments.php");
    exit();
}

// Set default filters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$consultant = isset($_GET['consultant']) ? $_GET['consultant'] : '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 15;
$offset = ($page - 1) * $records_per_page;

// Get consultants for dropdown filter
$consultants_query = "SELECT user_id, first_name, last_name FROM users WHERE user_type = 'consultant' ORDER BY first_name ASC";
$consultants = $conn->query($consultants_query);

// Process appointment actions
$errors = array();
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'update_status':
            $appointment_id = $_POST['appointment_id'];
            $new_status = $_POST['status'];
            $notes = trim($_POST['notes']);
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Update appointment status
                $update_stmt = $conn->prepare("UPDATE appointments SET status = ?, notes = ? WHERE appointment_id = ?");
                $update_stmt->bind_param("ssi", $new_status, $notes, $appointment_id);
                $update_stmt->execute();
                
                // Get appointment details for email
                $app_stmt = $conn->prepare("SELECT a.appointment_date, a.appointment_time, a.appointment_type, 
                                           u.email, u.first_name, u.last_name,
                                           c.first_name AS consultant_first_name, c.last_name AS consultant_last_name
                                           FROM appointments a 
                                           JOIN users u ON a.user_id = u.user_id 
                                           LEFT JOIN users c ON a.consultant_id = c.user_id 
                                           WHERE a.appointment_id = ?");
                $app_stmt->bind_param("i", $appointment_id);
                $app_stmt->execute();
                $apt_details = $app_stmt->get_result()->fetch_assoc();
                
                // Send email notification
                $template_key = 'appointment_status_update';
                $email_data = array(
                    'name' => $apt_details['first_name'] . ' ' . $apt_details['last_name'],
                    'email' => $apt_details['email'],
                    'appointment_date' => date('l, F j, Y', strtotime($apt_details['appointment_date'])),
                    'appointment_time' => date('g:i A', strtotime($apt_details['appointment_time'])),
                    'appointment_type' => $apt_details['appointment_type'],
                    'consultant' => $apt_details['consultant_first_name'] . ' ' . $apt_details['consultant_last_name'],
                    'status' => ucfirst($new_status),
                    'notes' => $notes
                );
                
                sendTemplateEmail($apt_details['email'], $template_key, $email_data);
                
                // Log the activity
                logActivity($_SESSION['user_id'], "Updated appointment #$appointment_id status to $new_status");
                
                // Commit transaction
                $conn->commit();
                
                $success_message = "Appointment status updated successfully. Email notification sent to client.";
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollback();
                $errors[] = "Error updating appointment: " . $e->getMessage();
            }
            
            // Refresh appointments list
            header("Location: appointments.php?status=$status_filter&search=$search&date_from=$date_from&date_to=$date_to&consultant=$consultant&page=$page");
            exit();
            break;
            
        case 'delete_appointment':
            $appointment_id = $_POST['appointment_id'];
            
            $stmt = $conn->prepare("DELETE FROM appointments WHERE appointment_id = ?");
            $stmt->bind_param("i", $appointment_id);
            
            if ($stmt->execute()) {
                logActivity($_SESSION['user_id'], "Deleted appointment #$appointment_id");
                $success_message = "Appointment deleted successfully!";
            } else {
                $errors[] = "Error deleting appointment: " . $conn->error;
            }
            
            // Refresh appointments list
            header("Location: appointments.php?status=$status_filter&search=$search&date_from=$date_from&date_to=$date_to&consultant=$consultant&page=$page");
            exit();
            break;
    }
}

// Build query based on filters
$query = "SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.appointment_type, a.status, a.notes,
                 u.user_id, u.first_name, u.last_name, u.email, u.phone,
                 c.first_name AS consultant_first_name, c.last_name AS consultant_last_name
          FROM appointments a
          JOIN users u ON a.user_id = u.user_id
          LEFT JOIN users c ON a.consultant_id = c.user_id
          WHERE 1=1";

$count_query = "SELECT COUNT(*) as total FROM appointments a 
                JOIN users u ON a.user_id = u.user_id
                LEFT JOIN users c ON a.consultant_id = c.user_id
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
    $query .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $count_query .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ssss";
}

if (!empty($date_from)) {
    $query .= " AND a.appointment_date >= ?";
    $count_query .= " AND a.appointment_date >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if (!empty($date_to)) {
    $query .= " AND a.appointment_date <= ?";
    $count_query .= " AND a.appointment_date <= ?";
    $params[] = $date_to;
    $types .= "s";
}

if (!empty($consultant)) {
    $query .= " AND a.consultant_id = ?";
    $count_query .= " AND a.consultant_id = ?";
    $params[] = $consultant;
    $types .= "i";
}

// Add ordering
$query .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";

// Add pagination
$query .= " LIMIT ?, ?";
$params[] = $offset;
$params[] = $records_per_page;
$types .= "ii";

// Execute count query
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $count_types = substr($types, 0, -2); // Remove the 'ii' for LIMIT parameters
    $count_params = array_slice($params, 0, -2);
    $count_stmt->bind_param($count_types, ...$count_params);
}
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Execute main query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$appointments = $stmt->get_result();

include 'includes/admin_header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Manage Appointments</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Appointments</li>
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
            Filter Appointments
        </div>
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Name, Email, Phone">
                </div>
                
                <div class="col-md-2">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Statuses</option>
                        <option value="scheduled" <?php echo $status_filter == 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                        <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        <option value="no_show" <?php echo $status_filter == 'no_show' ? 'selected' : ''; ?>>No Show</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="consultant" class="form-label">Consultant</label>
                    <select class="form-select" id="consultant" name="consultant">
                        <option value="">All Consultants</option>
                        <?php while ($cons = $consultants->fetch_assoc()): ?>
                            <option value="<?php echo $cons['user_id']; ?>" <?php echo $consultant == $cons['user_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cons['first_name'] . ' ' . $cons['last_name']); ?>
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
                <i class="fas fa-calendar-alt me-1"></i>
                Appointments
            </div>
            <div>
                <span class="badge bg-primary"><?php echo $total_records; ?> Total</span>
            </div>
        </div>
        <div class="card-body">
            <?php if ($appointments->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Client</th>
                                <th>Type</th>
                                <th>Consultant</th>
                                <th>Status</th>
                                <th>Notes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($apt = $appointments->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <?php echo date('M j, Y', strtotime($apt['appointment_date'])); ?><br>
                                        <small class="text-muted"><?php echo date('g:i A', strtotime($apt['appointment_time'])); ?></small>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($apt['first_name'] . ' ' . $apt['last_name']); ?><br>
                                        <small><a href="mailto:<?php echo $apt['email']; ?>"><?php echo $apt['email']; ?></a></small><br>
                                        <small><?php echo $apt['phone']; ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($apt['appointment_type']); ?></td>
                                    <td>
                                        <?php if (!empty($apt['consultant_first_name'])): ?>
                                            <?php echo htmlspecialchars($apt['consultant_first_name'] . ' ' . $apt['consultant_last_name']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Not assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $status_class = '';
                                        switch($apt['status']) {
                                            case 'scheduled': $status_class = 'primary'; break;
                                            case 'completed': $status_class = 'success'; break;
                                            case 'cancelled': $status_class = 'danger'; break;
                                            case 'no_show': $status_class = 'warning'; break;
                                            default: $status_class = 'secondary';
                                        }
                                        ?>
                                        <span class="badge bg-<?php echo $status_class; ?>">
                                            <?php echo ucfirst($apt['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($apt['notes'])): ?>
                                            <span class="text-truncate d-inline-block" style="max-width: 150px;" data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($apt['notes']); ?>">
                                                <?php echo htmlspecialchars($apt['notes']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">No notes</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#statusModal<?php echo $apt['appointment_id']; ?>">
                                                Status
                                            </button>
                                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $apt['appointment_id']; ?>">
                                                Delete
                                            </button>
                                        </div>
                                        
                                        <!-- Status Update Modal -->
                                        <div class="modal fade" id="statusModal<?php echo $apt['appointment_id']; ?>" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="post">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="appointment_id" value="<?php echo $apt['appointment_id']; ?>">
                                                        
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="statusModalLabel">Update Appointment Status</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label for="status<?php echo $apt['appointment_id']; ?>" class="form-label">Status</label>
                                                                <select class="form-select" id="status<?php echo $apt['appointment_id']; ?>" name="status" required>
                                                                    <option value="scheduled" <?php echo $apt['status'] == 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                                                    <option value="completed" <?php echo $apt['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                                    <option value="cancelled" <?php echo $apt['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                                    <option value="no_show" <?php echo $apt['status'] == 'no_show' ? 'selected' : ''; ?>>No Show</option>
                                                                </select>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label for="notes<?php echo $apt['appointment_id']; ?>" class="form-label">Notes</label>
                                                                <textarea class="form-control" id="notes<?php echo $apt['appointment_id']; ?>" name="notes" rows="3"><?php echo htmlspecialchars($apt['notes']); ?></textarea>
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
                                        
                                        <!-- Delete Modal -->
                                        <div class="modal fade" id="deleteModal<?php echo $apt['appointment_id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="post">
                                                        <input type="hidden" name="action" value="delete_appointment">
                                                        <input type="hidden" name="appointment_id" value="<?php echo $apt['appointment_id']; ?>">
                                                        
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>Are you sure you want to delete this appointment?</p>
                                                            <p><strong>Date:</strong> <?php echo date('M j, Y', strtotime($apt['appointment_date'])); ?> at <?php echo date('g:i A', strtotime($apt['appointment_time'])); ?></p>
                                                            <p><strong>Client:</strong> <?php echo htmlspecialchars($apt['first_name'] . ' ' . $apt['last_name']); ?></p>
                                                            <p class="text-danger">This action cannot be undone.</p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-danger">Delete Appointment</button>
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
                                <a class="page-link" href="?page=<?php echo $page-1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo $search; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>&consultant=<?php echo $consultant; ?>">Previous</a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&search=<?php echo $search; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>&consultant=<?php echo $consultant; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page+1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo $search; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>&consultant=<?php echo $consultant; ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>
                    No appointments found matching your criteria.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?> 