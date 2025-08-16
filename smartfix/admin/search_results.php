<?php
session_start();
include('../includes/db.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../auth.php?form=admin");
    exit();
}

$query = $_GET['q'] ?? '';
$type = $_GET['type'] ?? 'general';
$results = [];
$search_performed = false;

if (!empty($query)) {
    $search_performed = true;
    
    try {
        switch ($type) {
            case 'id':
                // Search by ID in users and service_requests
                $user_stmt = $pdo->prepare("SELECT 'user' as type, id, name, email, phone FROM users WHERE id = ?");
                $user_stmt->execute([$query]);
                $user_result = $user_stmt->fetch();
                if ($user_result) $results[] = $user_result;
                
                $request_stmt = $pdo->prepare("SELECT 'service_request' as type, id, name, email, service_type, status FROM service_requests WHERE id = ?");
                $request_stmt->execute([$query]);
                $request_result = $request_stmt->fetch();
                if ($request_result) $results[] = $request_result;
                break;
                
            case 'email':
                // Search by email
                $email_stmt = $pdo->prepare("SELECT 'user' as type, id, name, email, phone FROM users WHERE email LIKE ?");
                $email_stmt->execute(['%' . $query . '%']);
                $email_results = $email_stmt->fetchAll();
                foreach ($email_results as $result) $results[] = $result;
                
                $request_email_stmt = $pdo->prepare("SELECT 'service_request' as type, id, name, email, service_type, status FROM service_requests WHERE email LIKE ?");
                $request_email_stmt->execute(['%' . $query . '%']);
                $request_email_results = $request_email_stmt->fetchAll();
                foreach ($request_email_results as $result) $results[] = $result;
                break;
                
            case 'general':
            default:
                // General search across multiple fields
                $general_user_stmt = $pdo->prepare("SELECT 'user' as type, id, name, email, phone FROM users WHERE name LIKE ? OR email LIKE ? OR phone LIKE ?");
                $search_param = '%' . $query . '%';
                $general_user_stmt->execute([$search_param, $search_param, $search_param]);
                $general_user_results = $general_user_stmt->fetchAll();
                foreach ($general_user_results as $result) $results[] = $result;
                
                $general_request_stmt = $pdo->prepare("SELECT 'service_request' as type, id, name, email, service_type, status, description FROM service_requests WHERE name LIKE ? OR email LIKE ? OR service_type LIKE ? OR description LIKE ?");
                $general_request_stmt->execute([$search_param, $search_param, $search_param, $search_param]);
                $general_request_results = $general_request_stmt->fetchAll();
                foreach ($general_request_results as $result) $results[] = $result;
                
                // Search products if table exists
                try {
                    $product_stmt = $pdo->prepare("SELECT 'product' as type, id, name, description, price FROM products WHERE name LIKE ? OR description LIKE ?");
                    $product_stmt->execute([$search_param, $search_param]);
                    $product_results = $product_stmt->fetchAll();
                    foreach ($product_results as $result) $results[] = $result;
                } catch (Exception $e) {
                    // Products table doesn't exist or error occurred
                }
                break;
        }
    } catch (Exception $e) {
        $error_message = "Search error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - SmartFix Admin</title>
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
            padding: 2rem;
        }
        
        .search-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .search-header {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
        }
        
        .search-header h1 {
            color: var(--dark-color);
            margin-bottom: 1rem;
        }
        
        .search-form {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .search-input {
            flex: 1;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .search-btn {
            padding: 0.75rem 1.5rem;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: var(--transition);
        }
        
        .search-btn:hover {
            background: var(--primary-dark);
        }
        
        .search-meta {
            color: var(--secondary-color);
            font-size: 0.9rem;
        }
        
        .results-container {
            background: white;
            border-radius: 8px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }
        
        .results-header {
            background: var(--light-color);
            padding: 1rem 2rem;
            border-bottom: 1px solid #e9ecef;
        }
        
        .results-count {
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .result-item {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e9ecef;
            transition: var(--transition);
        }
        
        .result-item:hover {
            background: rgba(0, 123, 255, 0.03);
        }
        
        .result-item:last-child {
            border-bottom: none;
        }
        
        .result-type {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .result-type.user {
            background: rgba(0, 123, 255, 0.15);
            color: var(--primary-color);
        }
        
        .result-type.service_request {
            background: rgba(255, 193, 7, 0.15);
            color: var(--warning-color);
        }
        
        .result-type.product {
            background: rgba(40, 167, 69, 0.15);
            color: var(--success-color);
        }
        
        .result-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--dark-color);
        }
        
        .result-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .result-field {
            display: flex;
            flex-direction: column;
        }
        
        .result-field-label {
            font-size: 0.8rem;
            color: var(--secondary-color);
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .result-field-value {
            color: var(--dark-color);
        }
        
        .result-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .action-btn {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
        }
        
        .btn-secondary {
            background: var(--secondary-color);
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .no-results {
            text-align: center;
            padding: 3rem;
            color: var(--secondary-color);
        }
        
        .no-results i {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .back-btn {
            display: inline-block;
            margin-bottom: 2rem;
            padding: 0.5rem 1rem;
            background: var(--secondary-color);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: var(--transition);
        }
        
        .back-btn:hover {
            background: #5a6268;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-pending {
            background: rgba(255, 193, 7, 0.15);
            color: var(--warning-color);
        }
        
        .status-completed {
            background: rgba(40, 167, 69, 0.15);
            color: var(--success-color);
        }
        
        .status-cancelled {
            background: rgba(220, 53, 69, 0.15);
            color: var(--danger-color);
        }
    </style>
</head>
<body>
    <div class="search-container">
        <a href="admin_quick_panel.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Quick Panel
        </a>
        
        <div class="search-header">
            <h1><i class="fas fa-search"></i> Admin Search</h1>
            <form method="GET" action="" class="search-form">
                <input type="text" name="q" value="<?php echo htmlspecialchars($query); ?>" placeholder="Search users, service requests, products..." class="search-input">
                <input type="hidden" name="type" value="general">
                <button type="submit" class="search-btn">
                    <i class="fas fa-search"></i> Search
                </button>
            </form>
            <?php if ($search_performed): ?>
                <div class="search-meta">
                    Search query: <strong>"<?php echo htmlspecialchars($query); ?>"</strong> 
                    (Type: <?php echo ucfirst($type); ?>)
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($search_performed): ?>
            <div class="results-container">
                <div class="results-header">
                    <div class="results-count">
                        <?php echo count($results); ?> result(s) found
                    </div>
                </div>
                
                <?php if (empty($results)): ?>
                    <div class="no-results">
                        <i class="fas fa-search"></i>
                        <h3>No results found</h3>
                        <p>Try adjusting your search terms or search type.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($results as $result): ?>
                        <div class="result-item">
                            <span class="result-type <?php echo $result['type']; ?>">
                                <?php 
                                echo ucfirst(str_replace('_', ' ', $result['type']));
                                ?>
                            </span>
                            
                            <div class="result-title">
                                <?php if ($result['type'] == 'user'): ?>
                                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($result['name']); ?>
                                <?php elseif ($result['type'] == 'service_request'): ?>
                                    <i class="fas fa-clipboard-list"></i> Service Request #<?php echo $result['id']; ?> - <?php echo htmlspecialchars($result['name']); ?>
                                <?php elseif ($result['type'] == 'product'): ?>
                                    <i class="fas fa-box"></i> <?php echo htmlspecialchars($result['name']); ?>
                                <?php endif; ?>
                            </div>
                            
                            <div class="result-details">
                                <div class="result-field">
                                    <div class="result-field-label">ID</div>
                                    <div class="result-field-value">#<?php echo $result['id']; ?></div>
                                </div>
                                
                                <?php if (isset($result['email'])): ?>
                                <div class="result-field">
                                    <div class="result-field-label">Email</div>
                                    <div class="result-field-value"><?php echo htmlspecialchars($result['email']); ?></div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (isset($result['phone'])): ?>
                                <div class="result-field">
                                    <div class="result-field-label">Phone</div>
                                    <div class="result-field-value"><?php echo htmlspecialchars($result['phone']); ?></div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (isset($result['service_type'])): ?>
                                <div class="result-field">
                                    <div class="result-field-label">Service Type</div>
                                    <div class="result-field-value"><?php echo htmlspecialchars($result['service_type']); ?></div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (isset($result['status'])): ?>
                                <div class="result-field">
                                    <div class="result-field-label">Status</div>
                                    <div class="result-field-value">
                                        <span class="status-badge status-<?php echo $result['status']; ?>">
                                            <?php echo ucfirst($result['status']); ?>
                                        </span>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (isset($result['price'])): ?>
                                <div class="result-field">
                                    <div class="result-field-label">Price</div>
                                    <div class="result-field-value">$<?php echo number_format($result['price'], 2); ?></div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (isset($result['description'])): ?>
                                <div class="result-field">
                                    <div class="result-field-label">Description</div>
                                    <div class="result-field-value"><?php echo htmlspecialchars(substr($result['description'], 0, 100) . (strlen($result['description']) > 100 ? '...' : '')); ?></div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="result-actions">
                                <?php if ($result['type'] == 'user'): ?>
                                    <a href="manage_users.php?view=<?php echo $result['id']; ?>" class="action-btn btn-primary">
                                        <i class="fas fa-eye"></i> View Profile
                                    </a>
                                    <a href="manage_users.php?edit=<?php echo $result['id']; ?>" class="action-btn btn-secondary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                <?php elseif ($result['type'] == 'service_request'): ?>
                                    <a href="service_requests.php?id=<?php echo $result['id']; ?>" class="action-btn btn-primary">
                                        <i class="fas fa-eye"></i> View Request
                                    </a>
                                    <a href="assign_technician.php?request_id=<?php echo $result['id']; ?>" class="action-btn btn-secondary">
                                        <i class="fas fa-user-cog"></i> Assign Technician
                                    </a>
                                <?php elseif ($result['type'] == 'product'): ?>
                                    <a href="edit_product.php?id=<?php echo $result['id']; ?>" class="action-btn btn-primary">
                                        <i class="fas fa-edit"></i> Edit Product
                                    </a>
                                    <a href="manage_products.php?view=<?php echo $result['id']; ?>" class="action-btn btn-secondary">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="results-container">
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <h3>Enter a search query</h3>
                    <p>Search for users, service requests, products and more.</p>
                    <div style="margin-top: 2rem;">
                        <strong>Search Tips:</strong>
                        <ul style="text-align: left; max-width: 400px; margin: 1rem auto;">
                            <li>Use numbers to search by ID</li>
                            <li>Use @ to search by email</li>
                            <li>Use names, service types, or descriptions</li>
                            <li>Search is case-insensitive</li>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>