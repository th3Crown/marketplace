<?php  
session_start();  
require_once __DIR__ . '/db.php';  

if (!isset($_SESSION['user_id'])) {  
    header('Location: index.php');  
    exit;  
}  

$message = '';  

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 0);
    $userId = $_SESSION['user_id'];

    $image_url = '';

    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $allowed = ['jpg','jpeg','png','gif','webp'];
        $fileName = $_FILES['image']['name'];
        $fileTmp = $_FILES['image']['tmp_name'];

        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $message = 'Invalid image file type. Allowed: jpg, jpeg, png, gif, webp';
        } else {
            $newName = uniqid("prod_", true) . "." . $ext;
            $uploadPath = __DIR__ . '/images/products/' . $newName;

            if (move_uploaded_file($fileTmp, $uploadPath)) {
                $image_url = 'images/products/' . $newName;
            } else {
                $message = 'Image upload failed. Please check file permissions.';
            }
        }
    } else {
        $message = 'Image is required. Please upload an image.';
    }

    if ($message === '' && ($title === '' || $price <= 0)) {
        $message = 'Please provide a title and a valid price.';
    } elseif ($message === '' && $quantity <= 0) {
        $message = 'Please provide a valid quantity.';
    } elseif ($message === '' && $image_url === '') {
        $message = 'Image upload is required.';
    } else {
        if ($message === '') {
            try {
                $stmt = $pdo->prepare(
                    'INSERT INTO products (user_id, title, description, price, quantity, image_url, status)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                $result = $stmt->execute([$userId, $title, $description, $price, $quantity, $image_url, 'pending']);
                
                if ($result) {
                    $productId = $pdo->lastInsertId();
                    
                    $notifStmt = $pdo->prepare(
                        'INSERT INTO notifications (user_id, product_id, type, title, message)
                         VALUES (?, ?, ?, ?, ?)'
                    );
                    $notifStmt->execute([
                        $userId,
                        $productId,
                        'pending',
                        'Product Under Review',
                        'Your listing "' . $title . '" is under review. Admin approval may take up to 24 hours.'
                    ]);
                    
                    $message = 'success';
                } else {
                    $message = 'Could not add listing. Please try again.';
                }

            } catch (Exception $e) {
                error_log('Add listing error: ' . $e->getMessage());
                $message = 'Could not add listing. Check server logs.';
            }
        }
    }  
}  
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Add Listing - Marketplace</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = { theme: { extend: { colors: { primary: '#4ecdc4', accent: '#44a08d' } } } };
    </script>
    <link rel="stylesheet" href="assets/css/dash.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css" />
</head>
<body class="dashboard-page">

<div class="main-wrapper">
    <div class="dashboard-layout">
        <?php include __DIR__ . '/layout.php'; ?>

        <main class="main-content">
            <div class="add-listing-container" style="max-width:700px;margin:0 auto;padding:0 18px;">
                <h2>Add Product</h2>
                <?php if ($message === 'success'): ?>
                    <div id="successMsg" style="margin:12px 0;padding:14px;background:rgba(76, 205, 196, 0.15);border:1px solid #4ecdc4;border-radius:6px;color:#4ecdc4;font-weight:600;">
                        ✓ Product added successfully! Redirecting to dashboard...
                    </div>
                <?php elseif ($message): ?>
                    <div style="margin:12px 0;padding:10px;background:rgba(255,255,255,0.04);border-radius:6px;color:#ff6b6b;">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" id="addListingForm">
    <div style="margin-bottom:8px;">
        <label>Name: </label>
        <input type="text" name="title" class="field-input" required>
    </div>

    <div style="margin-bottom:8px;">
        <label>Description: </label>
        <textarea name="description" class="field-input" rows="4"></textarea>
    </div>

    <div style="margin-bottom:8px;">
        <label>Price: </label>
        <input type="number" name="price" step="0.01" class="field-input" required>
    </div>

    <div style="margin-bottom:8px;">
        <label>Quantity Available: </label>
        <div style="display:flex;align-items:center;gap:8px;">
            <button type="button" class="qty-btn" onclick="decreaseQty()">−</button>
            <input type="number" id="quantityInput" name="quantity" value="1" min="1" class="field-input" style="flex:1;text-align:center;" required>
            <button type="button" class="qty-btn" onclick="increaseQty()">+</button>
        </div>
    </div>

    <div style="margin-bottom:8px;">
        <label>Upload Image: </label>
        <input type="file" name="image" accept="image/*" required class="field-input">
    </div>

    <div style="margin-top:10px;">
        <button type="submit" class="action-button">Add Listing</button>
    </div>
</form>
            </div>
        </main>
    </div>
</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="script.js"></script>
<script>
    if (typeof AOS !== 'undefined') AOS.init();
    
    function increaseQty() {
        const input = document.getElementById('quantityInput');
        input.value = parseInt(input.value) + 1;
    }
    
    function decreaseQty() {
        const input = document.getElementById('quantityInput');
        if (parseInt(input.value) > 1) {
            input.value = parseInt(input.value) - 1;
        }
    }
    
    if (document.getElementById('successMsg')) {
        setTimeout(() => {
            window.location.href = 'dashboard.php';
        }, 2000);
    }
</script>

</body>
</html>
