<?php
session_start();
include('../includes/db.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../auth.php?form=admin");
    exit();
}

$success_message = '';
$error_message = '';

// Handle technician assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_technician'])) {
    $request_id = intval($_POST['request_id']);
    $technician_id = intval($_POST['technician_id']);
    $status = $_POST['status'];
    
    try {
        $update_stmt = $pdo->prepare("UPDATE service_requests SET technician_id = ?, status = ?, updated_at = NOW() WHERE id = ?");
        if ($update_stmt->execute([$technician_id, $status, $request_id])) {
            $success_message = "Technician assigned successfully!";
        } else {
            $error_message = "Failed to assign technician.";
        }
    } catch (Exception $e) {
        $error_message = "Database error: " . $e->getMessage();
    }
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $request_id = intval($_POST['request_id']);
    $status = $_POST['status'];
    
    try {
        $update_stmt = $pdo->prepare("UPDATE service_requests SET status = ?, updated_at = NOW() WHERE id = ?");
        if ($update_stmt->execute([$status, $request_id])) {
            $success_message = "Status updated successfully!";
        } else {
            $error_message = "Failed to update status.";
        }
    } catch (Exception $e) {
        $error_message = "Database error: " . $e->getMessage();
    }
}

// Get filters
$filter_status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build query with proper parameter binding
$where_conditions = ["1=1"];
$params = [];

if (!empty($filter_status)) {
    $where_conditions[] = "sr.status = ?";
    $params[] = $filter_status;
}

