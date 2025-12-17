<?php  
require_once 'session_config.php';
  
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {  
    header('Location: index.php');  
    exit;  
}  
  
$username = $_SESSION['username'];  
$userId = $_SESSION['user_id'];  
  
require_once 'db.php';

$stmt = $pdo->prepare("SELECT * FROM products WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$userProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM products WHERE status = 'approved' ORDER BY created_at DESC LIMIT 100");
$stmt->execute();
$popularProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>  <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Marketplace</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="session_keepalive.js"></script>
    <style>
        @keyframes fadeInPage {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        body.dashboard-page {
            animation: fadeInPage 0.5s ease-in-out;
        }
    </style>
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
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css">
    <link rel="stylesheet" href="assets/css/dash.css">
    <style>
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
    </style>
</head>
<body class="dashboard-page">
<div class="theme-toggle">
    <button type="button" class="theme-btn" id="themeToggleBtn" onclick="toggleTheme()">
        <i class="fas fa-moon"></i>
    </button>
</div>

<div class="main-wrapper" id="mainContainer">
    <div class="dashboard-layout">
        <?php include __DIR__ . '/layout.php'; ?>

        <main class="main-content">
            <div style="max-width:1200px;margin:0 auto;padding:20px;">
                <div style="text-align:center;margin-bottom:30px;">
                    <h2 style="color:#fff;font-size:1.8rem;margin-bottom:20px;">What are you looking for?</h2>
                    <div style="display:flex;gap:10px;margin-bottom:20px;">
                        <input type="text" id="searchInput" placeholder="Search products..." style="flex:1;padding:12px 16px;border-radius:8px;border:1px solid rgba(78,205,196,0.3);background:rgba(255,255,255,0.05);color:#fff;font-size:1rem;">
                        <button onclick="searchProducts()" style="padding:12px 24px;background:#4ecdc4;color:#052;border:none;border-radius:8px;font-weight:600;cursor:pointer;">Search</button>
                    </div>
                    <div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;">
                        <button onclick="filterByPrice('all')" class="filter-btn" style="padding:8px 16px;background:rgba(78,205,196,0.2);color:#4ecdc4;border:1px solid #4ecdc4;border-radius:6px;cursor:pointer;">All</button>
                        <button onclick="filterByPrice('low')" class="filter-btn" style="padding:8px 16px;background:rgba(255,255,255,0.05);color:#fff;border:1px solid rgba(78,205,196,0.3);border-radius:6px;cursor:pointer;">Low Price</button>
                        <button onclick="filterByPrice('high')" class="filter-btn" style="padding:8px 16px;background:rgba(255,255,255,0.05);color:#fff;border:1px solid rgba(78,205,196,0.3);border-radius:6px;cursor:pointer;">High Price</button>
                        <button onclick="filterByPrice('newest')" class="filter-btn" style="padding:8px 16px;background:rgba(255,255,255,0.05);color:#fff;border:1px solid rgba(78,205,196,0.3);border-radius:6px;cursor:pointer;">Newest</button>
                    </div>
                </div>

                <section style="margin-top:40px;">
                    <h3 style="color:#fff;font-size:1.4rem;margin-bottom:20px;">Popular This Week</h3>
                    <p style="color:rgba(255,255,255,0.6);font-size:0.9rem;margin-bottom:15px;">Most ordered products by buyers</p>
                    <div id="productsContainer" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px;max-height:calc(100vh - 300px);overflow-y:auto;padding-right:10px;">
                        <?php if (!empty($popularProducts)): ?>
                            <?php foreach ($popularProducts as $product): ?>
                            <div class="product-card" style="background:rgba(255,255,255,0.03);border:1px solid rgba(78,205,196,0.2);border-radius:10px;overflow:hidden;transition:all 0.3s;position:relative;cursor:pointer;" onclick="<?php echo ((int)$product['user_id'] !== (int)$userId) ? "showProductDetails(" . json_encode([
                                'id' => (int)$product['id'],
                                'title' => htmlspecialchars($product['title']),
                                'price' => htmlspecialchars($product['price']),
                                'quantity' => (int)$product['quantity'],
                                'image_url' => !empty($product['image_url']) ? htmlspecialchars($product['image_url']) : 'https://via.placeholder.com/400x300?text=No+Image',
                                'description' => htmlspecialchars($product['description'] ?? 'No description available')
                            ]) . ")" : ""; ?>" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                                <?php $imgUrl = !empty($product['image_url']) ? htmlspecialchars($product['image_url']) : 'https://via.placeholder.com/400x300?text=No+Image'; ?>
                                <img src="<?php echo $imgUrl; ?>" alt="<?php echo htmlspecialchars($product['title']); ?>" style="width:100%;height:160px;object-fit:cover;">
                                <?php if ((int)$product['quantity'] > 0): ?>
                                <div style="position:absolute;top:8px;right:8px;background:#4ecdc4;color:#052;padding:4px 8px;border-radius:4px;font-size:0.8rem;font-weight:700;">📦 <?php echo (int)$product['quantity']; ?> In Stock</div>
                                <?php else: ?>
                                <div style="position:absolute;top:8px;right:8px;background:#f44336;color:#fff;padding:4px 8px;border-radius:4px;font-size:0.8rem;font-weight:700;">Out of Stock</div>
                                <?php endif; ?>
                                <div style="padding:12px;">
                                    <h4 style="margin:0 0 8px 0;color:#fff;font-weight:600;"><?php echo htmlspecialchars($product['title']); ?></h4>
                                    <p style="margin:0 0 12px 0;color:#4ecdc4;font-weight:700;font-size:1.1rem;">₱<?php echo htmlspecialchars($product['price']); ?></p>
                                    <?php if ((int)$product['user_id'] === (int)$userId): ?>
                                    <button onclick="event.stopPropagation();deleteProduct(<?php echo (int)$product['id']; ?>, '<?php echo htmlspecialchars($product['title']); ?>')" style="width:100%;padding:8px;background:#f44336;color:#fff;border:none;border-radius:6px;cursor:pointer;font-weight:600;transition:all 0.3s;" onmouseover="this.style.background='#d32f2f'" onmouseout="this.style.background='#f44336'">Remove</button>
                                    <p style="margin:6px 0 0 0;font-size:0.75rem;color:rgba(255,255,255,0.5);text-align:center;">(Your Product)</p>
                                    <?php else: ?>
                                    <button onclick="event.stopPropagation();buyProduct(<?php echo (int)$product['id']; ?>, '<?php echo htmlspecialchars($product['title']); ?>', <?php echo htmlspecialchars($product['price']); ?>, <?php echo (int)$product['quantity']; ?>)" style="width:100%;padding:8px;background:<?php echo ((int)$product['quantity'] > 0) ? '#4ecdc4' : '#ccc'; ?>;color:<?php echo ((int)$product['quantity'] > 0) ? '#052' : '#999'; ?>;border:none;border-radius:6px;cursor:<?php echo ((int)$product['quantity'] > 0) ? 'pointer' : 'not-allowed'; ?>;font-weight:600;transition:all 0.3s;" onmouseover="<?php echo ((int)$product['quantity'] > 0) ? "this.style.background='#3db8af'" : ""; ?>" onmouseout="<?php echo ((int)$product['quantity'] > 0) ? "this.style.background='#4ecdc4'" : ""; ?>" <?php echo ((int)$product['quantity'] <= 0) ? 'disabled' : ''; ?>>Buy Now</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color:rgba(255,255,255,0.6);grid-column:1/-1;text-align:center;padding:40px;">No products found. <a href="add_listing.php" style="color:#4ecdc4;text-decoration:underline;">Add your first listing!</a></p>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </main>
    </div>
