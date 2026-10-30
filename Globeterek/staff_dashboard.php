<?php
$allowed_roles = ['staff', 'admin'];
require 'auth_check.php';
require 'db.php';
require 'header.php';

// quick overview stats
$pkgCount = $pdo->query("SELECT COUNT(*) FROM packages")->fetchColumn();
$destCount = $pdo->query("SELECT COUNT(*) FROM destinations")->fetchColumn();
$accCount = $pdo->query("SELECT COUNT(*) FROM accommodations")->fetchColumn();
$pendingBookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
$openQueries = $pdo->query("SELECT COUNT(*) FROM queries WHERE status = 'open'")->fetchColumn();
?>

<div class="section-header">
    <div>
        <h2>Staff Dashboard</h2>
        <p>Welcome back, <span class="gradient-text"><?= htmlspecialchars($_SESSION['name']) ?></span> (<?= htmlspecialchars(ucfirst($_SESSION['role'])) ?>)</p>
    </div>
</div>

<div class="card-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 40px; margin-top: 30px;">
    <div class="card" style="padding: 25px; text-align: center; justify-content: center; align-items: center;">
        <h3 style="color: var(--accent-solid); font-size: 2.5rem; font-family: var(--font-heading); font-weight: 800; margin-bottom: 5px;"><?= $pkgCount ?></h3>
        <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 0;">Total Packages</p>
    </div>
    <div class="card" style="padding: 25px; text-align: center; justify-content: center; align-items: center;">
        <h3 style="color: var(--accent-solid); font-size: 2.5rem; font-family: var(--font-heading); font-weight: 800; margin-bottom: 5px;"><?= $destCount ?></h3>
        <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 0;">Total Destinations</p>
    </div>
    <div class="card" style="padding: 25px; text-align: center; justify-content: center; align-items: center;">
        <h3 style="color: var(--accent-solid); font-size: 2.5rem; font-family: var(--font-heading); font-weight: 800; margin-bottom: 5px;"><?= $accCount ?></h3>
        <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 0;">Total Accommodations</p>
    </div>
    <div class="card" style="padding: 25px; text-align: center; justify-content: center; align-items: center;">
        <h3 style="color: <?= $pendingBookings > 0 ? '#fb923c' : 'var(--accent-solid)' ?>; font-size: 2.5rem; font-family: var(--font-heading); font-weight: 800; margin-bottom: 5px;"><?= $pendingBookings ?></h3>
        <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 0;">Pending Bookings</p>
    </div>
    <div class="card" style="padding: 25px; text-align: center; justify-content: center; align-items: center;">
        <h3 style="color: <?= $openQueries > 0 ? '#f87171' : 'var(--accent-solid)' ?>; font-size: 2.5rem; font-family: var(--font-heading); font-weight: 800; margin-bottom: 5px;"><?= $openQueries ?></h3>
        <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 0;">Open Queries</p>
    </div>
</div>

<div class="card" style="padding: 2.5rem; margin-bottom: 40px; border-radius: 20px;">
    <h3 style="font-family: var(--font-heading); font-size: 1.5rem; margin-bottom: 20px; font-weight: 700;">Quick Management Links</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px;">
        <a href="staff_packages.php" class="btn" style="margin-top: 0; padding: 1rem;">Manage Packages &rarr;</a>
        <a href="staff_destinations.php" class="btn" style="margin-top: 0; padding: 1rem; background: rgba(255,255,255,0.06); border: 1px solid var(--glass-border); color: var(--text-primary);">Manage Destinations &rarr;</a>
        <a href="staff_accommodations.php" class="btn" style="margin-top: 0; padding: 1rem; background: rgba(255,255,255,0.06); border: 1px solid var(--glass-border); color: var(--text-primary);">Manage Accommodations &rarr;</a>
        <a href="staff_bookings.php" class="btn" style="margin-top: 0; padding: 1rem; background: rgba(255,255,255,0.06); border: 1px solid var(--glass-border); color: var(--text-primary);">Bookings & Queries &rarr;</a>
        <?php if ($_SESSION['role'] === 'admin'): ?>
            <a href="admin_staff.php" class="btn" style="margin-top: 0; padding: 1rem; background: rgba(255,255,255,0.06); border: 1px solid var(--glass-border); color: var(--text-primary);">Manage Staff Users &rarr;</a>
            <a href="admin_reports.php" class="btn" style="margin-top: 0; padding: 1rem; background: rgba(255,255,255,0.06); border: 1px solid var(--glass-border); color: var(--text-primary);">System Reports &rarr;</a>
        <?php endif; ?>
    </div>
</div>

<?php require 'footer.php'; ?>