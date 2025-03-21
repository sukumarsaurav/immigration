<?php
$page_title = "Edit Email Template";
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

$template = array(
    'template_id' => '',
    'template_name' => '',
    'template_key' => '',
    'subject' => '',
    'body' => '',
    'description' => '',
    'available_variables' => '{name}, {email}, {application_id}, {visa_type}, {status}'
);

$errors = array();
$success_message = '';

// Check if editing existing template
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $template_id = $_GET['id'];
    
    // Get template data
    $stmt = $conn->prepare("SELECT * FROM email_templates WHERE template_id = ?");
    $stmt->bind_param("i", $template_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $template = $result->fetch_assoc();
    } else {
        // Template not found
        header("Location: email_templates.php");
        exit();
    }
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate inputs
    $template['template_name'] = trim($_POST['template_name']);
    $template['template_key'] = trim($_POST['template_key']);
    $template['subject'] = trim($_POST['subject']);
    $template['body'] = $_POST['body'];
    $template['description'] = trim($_POST['description']);
    $template['available_variables'] = trim($_POST['available_variables']);
    
    // Check for errors
    if (empty($template['template_name'])) {
        $errors[] = "Template name is required";
    }
    
    if (empty($template['template_key'])) {
        $errors[] = "Template key is required";
    }
    
    if (empty($template['subject'])) {
        $errors[] = "Subject is required";
    }
    
    if (empty($template['body'])) {
        $errors[] = "Body content is required";
    }
    
    // If no errors, save the template
    if (empty($errors)) {
        if (empty($template['template_id'])) {
            // Check if template key already exists
            $check_stmt = $conn->prepare("SELECT template_id FROM email_templates WHERE template_key = ?");
            $check_stmt->bind_param("s", $template['template_key']);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $errors[] = "Template key already exists. Please use a different key.";
            } else {
                // Create new template
                $stmt = $conn->prepare("INSERT INTO email_templates (template_name, template_key, subject, body, description, available_variables) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssss", $template['template_name'], $template['template_key'], $template['subject'], $template['body'], $template['description'], $template['available_variables']);
                
                if ($stmt->execute()) {
                    $template['template_id'] = $conn->insert_id;
                    logActivity($_SESSION['user_id'], "Created email template: {$template['template_name']}");
                    $success_message = "Email template created successfully!";
                } else {
                    $errors[] = "Error creating template: " . $conn->error;
                }
            }
        } else {
            // Update existing template
            $stmt = $conn->prepare("UPDATE email_templates SET template_name = ?, template_key = ?, subject = ?, body = ?, description = ?, available_variables = ?, updated_at = CURRENT_TIMESTAMP WHERE template_id = ?");
            $stmt->bind_param("ssssssi", $template['template_name'], $template['template_key'], $template['subject'], $template['body'], $template['description'], $template['available_variables'], $template['template_id']);
            
            if ($stmt->execute()) {
                logActivity($_SESSION['user_id'], "Updated email template: {$template['template_name']}");
                $success_message = "Email template updated successfully!";
            } else {
                $errors[] = "Error updating template: " . $conn->error;
            }
        }
    }
}

include 'includes/admin_header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4 mb-4">
        <?php echo empty($template['template_id']) ? 'Add New Email Template' : 'Edit Email Template'; ?>
    </h1>
    
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
            <i class="fas fa-envelope me-1"></i>
            Template Details
        </div>
        <div class="card-body">
            <form method="post">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="template_name" class="form-label">Template Name</label>
                        <input type="text" class="form-control" id="template_name" name="template_name" value="<?php echo htmlspecialchars($template['template_name']); ?>" required>
                        <div class="form-text">Descriptive name for internal use (e.g. "Application Confirmation")</div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="template_key" class="form-label">Template Key</label>
                        <input type="text" class="form-control" id="template_key" name="template_key" value="<?php echo htmlspecialchars($template['template_key']); ?>" pattern="[a-z0-9_]+" required>
                        <div class="form-text">Unique identifier (e.g. "application_confirmation"). Use only lowercase letters, numbers, and underscores.</div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="2"><?php echo htmlspecialchars($template['description']); ?></textarea>
                    <div class="form-text">Brief description of when this template is used</div>
                </div>
                
                <div class="mb-3">
                    <label for="subject" class="form-label">Email Subject</label>
                    <input type="text" class="form-control" id="subject" name="subject" value="<?php echo htmlspecialchars($template['subject']); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="body" class="form-label">Email Body</label>
                    <textarea class="form-control tinymce" id="body" name="body" rows="12"><?php echo htmlspecialchars($template['body']); ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="available_variables" class="form-label">Available Variables</label>
                    <input type="text" class="form-control" id="available_variables" name="available_variables" value="<?php echo htmlspecialchars($template['available_variables']); ?>">
                    <div class="form-text">Comma-separated list of variables that can be used in this template</div>
                </div>
                
                <div class="alert alert-info">
                    <strong>Tip:</strong> Use variables in your email by enclosing them in curly braces, e.g. <code>{name}</code>, <code>{application_id}</code>, etc.
                </div>
                
                <div class="mb-3 text-end">
                    <a href="email_templates.php" class="btn btn-outline-secondary me-2">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Template</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?> 