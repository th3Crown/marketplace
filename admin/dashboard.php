<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../db.php';

$users = [];
$pendingProducts = [];
$approvedProducts = [];
$error = '';
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'products';
$selectedUser = null;
$selectedProduct = null;

try {
    $query = "SELECT id, username, email, profile_image, created_at FROM users ORDER BY created_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error fetching users: " . $e->getMessage();
}

try {
    $query = "SELECT p.id, p.user_id, p.title, p.description, p.price, p.quantity, p.image_url, p.created_at, u.username, u.email
              FROM products p
              JOIN users u ON p.user_id = u.id
              WHERE p.status = 'pending'
              ORDER BY p.created_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $pendingProducts = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error fetching pending products: " . $e->getMessage();
}

try {
    $query = "SELECT p.id, p.user_id, p.title, p.description, p.price, p.quantity, p.image_url, p.created_at, u.username, u.email
              FROM products p
              JOIN users u ON p.user_id = u.id
              WHERE p.status = 'approved'
              ORDER BY p.created_at DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $approvedProducts = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error fetching approved products: " . $e->getMessage();
}

if (isset($_GET['user_id'])) {
    $userId = $_GET['user_id'];
    try {
        $query = "SELECT u.id, u.username, u.email, u.profile_image, u.created_at, 
                         COUNT(DISTINCT p.id) as total_products, 
                         COUNT(DISTINCT o.id) as total_orders,
                         COALESCE(SUM(o.total_price), 0) as total_spent
                  FROM users u
                  LEFT JOIN products p ON u.id = p.user_id
                  LEFT JOIN orders o ON u.id = o.user_id
                  WHERE u.id = :user_id
                  GROUP BY u.id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['user_id' => $userId]);
        $selectedUser = $stmt->fetch();
    } catch (PDOException $e) {
        $error = "Error fetching user details: " . $e->getMessage();
    }
}

if (isset($_GET['product_id'])) {
    $productId = $_GET['product_id'];
    try {
        $query = "SELECT p.id, p.user_id, p.title, p.description, p.price, p.quantity, p.image_url, p.created_at, p.status, u.username, u.email
                  FROM products p
                  JOIN users u ON p.user_id = u.id
                  WHERE p.id = :product_id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['product_id' => $productId]);
        $selectedProduct = $stmt->fetch();
    } catch (PDOException $e) {
        $error = "Error fetching product details: " . $e->getMessage();
    }
}

if (isset($_POST['approve_product'])) {
    $productId = $_POST['product_id'];
    try {
        $stmt = $pdo->prepare("SELECT user_id, title FROM products WHERE id = :product_id");
        $stmt->execute(['product_id' => $productId]);
        $product = $stmt->fetch();
        
        $updateStmt = $pdo->prepare("UPDATE products SET status = 'approved' WHERE id = :product_id");
        $updateStmt->execute(['product_id' => $productId]);
        
        $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, product_id, type, title, message) VALUES (:user_id, :product_id, 'approval', 'Product Approved', :message)");
        $notifStmt->execute([
            'user_id' => $product['user_id'],
            'product_id' => $productId,
            'message' => 'Your product "' . $product['title'] . '" has been approved and is now live on the marketplace!'
        ]);
        
        header('Location: dashboard.php?tab=pending');
        exit;
    } catch (PDOException $e) {
        $error = "Error approving product: " . $e->getMessage();
    }
}

