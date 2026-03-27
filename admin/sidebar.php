<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <div class="sidebar-header"><i class="fas fa-store"></i> POS Admin</div>
    <ul class="sidebar-menu">
        <li><a href="index.php" class="<?php echo $currentPage == 'index.php' ? 'active' : ''; ?>"><i class="fas fa-chart-pie"></i> แดชบอร์ด (Dashboard)</a></li>
        <li><a href="products.php" class="<?php echo $currentPage == 'products.php' ? 'active' : ''; ?>"><i class="fas fa-box"></i> เมนู & สต็อก (Stock)</a></li>
        <li><a href="categories.php" class="<?php echo $currentPage == 'categories.php' ? 'active' : ''; ?>"><i class="fas fa-tags"></i> หมวดหมู่ (Categories)</a></li>
        <li><a href="sales.php" class="<?php echo $currentPage == 'sales.php' ? 'active' : ''; ?>"><i class="fas fa-receipt"></i> ประวัติการขาย (Sales)</a></li>
        <li><a href="reports.php" class="<?php echo $currentPage == 'reports.php' ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> รายงาน (Reports)</a></li>
        <li><a href="staff.php" class="<?php echo $currentPage == 'staff.php' ? 'active' : ''; ?>"><i class="fas fa-users"></i> พนักงาน (Staff)</a></li>
        <li><a href="settings.php" class="<?php echo $currentPage == 'settings.php' ? 'active' : ''; ?>"><i class="fas fa-cog"></i> ตั้งค่าระบบ (Settings)</a></li>
        <li style="margin-top: auto;"><a href="../index.php" style="color: #94a3b8;"><i class="fas fa-sign-out-alt"></i> กลับหน้าร้าน</a></li>
        <li style="margin-top: 10px;"><a href="../logout.php" style="color: #ef4444;"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a></li>
    </ul>
</aside>
