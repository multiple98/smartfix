<?php
session_start();
include('../includes/db.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_provider':
                $result = addTransportProvider($_POST);
                $message = $result['message'];
                $message_type = $result['type'];
                break;
                
            case 'update_provider':
                $result = updateTransportProvider($_POST);
                $message = $result['message'];
                $message_type = $result['type'];
                break;
                
            case 'delete_provider':
                $result = deleteTransportProvider($_POST['provider_id']);
                $message = $result['message'];
                $message_type = $result['type'];
                break;
                
            case 'update_status':
                $result = updateProviderStatus($_POST['provider_id'], $_POST['status']);
                $message = $result['message'];
                $message_type = $result['type'];
                break;
        }
    }
}

// Get all transport providers
$providers = getAllTransportProviders();

// Get provider for editing if requested
$edit_provider = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_provider = getTransportProvider($_GET['edit']);
}

function addTransportProvider($data) {
    global $pdo;
    
    try {
        $query = "INSERT INTO transport_providers 
                  (name, contact, email, description, regions, address, cost_per_km, base_cost, 
                   estimated_days, max_weight_kg, vehicle_type, service_type, latitude, longitude, operating_hours) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($query);
        $result = $stmt->execute([
            $data['name'], $data['contact'], $data['email'], $data['description'],
            $data['regions'], $data['address'], $data['cost_per_km'], $data['base_cost'],
            $data['estimated_days'], $data['max_weight_kg'], $data['vehicle_type'],
            $data['service_type'], $data['latitude'] ?: null, $data['longitude'] ?: null,
            $data['operating_hours']
        ]);
        
        return [
            'message' => 'Transport provider added successfully!',
            'type' => 'success'
        ];
        
    } catch (PDOException $e) {
        return [
            'message' => 'Error adding provider: ' . $e->getMessage(),
            'type' => 'error'
        ];
    }
}

function updateTransportProvider($data) {
    global $pdo;
    
    try {
        $query = "UPDATE transport_providers SET 
                  name = ?, contact = ?, email = ?, description = ?, regions = ?, address = ?,
                  cost_per_km = ?, base_cost = ?, estimated_days = ?, max_weight_kg = ?,
                  vehicle_type = ?, service_type = ?, latitude = ?, longitude = ?, operating_hours = ?
                  WHERE id = ?";
        
        $stmt = $pdo->prepare($query);
        $result = $stmt->execute([
            $data['name'], $data['contact'], $data['email'], $data['description'],
            $data['regions'], $data['address'], $data['cost_per_km'], $data['base_cost'],
            $data['estimated_days'], $data['max_weight_kg'], $data['vehicle_type'],
            $data['service_type'], $data['latitude'] ?: null, $data['longitude'] ?: null,
            $data['operating_hours'], $data['provider_id']
        ]);
        
        return [
            'message' => 'Transport provider updated successfully!',
            'type' => 'success'
        ];
        
    } catch (PDOException $e) {
        return [
            'message' => 'Error updating provider: ' . $e->getMessage(),
            'type' => 'error'
        ];
    }
}

function deleteTransportProvider($provider_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM transport_providers WHERE id = ?");
        $stmt->execute([$provider_id]);
        
        return [
            'message' => 'Transport provider deleted successfully!',
            'type' => 'success'
        ];
        
    } catch (PDOException $e) {
        return [
            'message' => 'Error deleting provider: ' . $e->getMessage(),
            'type' => 'error'
        ];
    }
}

function updateProviderStatus($provider_id, $status) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE transport_providers SET status = ? WHERE id = ?");
        $stmt->execute([$status, $provider_id]);
        
        return [
            'message' => 'Provider status updated successfully!',
            'type' => 'success'
        ];
        
    } catch (PDOException $e) {
        return [
            'message' => 'Error updating status: ' . $e->getMessage(),
            'type' => 'error'
        ];
    }
}

function getAllTransportProviders() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT tp.*, 
                            COUNT(tq.id) as total_quotes,
                            COUNT(CASE WHEN tq.status = 'accepted' THEN 1 END) as accepted_quotes,
                            AVG(tq.estimated_cost) as avg_quote_cost
                            FROM transport_providers tp
                            LEFT JOIN transport_quotes tq ON tp.id = tq.transport_provider_id
                            GROUP BY tp.id
                            ORDER BY tp.name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

