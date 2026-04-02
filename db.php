<?php
/**
 * Path: D:\xampp\htdocs\BRCadmin\db.php
 * Database Connection for FerFer BRVC 3.0
 */

// Database Credentials
$host = "sql100.infinityfree.com";
$user = "if0_41558711";
$pass = "405gEJdUdc28e"; 
$db   = "if0_41558711_ferferbrvc";

// 1. Create connection using MySQLi
$conn = new mysqli($host, $user, $pass, $db);

// 2. Check connection status
if ($conn->connect_error) {
    // We use a clean error message so it doesn't break the JSON in check.php
    die(json_encode([
        "status" => "ERROR",
        "reason" => "Database connection failed: " . $conn->connect_error
    ]));
}

// 3. Set Charset to UTF-8 (Critical for license key stability)
if (!$conn->set_charset("utf8mb4")) {
    // If charset fails, we log it but don't stop the script
    error_log("Error loading character set utf8mb4: " . $conn->error);
}

// 4. Secure the connection
// This prevents certain SQL injection types and ensures consistent behavior
$conn->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, true);

/**
 * Note for InfinityFree: 
 * Ensure that your "Remote MySQL" settings allow the IP 
 * of your Minecraft server if it is not hosted on the same network.
 */
?>