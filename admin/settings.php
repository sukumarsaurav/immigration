<?php
$page_title = "System Settings";
$is_admin_page = true;
include '../includes/functions.php';
include '../includes/db_connect.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isUserType('admin')) {
    header("Location: ../user/login.php?redirect=admin/settings.php");
    exit();
}

// Get all system settings
$settings_query = "SELECT * FROM system_settings ORDER BY setting_group ASC, display_order ASC, setting_key ASC";
$settings = $conn->query($settings_query);

// Group settings by their group
$grouped_settings = array();

// Group settings
while ($setting = $settings->fetch_assoc()) {
    $group = $setting['setting_group'];
    
    if (!isset($grouped_settings[$group])) {
        $grouped_settings[$group] = array();
    }
    
    $grouped_settings[$group][] = $setting;
}

$errors = array();
$success_message = '';

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_settings':
                foreach ($_POST as $key => $value) {
                    if (strpos($key, 'setting_') === 0) {
                        $setting_id = substr($key, 8); // Remove 'setting_' prefix
                        
                        if (is_numeric($setting_id)) {
                            $value = trim($value);
                            
                            $stmt = $conn->prepare("UPDATE system_settings SET setting_value = ?, updated_at = CURRENT_TIMESTAMP WHERE setting_id = ?");
                            $stmt->bind_param("si", $value, $setting_id);
                            $stmt->execute();
                        }
                    }
                }
                
                logActivity($_SESSION['user_id'], "Updated system settings");
                $success_message = "Settings updated successfully!";
                
                // Reload settings
                $settings = $conn->query($settings_query);
                $grouped_settings = array();
                while ($setting = $settings->fetch_assoc()) {
                    $group = $setting['setting_group'];
                    if (!isset($grouped_settings[$group])) {
                        $grouped_settings[$group] = array();
                    }
                    $grouped_settings[$group][] = $setting;
                }
                break;
                
            case 'add_setting':
                $key = trim($_POST['setting_key']);
                $value = trim($_POST['setting_value']);
                $description = trim($_POST['setting_description']);
                $type = $_POST['setting_type'];
                $group = $_POST['setting_group'];
                $is_public = isset($_POST['is_public']) ? 1 : 0;
                $options = trim($_POST['setting_options']);
                
                // Validate key - only letters, numbers, and underscores allowed
                if (!preg_match('/^[a-zA-Z0-9_]+$/', $key)) {
                    $errors[] = "Setting key can only contain letters, numbers, and underscores.";
                } else {
                    // Check if key already exists
                    $check_stmt = $conn->prepare("SELECT setting_id FROM system_settings WHERE setting_key = ?");
                    $check_stmt->bind_param("s", $key);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();
                    
                    if ($check_result->num_rows > 0) {
                        $errors[] = "Setting key already exists. Please use a different key.";
                    } else {
                        // Add new setting
                        $stmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_type, setting_options, setting_description, is_public, setting_group) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("sssssss", $key, $value, $type, $options, $description, $is_public, $group);
                        
                        if ($stmt->execute()) {
                            logActivity($_SESSION['user_id'], "Added new system setting: $key");
                            $success_message = "New setting added successfully!";
                            
                            // Reload settings
                            $settings = $conn->query($settings_query);
                            $grouped_settings = array();
                            while ($setting = $settings->fetch_assoc()) {
                                $group = $setting['setting_group'];
                                if (!isset($grouped_settings[$group])) {
                                    $grouped_settings[$group] = array();
                                }
                                $grouped_settings[$group][] = $setting;
                            }
                        } else {
                            $errors[] = "Error adding setting: " . $conn->error;
                        }
                    }
                }
                break;
                
            case 'delete_setting':
                $setting_id = $_POST['setting_id'];
                
                $stmt = $conn->prepare("DELETE FROM system_settings WHERE setting_id = ?");
                $stmt->bind_param("i", $setting_id);
                
                if ($stmt->execute()) {
                    logActivity($_SESSION['user_id'], "Deleted system setting #$setting_id");
                    $success_message = "Setting deleted successfully!";
                    
                    // Reload settings
                    $settings = $conn->query($settings_query);
                    $grouped_settings = array();
                    while ($setting = $settings->fetch_assoc()) {
                        $group = $setting['setting_group'];
                        if (!isset($grouped_settings[$group])) {
                            $grouped_settings[$group] = array();
                        }
                        $grouped_settings[$group][] = $setting;
                    }
                } else {
                    $errors[] = "Error deleting setting: " . $conn->error;
                }
                break;
        }
    }
}

