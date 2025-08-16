<?php
include('includes/db.php');

try {
    // Check products table structure
    $stmt = $pdo->query("DESCRIBE products");
    echo "<h2>Products Table Structure:</h2>";
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if user_id column exists
    $check_user_id = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'user_id'");
    $check_user_id->execute();
    $user_id_exists = $check_user_id->fetchColumn() > 0;
    
    echo "<h3>User ID Column Exists: " . ($user_id_exists ? "YES" : "NO") . "</h3>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>