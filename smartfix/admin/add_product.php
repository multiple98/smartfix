<?php
session_start();
include('../includes/db.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $category = trim($_POST['category']);
    $stock = intval($_POST['stock']);
    
    // Validate form data
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Product name is required";
    }
    
    if (empty($description)) {
        $errors[] = "Product description is required";
    }
    
    if ($price <= 0) {
        $errors[] = "Price must be greater than zero";
    }
    
    if (empty($category)) {
        $errors[] = "Category is required";
    }
    
    // Process image upload
    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($_FILES['image']['type'], $allowed_types)) {
            $errors[] = "Only JPG, PNG, GIF, and WEBP images are allowed";
        } elseif ($_FILES['image']['size'] > $max_size) {
            $errors[] = "Image size must be less than 5MB";
        } else {
            // Create uploads directory if it doesn't exist
            $upload_dir = "../uploads/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate unique filename
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid('product_') . '.' . $file_extension;
            $image_path = "uploads/" . $file_name;
            $upload_path = $upload_dir . $file_name;
            
            // Move uploaded file
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $errors[] = "Failed to upload image";
            }
        }
    } else {
        $errors[] = "Product image is required";
    }
    
    // If no errors, insert product into database
    if (empty($errors)) {
        try {
            // Check which columns exist in the products table
            $check_stock = "SHOW COLUMNS FROM products LIKE 'stock'";
            $stock_stmt = $pdo->prepare($check_stock);
            $stock_stmt->execute();
            $stock_column_exists = $stock_stmt->rowCount() > 0;
            
            $check_created = "SHOW COLUMNS FROM products LIKE 'created_at'";
            $created_stmt = $pdo->prepare($check_created);
            $created_stmt->execute();
            $created_column_exists = $created_stmt->rowCount() > 0;
            
            // Check if user_id column exists
            $check_user_id = "SHOW COLUMNS FROM products LIKE 'user_id'";
            $user_id_stmt = $pdo->prepare($check_user_id);
            $user_id_stmt->execute();
            $user_id_column_exists = $user_id_stmt->rowCount() > 0;
            
            // Get the current admin user_id for the user_id field if it exists
            $admin_user_id = $_SESSION['user_id'] ?? 1; // Default to 1 if not set
            
            // Build the query based on which columns exist
            $columns = ["name", "description", "price", "category", "image"];
            $placeholders = ["?", "?", "?", "?", "?"];
            $params = [$name, $description, $price, $category, $image_path];
            
            if ($stock_column_exists) {
                $columns[] = "stock";
                $placeholders[] = "?";
                $params[] = $stock;
            }
            
            if ($user_id_column_exists) {
                $columns[] = "user_id";
                $placeholders[] = "?";
                $params[] = $admin_user_id;
            }
            
            if ($created_column_exists) {
                $columns[] = "created_at";
                $placeholders[] = "NOW()";
            }
            
            $query = "INSERT INTO products (" . implode(", ", $columns) . ") VALUES (" . implode(", ", $placeholders) . ")";
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            
            // Add warnings for missing columns
            $warnings = [];
            if (!$stock_column_exists) {
                $warnings[] = "stock";
            }
            if ($user_id_column_exists && !isset($_SESSION['user_id'])) {
                $warnings[] = "user_id (defaulted to 1)";
            }
            
            if (!empty($warnings)) {
                $_SESSION['warning_message'] = "Note: Some columns were handled with defaults: " . implode(", ", $warnings);
            }
            
            // Redirect to manage products page with success message
            $_SESSION['success_message'] = "Product added successfully!";
            header("Location: manage_products.php");
            exit();
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
            
            // Delete uploaded image if database insertion fails
            if (!empty($image_path) && file_exists("../" . $image_path)) {
                unlink("../" . $image_path);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - SmartFix Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #007BFF;
            --primary-dark: #0056b3;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --body-bg: #f0f2f5;
            --card-shadow: 0 4px 15px rgba(0,0,0,0.1);
            --transition: all 0.3s ease;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', sans-serif;
            background: var(--body-bg);
            color: var(--dark-color);
            line-height: 1.6;
        }
        
        /* Admin Sidebar */
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            background-color: var(--dark-color);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: var(--transition);
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .sidebar-logo i {
            margin-right: 0.5rem;
            font-size: 1.75rem;
        }
        
        .sidebar-menu {
            padding: 1rem 0;
        }
        
        .menu-item {
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: var(--transition);
            border-left: 3px solid transparent;
        }
        
        .menu-item:hover, .menu-item.active {
            background-color: rgba(255,255,255,0.1);
            color: white;
            border-left-color: var(--primary-color);
        }
        
        .menu-item i {
            margin-right: 0.75rem;
            width: 20px;
            text-align: center;
        }
        
        .menu-divider {
            height: 1px;
            background-color: rgba(255,255,255,0.1);
            margin: 0.5rem 0;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 2rem;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .page-title h1 {
            font-size: 1.75rem;
            color: var(--dark-color);
        }
        
        .page-title p {
            color: var(--secondary-color);
            margin-top: 0.5rem;
        }
        
        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            background-color: var(--primary-color);
            color: white;
            border-radius: 4px;
            text-decoration: none;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .btn:hover {
            background-color: var(--primary-dark);
        }
        
        .btn-secondary {
            background-color: var(--secondary-color);
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        /* Form Styles */
        .form-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: var(--card-shadow);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark-color);
        }
        
        input[type="text"],
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.15s ease-in-out;
        }
        
        input[type="text"]:focus,
        input[type="number"]:focus,
        textarea:focus,
        select:focus {
            border-color: var(--primary-color);
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        
        textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .form-footer {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        /* Alert Messages */
        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
        }
        
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.15);
            color: var(--danger-color);
            border: 1px solid rgba(220, 53, 69, 0.3);
        }
        
        .image-preview {
            width: 100%;
            height: 200px;
            border: 2px dashed #ced4da;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 0.5rem;
            overflow: hidden;
        }
        
        .image-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .image-preview-placeholder {
            color: var(--secondary-color);
            text-align: center;
        }
        
        .image-preview-placeholder i {
            font-size: 3rem;
            margin-bottom: 0.5rem;
            color: #dee2e6;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <a href="admin_dashboard_new.php" class="sidebar-logo">
                    <i class="fas fa-tools"></i>
                    <span>SmartFix</span>
                </a>
            </div>
            
            <div class="sidebar-menu">
                <a href="admin_dashboard_new.php" class="menu-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                
                <a href="service_requests.php" class="menu-item">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Service Requests</span>
                </a>
                
                <a href="manage_users.php" class="menu-item">
                    <i class="fas fa-users"></i>
                    <span>Manage Users</span>
                </a>
                
                <a href="manage_products.php" class="menu-item active">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Manage Products</span>
                </a>
                
                <a href="admin_notifications.php" class="menu-item">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
                </a>
                
                <div class="menu-divider"></div>
                
                <a href="settings.php" class="menu-item">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                
                <a href="logout.php" class="menu-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <div class="page-title">
                    <h1>Add New Product</h1>
                    <p>Create a new product to display in the shop</p>
                </div>
                
                <a href="manage_products.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Products
                </a>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul style="margin: 0; padding-left: 1.5rem;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="form-container">
                <form action="add_product.php" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="name">Product Name</label>
                        <input type="text" id="name" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Price (K)</label>
                        <input type="number" id="price" name="price" step="0.01" min="0.01" value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category" name="category" required>
                            <option value="">Select Category</option>
                            <option value="Phone" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Phone') ? 'selected' : ''; ?>>Phone</option>
                            <option value="Computer" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Computer') ? 'selected' : ''; ?>>Computer</option>
                            <option value="Spare" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Spare') ? 'selected' : ''; ?>>Spare Parts</option>
                            <option value="Car" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Car') ? 'selected' : ''; ?>>Car</option>
                            <option value="Other" <?php echo (isset($_POST['category']) && $_POST['category'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="stock">Stock Quantity</label>
                        <input type="number" id="stock" name="stock" min="1" value="<?php echo isset($_POST['stock']) ? intval($_POST['stock']) : '1'; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="image">Product Image</label>
                        <input type="file" id="image" name="image" accept="image/*" required>
                        <div class="image-preview" id="imagePreview">
                            <div class="image-preview-placeholder">
                                <i class="fas fa-image"></i>
                                <p>Image preview will appear here</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-footer">
                        <a href="manage_products.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn">Add Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Image preview functionality
        const imageInput = document.getElementById('image');
        const imagePreview = document.getElementById('imagePreview');
        
        imageInput.addEventListener('change', function() {
            const file = this.files[0];
            
            if (file) {
                const reader = new FileReader();
                
                reader.addEventListener('load', function() {
                    imagePreview.innerHTML = `<img src="${this.result}" alt="Preview">`;
                });
                
                reader.readAsDataURL(file);
            } else {
                imagePreview.innerHTML = `
                    <div class="image-preview-placeholder">
                        <i class="fas fa-image"></i>
                        <p>Image preview will appear here</p>
                    </div>
                `;
            }
        });
    </script>
</body>
</html>