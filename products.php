<?php
require_once 'session_config.php';
require_once __DIR__ . '/db.php';

$products = [];
try {
    $stmt = $pdo->query('SELECT id, title, description, price, image_url FROM products ORDER BY created_at DESC');
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Products fetch error: ' . $e->getMessage());
    $products = [];
}

if (count($products) === 0) {
    $products = [
        ['id' => 0, 'title' => 'Classic Runner', 'description' => 'Comfortable running shoe', 'price' => '79.00', 'image_url' => 'https://source.unsplash.com/collection/190727/400x300?shoe'],
        ['id' => 1, 'title' => 'Urban Sneaker', 'description' => 'Stylish everyday sneaker', 'price' => '99.00', 'image_url' => 'https://source.unsplash.com/collection/190727/400x300?trainers'],
        ['id' => 2, 'title' => 'Street Pro', 'description' => 'Durable street shoe', 'price' => '129.00', 'image_url' => 'https://source.unsplash.com/collection/190727/400x300?kicks'],
        ['id' => 3, 'title' => 'Light Runner', 'description' => 'Lightweight running shoe', 'price' => '69.00', 'image_url' => 'https://source.unsplash.com/collection/190727/400x300?sneakers'],
    ];
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Browse Products - Marketplace</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <script src="https://cdn.tailwindcss.com"></script>
        <script src="session_keepalive.js"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        colors: {
                            primary: '#4ecdc4',
                            accent: '#44a08d'
                        }
                    }
                }
            }
        </script>
    <link rel="stylesheet" href="assets/css/dash.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css" />
    <style>
        .main-content { padding-top: 20px; }
        .product-actions {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }
        .product-actions button {
            flex: 1;
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }
        .btn-view {
            background: rgba(255,255,255,0.15);
            color: #fff;
        }
        .btn-view:hover {
            background: rgba(255,255,255,0.25);
        }
        .btn-buy {
            background: linear-gradient(45deg, #4ecdc4, #44a08d);
            color: #fff;
        }
        .btn-buy:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(78, 205, 196, 0.4);
        }
        .btn-buy:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
    </style>
</head>
<body class="dashboard-page">

<div class="main-wrapper" id="mainContainer">
    <div class="animated-circle"></div>
    <div class="dashboard-layout">
        <?php include __DIR__ . '/layout.php'; ?>

        <main class="main-content">
            <div style="max-width:1100px;margin:0 auto;padding:0 18px;">
                <h2 style="color:#fff;margin-bottom:12px;">All Products</h2>
                <div class="product-grid">
            <?php foreach ($products as $p): ?>
                <div class="product-card">
                    <?php $img = !empty($p['image_url']) ? $p['image_url'] : 'https://source.unsplash.com/collection/190727/400x300?shoe'; ?>
                    <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($p['title']); ?>">
                    <div class="product-info">
                        <h4><?php echo htmlspecialchars($p['title']); ?></h4>
                        <p class="price">₱<?php echo number_format((float)$p['price'], 2); ?></p>
                        <p style="color: rgba(255,255,255,0.8); font-size:0.95rem;"><?php echo htmlspecialchars($p['description']); ?></p>
                        <div class="product-actions">
                            <button class="btn-view" onclick="viewProductDetails(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars($p['title']); ?>', <?php echo $p['price']; ?>, '<?php echo htmlspecialchars($img); ?>')">View Details</button>
                            <button class="btn-buy" onclick="buyProduct(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars($p['title']); ?>')">Buy Now</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="script.js"></script>
<script>
    if (typeof AOS !== 'undefined') AOS.init();
    
    function viewProductDetails(productId, title, price, imageUrl) {
        let modal = document.getElementById('productDetailModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'productDetailModal';
            modal.style.cssText = 'display: none; position: fixed; z-index: 999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.7); animation: fadeIn 0.3s ease;';
            modal.onclick = function(e) {
                if (e.target === modal) modal.style.display = 'none';
            };
            document.body.appendChild(modal);
        }
        
        modal.innerHTML = `
            <div style="background: rgba(45, 55, 72, 0.95); margin: 5% auto; padding: 24px; border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 12px; width: 90%; max-width: 600px; color: #fff; animation: slideDown 0.3s ease;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); padding-bottom: 12px;">
                    <h2 style="margin: 0; font-size: 1.5rem;">Product Details</h2>
                    <button onclick="document.getElementById('productDetailModal').style.display = 'none'" style="background: none; border: none; color: #fff; font-size: 2rem; cursor: pointer;">&times;</button>
                </div>
                <img src="${imageUrl}" alt="${title}" style="width: 100%; height: 300px; object-fit: cover; border-radius: 8px; margin-bottom: 20px;">
                <h3 style="margin: 0 0 12px 0; font-size: 1.3rem;">${title}</h3>
                <p style="color: #4ecdc4; font-size: 1.3rem; font-weight: 600; margin-bottom: 20px;">₱${parseFloat(price).toFixed(2)}</p>
                <button class="btn-buy" onclick="buyProduct(${productId}, '${title}')" style="width: 100%; padding: 12px; font-size: 1rem;">
                    <i class="fas fa-shopping-cart"></i> Buy Now
                </button>
            </div>
        `;
        modal.style.display = 'block';
    }
    
    function buyProduct(productId, productTitle) {
        const quantity = 1;
        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('quantity', quantity);
        
        fetch('place_order.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✓ Order placed successfully!\n\nProduct: ' + data.product_title + '\nQuantity: ' + data.quantity + '\nTotal: ₱' + parseFloat(data.total_price).toFixed(2));
                document.getElementById('productDetailModal').style.display = 'none';
                setTimeout(() => location.reload(), 1000);
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error placing order. Please try again.');
        });
    }
</script>

</body>
</html>


