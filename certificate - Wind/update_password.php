<?php
// Generate a password hash for "123"
$password = "123";
$hash = password_hash($password, PASSWORD_BCRYPT);
echo "Password: " . $password . "\n";
echo "Hash: " . $hash . "\n";

// Include database configuration
require_once 'config/database.php';

// Connect to database
$database = new Database();
$db = $database->getConnection();

// Update admin password
$query = "UPDATE admins SET password = :password WHERE username = 'admin'";
$stmt = $db->prepare($query);
$stmt->bindParam(':password', $hash);

if ($stmt->execute()) {
    echo "Password updated successfully!\n";
} else {
    echo "Failed to update password.\n";
}
?>
