<?php
require_once 'session_config.php';
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header('Location: index.php');
    exit;
}

$userId = $_SESSION['user_id'];

$orders = [];
try {
    $stmt = $pdo->prepare('SELECT o.id, o.product_id, o.quantity, o.total_price, o.order_date, p.title, p.image_url, p.description FROM orders o JOIN products p ON o.product_id = p.id WHERE o.user_id = ? ORDER BY o.order_date DESC');
    $stmt->execute([$userId]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Orders fetch error: ' . $e->getMessage());
    $orders = [];
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your Orders - Marketplace</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="session_keepalive.js"></script>
    <link rel="stylesheet" href="assets/css/dash.css">
</head>
<body class="dashboard-page">

<div class="main-wrapper">
    <div class="dashboard-layout">
        <?php include __DIR__ . '/layout.php'; ?>

        <main class="main-content">
            <div style="max-width:900px;margin:0 auto;padding:0 18px;color:#fff;">
                <h2 style="margin-bottom: 24px; font-size: 2rem; display: flex; align-items: center; gap: 12px;"><i class="fas fa-box"></i> Your Orders</h2>

                <div style="margin-top:12px;">
                    <?php if (empty($orders)): ?>
                        <div style="text-align: center; padding: 40px 20px; color: rgba(255,255,255,0.6);">
                            <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 12px; display: block; opacity: 0.5;"></i>
                            <p style="font-size: 1.1rem;">No orders yet</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($orders as $o): ?>
                                <img src="<?php echo htmlspecialchars($o['image_url'] ?: 'https://via.placeholder.com/80x80?text=No+Image'); ?>" alt="Product" class="order-item-img">
                                <div class="order-item-details">
                                    <h4><?php echo htmlspecialchars($o['title']); ?></h4>
                                    <p class="order-qty">Qty: <?php echo $o['quantity']; ?></p>
                                    <p class="order-price">$<?php echo number_format((float)$o['total_price'], 2); ?></p>
                                    <p class="order-date"><?php echo date('M d, Y', strtotime($o['order_date'])); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="script.js"></script>
</body>
</html>