if (isset($_POST['reject_product'])) {
    $productId = $_POST['product_id'];
    try {
        $stmt = $pdo->prepare("SELECT user_id, title FROM products WHERE id = :product_id");
        $stmt->execute(['product_id' => $productId]);
        $product = $stmt->fetch();
        
        $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, product_id, type, title, message) VALUES (:user_id, :product_id, 'rejection', 'Product Rejected', :message)");
        $notifStmt->execute([
            'user_id' => $product['user_id'],
            'product_id' => $productId,
            'message' => 'Your product "' . $product['title'] . '" has been rejected. Please review and resubmit.'
        ]);
        
        $deleteStmt = $pdo->prepare("DELETE FROM products WHERE id = :product_id");
        $deleteStmt->execute(['product_id' => $productId]);
        
        header('Location: dashboard.php?tab=pending');
        exit;
    } catch (PDOException $e) {
        $error = "Error rejecting product: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin-dash.css">
</head>
<body>

<div class="admin-container">
    <div class="sidebar">
        <div class="sidebar-title">
            <i class="fas fa-shield-alt"></i>
            Admin Panel
        </div>
        <a href="dashboard.php?tab=products" class="nav-item <?php echo $activeTab === 'products' ? 'active' : ''; ?>">
            <i class="fas fa-box"></i>
            Products
        </a>
        <a href="dashboard.php?tab=users" class="nav-item <?php echo $activeTab === 'users' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i>
            Users
        </a>
        <a href="../logout.php" class="nav-item">
            <i class="fas fa-sign-out-alt"></i>
            Logout
        </a>
    </div>

    <div class="main-content">
        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="header">
            <div style="display: flex; align-items: center; gap: 20px;">
                <?php if ($activeTab !== 'products' || isset($_GET['product_id'])): ?>
                    <a href="dashboard.php?tab=<?php echo isset($_GET['product_id']) && strpos($_GET['product_id'], 'approved') === false ? 'products' : 'products&filter=approved'; ?>" style="text-decoration: none;">
                        <button style="padding: 8px 16px; background: rgba(78,205,196,0.2); color: #4ecdc4; border: 1px solid #4ecdc4; border-radius: 6px; cursor: pointer; font-weight: 600;">
                            <i class="fas fa-arrow-left"></i> Back
                        </button>
                    </a>
                <?php endif; ?>
                <h1><?php 
                    if ($activeTab === 'products') {
                        if (isset($_GET['filter']) && $_GET['filter'] === 'approved') {
                            echo 'Approved Products';
                        } else {
                            echo 'Product Approval Queue';
                        }
                    } else {
                        echo 'Marketplace Users';
                    }
                ?></h1>
            </div>
            <div class="header-info">
                <span style="font-size: 0.95rem; color: rgba(255,255,255,0.7);">Admin</span>
            </div>
        </div>

        <?php if ($activeTab === 'products'): ?>

            <div class="stats-container">
                <a href="dashboard.php?tab=products" style="text-decoration: none; cursor: pointer;">
                    <div class="stat-card pending">
                        <div>
                            <div class="stat-number"><?php echo count($pendingProducts); ?></div>
                            <div class="stat-label">Pending</div>
                        </div>
                    </div>
                </a>
                <a href="dashboard.php?tab=products&filter=approved" style="text-decoration: none; cursor: pointer;">
                    <div class="stat-card approved" style="cursor: pointer;">
                        <div>
                            <div class="stat-number"><?php 
                                echo count($approvedProducts);
                            ?></div>
                            <div class="stat-label">Approved</div>
                        </div>
                    </div>
                </a>
            </div>

            <div class="products-grid">
                <?php 
                    $productsToShow = [];
                    $statusBadge = '';
                    
                    if (isset($_GET['filter']) && $_GET['filter'] === 'approved') {
                        $productsToShow = $approvedProducts;
                        $statusBadge = 'Approved';
                    } else {
                        $productsToShow = $pendingProducts;
                        $statusBadge = 'Pending';
                    }
                ?>
                <?php if (count($productsToShow) > 0): ?>
                    <?php foreach ($productsToShow as $product): ?>
                        <div class="product-card">
                            <div style="display: flex; align-items: center; justify-content: center;">
                                <?php 
                                    $imgUrl = !empty($product['image_url']) ? '../' . htmlspecialchars($product['image_url']) : 'https://via.placeholder.com/120x120?text=No+Image';
                                    echo '<img src="' . $imgUrl . '" alt="' . htmlspecialchars($product['title']) . '" class="product-image" style="width: 120px; height: 120px; object-fit: cover; border-radius: 8px;">';
                                ?>
                            </div>
                            
                            <div class="product-info">
                                <div class="status-badge" style="<?php echo isset($_GET['filter']) && $_GET['filter'] === 'approved' ? 'background: rgba(68,160,141,0.2); color: #44a08d;' : ''; ?>"><?php echo $statusBadge; ?></div>
                                <h3><?php echo htmlspecialchars($product['title']); ?></h3>
                                <div class="seller-info">
                                    <strong><?php echo htmlspecialchars($product['username']); ?></strong><br>
                                    <?php echo htmlspecialchars($product['email']); ?>
                                </div>
                                <div class="price">₱<?php echo number_format($product['price'], 2); ?></div>
                                <div class="date"><?php echo date('Y-m-d', strtotime($product['created_at'])); ?></div>
                            </div>

                            <div class="action-buttons">
                                <?php if (!isset($_GET['filter']) || $_GET['filter'] !== 'approved'): ?>
                                    <form method="POST" style="margin: 0;">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <button type="submit" name="approve_product" class="action-btn approve-btn">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                    </form>
                                    <form method="POST" style="margin: 0;">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <button type="submit" name="reject_product" class="action-btn reject-btn">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <a href="dashboard.php?tab=products&product_id=<?php echo $product['id']; echo isset($_GET['filter']) ? '&filter=' . htmlspecialchars($_GET['filter']) : ''; ?>" style="text-decoration: none;">
                                    <button type="button" class="action-btn details-btn" style="width: 100%;">
                                        <i class="fas fa-eye"></i> Details
                                    </button>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No <?php echo isset($_GET['filter']) && $_GET['filter'] === 'approved' ? 'approved' : 'pending'; ?> products</p>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($selectedProduct): 
                try {
                    $sellerProductsStmt = $pdo->prepare("SELECT id, title, price, image_url, status, created_at FROM products WHERE user_id = :user_id ORDER BY created_at DESC");
                    $sellerProductsStmt->execute(['user_id' => $selectedProduct['user_id']]);
                    $sellerProducts = $sellerProductsStmt->fetchAll();
                } catch (Exception $e) {
                    $sellerProducts = [];
                }
            ?>
                <div style="margin-top: 40px; padding: 20px; background: rgba(78,205,196,0.1); border: 1px solid rgba(78,205,196,0.2); border-radius: 10px;">
                    <h3 style="margin-bottom: 15px; color: #4ecdc4;">Product Details</h3>
                    
                    <div style="display: grid; grid-template-columns: 200px 1fr; gap: 30px; margin-bottom: 20px;">
                        <div>
                            <img src="<?php echo !empty($selectedProduct['image_url']) ? '../' . htmlspecialchars($selectedProduct['image_url']) : 'https://via.placeholder.com/200x200?text=No+Image'; ?>" alt="<?php echo htmlspecialchars($selectedProduct['title']); ?>" style="width: 100%; height: 200px; object-fit: cover; border-radius: 8px; border: 1px solid rgba(78,205,196,0.3);">
                        </div>
                        
                        <div>
                            <p><strong style="color: #4ecdc4;">ID:</strong> <?php echo $selectedProduct['id']; ?></p>
                            <p><strong style="color: #4ecdc4;">Title:</strong> <?php echo htmlspecialchars($selectedProduct['title']); ?></p>
                            <p><strong style="color: #4ecdc4;">Description:</strong> <?php echo htmlspecialchars($selectedProduct['description']); ?></p>
                            <p><strong style="color: #4ecdc4;">Price:</strong> ₱<?php echo number_format($selectedProduct['price'], 2); ?></p>
                            <p><strong style="color: #4ecdc4;">Stock Available:</strong> <span style="background: <?php echo ((int)$selectedProduct['quantity'] > 0) ? 'rgba(68,160,141,0.2)' : 'rgba(244,67,54,0.2)'; ?>; color: <?php echo ((int)$selectedProduct['quantity'] > 0) ? '#44a08d' : '#f44336'; ?>; padding: 4px 8px; border-radius: 4px; font-weight: 600;"><?php echo (int)$selectedProduct['quantity']; ?> units</span></p>
                            <p><strong style="color: #4ecdc4;">Seller:</strong> <?php echo htmlspecialchars($selectedProduct['username']); ?> (<?php echo htmlspecialchars($selectedProduct['email']); ?>)</p>
                            <p><strong style="color: #4ecdc4;">Status:</strong> <span style="background: rgba(255,165,0,0.2); color: #ffa500; padding: 4px 8px; border-radius: 4px; font-size: 0.85rem;"><?php echo htmlspecialchars($selectedProduct['status']); ?></span></p>
                            <p><strong style="color: #4ecdc4;">Submitted:</strong> <?php echo $selectedProduct['created_at']; ?></p>
                        </div>
                    </div>
                    
                    <a href="dashboard.php?tab=products<?php echo isset($_GET['filter']) ? '&filter=' . htmlspecialchars($_GET['filter']) : ''; ?>"><button style="margin-top: 15px; padding: 8px 16px; background: #4ecdc4; color: #052; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Back to Queue</button></a>
                </div>

                <?php if (count($sellerProducts) > 1): ?>
                    <div style="margin-top: 30px; padding: 20px; background: rgba(78,205,196,0.08); border: 1px solid rgba(78,205,196,0.15); border-radius: 10px;">
                        <h3 style="margin-bottom: 20px; color: #4ecdc4;">Other Products by <?php echo htmlspecialchars($selectedProduct['username']); ?></h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px;">
                            <?php foreach ($sellerProducts as $otherProduct): ?>
                                <?php if ($otherProduct['id'] !== $selectedProduct['id']): ?>
                                    <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(78,205,196,0.2); border-radius: 8px; overflow: hidden; transition: all 0.3s;">
                                        <img src="<?php echo !empty($otherProduct['image_url']) ? '../' . htmlspecialchars($otherProduct['image_url']) : 'https://via.placeholder.com/150x150?text=No+Image'; ?>" alt="<?php echo htmlspecialchars($otherProduct['title']); ?>" style="width: 100%; height: 120px; object-fit: cover;">
                                        <div style="padding: 10px;">
                                            <h4 style="margin: 0 0 5px 0; color: #fff; font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($otherProduct['title']); ?></h4>
                                            <p style="margin: 0 0 8px 0; color: #4ecdc4; font-weight: 600; font-size: 0.95rem;">₱<?php echo number_format($otherProduct['price'], 2); ?></p>
                                            <span style="background: <?php echo $otherProduct['status'] === 'approved' ? 'rgba(68,160,141,0.2)' : 'rgba(255,165,0,0.2)'; ?>; color: <?php echo $otherProduct['status'] === 'approved' ? '#44a08d' : '#ffa500'; ?>; padding: 3px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600;"><?php echo htmlspecialchars($otherProduct['status']); ?></span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

        <?php elseif ($activeTab === 'users'): ?>

            <?php if ($selectedUser): ?>
                <div style="padding: 20px; background: rgba(78,205,196,0.1); border: 1px solid rgba(78,205,196,0.2); border-radius: 10px; margin-bottom: 30px;">
                    <h3 style="margin-bottom: 15px; color: #4ecdc4;">User Details</h3>
                    <p><strong>Username:</strong> <?php echo htmlspecialchars($selectedUser['username']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($selectedUser['email']); ?></p>
                    <p><strong>Joined:</strong> <?php echo $selectedUser['created_at']; ?></p>
                    <p><strong>Total Products:</strong> <?php echo $selectedUser['total_products']; ?></p>
                    <p><strong>Total Orders:</strong> <?php echo $selectedUser['total_orders']; ?></p>
                    <p><strong>Total Spent:</strong> ₱<?php echo number_format($selectedUser['total_spent'], 2); ?></p>
                    <?php if ($selectedUser['profile_image']): ?>
                        <p><strong>Profile Image:</strong> <?php echo htmlspecialchars($selectedUser['profile_image']); ?></p>
                    <?php endif; ?>
                    <a href="dashboard.php?tab=users"><button style="margin-top: 15px; padding: 8px 16px; background: #4ecdc4; color: #052; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Back to Users</button></a>
                </div>
            <?php endif; ?>

            <h3 style="margin-bottom: 20px; color: rgba(255,255,255,0.9);">All Registered Users</h3>

            <?php if (count($users) > 0): ?>
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Joined Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                                <td style="display: flex; gap: 8px;">
                                    <a href="dashboard.php?tab=users&user_id=<?php echo $user['id']; ?>" style="text-decoration: none;">
                                        <button style="padding: 6px 12px; background: #4ecdc4; color: #052; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 0.85rem;">View Info</button>
                                    </a>
                                    <form method="POST" action="delete_user.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" style="padding: 6px 12px; background: #ff6b6b; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 0.85rem;">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <p>No users found in the marketplace</p>
                </div>
            <?php endif; ?>

        <?php endif; ?>

    </div>
</div>

</body>
</html>
