<?php
/**
 * Fix Issue Date Column
 * This script specifically targets the issue_date column problem
 */

// Display errors for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Fix Issue Date Column Problem</h1>";

try {
    // Connect to database
    require_once 'includes/config.php';
    require_once 'includes/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Function to check if a column exists in a table
    function columnExists($db, $tableName, $columnName) {
        try {
            $stmt = $db->prepare("SHOW COLUMNS FROM $tableName LIKE :columnName");
            $stmt->bindParam(':columnName', $columnName);
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // Function to get all columns from a table
    function getTableColumns($db, $tableName) {
        try {
            $stmt = $db->query("DESCRIBE $tableName");
            $columns = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $columns[$row['Field']] = $row;
            }
            return $columns;
        } catch (PDOException $e) {
            return [];
        }
    }
    
    // Check certificates table
    echo "<h2>Checking Certificates Table</h2>";
    
    try {
        $columns = getTableColumns($db, 'certificates');
        
        if (empty($columns)) {
            echo "<p style='color:red'>❌ Could not retrieve columns from certificates table. The table might not exist.</p>";
        } else {
            echo "<p>Found " . count($columns) . " columns in certificates table:</p>";
            echo "<ul>";
            foreach ($columns as $column => $details) {
                echo "<li><strong>$column</strong> - Type: {$details['Type']}</li>";
            }
            echo "</ul>";
            
            // Check for issue_date column
            if (!isset($columns['issue_date'])) {
                echo "<p style='color:red'>❌ The issue_date column is missing from the certificates table!</p>";
                
                // Add the column
                echo "<p>Adding issue_date column...</p>";
                $db->exec("ALTER TABLE certificates ADD COLUMN issue_date DATE");
                
                // Populate with data
                if (isset($columns['created_at'])) {
                    $db->exec("UPDATE certificates SET issue_date = DATE(created_at)");
                    echo "<p style='color:green'>✅ Added issue_date column and populated with created_at dates</p>";
                } else {
                    $db->exec("UPDATE certificates SET issue_date = CURDATE()");
                    echo "<p style='color:green'>✅ Added issue_date column and set to current date</p>";
                }
            } else {
                echo "<p style='color:green'>✅ The issue_date column exists in the certificates table.</p>";
            }
        }
    } catch (PDOException $e) {
        echo "<p style='color:red'>Error checking certificates table: " . $e->getMessage() . "</p>";
    }
    
    // Check for queries using issue_date
    echo "<h2>Checking Files Using issue_date</h2>";
    
    $filesToCheck = [
        'admin/create_certificate.php',
        'admin/edit_certificate.php',
        'admin/certificates.php',
        'admin/view_certificate.php',
        'admin/bulk_certificates.php'
    ];
    
    foreach ($filesToCheck as $file) {
        if (file_exists($file)) {
            echo "<h3>Checking $file</h3>";
            $content = file_get_contents($file);
            
            // Look for INSERT queries with issue_date
            preg_match_all('/INSERT\s+INTO\s+certificates\s*\(([^)]+)\)/i', $content, $insertMatches);
            if (!empty($insertMatches[1])) {
                foreach ($insertMatches[1] as $columnList) {
                    if (strpos($columnList, 'issue_date') !== false) {
                        echo "<p>Found INSERT query with issue_date column:</p>";
                        echo "<pre>" . htmlspecialchars("INSERT INTO certificates (" . $columnList . ")") . "</pre>";
                    }
                }
            }
            
            // Look for SELECT queries with issue_date
            preg_match_all('/SELECT\s+([^;]+?)\s+FROM\s+certificates/i', $content, $selectMatches);
            if (!empty($selectMatches[1])) {
                foreach ($selectMatches[1] as $columnList) {
                    if (strpos($columnList, 'issue_date') !== false) {
                        echo "<p>Found SELECT query with issue_date column:</p>";
                        echo "<pre>" . htmlspecialchars("SELECT " . $columnList . " FROM certificates") . "</pre>";
                    }
                }
            }
            
            // Look for UPDATE queries with issue_date
            preg_match_all('/UPDATE\s+certificates\s+SET\s+([^;]+?)\s+WHERE/i', $content, $updateMatches);
            if (!empty($updateMatches[1])) {
                foreach ($updateMatches[1] as $setClause) {
                    if (strpos($setClause, 'issue_date') !== false) {
                        echo "<p>Found UPDATE query with issue_date column:</p>";
                        echo "<pre>" . htmlspecialchars("UPDATE certificates SET " . $setClause . " WHERE ...") . "</pre>";
                    }
                }
            }
        } else {
            echo "<p style='color:orange'>⚠️ File $file does not exist</p>";
        }
    }
    
    // Check for any recent errors in the PHP error log
    echo "<h2>Recent PHP Errors</h2>";
    
    $errorLog = ini_get('error_log');
    if ($errorLog && file_exists($errorLog)) {
        $logContent = file_get_contents($errorLog);
        $lines = explode("\n", $logContent);
        $lines = array_slice($lines, -20); // Get last 20 lines
        
        $relevantErrors = [];
        foreach ($lines as $line) {
            if (strpos($line, 'issue_date') !== false || strpos($line, 'Unknown column') !== false) {
                $relevantErrors[] = $line;
            }
        }
        
        if (!empty($relevantErrors)) {
            echo "<p>Found relevant errors in the PHP error log:</p>";
            echo "<pre>" . implode("\n", $relevantErrors) . "</pre>";
        } else {
            echo "<p>No relevant errors found in the PHP error log.</p>";
        }
    } else {
        echo "<p>Could not access PHP error log.</p>";
    }
    
    // Fix create_certificate.php if needed
    echo "<h2>Fixing create_certificate.php</h2>";
    
    if (file_exists('admin/create_certificate.php')) {
        $content = file_get_contents('admin/create_certificate.php');
        
        // Check if the INSERT query includes issue_date
        if (preg_match('/INSERT\s+INTO\s+certificates\s*\(([^)]+)\)/i', $content, $match)) {
            $columnList = $match[1];
            
            if (strpos($columnList, 'issue_date') !== false) {
                echo "<p>The INSERT query in create_certificate.php includes the issue_date column.</p>";
                
                // Check if the issue_date column exists in the database
                if (!isset($columns['issue_date'])) {
                    echo "<p style='color:red'>❌ The issue_date column is referenced in the code but doesn't exist in the database!</p>";
                } else {
                    echo "<p style='color:green'>✅ The issue_date column exists in both the code and database.</p>";
                }
            } else {
                echo "<p style='color:orange'>⚠️ The INSERT query in create_certificate.php does not include the issue_date column.</p>";
            }
        }
    } else {
        echo "<p style='color:orange'>⚠️ File admin/create_certificate.php does not exist</p>";
    }
    
    // Create a test certificate to verify functionality
    echo "<h2>Testing Certificate Creation</h2>";
    
    try {
        // Generate a unique certificate ID
        $certificateId = uniqid('TEST-', true);
        
        // Insert a test certificate
        $query = "INSERT INTO certificates (certificate_id, holder_name, course_name, issue_date) 
                  VALUES (:certificate_id, 'Test Holder', 'Test Course', CURDATE())";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':certificate_id', $certificateId);
        
        if ($stmt->execute()) {
            echo "<p style='color:green'>✅ Successfully created a test certificate with issue_date!</p>";
            
            // Clean up the test certificate
            $db->exec("DELETE FROM certificates WHERE certificate_id = '$certificateId'");
            echo "<p>Test certificate removed.</p>";
        } else {
            echo "<p style='color:red'>❌ Failed to create a test certificate.</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color:red'>Error testing certificate creation: " . $e->getMessage() . "</p>";
        
        // If the error is about issue_date, provide more details
        if (strpos($e->getMessage(), 'issue_date') !== false) {
            echo "<p>This confirms that the issue_date column is causing problems.</p>";
            
            // Try to fix it one more time
            if (!isset($columns['issue_date'])) {
                try {
                    $db->exec("ALTER TABLE certificates ADD COLUMN issue_date DATE NOT NULL DEFAULT CURDATE()");
                    echo "<p style='color:green'>✅ Added issue_date column with a default value of today's date.</p>";
                    
                    // Try the test again
                    $query = "INSERT INTO certificates (certificate_id, holder_name, course_name, issue_date) 
                              VALUES (:certificate_id, 'Test Holder', 'Test Course', CURDATE())";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':certificate_id', $certificateId);
                    
                    if ($stmt->execute()) {
                        echo "<p style='color:green'>✅ Second attempt: Successfully created a test certificate!</p>";
                        
                        // Clean up the test certificate
                        $db->exec("DELETE FROM certificates WHERE certificate_id = '$certificateId'");
                    } else {
                        echo "<p style='color:red'>❌ Second attempt: Failed to create a test certificate.</p>";
                    }
                } catch (PDOException $e2) {
                    echo "<p style='color:red'>Error adding issue_date column: " . $e2->getMessage() . "</p>";
                }
            }
        }
    }
    
    // Success message
    echo "<h2>Diagnostic Complete</h2>";
    echo "<p>The issue_date column has been checked and fixed if necessary.</p>";
    echo "<p>You can now try to create a certificate again:</p>";
    echo "<ul>";
    echo "<li><a href='admin/create_certificate.php'>Create a New Certificate</a></li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<h2 style='color:red'>Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?> 