include 'includes/admin_header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">System Settings</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
        <li class="breadcrumb-item active">System Settings</li>
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
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-cogs me-1"></i>
                Manage System Settings
            </div>
            <div>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addSettingModal">
                    <i class="fas fa-plus me-1"></i> Add New Setting
                </button>
            </div>
        </div>
        <div class="card-body">
            <form method="post">
                <input type="hidden" name="action" value="update_settings">
                
                <ul class="nav nav-tabs mb-4" id="settingsTabs" role="tablist">
                    <?php $first_tab = true; ?>
                    <?php foreach ($grouped_settings as $group => $group_settings): ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?php echo $first_tab ? 'active' : ''; ?>" 
                                    id="<?php echo $group; ?>-tab" 
                                    data-bs-toggle="tab" 
                                    data-bs-target="#<?php echo $group; ?>" 
                                    type="button" 
                                    role="tab" 
                                    aria-controls="<?php echo $group; ?>" 
                                    aria-selected="<?php echo $first_tab ? 'true' : 'false'; ?>">
                                <?php echo ucfirst($group); ?>
                            </button>
                        </li>
                        <?php $first_tab = false; ?>
                    <?php endforeach; ?>
                </ul>
                
                <div class="tab-content" id="settingsTabContent">
                    <?php $first_tab = true; ?>
                    <?php foreach ($grouped_settings as $group => $group_settings): ?>
                        <div class="tab-pane fade <?php echo $first_tab ? 'show active' : ''; ?>" 
                             id="<?php echo $group; ?>" 
                             role="tabpanel" 
                             aria-labelledby="<?php echo $group; ?>-tab">
                            
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th style="width: 30%;">Setting</th>
                                            <th style="width: 50%;">Value</th>
                                            <th style="width: 20%;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($group_settings as $setting): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($setting['setting_key']); ?></strong>
                                                    <?php if (!empty($setting['setting_description'])): ?>
                                                        <p class="text-muted small mb-0"><?php echo htmlspecialchars($setting['setting_description']); ?></p>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php switch($setting['setting_type']): 
                                                        case 'textarea': ?>
                                                            <textarea class="form-control" name="setting_<?php echo $setting['setting_id']; ?>" rows="3"><?php echo htmlspecialchars($setting['setting_value']); ?></textarea>
                                                            <?php break; ?>
                                                        
                                                        <?php case 'select': ?>
                                                            <select class="form-select" name="setting_<?php echo $setting['setting_id']; ?>">
                                                                <?php 
                                                                $options = json_decode($setting['setting_options'], true);
                                                                if (is_array($options)) {
                                                                    foreach ($options as $option_value => $option_label) {
                                                                        $selected = $setting['setting_value'] == $option_value ? 'selected' : '';
                                                                        echo "<option value=\"$option_value\" $selected>$option_label</option>";
                                                                    }
                                                                }
                                                                ?>
                                                            </select>
                                                            <?php break; ?>
                                                            
                                                        <?php case 'boolean': ?>
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox" name="setting_<?php echo $setting['setting_id']; ?>" id="setting_<?php echo $setting['setting_id']; ?>" value="1" <?php echo $setting['setting_value'] ? 'checked' : ''; ?>>
                                                                <label class="form-check-label" for="setting_<?php echo $setting['setting_id']; ?>">
                                                                    <?php echo $setting['setting_value'] ? 'Enabled' : 'Disabled'; ?>
                                                                </label>
                                                            </div>
                                                            <?php break; ?>
                                                            
                                                        <?php default: ?>
                                                            <input type="<?php echo $setting['setting_type']; ?>" class="form-control" name="setting_<?php echo $setting['setting_id']; ?>" value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                                    <?php endswitch; ?>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $setting['setting_id']; ?>">
                                                        <i class="fas fa-trash-alt"></i> Delete
                                                    </button>
                                                    
                                                    <!-- Delete Modal -->
                                                    <div class="modal fade" id="deleteModal<?php echo $setting['setting_id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <form method="post">
                                                                    <input type="hidden" name="action" value="delete_setting">
                                                                    <input type="hidden" name="setting_id" value="<?php echo $setting['setting_id']; ?>">
                                                                    
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <p>Are you sure you want to delete the setting "<?php echo htmlspecialchars($setting['setting_key']); ?>"?</p>
                                                                        <p class="text-danger">Warning: This could break functionality if the setting is being used elsewhere in the system.</p>
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
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php $first_tab = false; ?>
                    <?php endforeach; ?>
                </div>
                
                <div class="text-end mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Save All Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Setting Modal -->
<div class="modal fade" id="addSettingModal" tabindex="-1" aria-labelledby="addSettingModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="action" value="add_setting">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="addSettingModalLabel">Add New Setting</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="setting_key" class="form-label">Setting Key</label>
                        <input type="text" class="form-control" id="setting_key" name="setting_key" required pattern="[a-zA-Z0-9_]+">
                        <div class="form-text">Use only letters, numbers, and underscores (e.g., site_name)</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="setting_value" class="form-label">Setting Value</label>
                        <input type="text" class="form-control" id="setting_value" name="setting_value">
                    </div>
                    
                    <div class="mb-3">
                        <label for="setting_description" class="form-label">Description</label>
                        <input type="text" class="form-control" id="setting_description" name="setting_description">
                    </div>
                    
                    <div class="mb-3">
                        <label for="setting_type" class="form-label">Type</label>
                        <select class="form-select" id="setting_type" name="setting_type">
                            <option value="text">Text</option>
                            <option value="number">Number</option>
                            <option value="email">Email</option>
                            <option value="textarea">Text Area</option>
                            <option value="select">Select / Dropdown</option>
                            <option value="boolean">Boolean / Toggle</option>
                            <option value="color">Color Picker</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="setting_options" class="form-label">Options (for select type)</label>
                        <textarea class="form-control" id="setting_options" name="setting_options" rows="3"></textarea>
                        <div class="form-text">JSON format: {"value1":"Label 1","value2":"Label 2"}</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="setting_group" class="form-label">Group</label>
                        <select class="form-select" id="setting_group" name="setting_group">
                            <option value="general">General</option>
                            <option value="email">Email</option>
                            <option value="contact">Contact</option>
                            <option value="application">Application</option>
                            <option value="payment">Payment</option>
                            <option value="social">Social Media</option>
                            <option value="appearance">Appearance</option>
                            <option value="api">API</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_public" name="is_public" value="1">
                        <label class="form-check-label" for="is_public">Publicly accessible</label>
                        <div class="form-text">If checked, this setting may be visible to non-admin users</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Setting</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?> 