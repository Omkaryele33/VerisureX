<?php
/**
 * Check Admin Table Structure
 * This script examines the structure of the admins table and its relationship with users
 */

// Display errors for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Admin Table Structure Check</h1>";

try {
    // Connect to database
    require_once 'config/config.php';
    require_once 'config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if admins table exists
    $stmt = $db->query("SHOW TABLES LIKE 'admins'");
    if ($stmt->rowCount() == 0) {
        echo "<p style='color:red'>Error: The admins table does not exist!</p>";
        exit;
    }
    
    // Get admins table structure
    $stmt = $db->query("DESCRIBE admins");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Admins Table Structure:</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        foreach ($column as $key => $value) {
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Check if users table exists
    $stmt = $db->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        // Get users table structure
        $stmt = $db->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h2>Users Table Structure:</h2>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        foreach ($columns as $column) {
            echo "<tr>";
            foreach ($column as $key => $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        
        echo "</table>";
        
        // Check if there's any relationship between users and admins
        echo "<h2>Relationship between Users and Admins:</h2>";
        $stmt = $db->query("SELECT COUNT(*) FROM information_schema.key_column_usage 
                            WHERE referenced_table_name = 'users' 
                            AND table_name = 'admins'");
        $hasRelationship = $stmt->fetchColumn() > 0;
        
        if ($hasRelationship) {
            echo "<p>There is a foreign key relationship between the admins and users tables.</p>";
        } else {
            echo "<p>No direct foreign key relationship found between admins and users tables.</p>";
        }
    } else {
        echo "<p>The users table does not exist in the database.</p>";
    }
    
    // List admin records
    $stmt = $db->query("SELECT * FROM admins LIMIT 10");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Admin Records:</h2>";
    
    if (count($admins) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr>";
        
        // Get column names
        $columns = array_keys($admins[0]);
        foreach ($columns as $column) {
            echo "<th>" . htmlspecialchars($column) . "</th>";
        }
        
        echo "</tr>";
        
        // Output data
        foreach ($admins as $admin) {
            echo "<tr>";
            foreach ($admin as $key => $value) {
                if ($key == 'password') {
                    echo "<td>[HIDDEN]</td>";
                } else {
                    echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                }
            }
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No admin records found.</p>";
    }
    
} catch (PDOException $e) {
    echo "<h2 style='color:red'>Database Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<h2 style='color:red'>General Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?> 