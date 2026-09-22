<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['barcode'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit;
    }
    
    session_start();
    
    $barcode = $data['barcode'];
    $idType = identifyBarcode($barcode);
    
    $scanData = [
        'barcode' => $barcode,
        'type' => $idType['type'],
        'data' => $idType['data'],
        'timestamp' => time()
    ];
    
    $_SESSION['last_barcode_scan'] = $scanData;
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Barcode scan recorded',
        'type' => $idType['type'],
        'data' => $idType['data']
    ]);
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

/**
 * Identify the type of barcode scanned
 * Returns: product, senior_citizen, or pwd
 */
function identifyBarcode($barcode) {
    // Senior Citizen ID pattern (customize based on your format)
    // Example: SC-followed by alphanumeric characters
    if (preg_match('/^SC-?[A-Z0-9]{6,}$/i', $barcode)) {
        return [
            'type' => 'senior_citizen',
            'data' => extractIDInfo($barcode)
        ];
    }
    
    // PWD ID pattern (customize based on your format)
    // Example: PWD-followed by alphanumeric characters
    if (preg_match('/^PWD-?[A-Z0-9]{6,}$/i', $barcode)) {
        return [
            'type' => 'pwd',
            'data' => extractIDInfo($barcode)
        ];
    }
    
    // Default to product barcode (supports alphanumeric values)
    return [
        'type' => 'product',
        'data' => ['barcode' => $barcode]
    ];
}

/**
 * Extract ID information from the scanned ID
 */
function extractIDInfo($id) {
    return [
        'id_number' => $id,
        'verified' => true
    ];
}
?>
