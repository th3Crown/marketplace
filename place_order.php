<?php
session_start();
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$userId = $_SESSION['user_id'];
$productId = intval($_POST['product_id'] ?? 0);
$quantity = intval($_POST['quantity'] ?? 1);

if ($productId <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product or quantity']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT id, title, price, quantity as available_quantity FROM products WHERE id = ?');
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }
    
    if ($quantity > $product['available_quantity']) {
        echo json_encode(['success' => false, 'message' => 'Not enough stock available. Only ' . $product['available_quantity'] . ' item(s) in stock.']);
        exit;
    }
    
    $totalPrice = (float)$product['price'] * $quantity;
    
    $stmt = $pdo->prepare('INSERT INTO orders (user_id, product_id, quantity, total_price) VALUES (?, ?, ?, ?)');
    $stmt->execute([$userId, $productId, $quantity, $totalPrice]);
    
    $stmt = $pdo->prepare('UPDATE products SET quantity = quantity - ? WHERE id = ?');
    $stmt->execute([$quantity, $productId]);
    
    $orderId = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Order placed successfully!',
        'order_id' => $orderId,
        'product_title' => $product['title'],
        'quantity' => $quantity,
        'total_price' => $totalPrice
    ]);
    
} catch (Exception $e) {
    error_log('Order creation error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