if (!empty($search)) {
    $where_conditions[] = "(sr.name LIKE ? OR sr.email LIKE ? OR sr.service_type LIKE ? OR sr.description LIKE ?)";
    $search_param = '%' . $search . '%';
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count for pagination
try {
    $count_query = "SELECT COUNT(*) FROM service_requests sr WHERE " . $where_clause;
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetchColumn();
    $total_pages = ceil($total_records / $per_page);
} catch (Exception $e) {
    $total_records = 0;
    $total_pages = 1;
}

// Fetch service requests with enhanced error handling
try {
    $query = "SELECT sr.*, 
              COALESCE(u.name, sr.name) AS user_name,
              u.email AS user_email,
              t.name AS tech_name,
              t.phone AS tech_phone
              FROM service_requests sr 
              LEFT JOIN users u ON sr.user_id = u.id 
              LEFT JOIN technicians t ON sr.technician_id = t.id 
              WHERE " . $where_clause . "
              ORDER BY COALESCE(sr.request_date, sr.created_at, sr.id) DESC
              LIMIT ? OFFSET ?";
    
    $stmt = $pdo->prepare($query);
    $params[] = $per_page;
    $params[] = $offset;
    $stmt->execute($params);
    $service_requests = $stmt->fetchAll();
} catch (Exception $e) {
    $error_message = "Error fetching service requests: " . $e->getMessage();
    $service_requests = [];
}

// Get technicians for dropdown
try {
    $tech_stmt = $pdo->query("SELECT id, name, phone, email FROM technicians ORDER BY name");
    $technicians = $tech_stmt->fetchAll();
} catch (Exception $e) {
    $technicians = [];
}

// Get status counts for dashboard
try {
    $status_counts = [];
    $status_query = "SELECT status, COUNT(*) as count FROM service_requests GROUP BY status";
    $status_stmt = $pdo->query($status_query);
    while ($row = $status_stmt->fetch()) {
        $status_counts[$row['status']] = $row['count'];
    }
} catch (Exception $e) {
    $status_counts = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Requests Management - SmartFix Admin</title>
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
        
        .header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 2rem;
            text-align: center;
            box-shadow: var(--card-shadow);
        }
        
        .header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .top-controls {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 1rem;
            align-items: center;
        }
        
        .search-form {
            display: flex;
            gap: 0.5rem;
        }
        
        .search-input, .filter-select {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.95rem;
        }
        
        .search-input {
            flex: 1;
            min-width: 200px;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 0.9rem;
            transition: var(--transition);
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
        }
        
        .btn-success {
            background: var(--success-color);
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
        }
        
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
            box-shadow: var(--card-shadow);
            border-left: 4px solid var(--primary-color);
        }
        
        .stat-card.pending {
            border-left-color: var(--warning-color);
        }
        
        .stat-card.in_progress {
            border-left-color: var(--info-color);
        }
        
        .stat-card.completed {
            border-left-color: var(--success-color);
        }
        
        .stat-card.cancelled {
            border-left-color: var(--danger-color);
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--dark-color);
        }
        
        .stat-label {
            font-size: 0.85rem;
            color: var(--secondary-color);
            text-transform: uppercase;
        }
        
        .requests-table {
            background: white;
            border-radius: 8px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        
        .table th {
            background: var(--light-color);
            font-weight: 600;
            color: var(--dark-color);
            font-size: 0.9rem;
        }
        
        .table tbody tr {
            transition: var(--transition);
        }
        
        .table tbody tr:hover {
            background: rgba(0, 123, 255, 0.03);
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: capitalize;
        }
        
        .status-pending {
            background: rgba(255, 193, 7, 0.15);
            color: var(--warning-color);
        }
        
        .status-confirmed {
            background: rgba(23, 162, 184, 0.15);
            color: var(--info-color);
        }
        
        .status-in_progress {
            background: rgba(23, 162, 184, 0.15);
            color: var(--info-color);
        }
        
        .status-completed {
            background: rgba(40, 167, 69, 0.15);
            color: var(--success-color);
        }
        
        .status-cancelled {
            background: rgba(220, 53, 69, 0.15);
            color: var(--danger-color);
        }
        
        .actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }
        
        .pagination a {
            padding: 0.5rem 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            color: var(--primary-color);
            text-decoration: none;
            transition: var(--transition);
        }
        
        .pagination a:hover,
        .pagination a.active {
            background: var(--primary-color);
            color: white;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: rgba(40, 167, 69, 0.15);
            color: var(--success-color);
            border: 1px solid rgba(40, 167, 69, 0.3);
        }
        
        .alert-danger {
            background: rgba(220, 53, 69, 0.15);
            color: var(--danger-color);
            border: 1px solid rgba(220, 53, 69, 0.3);
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }
        
        .modal-content {
            background: white;
            margin: 10% auto;
            padding: 2rem;
            border-radius: 8px;
            max-width: 500px;
            position: relative;
        }
        
        .close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .form-control {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .back-btn {
            display: inline-block;
            margin-bottom: 1rem;
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
        
        @media (max-width: 768px) {
            .top-controls {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .search-form {
                flex-direction: column;
            }
            
            .requests-table {
                overflow-x: auto;
            }
            
            .table th,
            .table td {
                padding: 0.5rem;
                font-size: 0.85rem;
            }
            
            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-clipboard-list"></i> Service Requests Management</h1>
        <p>Manage and track all customer service requests</p>
    </div>
    
    <div class="container">
        <a href="admin_dashboard_new.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Status Statistics -->
        <?php if (!empty($status_counts)): ?>
        <div class="stats-bar">
            <?php foreach ($status_counts as $status => $count): ?>
                <div class="stat-card <?php echo $status; ?>">
                    <div class="stat-number"><?php echo $count; ?></div>
                    <div class="stat-label"><?php echo str_replace('_', ' ', $status); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Search and Filter Controls -->
        <div class="top-controls">
            <form method="GET" class="search-form">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Search by name, email, service type..." class="search-input">
                <select name="status" class="filter-select">
                    <option value="">All Status</option>
                    <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="confirmed" <?php echo $filter_status == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                    <option value="in_progress" <?php echo $filter_status == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="completed" <?php echo $filter_status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $filter_status == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Search
                </button>
            </form>
            
            <a href="fix_service_requests_table.php" class="btn btn-success">
                <i class="fas fa-tools"></i> Fix Table
            </a>
            
            <div>
                Showing <?php echo number_format($total_records); ?> record(s)
            </div>
        </div>
        
        <!-- Service Requests Table -->
        <div class="requests-table">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Service</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Technician</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($service_requests)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2rem;">
                                <i class="fas fa-clipboard-list" style="font-size: 2rem; color: var(--secondary-color); margin-bottom: 1rem;"></i>
                                <p>No service requests found.</p>
                                <?php if (empty($search) && empty($filter_status)): ?>
                                    <a href="fix_service_requests_table.php" class="btn btn-primary" style="margin-top: 1rem;">
                                        <i class="fas fa-plus"></i> Add Sample Data
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($service_requests as $request): ?>
                            <tr>
                                <td><strong>#<?php echo $request['id']; ?></strong></td>
                                <td>
                                    <div style="font-weight: 600;">
                                        <?php echo htmlspecialchars($request['user_name']); ?>
                                    </div>
                                    <div style="font-size: 0.85rem; color: var(--secondary-color);">
                                        <?php echo htmlspecialchars($request['email'] ?? $request['user_email']); ?>
                                    </div>
                                    <?php if (!empty($request['phone'])): ?>
                                        <div style="font-size: 0.85rem; color: var(--secondary-color);">
                                            <i class="fas fa-phone"></i> <?php echo htmlspecialchars($request['phone']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="font-weight: 600;">
                                        <?php echo htmlspecialchars($request['service_type']); ?>
                                    </div>
                                    <?php if (!empty($request['service_option'])): ?>
                                        <div style="font-size: 0.85rem; color: var(--secondary-color);">
                                            <?php echo htmlspecialchars($request['service_option']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $date = $request['request_date'] ?? $request['created_at'] ?? null;
                                    echo $date ? date('M j, Y', strtotime($date)) : 'N/A';
                                    ?>
                                    <?php if (!empty($request['preferred_time'])): ?>
                                        <div style="font-size: 0.85rem; color: var(--secondary-color);">
                                            <?php echo date('g:i A', strtotime($request['preferred_time'])); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $request['status']; ?>">
                                        <?php echo str_replace('_', ' ', $request['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($request['tech_name'])): ?>
                                        <div style="font-weight: 600;">
                                            <?php echo htmlspecialchars($request['tech_name']); ?>
                                        </div>
                                        <?php if (!empty($request['tech_phone'])): ?>
                                            <div style="font-size: 0.85rem; color: var(--secondary-color);">
                                                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($request['tech_phone']); ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: var(--secondary-color);">Not Assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="actions">
                                        <button onclick="openAssignModal(<?php echo $request['id']; ?>, <?php echo $request['technician_id'] ?? 0; ?>, '<?php echo $request['status']; ?>')" 
                                                class="btn btn-primary btn-sm">
                                            <i class="fas fa-user-cog"></i> Assign
                                        </button>
                                        <button onclick="openStatusModal(<?php echo $request['id']; ?>, '<?php echo $request['status']; ?>')" 
                                                class="btn btn-success btn-sm">
                                            <i class="fas fa-edit"></i> Status
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo ($page - 1); ?>&status=<?php echo urlencode($filter_status); ?>&search=<?php echo urlencode($search); ?>">
                    <i class="fas fa-chevron-left"></i> Previous
                </a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <a href="?page=<?php echo $i; ?>&status=<?php echo urlencode($filter_status); ?>&search=<?php echo urlencode($search); ?>" 
                   class="<?php echo $i == $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo ($page + 1); ?>&status=<?php echo urlencode($filter_status); ?>&search=<?php echo urlencode($search); ?>">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Assign Technician Modal -->
    <div id="assignModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('assignModal')">&times;</span>
            <h3><i class="fas fa-user-cog"></i> Assign Technician</h3>
            <form method="POST">
                <input type="hidden" id="assign_request_id" name="request_id">
                <input type="hidden" name="assign_technician" value="1">
                
                <div class="form-group">
                    <label>Technician:</label>
                    <select name="technician_id" id="assign_technician_id" class="form-control" required>
                        <option value="">Select Technician</option>
                        <?php foreach ($technicians as $tech): ?>
                            <option value="<?php echo $tech['id']; ?>">
                                <?php echo htmlspecialchars($tech['name']); ?>
                                <?php if ($tech['phone']): ?>
                                    (<?php echo htmlspecialchars($tech['phone']); ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Status:</label>
                    <select name="status" id="assign_status" class="form-control" required>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                
                <div style="text-align: right; margin-top: 2rem;">
                    <button type="button" onclick="closeModal('assignModal')" class="btn" style="background: var(--secondary-color); color: white; margin-right: 1rem;">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Assign
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Status Update Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('statusModal')">&times;</span>
            <h3><i class="fas fa-edit"></i> Update Status</h3>
            <form method="POST">
                <input type="hidden" id="status_request_id" name="request_id">
                <input type="hidden" name="update_status" value="1">
                
                <div class="form-group">
                    <label>Status:</label>
                    <select name="status" id="status_select" class="form-control" required>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                
                <div style="text-align: right; margin-top: 2rem;">
                    <button type="button" onclick="closeModal('statusModal')" class="btn" style="background: var(--secondary-color); color: white; margin-right: 1rem;">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Update
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openAssignModal(requestId, technicianId, status) {
            document.getElementById('assign_request_id').value = requestId;
            document.getElementById('assign_technician_id').value = technicianId;
            document.getElementById('assign_status').value = status;
            document.getElementById('assignModal').style.display = 'block';
        }
        
        function openStatusModal(requestId, status) {
            document.getElementById('status_request_id').value = requestId;
            document.getElementById('status_select').value = status;
            document.getElementById('statusModal').style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const assignModal = document.getElementById('assignModal');
            const statusModal = document.getElementById('statusModal');
            
            if (event.target == assignModal) {
                assignModal.style.display = 'none';
            }
            if (event.target == statusModal) {
                statusModal.style.display = 'none';
            }
        }
        
        // Auto-refresh every 2 minutes
        setInterval(function() {
            if (!document.querySelector('.modal').style.display === 'block') {
                location.reload();
            }
        }, 120000);
    </script>
</body>
</html>