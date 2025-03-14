<?php
// Script to check database structure
require_once __DIR__ . "/config/database.php";

$database = new Database();
$db = $database->getConnection();

echo "<h1>Database Tables</h1>";
try {
    $stmt = $db->query("SHOW TABLES");
    echo "<h2>Tables in database:</h2>";
    echo "<ul>";
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        echo "<li>" . $row[0] . "</li>";
    }
    echo "</ul>";
} catch (PDOException $e) {
    echo "Error listing tables: " . $e->getMessage();
}

echo "<h1>Certificates Table Structure</h1>";
try {
    $stmt = $db->query("DESCRIBE certificates");
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . ($row['Default'] === null ? "NULL" : $row['Default']) . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (PDOException $e) {
    echo "Error describing certificates table: " . $e->getMessage();
} 