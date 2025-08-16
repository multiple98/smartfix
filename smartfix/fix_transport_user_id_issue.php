<?php
// Fix Transport System User ID Issue
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/db.php');

echo "<h1>SmartFix Transport System - User ID Issue Fix</h1>";
echo "<style>
body{font-family:Arial;margin:40px;background:#f5f5f5;} 
.success{color:green;background:#f0fff0;padding:15px;margin:10px 0;border-left:4px solid green;border-radius:5px;} 
.error{color:red;background:#fff0f0;padding:15px;margin:10px 0;border-left:4px solid red;border-radius:5px;} 
.info{color:blue;background:#f0f8ff;padding:15px;margin:10px 0;border-left:4px solid blue;border-radius:5px;}
.warning{color:orange;background:#fffaf0;padding:15px;margin:10px 0;border-left:4px solid orange;border-radius:5px;}
h2{color:#004080;border-bottom:2px solid #007BFF;padding-bottom:10px;margin-top:30px;}
.container{background:white;padding:30px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}
.table{width:100%;border-collapse:collapse;margin:20px 0;}
.table th,.table td{padding:12px;border:1px solid #ddd;text-align:left;}
.table th{background:#f8f9fa;}
</style>";

echo "<div class='container'>";

try {
    echo "<h2>1. Diagnosing Transport System Tables</h2>";
    
    // Check all tables that might have user_id issues
    $tables_to_check = ['transport_providers', 'transport_quotes', 'transport_tracking'];
    $issues_found = [];
    
    foreach ($tables_to_check as $table) {
        echo "<h3>Checking table: $table</h3>";
        
        try {
            $check_table = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($check_table->rowCount() > 0) {
                echo "<div class='info'>✓ Table $table exists</div>";
                
                // Get table structure
                $columns = $pdo->query("SHOW COLUMNS FROM $table")->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<table class='table'>";
                echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
                
                $has_user_id = false;
                foreach ($columns as $column) {
                    echo "<tr>";
                    echo "<td>{$column['Field']}</td>";
                    echo "<td>{$column['Type']}</td>";
                    echo "<td>{$column['Null']}</td>";
                    echo "<td>{$column['Key']}</td>";
                    echo "<td>{$column['Default']}</td>";
                    echo "<td>{$column['Extra']}</td>";
                    echo "</tr>";
                    
                    if ($column['Field'] === 'user_id') {
                        $has_user_id = true;
                        if ($column['Null'] === 'NO' && $column['Default'] === null) {
                            $issues_found[] = "Table $table has user_id column that cannot be null and has no default value";
                            echo "<tr style='background:#ffebee;'><td colspan='6'><strong>⚠ ISSUE: user_id column cannot be null!</strong></td></tr>";
                        }
                    }
                }
                echo "</table>";
                
                if ($has_user_id) {
                    echo "<div class='warning'>⚠ Table $table has user_id column</div>";
                } else {
                    echo "<div class='success'>✓ Table $table does not have user_id column</div>";
                }
                
            } else {
                echo "<div class='info'>ℹ Table $table does not exist</div>";
            }
        } catch (PDOException $e) {
            echo "<div class='error'>Error checking table $table: " . $e->getMessage() . "</div>";
        }
    }
    
    echo "<h2>2. Issues Summary</h2>";
    if (empty($issues_found)) {
        echo "<div class='success'>✓ No user_id issues found in transport system tables!</div>";
    } else {
        echo "<div class='error'>Found " . count($issues_found) . " issues:</div>";
        foreach ($issues_found as $issue) {
            echo "<div class='error'>• $issue</div>";
        }
    }
    
    echo "<h2>3. Automatic Fix</h2>";
    
    if (!empty($issues_found)) {
        echo "<div class='warning'>Attempting to fix user_id issues...</div>";
        
        // Fix transport_providers table
        try {
            $check_tp = $pdo->query("SHOW TABLES LIKE 'transport_providers'");
            if ($check_tp->rowCount() > 0) {
                $tp_columns = $pdo->query("SHOW COLUMNS FROM transport_providers")->fetchAll(PDO::FETCH_ASSOC);
                $tp_has_user_id = false;
                foreach ($tp_columns as $column) {
                    if ($column['Field'] === 'user_id') {
                        $tp_has_user_id = true;
                        break;
                    }
                }
                
                if ($tp_has_user_id) {
                    echo "<div class='info'>Fixing transport_providers table...</div>";
                    
                    // Try to make user_id nullable first
                    try {
                        $pdo->exec("ALTER TABLE transport_providers MODIFY COLUMN user_id INT NULL");
                        echo "<div class='success'>✓ Made user_id nullable in transport_providers</div>";
                    } catch (PDOException $e) {
                        // If that fails, try to drop the column
                        try {
                            $pdo->exec("ALTER TABLE transport_providers DROP COLUMN user_id");
                            echo "<div class='success'>✓ Removed user_id column from transport_providers</div>";
                        } catch (PDOException $e) {
                            echo "<div class='error'>Failed to fix transport_providers: " . $e->getMessage() . "</div>";
                        }
                    }
                }
            }
        } catch (PDOException $e) {
            echo "<div class='error'>Error fixing transport_providers: " . $e->getMessage() . "</div>";
        }
        
        // Fix transport_quotes table
        try {
            $check_tq = $pdo->query("SHOW TABLES LIKE 'transport_quotes'");
            if ($check_tq->rowCount() > 0) {
                $tq_columns = $pdo->query("SHOW COLUMNS FROM transport_quotes")->fetchAll(PDO::FETCH_ASSOC);
                $tq_has_user_id = false;
                foreach ($tq_columns as $column) {
                    if ($column['Field'] === 'user_id') {
                        $tq_has_user_id = true;
                        break;
                    }
                }
                
                if ($tq_has_user_id) {
                    echo "<div class='info'>Fixing transport_quotes table...</div>";
                    
                    // Try to make user_id nullable first
                    try {
                        $pdo->exec("ALTER TABLE transport_quotes MODIFY COLUMN user_id INT NULL");
                        echo "<div class='success'>✓ Made user_id nullable in transport_quotes</div>";
                    } catch (PDOException $e) {
                        // If that fails, try to drop the column
                        try {
                            $pdo->exec("ALTER TABLE transport_quotes DROP COLUMN user_id");
                            echo "<div class='success'>✓ Removed user_id column from transport_quotes</div>";
                        } catch (PDOException $e) {
                            echo "<div class='error'>Failed to fix transport_quotes: " . $e->getMessage() . "</div>";
                        }
                    }
                }
            }
        } catch (PDOException $e) {
            echo "<div class='error'>Error fixing transport_quotes: " . $e->getMessage() . "</div>";
        }
        
        // Fix transport_tracking table
        try {
            $check_tt = $pdo->query("SHOW TABLES LIKE 'transport_tracking'");
            if ($check_tt->rowCount() > 0) {
                $tt_columns = $pdo->query("SHOW COLUMNS FROM transport_tracking")->fetchAll(PDO::FETCH_ASSOC);
                $tt_has_user_id = false;
                foreach ($tt_columns as $column) {
                    if ($column['Field'] === 'user_id') {
                        $tt_has_user_id = true;
                        break;
                    }
                }
                
                if ($tt_has_user_id) {
                    echo "<div class='info'>Fixing transport_tracking table...</div>";
                    
                    // Try to make user_id nullable first
                    try {
                        $pdo->exec("ALTER TABLE transport_tracking MODIFY COLUMN user_id INT NULL");
                        echo "<div class='success'>✓ Made user_id nullable in transport_tracking</div>";
                    } catch (PDOException $e) {
                        // If that fails, try to drop the column
                        try {
                            $pdo->exec("ALTER TABLE transport_tracking DROP COLUMN user_id");
                            echo "<div class='success'>✓ Removed user_id column from transport_tracking</div>";
                        } catch (PDOException $e) {
                            echo "<div class='error'>Failed to fix transport_tracking: " . $e->getMessage() . "</div>";
                        }
                    }
                }
            }
        } catch (PDOException $e) {
            echo "<div class='error'>Error fixing transport_tracking: " . $e->getMessage() . "</div>";
        }
        
        echo "<div class='success'>✓ Fix attempts completed!</div>";
    }
    
    echo "<h2>4. Verification</h2>";
    
    // Re-check all tables
    $remaining_issues = [];
    foreach ($tables_to_check as $table) {
        try {
            $check_table = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($check_table->rowCount() > 0) {
                $columns = $pdo->query("SHOW COLUMNS FROM $table")->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($columns as $column) {
                    if ($column['Field'] === 'user_id' && $column['Null'] === 'NO' && $column['Default'] === null) {
                        $remaining_issues[] = "Table $table still has problematic user_id column";
                    }
                }
            }
        } catch (PDOException $e) {
            // Ignore
        }
    }
    
    if (empty($remaining_issues)) {
        echo "<div class='success'>✓ All user_id issues have been resolved!</div>";
        echo "<div class='info'>You can now safely run the transport system setup and use the enhanced features.</div>";
    } else {
        echo "<div class='error'>Some issues remain:</div>";
        foreach ($remaining_issues as $issue) {
            echo "<div class='error'>• $issue</div>";
        }
        echo "<div class='warning'>You may need to manually drop and recreate these tables.</div>";
    }
    
    echo "<h2>5. Next Steps</h2>";
    echo "<div class='info'>";
    echo "<strong>If all issues are resolved:</strong><br>";
    echo "1. Run <a href='setup_transport_system.php'>setup_transport_system.php</a> to set up the enhanced transport system<br>";
    echo "2. Use <a href='admin/transport_providers_enhanced.php'>Enhanced Transport Providers Management</a><br>";
    echo "3. Test the <a href='smart_transport_selector_enhanced.php?order_id=1'>Smart Transport Selector</a><br><br>";
    echo "<strong>If issues remain:</strong><br>";
    echo "1. Manually drop the problematic tables in phpMyAdmin<br>";
    echo "2. Run the setup script to recreate them properly<br>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>Error during diagnosis: " . $e->getMessage() . "</div>";
}

echo "</div>";
?>