</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

<script>
AOS.init();

function logout() {
    fetch('logout.php', {
        method: 'POST'
    })
    .then(() => {
        window.location.href = 'index.php';
    })
    .catch(error => {
        window.location.href = 'index.php';
    });
}

function toggleTheme() {
    const root = document.documentElement;
    const themeBtn = document.getElementById('themeToggleBtn');
    const icon = themeBtn ? themeBtn.querySelector('i') : null;

    if (root.hasAttribute('data-theme')) {
        root.removeAttribute('data-theme');
        localStorage.setItem('selectedTheme', 'light');
        icon.classList.remove('fa-sun');
        icon.classList.add('fa-moon');
    } else {
        root.setAttribute('data-theme', 'dark');
        localStorage.setItem('selectedTheme', 'dark');
        icon.classList.remove('fa-moon');
        icon.classList.add('fa-sun');
    }
}

window.addEventListener('DOMContentLoaded', function() {
    const savedTheme = localStorage.getItem('selectedTheme');
    const themeBtn = document.getElementById('themeToggleBtn');
    const icon = themeBtn ? themeBtn.querySelector('i') : null;

    if (savedTheme === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
        icon.classList.remove('fa-moon');
        icon.classList.add('fa-sun');
    }
});

function showProductDetails(product) {
    let modal = document.getElementById('productDetailModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'productDetailModal';
        modal.style.cssText = 'display: none; position: fixed; z-index: 1100; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.7); overflow-y: auto;';
        modal.onclick = function(e) { if (e.target === modal) closeProductModal(); };
        document.body.appendChild(modal);
    }

    const isOutOfStock = product.quantity <= 0;
    const stockDisplay = isOutOfStock ? '<p style="color:#f44336;font-weight:600;margin:8px 0;">Out of Stock</p>' : `<p style="color:#4ecdc4;font-weight:600;margin:8px 0;">📦 ${product.quantity} items available</p>`;
    
    modal.innerHTML = `
        <div style="background: rgba(45,55,72,0.98); margin: 4% auto; padding: 20px; border-radius: 10px; width: 92%; max-width: 700px; color: #fff;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                <h2 style="margin:0;font-size:1.25rem;">${product.title}</h2>
                <button onclick="closeProductModal()" style="background:none;border:none;color:#fff;font-size:1.6rem;cursor:pointer;">&times;</button>
            </div>
            <div style="display:flex;gap:16px;flex-wrap:wrap;">
                <img src="${product.image_url}" alt="${product.title}" style="width:260px;height:180px;object-fit:cover;border-radius:8px;flex-shrink:0;">
                <div style="flex:1;min-width:200px;">
                    <p style="margin:0 0 8px 0;color:#cbd5e0;">${product.description}</p>
                    <p style="color:#4ecdc4;font-weight:700;font-size:1.2rem;margin-top:8px;">₱${parseFloat(product.price).toFixed(2)}</p>
                    ${stockDisplay}
                    <div style="margin-top:14px;">
                        <label style="display:block;color:#cbd5e0;font-size:0.9rem;margin-bottom:6px;">Quantity:</label>
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
                            <button type="button" onclick="decreaseQtyModal()" style="background:linear-gradient(45deg,#4ecdc4,#44a08d);color:#fff;width:40px;height:40px;border:none;border-radius:6px;font-size:18px;cursor:pointer;font-weight:bold;">−</button>
                            <input type="number" id="modalQuantity" value="1" min="1" max="${product.quantity}" style="flex:1;padding:8px;background:rgba(255,255,255,0.1);color:#fff;border:1px solid rgba(78,205,196,0.3);border-radius:6px;text-align:center;" />
                            <button type="button" onclick="increaseQtyModal(${product.quantity})" style="background:linear-gradient(45deg,#4ecdc4,#44a08d);color:#fff;width:40px;height:40px;border:none;border-radius:6px;font-size:18px;cursor:pointer;font-weight:bold;">+</button>
                        </div>
                    </div>
                    <div style="margin-top:14px;display:flex;gap:8px;">
                        <button onclick="buyProductFromModal(${product.id}, '${product.title}', ${product.price})" style="flex:1;padding:10px;background:${isOutOfStock ? '#ccc' : '#4ecdc4'};color:${isOutOfStock ? '#999' : '#052'};border:none;border-radius:6px;font-weight:600;cursor:${isOutOfStock ? 'not-allowed' : 'pointer'};transition:all 0.3s;" ${isOutOfStock ? 'disabled' : ''}>Buy Now</button>
                        <button onclick="closeProductModal()" style="flex:1;padding:10px;background:rgba(255,255,255,0.1);color:#fff;border:none;border-radius:6px;font-weight:600;cursor:pointer;">Close</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    modal.style.display = 'block';
}

function increaseQtyModal(maxQty) {
    const input = document.getElementById('modalQuantity');
    if (parseInt(input.value) < maxQty) {
        input.value = parseInt(input.value) + 1;
    }
}

function decreaseQtyModal() {
    const input = document.getElementById('modalQuantity');
    if (parseInt(input.value) > 1) {
        input.value = parseInt(input.value) - 1;
    }
}

function closeProductModal() {
    const modal = document.getElementById('productDetailModal');
    if (modal) modal.style.display = 'none';
}

function buyProductFromModal(productId, productTitle, productPrice) {
    const quantityInput = document.getElementById('modalQuantity');
    const quantity = parseInt(quantityInput.value) || 1;
    
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
            closeProductModal();
            showNotification('✓ Order placed! Product: ' + data.product_title + ' (Qty: ' + quantity + ') | Total: ₱' + parseFloat(data.total_price).toFixed(2), 'success');
            setTimeout(() => location.reload(), 2000);
        } else {
            showNotification('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('Error placing order. Please try again.', 'error');
    });
}

function buyProduct(productId, productTitle, productPrice, availableQty) {
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('quantity', 1);

    fetch('place_order.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('✓ Order placed! Product: ' + data.product_title + ' | Total: ₱' + parseFloat(data.total_price).toFixed(2), 'success');
            setTimeout(() => location.reload(), 2000);
        } else {
            showNotification('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('Error placing order. Please try again.', 'error');
    });
}

function deleteProduct(productId) {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'delete_product.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onload = function() {
        try {
            const data = JSON.parse(xhr.responseText);
            if (data.success) {
                showNotification('✓ Product removed successfully', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showNotification('Error: ' + (data.message || 'Could not remove product'), 'error');
            }
        } catch (e) {
            showNotification('Server error. Please try again.', 'error');
        }
    };
    
    xhr.onerror = function() {
        showNotification('Network error. Please try again.', 'error');
    };
    
    xhr.send('product_id=' + productId);
}

function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 16px 24px;
        border-radius: 8px;
        font-weight: 600;
        z-index: 2000;
        animation: slideIn 0.4s ease-out;
        backdrop-filter: blur(8px);
    `;
    
    if (type === 'success') {
        notification.style.background = 'rgba(76, 205, 196, 0.95)';
        notification.style.color = '#052';
        notification.style.border = '1px solid #4ecdc4';
    } else {
        notification.style.background = 'rgba(244, 67, 54, 0.95)';
        notification.style.color = '#fff';
        notification.style.border = '1px solid #f44336';
    }
    
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.4s ease-out';
        setTimeout(() => notification.remove(), 400);
    }, 3000);
}

