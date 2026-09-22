<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

session_start();

if (isset($_SESSION['last_barcode_scan'])) {
    $scan = $_SESSION['last_barcode_scan'];
    
    // Clear the session after reading to avoid duplicate processing
    unset($_SESSION['last_barcode_scan']);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $scan
    ]);
} else {
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'data' => null,
        'message' => 'No recent barcode scan'
    ]);
}
?>
