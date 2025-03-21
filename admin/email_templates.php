<?php
$page_title = "Email Templates";
$is_admin_page = true;
include '../includes/functions.php';
include '../includes/db_connect.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isUserType('admin')) {
    header("Location: ../user/login.php?redirect=admin/email_templates.php");
    exit();
}

// Load TinyMCE
$load_tinymce = true;

// Get all email templates
$templates_query = "SELECT * FROM email_templates ORDER BY template_name ASC";
$templates = $conn->query($templates_query);

$errors = array();
$success_message = '';

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'delete_template':
                $template_id = $_POST['template_id'];
                
                $stmt = $conn->prepare("DELETE FROM email_templates WHERE template_id = ?");
                $stmt->bind_param("i", $template_id);
                
                if ($stmt->execute()) {
                    logActivity($_SESSION['user_id'], "Deleted email template #$template_id");
                    $success_message = "Email template deleted successfully!";
                    
                    // Reload templates
                    $templates = $conn->query($templates_query);
                } else {
                    $errors[] = "Error deleting template: " . $conn->error;
                }
                break;
                
            case 'test_email':
                $template_id = $_POST['template_id'];
                $test_email = $_POST['test_email'];
                
                // Get template
                $stmt = $conn->prepare("SELECT * FROM email_templates WHERE template_id = ?");
                $stmt->bind_param("i", $template_id);
                $stmt->execute();
                $template = $stmt->get_result()->fetch_assoc();
                
                // Send test email
                if (sendEmail($test_email, $template['subject'], $template['body'], true)) {
                    $success_message = "Test email sent successfully to $test_email!";
                    logActivity($_SESSION['user_id'], "Sent test email for template #$template_id to $test_email");
                } else {
                    $errors[] = "Error sending test email. Please check your mail server settings.";
                }
                break;
        }
    }
}

include 'includes/admin_header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4 mb-4">Email Templates</h1>
    
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
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-envelope me-1"></i>
                Email Templates
            </div>
            <a href="email_template_edit.php" class="btn btn-primary btn-sm">
                <i class="fas fa-plus me-1"></i> Add New Template
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Template Name</th>
                            <th>Subject</th>
                            <th>Last Updated</th>
                            <th width="200">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($templates->num_rows > 0): ?>
                            <?php while ($template = $templates->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($template['template_name']); ?></td>
                                    <td><?php echo htmlspecialchars($template['subject']); ?></td>
                                    <td><?php echo formatDate($template['updated_at']); ?></td>
                                    <td>
                                        <a href="email_template_edit.php?id=<?php echo $template['template_id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#testModal<?php echo $template['template_id']; ?>">
                                            <i class="fas fa-paper-plane"></i> Test
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $template['template_id']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        
                                        <!-- Test Modal -->
                                        <div class="modal fade" id="testModal<?php echo $template['template_id']; ?>" tabindex="-1" aria-labelledby="testModalLabel" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="post">
                                                        <input type="hidden" name="action" value="test_email">
                                                        <input type="hidden" name="template_id" value="<?php echo $template['template_id']; ?>">
                                                        
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="testModalLabel">Send Test Email</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label for="test_email" class="form-label">Email Address</label>
                                                                <input type="email" class="form-control" id="test_email" name="test_email" required>
                                                                <div class="form-text">The test email will be sent to this address.</div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-primary">Send Test</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Delete Modal -->
                                        <div class="modal fade" id="deleteModal<?php echo $template['template_id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="post">
                                                        <input type="hidden" name="action" value="delete_template">
                                                        <input type="hidden" name="template_id" value="<?php echo $template['template_id']; ?>">
                                                        
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            Are you sure you want to delete the template "<?php echo htmlspecialchars($template['template_name']); ?>"? This action cannot be undone.
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-danger">Delete</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center">No email templates found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?> 