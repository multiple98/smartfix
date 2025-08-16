<?php
session_start();
include('../includes/db.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

// Function to check if a column exists
function columnExists($pdo, $table, $column) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
        $stmt->execute([$table, $column]);
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        // Fallback method for older MySQL versions or compatibility issues
        try {
            $stmt = $pdo->query("DESCRIBE `$table`");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($row['Field'] === $column) {
                    return true;
                }
            }
            return false;
        } catch (PDOException $e2) {
            return false;
        }
    }
}

// Check if status and is_deleted columns exist
$status_exists = columnExists($pdo, 'products', 'status');
$is_deleted_exists = columnExists($pdo, 'products', 'is_deleted');

// Handle product restoration
if (isset($_GET['restore']) && is_numeric($_GET['restore'])) {
    $product_id = $_GET['restore'];
    
    if ($status_exists && $is_deleted_exists) {
        $update_query = "UPDATE products SET status = 'active', is_deleted = 0 WHERE id = ?";
        $stmt = $pdo->prepare($update_query);
        
        if ($stmt->execute([$product_id])) {
            $success_message = "Product restored successfully! It will now appear in the shop.";
        } else {
            $error_message = "Failed to restore product.";
        }
    } else {
        // Redirect to update table structure page
        $_SESSION['warning_message'] = "Product table needs to be updated first. Redirecting...";
        header("Location: update_products_table.php");
        exit();
    }
}

// Handle product activation
if (isset($_GET['activate']) && is_numeric($_GET['activate'])) {
    $product_id = $_GET['activate'];
    
    if ($status_exists) {
        $update_query = "UPDATE products SET status = 'active' WHERE id = ?";
        $stmt = $pdo->prepare($update_query);
        
        if ($stmt->execute([$product_id])) {
            $success_message = "Product activated successfully! It will now appear in the shop.";
        } else {
            $error_message = "Failed to activate product.";
        }
    } else {
        // Redirect to update table structure page
        $_SESSION['warning_message'] = "Product table needs to be updated first. Redirecting...";
        header("Location: update_products_table.php");
        exit();
    }
}

// Handle marking product as featured
if (isset($_GET['feature']) && is_numeric($_GET['feature'])) {
    $product_id = $_GET['feature'];
    
    // Check if is_featured column exists
    $is_featured_exists = columnExists($pdo, 'products', 'is_featured');
    
    if ($is_featured_exists) {
        $update_query = "UPDATE products SET is_featured = 1 WHERE id = ?";
        $stmt = $pdo->prepare($update_query);
        
        if ($stmt->execute([$product_id])) {
            $success_message = "Product marked as featured successfully!";
        } else {
            $error_message = "Failed to mark product as featured.";
        }
    } else {
        // Redirect to update table structure page
        $_SESSION['warning_message'] = "Product table needs to be updated first. Redirecting...";
        header("Location: update_products_table.php");
        exit();
    }
}

// Handle unmarking product as featured
if (isset($_GET['unfeature']) && is_numeric($_GET['unfeature'])) {
    $product_id = $_GET['unfeature'];
    
    // Check if is_featured column exists
    $is_featured_exists = columnExists($pdo, 'products', 'is_featured');
    
    if ($is_featured_exists) {
        $update_query = "UPDATE products SET is_featured = 0 WHERE id = ?";
        $stmt = $pdo->prepare($update_query);
        
        if ($stmt->execute([$product_id])) {
            $success_message = "Product unmarked as featured successfully!";
        } else {
            $error_message = "Failed to unmark product as featured.";
        }
    } else {
        // Redirect to update table structure page
        $_SESSION['warning_message'] = "Product table needs to be updated first. Redirecting...";
        header("Location: update_products_table.php");
        exit();
    }
}

// Handle marking product as new
if (isset($_GET['mark_new']) && is_numeric($_GET['mark_new'])) {
    $product_id = $_GET['mark_new'];
    
    // Check if is_new column exists
    $is_new_exists = columnExists($pdo, 'products', 'is_new');
    
    if ($is_new_exists) {
        $update_query = "UPDATE products SET is_new = 1 WHERE id = ?";
        $stmt = $pdo->prepare($update_query);
        
        if ($stmt->execute([$product_id])) {
            $success_message = "Product marked as new successfully!";
        } else {
            $error_message = "Failed to mark product as new.";
        }
    } else {
        // Redirect to update table structure page
        $_SESSION['warning_message'] = "Product table needs to be updated first. Redirecting...";
        header("Location: update_products_table.php");
        exit();
    }
}

// Handle unmarking product as new
if (isset($_GET['unmark_new']) && is_numeric($_GET['unmark_new'])) {
    $product_id = $_GET['unmark_new'];
    
    // Check if is_new column exists
    $is_new_exists = columnExists($pdo, 'products', 'is_new');
    
    if ($is_new_exists) {
        $update_query = "UPDATE products SET is_new = 0 WHERE id = ?";
        $stmt = $pdo->prepare($update_query);
        
        if ($stmt->execute([$product_id])) {
            $success_message = "Product unmarked as new successfully!";
        } else {
            $error_message = "Failed to unmark product as new.";
        }
    } else {
        // Redirect to update table structure page
        $_SESSION['warning_message'] = "Product table needs to be updated first. Redirecting...";
        header("Location: update_products_table.php");
        exit();
    }
}

