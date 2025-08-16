<?php
session_start();
include('includes/db.php');

// For demo purposes, we'll use a simple provider ID from URL parameter
// In a real system, this would be based on authenticated provider login
$provider_id = isset($_GET['provider_id']) ? intval($_GET['provider_id']) : 1;

// Get provider information
$provider = null;
$quotes = [];
$stats = ['total_quotes' => 0, 'active_quotes' => 0, 'completed_quotes' => 0, 'total_revenue' => 0];

try {
    // Get provider details
    $stmt = $pdo->prepare("SELECT * FROM transport_providers WHERE id = ?");
    $stmt->execute([$provider_id]);
    $provider = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$provider) {
        die("Provider not found");
    }
    
    // Get provider quotes
    $quotes_stmt = $pdo->prepare("
        SELECT tq.*, o.shipping_name, o.shipping_phone, o.shipping_address, o.total_amount
        FROM transport_quotes tq
        LEFT JOIN orders o ON tq.order_id = o.id
        WHERE tq.transport_provider_id = ?
        ORDER BY tq.created_at DESC
        LIMIT 20
    ");
    $quotes_stmt->execute([$provider_id]);
    $quotes = $quotes_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate statistics
    $stats_stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_quotes,
            SUM(CASE WHEN status IN ('accepted', 'pending') THEN 1 ELSE 0 END) as active_quotes,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_quotes,
            SUM(CASE WHEN status = 'completed' THEN actual_cost ELSE 0 END) as total_revenue
        FROM transport_quotes 
        WHERE transport_provider_id = ?
    ");
    $stats_stmt->execute([$provider_id]);
    $stats_result = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    if ($stats_result) {
        $stats = $stats_result;
    }
    
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'update_quote_status':
            $quote_id = intval($_POST['quote_id']);
            $new_status = $_POST['status'];
            $notes = trim($_POST['notes']);
            
            try {
                $update_stmt = $pdo->prepare("
                    UPDATE transport_quotes 
                    SET status = ?, notes = ?, updated_at = NOW() 
                    WHERE id = ? AND transport_provider_id = ?
                ");
                $update_stmt->execute([$new_status, $notes, $quote_id, $provider_id]);
                
                // Add tracking entry
                $tracking_stmt = $pdo->prepare("
                    INSERT INTO transport_tracking (quote_id, status, description) 
                    VALUES (?, ?, ?)
                ");
                $tracking_stmt->execute([
                    $quote_id, 
                    ucfirst($new_status), 
                    $notes ?: "Status updated to " . ucfirst($new_status)
                ]);
                
                $success_message = "Quote status updated successfully!";
                
                // Refresh quotes
                $quotes_stmt->execute([$provider_id]);
                $quotes = $quotes_stmt->fetchAll(PDO::FETCH_ASSOC);
                
            } catch (PDOException $e) {
                $error_message = "Error updating quote: " . $e->getMessage();
            }
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transport Provider Dashboard - <?= htmlspecialchars($provider['name']) ?></title>
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
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: var(--card-shadow);
        }
        
        .header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        .provider-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-item i {
            width: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: var(--card-shadow);
            text-align: center;
            transition: var(--transition);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        
        .stat-icon.total { color: var(--primary-color); }
        .stat-icon.active { color: var(--warning-color); }
        .stat-icon.completed { color: var(--success-color); }
        .stat-icon.revenue { color: var(--info-color); }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: var(--secondary-color);
            font-size: 0.9rem;
        }
        
        .card {
            background: white;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        .card-header {
            padding: 20px 25px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header h2 {
            font-size: 1.25rem;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-body {
            padding: 25px;
        }
        
        .quotes-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .quotes-table th,
        .quotes-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        
        .quotes-table th {
            background-color: var(--light-color);
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .quotes-table tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.05);
        }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending {
            background-color: rgba(255, 193, 7, 0.15);
            color: var(--warning-color);
        }
        
        .status-accepted {
            background-color: rgba(23, 162, 184, 0.15);
            color: var(--info-color);
        }
        
        .status-completed {
            background-color: rgba(40, 167, 69, 0.15);
            color: var(--success-color);
        }
        
        .status-cancelled {
            background-color: rgba(220, 53, 69, 0.15);
            color: var(--danger-color);
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
        }
        
        .btn-success {
            background-color: var(--success-color);
            color: white;
        }
        
        .btn-success:hover {
            background-color: #218838;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 0.8rem;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
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
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 0;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
        }
        
        .modal-header {
            padding: 20px 25px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-body {
            padding: 25px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.15s ease-in-out;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            outline: 0;
        }
        
        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: black;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .provider-info {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .quotes-table {
                font-size: 0.9rem;
            }
            
            .quotes-table th,
            .quotes-table td {
                padding: 10px 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                <i class="fas fa-truck"></i>
                <?= htmlspecialchars($provider['name']) ?>
            </h1>
            <p><?= htmlspecialchars($provider['description']) ?></p>
            
            <div class="provider-info">
                <div class="info-item">
                    <i class="fas fa-phone"></i>
                    <span><?= htmlspecialchars($provider['contact']) ?></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-envelope"></i>
                    <span><?= htmlspecialchars($provider['email']) ?></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-star"></i>
                    <span><?= number_format($provider['rating'], 1) ?>/5.0 Rating</span>
                </div>
                <div class="info-item">
                    <i class="fas fa-truck"></i>
                    <span><?= htmlspecialchars($provider['vehicle_type']) ?></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-clock"></i>
                    <span><?= htmlspecialchars($provider['operating_hours']) ?></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-shield-alt"></i>
                    <span><?= $provider['insurance_valid'] ? 'Insured' : 'Not Insured' ?></span>
                </div>
            </div>
        </div>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="stat-number"><?= $stats['total_quotes'] ?></div>
                <div class="stat-label">Total Quotes</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon active">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="stat-number"><?= $stats['active_quotes'] ?></div>
                <div class="stat-label">Active Quotes</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon completed">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-number"><?= $stats['completed_quotes'] ?></div>
                <div class="stat-label">Completed</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon revenue">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-number">K<?= number_format($stats['total_revenue'], 2) ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>
                    <i class="fas fa-list"></i>
                    Recent Quotes
                </h2>
            </div>
            <div class="card-body">
                <?php if (!empty($quotes)): ?>
                    <div style="overflow-x: auto;">
                        <table class="quotes-table">
                            <thead>
                                <tr>
                                    <th>Quote ID</th>
                                    <th>Customer</th>
                                    <th>Delivery Address</th>
                                    <th>Distance</th>
                                    <th>Cost</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($quotes as $quote): ?>
                                    <tr>
                                        <td>#<?= $quote['id'] ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($quote['shipping_name'] ?? 'N/A') ?></strong><br>
                                            <small><?= htmlspecialchars($quote['shipping_phone'] ?? '') ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($quote['delivery_address']) ?></td>
                                        <td><?= $quote['distance_km'] ?>km</td>
                                        <td>K<?= number_format($quote['estimated_cost'], 2) ?></td>
                                        <td>
                                            <span class="status-badge status-<?= $quote['status'] ?>">
                                                <?= ucfirst($quote['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('M d, Y', strtotime($quote['created_at'])) ?></td>
                                        <td>
                                            <?php if ($quote['status'] !== 'completed' && $quote['status'] !== 'cancelled'): ?>
                                                <button class="btn btn-primary btn-sm" onclick="updateQuoteStatus(<?= $quote['id'] ?>, '<?= $quote['status'] ?>')">
                                                    <i class="fas fa-edit"></i>
                                                    Update
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px;">
                        <i class="fas fa-inbox" style="font-size: 3rem; color: var(--secondary-color); margin-bottom: 20px;"></i>
                        <h3>No Quotes Yet</h3>
                        <p>You haven't received any delivery quotes yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Update Quote Status Modal -->
    <div id="updateModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Update Quote Status</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" id="updateForm">
                    <input type="hidden" name="action" value="update_quote_status">
                    <input type="hidden" name="quote_id" id="quoteId">
                    
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select name="status" id="status" class="form-control" required>
                            <option value="pending">Pending</option>
                            <option value="accepted">Accepted</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea name="notes" id="notes" class="form-control" rows="3" placeholder="Add any notes about this status update..."></textarea>
                    </div>
                    
                    <div style="text-align: right;">
                        <button type="button" class="btn" onclick="closeModal()" style="background: var(--secondary-color); color: white; margin-right: 10px;">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i>
                            Update Status
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function updateQuoteStatus(quoteId, currentStatus) {
            document.getElementById('quoteId').value = quoteId;
            document.getElementById('status').value = currentStatus;
            document.getElementById('updateModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('updateModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('updateModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
        
        // Auto-refresh every 30 seconds
        setInterval(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>