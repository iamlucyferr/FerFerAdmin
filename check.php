<?php
/**
 * Path: D:\xampp\htdocs\BRCadmin\api\check.php
 * FerFer BRVC 3.0 - High Precision License Validator
 */

// 1. Production Settings: Disable error reporting to prevent JSON corruption
error_reporting(0); 

// 2. Set Local Timezone (Critical for accurate expiry checks)
date_default_timezone_set('Asia/Phnom_Penh');

require_once '../db.php'; 
header('Content-Type: application/json');

// Get the key from the URL (?key=FER-XXXX)
$key = $_GET['key'] ?? '';

// 3. Check if key is provided
if (empty($key)) {
    http_response_code(400); // Bad Request
    echo json_encode([
        "status" => "INVALID", 
        "reason" => "No license key provided"
    ]);
    exit;
}

try {
    // 4. Ensure DB connection exists (Check if $conn from db.php is valid)
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    // 5. Prepare statement to prevent SQL Injection
    $stmt = $conn->prepare("SELECT expiry_date, is_active FROM plugin_licenses WHERE license_key = ? LIMIT 1");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        
        // 6. Precise Time Comparison
        $current_time = new DateTime(); // Current system time
        $expiry_time  = new DateTime($row['expiry_date']); // Expiry date from DB
        
        $is_expired = ($current_time > $expiry_time);
        $is_active  = (int)$row['is_active'] === 1;

        if ($is_active && !$is_expired) {
            // --- SUCCESS ---
            // The plugin looks for exactly: "status":"VALID"
            echo json_encode([
                "status" => "VALID", 
                "expiry" => $row['expiry_date'],
                "server_time" => $current_time->format('Y-m-d H:i:s'),
                "message" => "License verified successfully"
            ]);
        } else {
            // --- FAILURE: Active but Expired OR Revoked ---
            $reason = !$is_active ? "License revoked by administrator" : "License expired on " . $row['expiry_date'];
            
            echo json_encode([
                "status" => "INVALID", 
                "reason" => $reason,
                "server_time" => $current_time->format('Y-m-d H:i:s')
            ]);
        }
    } else {
        // --- FAILURE: Key not found ---
        echo json_encode([
            "status" => "INVALID", 
            "reason" => "License key not found in database"
        ]);
    }
    
    $stmt->close();

} catch (Exception $e) {
    // 7. Handle system-level errors
    http_response_code(500); 
    echo json_encode([
        "status" => "ERROR", 
        "reason" => "Internal server error"
    ]);
}
?>