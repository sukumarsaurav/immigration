<?php
$page_title = "Admin Dashboard";
$is_admin_page = true;
include '../includes/functions.php';
include '../includes/db_connect.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isUserType('admin')) {
    header("Location: ../user/login.php?redirect=admin/index.php");
    exit();
}

// Get admin dashboard stats
$stats = array();

// Total users
$users_query = "SELECT COUNT(*) as total FROM users WHERE user_type = 'client'";
$stats['total_users'] = $conn->query($users_query)->fetch_assoc()['total'];

// Total applications
$applications_query = "SELECT COUNT(*) as total FROM applications";
$stats['total_applications'] = $conn->query($applications_query)->fetch_assoc()['total'];

// Pending applications
$pending_query = "SELECT COUNT(*) as total FROM applications WHERE status IN ('submitted', 'in_progress', 'processing', 'review')";
$stats['pending_applications'] = $conn->query($pending_query)->fetch_assoc()['total'];

// Total consultants
$consultants_query = "SELECT COUNT(*) as total FROM users WHERE user_type = 'consultant'";
$stats['total_consultants'] = $conn->query($consultants_query)->fetch_assoc()['total'];

// Upcoming appointments
$appointments_query = "SELECT COUNT(*) as total FROM appointments WHERE appointment_date >= CURDATE() AND status = 'scheduled'";
$stats['upcoming_appointments'] = $conn->query($appointments_query)->fetch_assoc()['total'];

// Blog posts
$posts_query = "SELECT COUNT(*) as total FROM blog_posts";
$stats['total_posts'] = $conn->query($posts_query)->fetch_assoc()['total'];

// Recent activity
$activity_query = "SELECT a.action, a.timestamp, u.first_name, u.last_name 
                  FROM activity_log a 
                  LEFT JOIN users u ON a.user_id = u.user_id 
                  ORDER BY a.timestamp DESC LIMIT 10";
$recent_activity = $conn->query($activity_query);

// Recent applications
$recent_applications_query = "SELECT a.application_id, a.status, a.last_updated, 
                              u.first_name, u.last_name, v.visa_name 
                              FROM applications a 
                              JOIN users u ON a.user_id = u.user_id 
                              JOIN visa_types v ON a.visa_id = v.visa_id 
                              ORDER BY a.last_updated DESC LIMIT 5";
$recent_applications = $conn->query($recent_applications_query);

include 'includes/admin_header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Admin Dashboard</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item active">Dashboard</li>
    </ol>
    
    <!-- Dashboard Stats -->
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Users</div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo $stats['total_users']; ?></div>
                        </div>
                        <div><i class="fas fa-users fa-2x"></i></div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="users.php">View Details</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Applications</div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo $stats['total_applications']; ?></div>
                        </div>
                        <div><i class="fas fa-file-alt fa-2x"></i></div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="applications.php">View Details</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Pending Applications</div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo $stats['pending_applications']; ?></div>
                        </div>
                        <div><i class="fas fa-clock fa-2x"></i></div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="applications.php?status=pending">View Details</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Appointments</div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo $stats['upcoming_appointments']; ?></div>
                        </div>
                        <div><i class="fas fa-calendar-alt fa-2x"></i></div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="appointments.php">View Details</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity and Applications -->
    <div class="row">
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-history me-1"></i>
                    Recent Activity
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recent_activity->num_rows > 0): ?>
                                    <?php while ($activity = $recent_activity->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $activity['first_name'] ? $activity['first_name'] . ' ' . $activity['last_name'] : 'System'; ?></td>
                                            <td><?php echo htmlspecialchars($activity['action']); ?></td>
                                            <td><?php echo formatDateTime($activity['timestamp']); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center">No recent activity</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer text-center">
                    <a href="activity_log.php">View All Activity</a>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-file-alt me-1"></i>
                    Recent Applications
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Client</th>
                                    <th>Visa Type</th>
                                    <th>Status</th>
                                    <th>Last Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recent_applications->num_rows > 0): ?>
                                    <?php while ($app = $recent_applications->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $app['first_name'] . ' ' . $app['last_name']; ?></td>
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
                                            <td><?php echo formatDate($app['last_updated']); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No recent applications</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer text-center">
                    <a href="applications.php">View All Applications</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?> 