function searchProducts() {
    const searchInput = document.getElementById('searchInput').value.toLowerCase().trim();
    const productCards = document.querySelectorAll('.product-card');
    let visibleCount = 0;
    
    productCards.forEach(card => {
        const title = card.querySelector('h4').textContent.toLowerCase();
        const price = card.querySelector('p').textContent.toLowerCase();
        
        if (searchInput === '' || title.includes(searchInput) || price.includes(searchInput)) {
            card.style.display = 'block';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });
    
    const container = document.getElementById('productsContainer');
    let noResultsMsg = container.querySelector('.no-results-msg');
    if (visibleCount === 0 && searchInput !== '') {
        if (!noResultsMsg) {
            noResultsMsg = document.createElement('div');
            noResultsMsg.className = 'no-results-msg';
            noResultsMsg.style.cssText = 'grid-column:1/-1;text-align:center;padding:40px;color:rgba(255,255,255,0.6);';
            noResultsMsg.textContent = 'No products found matching "' + searchInput + '"';
            container.appendChild(noResultsMsg);
        }
    } else if (noResultsMsg) {
        noResultsMsg.remove();
    }
}

function filterByPrice(filter) {
    const productCards = Array.from(document.querySelectorAll('.product-card'));
    let filtered = productCards;
    
    if (filter === 'low') {
        filtered = productCards.sort((a, b) => {
            const priceA = parseFloat(a.querySelector('h4').nextElementSibling.textContent.replace('₱', ''));
            const priceB = parseFloat(b.querySelector('h4').nextElementSibling.textContent.replace('₱', ''));
            return priceA - priceB;
        });
    } else if (filter === 'high') {
        filtered = productCards.sort((a, b) => {
            const priceA = parseFloat(a.querySelector('h4').nextElementSibling.textContent.replace('₱', ''));
            const priceB = parseFloat(b.querySelector('h4').nextElementSibling.textContent.replace('₱', ''));
            return priceB - priceA;
        });
    }
    
    const container = document.getElementById('productsContainer');
    container.innerHTML = '';
    filtered.forEach(card => container.appendChild(card.cloneNode(true)));
}

document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        searchProducts();
    }
});

</script>

</body>
</html>
