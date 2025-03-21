<?php
session_start();
include '../includes/header.php';
include '../includes/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../user/login.php?redirect=booking/document_upload.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user's applications
$stmt = $conn->prepare("SELECT a.application_id, a.status, v.visa_name 
                        FROM applications a 
                        JOIN visa_types v ON a.visa_id = v.visa_id 
                        WHERE a.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Handle file upload
$upload_message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload'])) {
    $application_id = $_POST['application_id'];
    $document_name = $_POST['document_name'];
    
    // Check if application belongs to user
    $check_stmt = $conn->prepare("SELECT application_id FROM applications WHERE application_id = ? AND user_id = ?");
    $check_stmt->bind_param("ii", $application_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Process file upload
        $target_dir = "../uploads/documents/";
        $file_extension = strtolower(pathinfo($_FILES["document_file"]["name"], PATHINFO_EXTENSION));
        $new_filename = $user_id . '_' . $application_id . '_' . time() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        // Check file type
        $allowed_types = array('pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx');
        if (!in_array($file_extension, $allowed_types)) {
            $upload_message = '<div class="alert alert-danger">Sorry, only PDF, JPG, JPEG, PNG, DOC & DOCX files are allowed.</div>';
        } else if ($_FILES["document_file"]["size"] > 5000000) { // 5MB max
            $upload_message = '<div class="alert alert-danger">Sorry, your file is too large. Maximum size is 5MB.</div>';
        } else if (move_uploaded_file($_FILES["document_file"]["tmp_name"], $target_file)) {
            // Save file info to database
            $file_path = $new_filename;
            $insert_stmt = $conn->prepare("INSERT INTO documents (application_id, document_name, file_path) VALUES (?, ?, ?)");
            $insert_stmt->bind_param("iss", $application_id, $document_name, $file_path);
            
            if ($insert_stmt->execute()) {
                $upload_message = '<div class="alert alert-success">The file has been uploaded successfully.</div>';
            } else {
                $upload_message = '<div class="alert alert-danger">Sorry, there was an error uploading your file to the database.</div>';
            }
        } else {
            $upload_message = '<div class="alert alert-danger">Sorry, there was an error uploading your file.</div>';
        }
    } else {
        $upload_message = '<div class="alert alert-danger">Invalid application selected.</div>';
    }
}

// Get uploaded documents
$documents = array();
if (isset($_GET['application_id'])) {
    $app_id = $_GET['application_id'];
    
    // Verify application belongs to user
    $check_stmt = $conn->prepare("SELECT application_id FROM applications WHERE application_id = ? AND user_id = ?");
    $check_stmt->bind_param("ii", $app_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $doc_stmt = $conn->prepare("SELECT document_id, document_name, file_path, upload_date FROM documents WHERE application_id = ? ORDER BY upload_date DESC");
        $doc_stmt->bind_param("i", $app_id);
        $doc_stmt->execute();
        $documents = $doc_stmt->get_result();
    }
}
?>

<main class="document-upload-page">
    <div class="container">
        <h1>Document Upload Portal</h1>
        <p>Securely upload documents for your visa applications.</p>
        
        <?php echo $upload_message; ?>
        
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3>Your Applications</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($result->num_rows > 0): ?>
                            <ul class="application-list">
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <li>
                                        <a href="?application_id=<?php echo $row['application_id']; ?>" class="application-link <?php echo isset($_GET['application_id']) && $_GET['application_id'] == $row['application_id'] ? 'active' : ''; ?>">
                                            <span class="visa-type"><?php echo htmlspecialchars($row['visa_name']); ?></span>
                                            <span class="status <?php echo strtolower($row['status']); ?>"><?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?></span>
                                        </a>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        <?php else: ?>
                            <p>You don't have any active applications. <a href="../visa_types/index.php">Start a new application</a>.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <?php if (isset($_GET['application_id'])): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3>Upload New Document</h3>
                        </div>
                        <div class="card-body">
                            <form method="post" enctype="multipart/form-data">
                                <input type="hidden" name="application_id" value="<?php echo $_GET['application_id']; ?>">
                                
                                <div class="form-group">
                                    <label for="document_name">Document Name/Type:</label>
                                    <select name="document_name" id="document_name" class="form-control" required>
                                        <option value="">Select document type</option>
                                        <option value="Passport">Passport</option>
                                        <option value="Birth Certificate">Birth Certificate</option>
                                        <option value="Marriage Certificate">Marriage Certificate</option>
                                        <option value="Education Credential">Education Credential</option>
                                        <option value="Language Test Result">Language Test Result</option>
                                        <option value="Employment Reference">Employment Reference</option>
                                        <option value="Police Clearance">Police Clearance</option>
                                        <option value="Medical Exam">Medical Exam</option>
                                        <option value="Bank Statement">Bank Statement</option>
                                        <option value="Other">Other Document</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="document_file">Select File:</label>
                                    <input type="file" name="document_file" id="document_file" class="form-control-file" required>
                                    <small class="form-text text-muted">Accepted formats: PDF, JPG, JPEG, PNG, DOC, DOCX. Maximum size: 5MB.</small>
                                </div>
                                
                                <button type="submit" name="upload" class="btn btn-primary">Upload Document</button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="card mt-4">
                        <div class="card-header">
                            <h3>Uploaded Documents</h3>
                        </div>
                        <div class="card-body">
                            <?php if ($documents && $documents->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Document Type</th>
                                                <th>Upload Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($doc = $documents->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($doc['document_name']); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($doc['upload_date'])); ?></td>
                                                    <td>
                                                        <a href="../uploads/documents/<?php echo $doc['file_path']; ?>" target="_blank" class="btn btn-sm btn-info">View</a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p>No documents uploaded for this application yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body text-center">
                            <h3>Document Management</h3>
                            <p>Please select an application from the list to upload or view documents.</p>
                            <img src="../assets/images/document-icon.svg" alt="Documents" class="img-fluid" style="max-width: 200px;">
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?> 