<?php
$page_title = "Edit Blog Post";
$is_admin_page = true;
include '../includes/functions.php';
include '../includes/db_connect.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isUserType('admin')) {
    header("Location: ../user/login.php?redirect=admin/blog_posts.php");
    exit();
}

// Load TinyMCE
$load_tinymce = true;

// Get all categories for dropdown
$categories_query = "SELECT * FROM blog_categories ORDER BY category_name ASC";
$categories = $conn->query($categories_query);

$post = array(
    'post_id' => '',
    'title' => '',
    'slug' => '',
    'content' => '',
    'excerpt' => '',
    'featured_image' => '',
    'status' => 'draft',
    'author_id' => $_SESSION['user_id']
);

$post_categories = array();
$errors = array();
$success_message = '';

// Check if editing existing post
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $post_id = $_GET['id'];
    
    // Get post data
    $stmt = $conn->prepare("SELECT * FROM blog_posts WHERE post_id = ?");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $post = $result->fetch_assoc();
        
        // Get categories for this post
        $cat_stmt = $conn->prepare("SELECT category_id FROM blog_post_categories WHERE post_id = ?");
        $cat_stmt->bind_param("i", $post_id);
        $cat_stmt->execute();
        $cat_result = $cat_stmt->get_result();
        
        while ($row = $cat_result->fetch_assoc()) {
            $post_categories[] = $row['category_id'];
        }
    } else {
        // Post not found
        header("Location: blog_posts.php");
        exit();
    }
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate inputs
    $post['title'] = trim($_POST['title']);
    $post['content'] = trim($_POST['content']);
    $post['excerpt'] = trim($_POST['excerpt']);
    $post['status'] = $_POST['status'];
    $post['author_id'] = $_SESSION['user_id'];
    $post_categories = isset($_POST['categories']) ? $_POST['categories'] : array();
    
    // Generate slug if empty or new post
    if (empty($_POST['slug']) || empty($post['post_id'])) {
        $post['slug'] = createSlug($post['title']);
    } else {
        $post['slug'] = trim($_POST['slug']);
    }
    
    // Validate title
    if (empty($post['title'])) {
        $errors[] = "Title is required";
    }
    
    // Validate content
    if (empty($post['content'])) {
        $errors[] = "Content is required";
    }
    
    // Validate slug uniqueness
    $slug_stmt = $conn->prepare("SELECT post_id FROM blog_posts WHERE slug = ? AND post_id != ?");
    $slug_stmt->bind_param("si", $post['slug'], $post['post_id']);
    $slug_stmt->execute();
    $slug_result = $slug_stmt->get_result();
    
    if ($slug_result->num_rows > 0) {
        $errors[] = "Slug already exists. Please choose a different one.";
    }
    
    // Handle featured image upload
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['size'] > 0) {
        $upload = uploadImage($_FILES['featured_image'], '../uploads/blog/');
        
        if (isset($upload['error'])) {
            $errors[] = $upload['error'];
        } else {
            $post['featured_image'] = $upload['path'];
        }
    }
    
    // If no errors, save the post
    if (empty($errors)) {
        if (empty($post['post_id'])) {
            // Create new post
            $stmt = $conn->prepare("INSERT INTO blog_posts (title, slug, content, excerpt, featured_image, status, author_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssi", $post['title'], $post['slug'], $post['content'], $post['excerpt'], $post['featured_image'], $post['status'], $post['author_id']);
            
            if ($stmt->execute()) {
                $post['post_id'] = $conn->insert_id;
                
                // Save categories
                foreach ($post_categories as $category_id) {
                    $cat_stmt = $conn->prepare("INSERT INTO blog_post_categories (post_id, category_id) VALUES (?, ?)");
                    $cat_stmt->bind_param("ii", $post['post_id'], $category_id);
                    $cat_stmt->execute();
                }
                
                logActivity($_SESSION['user_id'], "Created new blog post: {$post['title']}");
                $success_message = "Blog post created successfully!";
            } else {
                $errors[] = "Error creating blog post: " . $conn->error;
            }
        } else {
            // Update existing post
            $stmt = $conn->prepare("UPDATE blog_posts SET title = ?, slug = ?, content = ?, excerpt = ?, featured_image = ?, status = ? WHERE post_id = ?");
            $stmt->bind_param("ssssssi", $post['title'], $post['slug'], $post['content'], $post['excerpt'], $post['featured_image'], $post['status'], $post['post_id']);
            
            if ($stmt->execute()) {
                // Clear existing categories
                $cat_stmt = $conn->prepare("DELETE FROM blog_post_categories WHERE post_id = ?");
                $cat_stmt->bind_param("i", $post['post_id']);
                $cat_stmt->execute();
                
                // Save new categories
                foreach ($post_categories as $category_id) {
                    $cat_stmt = $conn->prepare("INSERT INTO blog_post_categories (post_id, category_id) VALUES (?, ?)");
                    $cat_stmt->bind_param("ii", $post['post_id'], $category_id);
                    $cat_stmt->execute();
                }
                
                logActivity($_SESSION['user_id'], "Updated blog post: {$post['title']}");
                $success_message = "Blog post updated successfully!";
            } else {
                $errors[] = "Error updating blog post: " . $conn->error;
            }
        }
    }
}

