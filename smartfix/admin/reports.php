<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Counts for summary
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users"))['total'];
$total_services = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM service_requests"))['total'];
$total_products = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM products"))['total'];
$total_emergencies = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM emergencies"))['total'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Reports</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            background: #f4f7f9;
        }

        .header {
            background: #343a40;
            color: white;
            padding: 20px;
            text-align: center;
            font-size: 24px;
        }

        .container {
            max-width: 1100px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .summary {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            justify-content: center;
            margin-bottom: 30px;
        }

        .box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            flex: 1 1 200px;
            text-align: center;
        }

        .box h2 {
            margin: 10px 0;
            color: #007bff;
        }

        .chart-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        canvas {
            max-width: 100%;
        }
    </style>
</head>
<body>

<div class="header">📊 Platform Analytics</div>

<div class="container">

    <div class="summary">
        <div class="box">
            <h4>Total Users</h4>
            <h2><?php echo $total_users; ?></h2>
        </div>
        <div class="box">
            <h4>Service Requests</h4>
            <h2><?php echo $total_services; ?></h2>
        </div>
        <div class="box">
            <h4>Products Posted</h4>
            <h2><?php echo $total_products; ?></h2>
        </div>
        <div class="box">
            <h4>Emergencies</h4>
            <h2><?php echo $total_emergencies; ?></h2>
        </div>
    </div>

    <div class="chart-section">
        <canvas id="reportChart"></canvas>
    </div>

</div>

<script>
    const ctx = document.getElementById('reportChart').getContext('2d');
    const reportChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Users', 'Services', 'Products', 'Emergencies'],
            datasets: [{
                label: 'Platform Stats',
                data: [<?php echo "$total_users, $total_services, $total_products, $total_emergencies"; ?>],
                backgroundColor: ['#007bff', '#28a745', '#ffc107', '#dc3545']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: context => `${context.dataset.label}: ${context.parsed.y}`
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision:0
                    }
                }
            }
        }
    });
</script>

</body>
</html>
