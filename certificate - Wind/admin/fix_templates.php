<?php
/**
 * Template Page Fixer
 * This script fixes the certificate_templates.php file
 */

// Set error reporting to maximum for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$templateFile = 'certificate_templates.php';

// Check if file exists
if (!file_exists($templateFile)) {
    die('Error: Template file not found.');
}

// Read the file content
$content = file_get_contents($templateFile);

// Check if security_header.php is already included
if (strpos($content, "require_once 'security_header.php';") === false) {
    // Add security_header.php include after session_start
    $content = str_replace(
        "require_once __DIR__ . "/../includes/session.php";",
        "require_once __DIR__ . "/../includes/session.php";\n\n// Include security constants\nrequire_once 'security_header.php';",
        $content
    );
}

// Check if security.php is already included
if (strpos($content, "require_once '../includes/security.php';") === false) {
    // Add security.php include after functions.php
    $content = str_replace(
        "require_once '../includes/functions.php';",
        "require_once '../includes/functions.php';\nrequire_once '../includes/security.php';",
        $content
    );
}

// Write the updated content back to the file
if (file_put_contents($templateFile, $content)) {
    echo "Success: Template file has been fixed.";
} else {
    echo "Error: Could not write to template file.";
}
