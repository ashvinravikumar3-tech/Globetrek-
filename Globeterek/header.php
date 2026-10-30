<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$role = $_SESSION['role'] ?? null;
$name = $_SESSION['name'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>GlobeTrek Adventures</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="navbar-wrapper">
    <nav class="navbar">
        <div class="nav-brand gradient-text">GlobeTrek.</div>
        <button class="hamburger" id="hamburger-menu" aria-label="Toggle Navigation">
            <span class="bar"></span>
            <span class="bar"></span>
            <span class="bar"></span>
        </button>
        <ul class="nav-links" id="nav-links">
            <?php
            $currentPage = basename($_SERVER['SCRIPT_NAME']);
            $isAdminPage = (strpos($currentPage, 'staff_') === 0 || strpos($currentPage, 'admin_') === 0);
            $isCustomerPage = (strpos($currentPage, 'cust_') === 0);
            ?>

            <?php if ($role === 'customer' && $isCustomerPage): ?>
                <!-- Customer Dashboard Menu -->
                <li><a href="cust_dashboard.php">Dashboard</a></li>
                <li><a href="cust_bookings.php">My Bookings</a></li>
                <li><a href="cust_queries.php">My Queries</a></li>
                <li><a href="index.php">View Site</a></li>
                <li><a href="logout.php">Logout (<?= htmlspecialchars($name) ?>)</a></li>
            <?php elseif (($role === 'staff' || $role === 'admin') && $isAdminPage): ?>
                <!-- Staff/Admin Dashboard Menu -->
                <li><a href="staff_dashboard.php">Dashboard</a></li>
                <li><a href="staff_packages.php">Packages</a></li>
                <li><a href="staff_accommodations.php">Accommodations</a></li>
                <li><a href="staff_destinations.php">Destinations</a></li>
                <li><a href="staff_bookings.php">Bookings & Queries</a></li>
                <?php if ($role === 'admin'): ?>
                    <li><a href="admin_staff.php">Manage Staff</a></li>
                    <li><a href="admin_reports.php">Reports</a></li>
                <?php endif; ?>
                <li><a href="index.php">View Site</a></li>
                <li><a href="logout.php">Logout (<?= htmlspecialchars($name) ?>)</a></li>
            <?php else: ?>
                <!-- Public Site Menu -->
                <li><a href="index.php">Home</a></li>
                <li><a href="packages.php">Packages</a></li>
                <li><a href="showcase.php">Explore</a></li>
                <li><a href="contact.php">Contact</a></li>
                <li><a href="showcase.php#trans">Transport</a></li>

                <?php if (!$role): ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                <?php elseif ($role === 'customer'): ?>
                    <li><a href="cust_dashboard.php">Dashboard</a></li>
                    <li><a href="logout.php">Logout (<?= htmlspecialchars($name) ?>)</a></li>
                <?php elseif ($role === 'staff' || $role === 'admin'): ?>
                    <li><a href="staff_dashboard.php">Dashboard</a></li>
                    <li><a href="logout.php">Logout (<?= htmlspecialchars($name) ?>)</a></li>
                <?php endif; ?>
            <?php endif; ?>
        </ul>
    </nav>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const hamburger = document.getElementById('hamburger-menu');
        const navLinks = document.getElementById('nav-links');
        
        if (hamburger && navLinks) {
            hamburger.addEventListener('click', () => {
                navLinks.classList.toggle('active');
                hamburger.classList.toggle('is-active');
            });
        }
    });
</script>
<div class="container <?= ($isAdminPage || $isCustomerPage) ? 'dashboard-container' : '' ?>">