// Handle product deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $product_id = $_GET['delete'];
    
    // Get image path before deleting
    $image_query = "SELECT image FROM products WHERE id = ?";
    $stmt = $pdo->prepare($image_query);
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    // Check if the product has any orders
    $check_orders_query = "SELECT COUNT(*) FROM orders WHERE product_id = ?";
    $check_stmt = $pdo->prepare($check_orders_query);
    $check_stmt->execute([$product_id]);
    $has_orders = $check_stmt->fetchColumn() > 0;
    
    if ($has_orders) {
        // Soft delete - mark the product as inactive instead of deleting it
        if ($status_exists && $is_deleted_exists) {
            $update_query = "UPDATE products SET status = 'inactive', is_deleted = 1 WHERE id = ?";
            $stmt = $pdo->prepare($update_query);
            
            if ($stmt->execute([$product_id])) {
                $success_message = "Product marked as inactive because it has associated orders. It will no longer appear in the shop.";
            } else {
                $error_message = "Failed to update product status.";
            }
        } else {
            // Redirect to update table structure page
            $_SESSION['warning_message'] = "Product table needs to be updated first. Redirecting...";
            header("Location: update_products_table.php");
            exit();
        }
    } else {
        // Hard delete if no orders reference this product
        $delete_query = "DELETE FROM products WHERE id = ?";
        $stmt = $pdo->prepare($delete_query);
        
        if ($stmt->execute([$product_id])) {
            // Delete the image file if it exists
            if (!empty($product['image']) && file_exists('../' . $product['image'])) {
                unlink('../' . $product['image']);
            }
            $success_message = "Product deleted successfully!";
        } else {
            $error_message = "Failed to delete product.";
        }
    }
}

