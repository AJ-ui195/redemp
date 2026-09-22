<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$barcode = isset($_GET['barcode']) ? trim($_GET['barcode']) : null;

if ($method === 'GET' && $barcode) {
    try {
        // Exact match only - no fallback logic
        $query = "SELECT * FROM inventory_products WHERE barcode_value = ? LIMIT 1";
        $stmt = $connection->prepare($query);
        $stmt->bind_param('s', $barcode);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $product = $result->fetch_assoc();
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'product' => $product
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Product not found'
            ]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}
?>
