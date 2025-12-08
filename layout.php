<?php
?>
<?php 
$current = basename($_SERVER['PHP_SELF']);
$userInitials = 'U';
$profileImage = null;

if (isset($_SESSION['username']) && !empty($_SESSION['username'])) {
    $parts = preg_split('/\s+/', $_SESSION['username']);
    foreach ($parts as $p) { if (!empty($p)) $userInitials .= strtoupper($p[0]); }
    $userInitials = substr($userInitials, 0, 2);
}

if (isset($_SESSION['user_id'])) {
    try {
        require_once __DIR__ . '/db.php';
        $stmt = $pdo->prepare('SELECT profile_image FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result && !empty($result['profile_image'])) {
            $profileImage = htmlspecialchars($result['profile_image']);
        }
    } catch (Exception $e) {
    }
}
?>
<div class="floating-item"></div>
<div class="floating-item"></div>
<div class="floating-item"></div>
<div class="floating-item"></div>
<button id="sidebarToggle" style="display:none;position:fixed;top:16px;left:16px;z-index:1001;background:rgba(78,205,196,0.2);border:1px solid #4ecdc4;color:#4ecdc4;width:40px;height:40px;border-radius:8px;font-size:1.2rem;cursor:pointer;transition:all 0.3s;">
    <i class="fas fa-bars"></i>
</button>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-profile" style="text-align:center;padding:16px;border-bottom:1px solid rgba(255,255,255,0.1);margin-bottom:12px;">
        <?php if ($profileImage): ?>
        <img src="<?php echo $profileImage; ?>" alt="Profile Image" style="width:56px;height:56px;border-radius:8px;object-fit:cover;margin:0 auto 8px;display:block;box-shadow:0 4px 12px rgba(0,0,0,0.3);">
        <?php else: ?>
        <div style="width:56px;height:56px;border-radius:8px;background:linear-gradient(135deg,#4ecdc4,#44a08d);display:flex;align-items:center;justify-content:center;font-weight:700;color:#052;margin:0 auto 8px;font-size:1.2rem;">
            <?php echo $userInitials; ?>
        </div>
        <?php endif; ?>
        <p style="margin:0 0 4px 0;font-weight:600;color:#fff;"><?php echo isset($_SESSION['username'])?htmlspecialchars($_SESSION['username']):'User'; ?></p>
        <a href="profile.php" style="color:#4ecdc4;text-decoration:none;font-size:0.9rem;">View Profile</a>
    </div>
    
    <div class="sidebar-brand">Marketplace</div>
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-link <?php echo $current === 'dashboard.php' ? 'active' : '';?>"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a>
        <a href="#" class="nav-link" onclick="alert('Messages coming soon!');return false;"><i class="fas fa-envelope"></i> <span>Messages</span></a>
        <a href="add_listing.php" class="nav-link <?php echo $current === 'add_listing.php' ? 'active' : '';?>"><i class="fas fa-plus"></i> <span>Add Listing</span></a>
        <a href="notifications.php" class="nav-link <?php echo $current === 'notifications.php' ? 'active' : '';?>"><i class="fas fa-bell"></i> <span>Notifications</span></a>
        <a href="orders.php" class="nav-link <?php echo $current === 'orders.php' ? 'active' : '';?>"><i class="fas fa-box-open"></i> <span>Orders</span></a>
        <a href="#" class="nav-link logout-link" onclick="fetch('logout.php',{method:'POST'}).then(()=>location.href='index.php')"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
    </nav>
    <div class="sidebar-footer">Marketplace v1.0</div>
</aside>
<script>
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    
    function handleResize() {
        const isSmallScreen = window.innerWidth < 768;
        sidebarToggle.style.display = isSmallScreen ? 'block' : 'none';
        if (!isSmallScreen) {
            sidebar.classList.remove('sidebar-hidden');
        } else {
            sidebar.classList.add('sidebar-hidden');
        }
    }
    
    window.addEventListener('resize', handleResize);
    handleResize();
    
    sidebarToggle.addEventListener('click', () => {
        sidebar.classList.toggle('sidebar-hidden');
    });
    
    document.addEventListener('click', (e) => {
        if (window.innerWidth < 768 && !sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
            sidebar.classList.add('sidebar-hidden');
        }
    });
    
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth < 768) {
                sidebar.classList.add('sidebar-hidden');
            }
        });
    });
</script>
