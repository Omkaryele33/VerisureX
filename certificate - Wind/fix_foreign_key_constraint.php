<?php
/**
 * Fix Foreign Key Constraint Issues for Certificates Table
 * This script diagnoses and fixes issues with the created_by foreign key constraint
 */

// Display errors for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Fix Foreign Key Constraint Issues</h1>";

try {
    // Connect to database
    require_once 'config/config.php';
    require_once 'config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Step 1: Check if admins table exists and has records
    $stmt = $db->query("SHOW TABLES LIKE 'admins'");
    $adminTableExists = $stmt->rowCount() > 0;
    
    if (!$adminTableExists) {
        echo "<p style='color:red'>Error: The admins table does not exist!</p>";
        exit;
    }
    
    // Step 2: Get admin records
    $stmt = $db->query("SELECT id, username FROM admins");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($admins) === 0) {
        echo "<p style='color:red'>Error: No admin users found in the database. At least one admin is required.</p>";
        exit;
    }
    
    echo "<h2>Available Admin Users:</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Username</th></tr>";
    
    foreach ($admins as $admin) {
        echo "<tr>";
        echo "<td>" . $admin['id'] . "</td>";
        echo "<td>" . htmlspecialchars($admin['username']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Step 3: Check foreign key constraints
    $stmt = $db->query("SHOW CREATE TABLE certificates");
    $tableInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    $createTableStatement = $tableInfo['Create Table'] ?? '';
    
    echo "<h2>Certificates Table Foreign Keys:</h2>";
    echo "<pre>" . htmlspecialchars($createTableStatement) . "</pre>";
    
    // Step 4: Check for certificates with invalid created_by values
    $stmt = $db->query("
        SELECT c.id, c.certificate_id, c.full_name, c.created_by 
        FROM certificates c 
        LEFT JOIN admins a ON c.created_by = a.id 
        WHERE a.id IS NULL
    ");
    
    $invalidCertificates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $invalidCount = count($invalidCertificates);
    
    echo "<h2>Certificates with Invalid created_by Values:</h2>";
    
    if ($invalidCount > 0) {
        echo "<p>Found {$invalidCount} certificates with invalid created_by values:</p>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Certificate ID</th><th>Full Name</th><th>Invalid created_by</th></tr>";
        
        foreach ($invalidCertificates as $cert) {
            echo "<tr>";
            echo "<td>" . $cert['id'] . "</td>";
            echo "<td>" . htmlspecialchars($cert['certificate_id']) . "</td>";
            echo "<td>" . htmlspecialchars($cert['full_name']) . "</td>";
            echo "<td>" . $cert['created_by'] . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // Step 5: Fix invalid created_by values
        $defaultAdminId = $admins[0]['id']; // Use the first admin as default
        
        echo "<h3>Fixing Invalid References:</h3>";
        echo "<p>Using admin ID {$defaultAdminId} as the default created_by value...</p>";
        
        $updateStmt = $db->prepare("
            UPDATE certificates 
            SET created_by = :admin_id 
            WHERE id = :cert_id
        ");
        
        $fixCount = 0;
        foreach ($invalidCertificates as $cert) {
            $updateStmt->bindParam(':admin_id', $defaultAdminId, PDO::PARAM_INT);
            $updateStmt->bindParam(':cert_id', $cert['id'], PDO::PARAM_INT);
            
            if ($updateStmt->execute()) {
                $fixCount++;
                echo "<p style='color:green'>✓ Fixed certificate ID {$cert['id']} for {$cert['full_name']}</p>";
            } else {
                echo "<p style='color:red'>✗ Failed to fix certificate ID {$cert['id']}</p>";
            }
        }
        
        echo "<p style='color:green'>Successfully fixed {$fixCount} of {$invalidCount} certificates.</p>";
    } else {
        echo "<p style='color:green'>✓ No certificates with invalid created_by values found.</p>";
    }
    
    // Step 6: Verify the fixes
    $stmt = $db->query("
        SELECT COUNT(*) as remaining_invalid 
        FROM certificates c 
        LEFT JOIN admins a ON c.created_by = a.id 
        WHERE a.id IS NULL
    ");
    
    $remaining = $stmt->fetch(PDO::FETCH_ASSOC)['remaining_invalid'] ?? 0;
    
    if ($remaining > 0) {
        echo "<p style='color:red'>Warning: There are still {$remaining} certificates with invalid created_by values. Manual intervention may be needed.</p>";
    } else {
        echo "<p style='color:green'>✓ All certificates now have valid created_by values referencing existing admins.</p>";
        echo "<p>The foreign key constraint issue should now be resolved.</p>";
    }
    
    echo "<p><a href='admin/certificates.php'>Go to Certificates List page</a></p>";
    
} catch (PDOException $e) {
    echo "<h2 style='color:red'>Database Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<h2 style='color:red'>General Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?> 