include 'includes/admin_header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo empty($post['post_id']) ? 'Create Blog Post' : 'Edit Blog Post'; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="blog_posts.php">Blog Posts</a></li>
        <li class="breadcrumb-item active"><?php echo empty($post['post_id']) ? 'Create' : 'Edit'; ?></li>
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
            <i class="fas fa-edit me-1"></i>
            Post Details
        </div>
        <div class="card-body">
            <form action="<?php echo $_SERVER['PHP_SELF'] . (empty($post['post_id']) ? '' : '?id=' . $post['post_id']); ?>" method="post" enctype="multipart/form-data">
                <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                
                <div class="row mb-3">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="slug" class="form-label">Slug</label>
                            <input type="text" class="form-control" id="slug" name="slug" value="<?php echo htmlspecialchars($post['slug']); ?>" placeholder="Leave empty to auto-generate">
                            <small class="text-muted">URL-friendly version of the title. Leave empty to generate automatically.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="excerpt" class="form-label">Excerpt</label>
                            <textarea class="form-control" id="excerpt" name="excerpt" rows="3"><?php echo htmlspecialchars($post['excerpt']); ?></textarea>
                            <small class="text-muted">Short description for SEO and previews (optional).</small>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="draft" <?php echo $post['status'] == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                <option value="published" <?php echo $post['status'] == 'published' ? 'selected' : ''; ?>>Published</option>
                                <option value="archived" <?php echo $post['status'] == 'archived' ? 'selected' : ''; ?>>Archived</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="categories" class="form-label">Categories</label>
                            <select class="form-select" id="categories" name="categories[]" multiple>
                                <?php 
                                if ($categories->num_rows > 0):
                                    while ($category = $categories->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $category['category_id']; ?>" <?php echo in_array($category['category_id'], $post_categories) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['category_name']); ?>
                                    </option>
                                <?php 
                                    endwhile;
                                endif;
                                ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="featured_image" class="form-label">Featured Image</label>
                            <?php if (!empty($post['featured_image'])): ?>
                                <div class="mb-2">
                                    <img src="<?php echo $post['featured_image']; ?>" alt="Featured Image" class="img-thumbnail" style="max-height: 150px;">
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" id="featured_image" name="featured_image" accept="image/*">
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="content" class="form-label">Content</label>
                    <textarea class="form-control tinymce" id="content" name="content" rows="12"><?php echo htmlspecialchars($post['content']); ?></textarea>
                </div>
                
                <div class="mb-3 text-end">
                    <a href="blog_posts.php" class="btn btn-outline-secondary me-2">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Post</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-generate slug from title
    const titleInput = document.getElementById('title');
    const slugInput = document.getElementById('slug');
    
    titleInput.addEventListener('blur', function() {
        if (slugInput.value === '') {
            // Generate a slug from the title
            fetch('../includes/ajax/generate-slug.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'title=' + encodeURIComponent(titleInput.value)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    slugInput.value = data.slug;
                }
            })
            .catch(error => {
                console.error('Error generating slug:', error);
            });
        }
    });
});
</script>

<?php include 'includes/admin_footer.php'; ?> 