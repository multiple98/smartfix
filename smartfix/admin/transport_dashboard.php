<?php
session_start();
include('../includes/db.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

// Handle provider status updates
if (isset($_POST['update_status'])) {
    $provider_id = intval($_POST['provider_id']);
    $new_status = $_POST['status'];
    
    try {
        $stmt = $pdo->prepare("UPDATE transport_providers SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $provider_id]);
        $success_message = "Provider status updated successfully!";
    } catch (PDOException $e) {
        $error_message = "Error updating status: " . $e->getMessage();
    }
}

// Get transport statistics
$stats = [];
try {
    // Total providers
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM transport_providers");
    $stats['total_providers'] = $stmt->fetch()['total'];
    
    // Active providers
    $stmt = $pdo->query("SELECT COUNT(*) as active FROM transport_providers WHERE status = 'active'");
    $stats['active_providers'] = $stmt->fetch()['active'];
    
    // Total quotes
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM transport_quotes");
    $stats['total_quotes'] = $stmt->fetch()['total'];
    
    // Accepted quotes
    $stmt = $pdo->query("SELECT COUNT(*) as accepted FROM transport_quotes WHERE status = 'accepted'");
    $stats['accepted_quotes'] = $stmt->fetch()['accepted'];
    
    // Average delivery cost
    $stmt = $pdo->query("SELECT AVG(estimated_cost) as avg_cost FROM transport_quotes WHERE status = 'accepted'");
    $stats['avg_cost'] = $stmt->fetch()['avg_cost'] ?? 0;
    
    // Recent deliveries
    $stmt = $pdo->query("SELECT COUNT(*) as recent FROM delivery_tracking WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stats['recent_deliveries'] = $stmt->fetch()['recent'];
    
} catch (PDOException $e) {
    // Handle error
}

// Get transport providers
$providers = [];
try {
    $stmt = $pdo->query("SELECT tp.*, 
                         COUNT(tq.id) as total_quotes,
                         COUNT(CASE WHEN tq.status = 'accepted' THEN 1 END) as accepted_quotes,
                         AVG(tq.estimated_cost) as avg_quote_cost
                         FROM transport_providers tp
                         LEFT JOIN transport_quotes tq ON tp.id = tq.transport_provider_id
                         GROUP BY tp.id
                         ORDER BY tp.rating DESC, tp.name ASC");
    $providers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle error
}

// Get recent transport quotes
$recent_quotes = [];
try {
    $stmt = $pdo->query("SELECT tq.*, tp.name as provider_name, o.tracking_number
                         FROM transport_quotes tq
                         LEFT JOIN transport_providers tp ON tq.transport_provider_id = tp.id
                         LEFT JOIN orders o ON tq.order_id = o.id
                         ORDER BY tq.created_at DESC
                         LIMIT 10");
    $recent_quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle error
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transport Dashboard - SmartFix Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #007BFF;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --dark-color: #343a40;
            --light-color: #f8f9fa;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--light-color);
            color: var(--dark-color);
        }

        .header {
            background: var(--primary-color);
            color: white;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header h1 {
            margin: 0;
            font-size: 28px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .stat-value {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
        }

        .providers-section, .quotes-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--light-color);
        }

        .section-title {
            font-size: 24px;
            color: var(--dark-color);
            margin: 0;
        }

        .providers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }

        .provider-card {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            transition: all 0.3s ease;
        }

        .provider-card:hover {
            border-color: var(--primary-color);
            box-shadow: 0 5px 15px rgba(0,123,255,0.1);
        }

        .provider-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .provider-name {
            font-size: 18px;
            font-weight: bold;
            color: var(--dark-color);
            margin-bottom: 5px;
        }

        .provider-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-active { background: var(--success-color); color: white; }
        .status-inactive { background: var(--danger-color); color: white; }
        .status-maintenance { background: var(--warning-color); color: #333; }

        .provider-details {
            margin: 15px 0;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .provider-stats {
            background: var(--light-color);
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }

        .stats-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 13px;
        }

        .quotes-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .quotes-table th,
        .quotes-table td {
            text-align: left;
            padding: 12px 15px;
            border-bottom: 1px solid #e9ecef;
        }

        .quotes-table th {
            background-color: var(--light-color);
            font-weight: 600;
            color: var(--dark-color);
        }

        .quotes-table tbody tr:hover {
            background-color: rgba(0,123,255,0.05);
        }

        .btn {
            display: inline-block;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary { background: var(--primary-color); color: white; }
        .btn-success { background: var(--success-color); color: white; }
        .btn-warning { background: var(--warning-color); color: #333; }
        .btn-danger { background: var(--danger-color); color: white; }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .rating {
            color: #ffc107;
            font-size: 16px;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .providers-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1><i class="fas fa-truck"></i> Transport Dashboard</h1>
                <p>Manage transport providers and monitor delivery operations</p>
            </div>
            <div>
                <a href="admin_dashboard_new.php" class="btn btn-primary" style="margin-right: 10px;">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <a href="../smart_transport_selector.php" class="btn btn-success">
                    <i class="fas fa-plus"></i> New Transport Quote
                </a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Section -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="color: var(--primary-color);">
                    <i class="fas fa-truck"></i>
                </div>
                <div class="stat-value"><?php echo $stats['total_providers'] ?? 0; ?></div>
                <div class="stat-label">Total Providers</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="color: var(--success-color);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-value"><?php echo $stats['active_providers'] ?? 0; ?></div>
                <div class="stat-label">Active Providers</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="color: var(--info-color);">
                    <i class="fas fa-quote-right"></i>
                </div>
                <div class="stat-value"><?php echo $stats['total_quotes'] ?? 0; ?></div>
                <div class="stat-label">Total Quotes</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="color: var(--warning-color);">
                    <i class="fas fa-handshake"></i>
                </div>
                <div class="stat-value"><?php echo $stats['accepted_quotes'] ?? 0; ?></div>
                <div class="stat-label">Accepted Quotes</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="color: var(--success-color);">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-value">K<?php echo number_format($stats['avg_cost'] ?? 0, 2); ?></div>
                <div class="stat-label">Average Delivery Cost</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="color: var(--primary-color);">
                    <i class="fas fa-shipping-fast"></i>
                </div>
                <div class="stat-value"><?php echo $stats['recent_deliveries'] ?? 0; ?></div>
                <div class="stat-label">Recent Deliveries (7 days)</div>
            </div>
        </div>

        <!-- Transport Providers Section -->
        <div class="providers-section">
            <div class="section-header">
                <h2 class="section-title"><i class="fas fa-truck-moving"></i> Transport Providers</h2>
                <a href="transport_providers.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Provider
                </a>
            </div>

            <div class="providers-grid">
                <?php foreach ($providers as $provider): ?>
                    <div class="provider-card">
                        <div class="provider-header">
                            <div>
                                <div class="provider-name">
                                    <i class="fas fa-<?php echo $provider['vehicle_type'] === 'motorbike' ? 'motorcycle' : ($provider['vehicle_type'] === 'truck' ? 'truck' : 'car'); ?>"></i>
                                    <?php echo htmlspecialchars($provider['name']); ?>
                                </div>
                                <div class="rating">
                                    <?php for ($i = 1; $i <= 5; $i++) echo $i <= $provider['rating'] ? '★' : '☆'; ?>
                                    (<?php echo $provider['rating']; ?>)
                                </div>
                            </div>
                            <span class="provider-status status-<?php echo $provider['status']; ?>">
                                <?php echo ucfirst($provider['status']); ?>
                            </span>
                        </div>

                        <div class="provider-details">
                            <div class="detail-row">
                                <span>Contact:</span>
                                <span><?php echo htmlspecialchars($provider['contact']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span>Service Type:</span>
                                <span><?php echo ucfirst(str_replace('_', ' ', $provider['service_type'])); ?></span>
                            </div>
                            <div class="detail-row">
                                <span>Base Cost:</span>
                                <span>K<?php echo number_format($provider['base_cost'], 2); ?></span>
                            </div>
                            <div class="detail-row">
                                <span>Cost per km:</span>
                                <span>K<?php echo number_format($provider['cost_per_km'], 2); ?></span>
                            </div>
                            <div class="detail-row">
                                <span>Max Weight:</span>
                                <span><?php echo $provider['max_weight_kg']; ?> kg</span>
                            </div>
                        </div>

                        <div class="provider-stats">
                            <div class="stats-row">
                                <span>Total Quotes:</span>
                                <span><?php echo $provider['total_quotes']; ?></span>
                            </div>
                            <div class="stats-row">
                                <span>Accepted Quotes:</span>
                                <span><?php echo $provider['accepted_quotes']; ?></span>
                            </div>
                            <div class="stats-row">
                                <span>Avg Quote Cost:</span>
                                <span>K<?php echo number_format($provider['avg_quote_cost'] ?? 0, 2); ?></span>
                            </div>
                        </div>

                        <div style="margin-top: 15px; display: flex; gap: 10px;">
                            <form method="POST" style="flex: 1;">
                                <input type="hidden" name="provider_id" value="<?php echo $provider['id']; ?>">
                                <select name="status" class="form-control" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; width: 100%;" onchange="this.form.submit()">
                                    <option value="active" <?php echo $provider['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $provider['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    <option value="maintenance" <?php echo $provider['status'] === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                </select>
                                <input type="hidden" name="update_status" value="1">
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Recent Transport Quotes Section -->
        <div class="quotes-section">
            <div class="section-header">
                <h2 class="section-title"><i class="fas fa-receipt"></i> Recent Transport Quotes</h2>
                <a href="../transport_quotes.php" class="btn btn-primary">
                    <i class="fas fa-calculator"></i> Get New Quote
                </a>
            </div>

            <?php if (!empty($recent_quotes)): ?>
                <table class="quotes-table">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Provider</th>
                            <th>Distance</th>
                            <th>Cost</th>
                            <th>Delivery Time</th>
                            <th>Status</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_quotes as $quote): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($quote['tracking_number'] ?? 'N/A'); ?></strong><br>
                                    <small style="color: #666;">Order #<?php echo $quote['order_id']; ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($quote['provider_name']); ?></td>
                                <td><?php echo $quote['distance_km']; ?> km</td>
                                <td><strong>K<?php echo number_format($quote['estimated_cost'], 2); ?></strong></td>
                                <td><?php echo $quote['estimated_delivery_time']; ?> day<?php echo $quote['estimated_delivery_time'] != 1 ? 's' : ''; ?></td>
                                <td>
                                    <span class="provider-status status-<?php echo $quote['status'] === 'accepted' ? 'active' : ($quote['status'] === 'declined' ? 'inactive' : 'maintenance'); ?>">
                                        <?php echo ucfirst($quote['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y H:i', strtotime($quote['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <i class="fas fa-receipt" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                    <h3>No Recent Quotes</h3>
                    <p>Transport quotes will appear here when customers request them.</p>
                </div>
            <?php endif; ?>
        </div>

        <div style="text-align: center; margin-top: 40px;">
            <a href="admin_dashboard_new.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Back to Admin Dashboard
            </a>
            <a href="../enhanced_transport_system.php" class="btn btn-success">
                <i class="fas fa-cog"></i> Setup Transport System
            </a>
        </div>
    </div>
</body>
</html>