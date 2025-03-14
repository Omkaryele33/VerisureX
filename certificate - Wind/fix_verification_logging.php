<?php
/**
 * Fix Verification Logging
 * This script modifies the logEnhancedVerification function in verify/index.php
 * to handle the case when the certificate_verifications table doesn't exist
 */

// Display errors for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Fixing Certificate Verification Logging</h1>";

// Path to the verify/index.php file
$indexPath = 'verify/index.php';

try {
    if (file_exists($indexPath)) {
        $indexContent = file_get_contents($indexPath);
        
        // Check if we need to modify the function
        if (strpos($indexContent, 'certificate_verifications table is missing') === false) {
            // Find the logEnhancedVerification function
            if (preg_match('/function logEnhancedVerification\([^)]+\)[^{]*\{/', $indexContent, $matches, PREG_OFFSET_CAPTURE)) {
                // Make a backup of the original file
                $backupPath = 'verify/index.php.bak.' . time();
                file_put_contents($backupPath, $indexContent);
                echo "<p style='color:blue'>Created backup of index.php at $backupPath</p>";
                
                // Find the beginning of the try block inside the function
                $functionStart = $matches[0][0];
                $tryPos = strpos($indexContent, 'try {', $matches[0][1]);
                
                if ($tryPos !== false) {
                    // Determine where to insert our check
                    $tryBlockStart = $tryPos + 6; // Length of "try {"
                    
                    // Create the table check code
                    $tableCheck = "\n        // Check if certificate_verifications table exists\n        try {\n            \$db->query(\"SELECT 1 FROM certificate_verifications LIMIT 1\");\n        } catch (PDOException \$e) {\n            // Table doesn't exist, log and return\n            error_log(\"certificate_verifications table is missing: \" . \$e->getMessage());\n            return false;\n        }\n";
                    
                    // Insert the check after the try { line
                    $newContent = substr($indexContent, 0, $tryBlockStart) . $tableCheck . substr($indexContent, $tryBlockStart);
                    
                    // Write the modified content back
                    file_put_contents($indexPath, $newContent);
                    
                    echo "<p style='color:green'>✓ Successfully modified logEnhancedVerification function to handle missing tables</p>";
                } else {
                    echo "<p style='color:orange'>Could not find try block in logEnhancedVerification function.</p>";
                }
            } else {
                echo "<p style='color:orange'>Could not find logEnhancedVerification function in index.php.</p>";
            }
        } else {
            echo "<p style='color:blue'>logEnhancedVerification function already handles missing tables.</p>";
        }
    } else {
        echo "<p style='color:red'>Could not find verify/index.php file.</p>";
    }
} catch (Exception $e) {
    echo "<h2 style='color:red'>Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}

echo "<p>Now you can <a href='verify/index.php'>try verifying a certificate</a>.</p>";
?> 