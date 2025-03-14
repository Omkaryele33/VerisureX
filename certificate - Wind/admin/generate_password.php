<?php
/**
 * Password Hash Generator
 * Use this script to generate password hashes for admin users
 */

// Check if password is provided
if (isset($_GET['password'])) {
    $password = $_GET['password'];
    $hash = password_hash($password, PASSWORD_BCRYPT);
    
    echo "<p>Password: " . htmlspecialchars($password) . "</p>";
    echo "<p>Hash: " . $hash . "</p>";
} else {
    // Display form
    echo "<form method='get'>";
    echo "<label for='password'>Enter password:</label>";
    echo "<input type='text' name='password' id='password' required>";
    echo "<button type='submit'>Generate Hash</button>";
    echo "</form>";
}
?>