function getTransportProvider($id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM transport_providers WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return null;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enhanced Transport Providers - SmartFix Admin</title>
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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--light-color);
            color: var(--dark-color);
            line-height: 1.6;
        }

        .header {
            background: linear-gradient(135deg, var(--primary-color), #0056b3);
            color: white;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 123, 255, 0.3);
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .nav-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary { background: var(--primary-color); color: white; }
        .btn-success { background: var(--success-color); color: white; }
        .btn-warning { background: var(--warning-color); color: #333; }
        .btn-danger { background: var(--danger-color); color: white; }
        .btn-secondary { background: #6c757d; color: white; }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-success {
            background: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(40, 167, 69, 0.3);
        }

        .alert-error {
            background: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        .form-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .form-section h2 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--light-color);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark-color);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .providers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .provider-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .provider-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary-color);
            box-shadow: 0 10px 30px rgba(0, 123, 255, 0.2);
        }

        .provider-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .provider-name {
            font-size: 1.25rem;
            font-weight: bold;
            color: var(--dark-color);
            margin-bottom: 0.25rem;
        }

        .provider-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-active { background: var(--success-color); color: white; }
        .status-inactive { background: var(--danger-color); color: white; }
        .status-maintenance { background: var(--warning-color); color: #333; }

        .provider-details {
            margin: 1rem 0;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .detail-label {
            font-weight: 600;
            color: #666;
        }

        .detail-value {
            color: var(--dark-color);
        }

        .provider-stats {
            background: var(--light-color);
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            text-align: center;
        }

        .stat-item {
            padding: 0.5rem;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .stat-label {
            font-size: 0.75rem;
            color: #666;
            text-transform: uppercase;
        }

        .provider-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }

        .rating {
            color: #ffc107;
            font-size: 1.1rem;
        }

        .location-info {
            background: rgba(0, 123, 255, 0.1);
            padding: 0.75rem;
            border-radius: 6px;
            margin: 0.5rem 0;
            font-size: 0.9rem;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: var(--danger-color);
        }

        @media (max-width: 768px) {
            .providers-grid {
                grid-template-columns: 1fr;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .nav-buttons {
                flex-direction: column;
            }
            
            .provider-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-truck"></i> Enhanced Transport Providers</h1>
        <p>Manage your delivery network with advanced features</p>
    </div>

    <div class="container">
        <!-- Navigation -->
        <div class="nav-buttons">
            <a href="admin_dashboard_new.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            <a href="transport_dashboard.php" class="btn btn-primary">
                <i class="fas fa-chart-line"></i> Transport Dashboard
            </a>
            <button onclick="openAddModal()" class="btn btn-success">
                <i class="fas fa-plus"></i> Add New Provider
            </button>
            <a href="gps_dashboard.php" class="btn btn-info">
                <i class="fas fa-map-marked-alt"></i> GPS Dashboard
            </a>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Providers Grid -->
        <div class="providers-grid">
            <?php foreach ($providers as $provider): ?>
                <div class="provider-card">
                    <div class="provider-header">
                        <div>
                            <div class="provider-name"><?php echo htmlspecialchars($provider['name']); ?></div>
                            <div class="rating">
                                <?php 
                                $rating = floatval($provider['rating']);
                                for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= $rating ? '★' : '☆';
                                }
                                echo ' (' . number_format($rating, 1) . ')';
                                ?>
                            </div>
                        </div>
                        <span class="provider-status status-<?php echo $provider['status']; ?>">
                            <?php echo ucfirst($provider['status']); ?>
                        </span>
                    </div>

                    <div class="provider-details">
                        <div class="detail-row">
                            <span class="detail-label">Contact:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($provider['contact']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Email:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($provider['email']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Vehicle Type:</span>
                            <span class="detail-value"><?php echo ucfirst($provider['vehicle_type']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Service Type:</span>
                            <span class="detail-value"><?php echo ucfirst(str_replace('_', ' ', $provider['service_type'])); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Base Cost:</span>
                            <span class="detail-value">K<?php echo number_format($provider['base_cost'], 2); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Per KM:</span>
                            <span class="detail-value">K<?php echo number_format($provider['cost_per_km'], 2); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Max Weight:</span>
                            <span class="detail-value"><?php echo $provider['max_weight_kg']; ?> kg</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Operating Hours:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($provider['operating_hours']); ?></span>
                        </div>
                    </div>

                    <?php if ($provider['latitude'] && $provider['longitude']): ?>
                        <div class="location-info">
                            <i class="fas fa-map-marker-alt"></i>
                            Location: <?php echo number_format($provider['latitude'], 4); ?>, <?php echo number_format($provider['longitude'], 4); ?>
                        </div>
                    <?php endif; ?>

                    <div class="provider-stats">
                        <div class="stats-grid">
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $provider['total_quotes'] ?? 0; ?></div>
                                <div class="stat-label">Total Quotes</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $provider['accepted_quotes'] ?? 0; ?></div>
                                <div class="stat-label">Accepted</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">K<?php echo number_format($provider['avg_quote_cost'] ?? 0, 0); ?></div>
                                <div class="stat-label">Avg Cost</div>
                            </div>
                        </div>
                    </div>

                    <div class="provider-actions">
                        <button onclick="editProvider(<?php echo $provider['id']; ?>)" class="btn btn-primary btn-sm">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="provider_id" value="<?php echo $provider['id']; ?>">
                            <select name="status" onchange="this.form.submit()" class="btn btn-sm" style="border: none;">
                                <option value="active" <?php echo $provider['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $provider['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="maintenance" <?php echo $provider['status'] === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                            </select>
                        </form>
                        
                        <button onclick="deleteProvider(<?php echo $provider['id']; ?>)" class="btn btn-danger btn-sm">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Add/Edit Provider Modal -->
    <div id="providerModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle">Add New Transport Provider</h2>
            
            <form id="providerForm" method="POST">
                <input type="hidden" name="action" id="formAction" value="add_provider">
                <input type="hidden" name="provider_id" id="providerId">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">Provider Name *</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="contact">Contact Number *</label>
                        <input type="text" id="contact" name="contact" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email">
                    </div>
                    
                    <div class="form-group">
                        <label for="vehicle_type">Vehicle Type *</label>
                        <select id="vehicle_type" name="vehicle_type" required>
                            <option value="motorbike">Motorbike</option>
                            <option value="car">Car</option>
                            <option value="van">Van</option>
                            <option value="truck">Truck</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="service_type">Service Type *</label>
                        <select id="service_type" name="service_type" required>
                            <option value="standard">Standard</option>
                            <option value="express">Express</option>
                            <option value="overnight">Overnight</option>
                            <option value="same_day">Same Day</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="base_cost">Base Cost (K) *</label>
                        <input type="number" id="base_cost" name="base_cost" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="cost_per_km">Cost per KM (K) *</label>
                        <input type="number" id="cost_per_km" name="cost_per_km" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="max_weight_kg">Max Weight (kg) *</label>
                        <input type="number" id="max_weight_kg" name="max_weight_kg" min="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="estimated_days">Estimated Days *</label>
                        <input type="number" id="estimated_days" name="estimated_days" min="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="operating_hours">Operating Hours</label>
                        <input type="text" id="operating_hours" name="operating_hours" placeholder="e.g., 08:00-18:00">
                    </div>
                    
                    <div class="form-group">
                        <label for="latitude">Latitude (GPS)</label>
                        <input type="number" id="latitude" name="latitude" step="0.000001" placeholder="-15.3875">
                    </div>
                    
                    <div class="form-group">
                        <label for="longitude">Longitude (GPS)</label>
                        <input type="number" id="longitude" name="longitude" step="0.000001" placeholder="28.3228">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" placeholder="Brief description of services offered"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="regions">Service Regions</label>
                    <textarea id="regions" name="regions" placeholder="e.g., Lusaka, Kabwe, Mazabuka"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="address">Physical Address</label>
                    <textarea id="address" name="address"></textarea>
                </div>
                
                <div style="text-align: right; margin-top: 2rem;">
                    <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Save Provider
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New Transport Provider';
            document.getElementById('formAction').value = 'add_provider';
            document.getElementById('providerForm').reset();
            document.getElementById('providerId').value = '';
            document.getElementById('providerModal').style.display = 'block';
        }

        function editProvider(id) {
            // This would typically fetch provider data via AJAX
            // For now, we'll redirect to the same page with edit parameter
            window.location.href = `?edit=${id}`;
        }

        function deleteProvider(id) {
            if (confirm('Are you sure you want to delete this transport provider? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_provider">
                    <input type="hidden" name="provider_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function closeModal() {
            document.getElementById('providerModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('providerModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        // If editing, populate the form
        <?php if ($edit_provider): ?>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('modalTitle').textContent = 'Edit Transport Provider';
            document.getElementById('formAction').value = 'update_provider';
            document.getElementById('providerId').value = '<?php echo $edit_provider['id']; ?>';
            
            // Populate form fields
            <?php foreach ($edit_provider as $key => $value): ?>
                <?php if ($key !== 'id' && $value !== null): ?>
                    const field<?php echo ucfirst($key); ?> = document.getElementById('<?php echo $key; ?>');
                    if (field<?php echo ucfirst($key); ?>) {
                        field<?php echo ucfirst($key); ?>.value = '<?php echo addslashes($value); ?>';
                    }
                <?php endif; ?>
            <?php endforeach; ?>
            
            document.getElementById('providerModal').style.display = 'block';
        });
        <?php endif; ?>
    </script>
</body>
</html>