// Fetch all products
try {
    // Check if status and is_deleted columns exist
    $check_column = "SHOW COLUMNS FROM products LIKE 'status'";
    $column_stmt = $pdo->prepare($check_column);
    $column_stmt->execute();
    $status_exists = $column_stmt->rowCount() > 0;
    
    $check_column = "SHOW COLUMNS FROM products LIKE 'is_deleted'";
    $column_stmt = $pdo->prepare($check_column);
    $column_stmt->execute();
    $is_deleted_exists = $column_stmt->rowCount() > 0;
    
    // Check if created_at column exists
    $check_column = "SHOW COLUMNS FROM products LIKE 'created_at'";
    $column_stmt = $pdo->prepare($check_column);
    $column_stmt->execute();
    $created_at_exists = $column_stmt->rowCount() > 0;
    
    if ($status_exists && $is_deleted_exists) {
        $query = "SELECT * FROM products ORDER BY 
                  CASE 
                    WHEN status = 'active' AND is_deleted = 0 THEN 1
                    WHEN status = 'inactive' AND is_deleted = 0 THEN 2
                    ELSE 3
                  END, 
                  " . ($created_at_exists ? "created_at" : "id") . " DESC";
    } else {
        $query = "SELECT * FROM products ORDER BY " . ($created_at_exists ? "created_at" : "id") . " DESC";
    }
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    // Fallback to simple query if there's an error
    $query = "SELECT * FROM products ORDER BY id DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $products = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - SmartFix Admin</title>
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
        
        .btn-danger {
            background-color: var(--danger-color);
        }
        
        .btn-danger:hover {
            background-color: #bd2130;
        }
        
        .btn-success {
            background-color: var(--success-color);
        }
        
        .btn-success:hover {
            background-color: #218838;
        }
        
        /* Alert Messages */
        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background-color: rgba(40, 167, 69, 0.15);
            color: var(--success-color);
            border: 1px solid rgba(40, 167, 69, 0.3);
        }
        
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.15);
            color: var(--danger-color);
            border: 1px solid rgba(220, 53, 69, 0.3);
        }
        
        .alert-warning {
            background-color: rgba(255, 193, 7, 0.15);
            color: #856404;
            border: 1px solid rgba(255, 193, 7, 0.3);
        }
        
        /* Table Styles */
        .table-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 0.75rem 1rem;
            text-align: left;
        }
        
        th {
            background-color: var(--light-color);
            font-weight: 600;
            color: var(--dark-color);
            font-size: 0.9rem;
        }
        
        tr {
            border-bottom: 1px solid #e9ecef;
        }
        
        tr:last-child {
            border-bottom: none;
        }
        
        tr:hover {
            background-color: rgba(0, 123, 255, 0.03);
        }
        
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .status {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
            text-align: center;
        }
        
        .status-success {
            background-color: rgba(40, 167, 69, 0.15);
            color: var(--success-color);
        }
        
        .status-danger {
            background-color: rgba(220, 53, 69, 0.15);
            color: var(--danger-color);
        }
        
        .status-warning {
            background-color: rgba(255, 193, 7, 0.15);
            color: var(--warning-color);
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--secondary-color);
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #dee2e6;
        }
        
        .empty-state h3 {
            margin-bottom: 0.5rem;
            color: var(--dark-color);
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
                    <h1>Manage Products</h1>
                    <p>Add, edit, and remove products from the shop</p>
                </div>
                
                <div>
                    <a href="add_product.php" class="btn btn-success" style="margin-right: 10px;">
                        <i class="fas fa-plus"></i> Add New Product
                    </a>
                    <a href="../shop.php" target="_blank" class="btn" style="background-color: #17a2b8; margin-right: 10px;">
                        <i class="fas fa-eye"></i> View Shop
                    </a>
                    <a href="update_products_table.php" class="btn" style="background-color: #6c757d;">
                        <i class="fas fa-database"></i> Update Table Structure
                    </a>
                </div>
            </div>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['success_message']; ?>
                    <?php unset($_SESSION['success_message']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['warning_message'])): ?>
                <div class="alert alert-warning">
                    <?php echo $_SESSION['warning_message']; ?>
                    <?php unset($_SESSION['warning_message']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger">
                    <?php echo $_SESSION['error_message']; ?>
                    <?php unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!$status_exists || !$is_deleted_exists): ?>
                <div class="alert alert-warning">
                    <strong>Notice:</strong> Your products table needs to be updated to enable all features.
                    <a href="update_products_table.php" class="btn" style="background-color: #6c757d; color: white; margin-left: 10px; font-size: 0.8rem; padding: 0.25rem 0.5rem;">
                        <i class="fas fa-database"></i> Update Table Structure
                    </a>
                </div>
            <?php endif; ?>
            
            <div class="table-container">
                <?php if (count($products) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Featured</th>
                                <th>New</th>
                                <th>Date Added</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($product['image']) && file_exists('../' . $product['image'])): ?>
                                            <img src="../<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                                        <?php else: ?>
                                            <div class="product-image" style="background-color: #eee; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-image" style="color: #aaa;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['category']); ?></td>
                                    <td>K<?php echo number_format($product['price'], 2); ?></td>
                                    <td>
                                        <?php 
                                        if (isset($product['stock'])) {
                                            echo $product['stock'];
                                        } else {
                                            echo '<span style="color: #6c757d;">N/A</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if (isset($product['status'])) {
                                            $status_class = ($product['status'] == 'active') ? 'success' : 'danger';
                                            $status_text = ucfirst($product['status']);
                                            if (isset($product['is_deleted']) && $product['is_deleted'] == 1) {
                                                $status_class = 'danger';
                                                $status_text = 'Deleted';
                                            }
                                            echo '<span class="status status-' . $status_class . '">' . $status_text . '</span>';
                                        } else {
                                            echo '<span class="status status-success">Active</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if (isset($product['is_featured']) && $product['is_featured'] == 1): ?>
                                            <a href="manage_products.php?unfeature=<?php echo $product['id']; ?>" class="status status-success" title="Click to unmark as featured">
                                                <i class="fas fa-star"></i> Yes
                                            </a>
                                        <?php else: ?>
                                            <a href="manage_products.php?feature=<?php echo $product['id']; ?>" class="status status-danger" title="Click to mark as featured">
                                                <i class="far fa-star"></i> No
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (isset($product['is_new']) && $product['is_new'] == 1): ?>
                                            <a href="manage_products.php?unmark_new=<?php echo $product['id']; ?>" class="status status-success" title="Click to unmark as new">
                                                <i class="fas fa-tag"></i> Yes
                                            </a>
                                        <?php else: ?>
                                            <a href="manage_products.php?mark_new=<?php echo $product['id']; ?>" class="status status-danger" title="Click to mark as new">
                                                <i class="far fa-tag"></i> No
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if (isset($product['created_at']) && !empty($product['created_at'])) {
                                            echo date('M d, Y', strtotime($product['created_at']));
                                        } else {
                                            echo '<span style="color: #6c757d;">N/A</span>';
                                        }
                                        ?>
                                    </td>
                                    <td class="actions">
                                        <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        <?php if (isset($product['is_deleted']) && $product['is_deleted'] == 1): ?>
                                            <a href="manage_products.php?restore=<?php echo $product['id']; ?>" class="btn" style="background-color: #28a745;" title="Restore Product">
                                                <i class="fas fa-undo"></i>
                                            </a>
                                        <?php elseif (isset($product['status']) && $product['status'] == 'inactive'): ?>
                                            <a href="manage_products.php?activate=<?php echo $product['id']; ?>" class="btn" style="background-color: #28a745;" title="Activate Product">
                                                <i class="fas fa-check"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="manage_products.php?delete=<?php echo $product['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this product?');" title="Delete Product">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-shopping-bag"></i>
                        <h3>No Products Found</h3>
                        <p>Start adding products to your shop</p>
                        <a href="add_product.php" class="btn btn-success" style="margin-top: 1rem;">
                            <i class="fas fa-plus"></i> Add First Product
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>