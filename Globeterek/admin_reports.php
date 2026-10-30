<?php
$allowed_roles = ['admin'];
require 'auth_check.php';
require 'db.php';
require 'header.php';
?>

<div class="section-header">
    <div>
        <h2>System Reports & Analytics</h2>
        <p style="color: var(--text-secondary); font-size: 0.95rem;">Track sales summary, package performance, monthly revenue, and client metrics.</p>
    </div>
</div>

<?php
$totalRevenue = $pdo->query("SELECT SUM(total_price) FROM bookings WHERE status = 'confirmed'")->fetchColumn();
$totalBookings = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$confirmedCount = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'")->fetchColumn();
$pendingCount = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
$cancelledCount = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'cancelled'")->fetchColumn();
?>

<!-- Sales Summary Cards -->
<div class="card-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-top: 30px; margin-bottom: 40px;">
    <div class="card" style="padding: 25px; text-align: center; justify-content: center; align-items: center; grid-column: span 2; min-height: 140px;">
        <h3 class="gradient-text" style="font-size: 2.8rem; font-family: var(--font-heading); font-weight: 800; margin-bottom: 5px;">Rs. <?= number_format($totalRevenue ?? 0, 2) ?></h3>
        <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 0; font-weight: 600; text-transform: uppercase; letter-spacing: 1.5px;">Total Revenue</p>
    </div>
    <div class="card" style="padding: 25px; text-align: center; justify-content: center; align-items: center;">
        <h3 style="color: var(--text-primary); font-size: 2.5rem; font-family: var(--font-heading); font-weight: 800; margin-bottom: 5px;"><?= $totalBookings ?></h3>
        <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 0;">Total Bookings</p>
    </div>
    <div class="card" style="padding: 25px; text-align: center; justify-content: center; align-items: center;">
        <h3 style="color: #4ade80; font-size: 2.5rem; font-family: var(--font-heading); font-weight: 800; margin-bottom: 5px;"><?= $confirmedCount ?></h3>
        <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 0;">Confirmed</p>
    </div>
    <div class="card" style="padding: 25px; text-align: center; justify-content: center; align-items: center;">
        <h3 style="color: #fb923c; font-size: 2.5rem; font-family: var(--font-heading); font-weight: 800; margin-bottom: 5px;"><?= $pendingCount ?></h3>
        <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 0;">Pending</p>
    </div>
    <div class="card" style="padding: 25px; text-align: center; justify-content: center; align-items: center;">
        <h3 style="color: #f87171; font-size: 2.5rem; font-family: var(--font-heading); font-weight: 800; margin-bottom: 5px;"><?= $cancelledCount ?></h3>
        <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 0;">Cancelled</p>
    </div>
</div>

<div style="display: flex; flex-wrap: wrap; gap: 30px; margin-bottom: 40px;">
    <!-- Sales by Package -->
    <div class="card" style="flex: 1 1 500px; padding: 2rem; border-radius: 20px; margin-bottom: 0;">
        <h3 style="font-family: var(--font-heading); font-size: 1.3rem; margin-bottom: 20px; font-weight: 700;">Sales by Package</h3>
        <?php
        $stmt = $pdo->query("SELECT p.title,
                                     COUNT(b.booking_id) AS total_bookings,
                                     SUM(CASE WHEN b.status = 'confirmed' THEN b.total_price ELSE 0 END) AS revenue
                              FROM packages p
                              LEFT JOIN bookings b ON p.package_id = b.package_id
                              GROUP BY p.package_id
                              ORDER BY revenue DESC");
        $salesByPackage = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <?php if (empty($salesByPackage)): ?>
            <p style="color: var(--text-secondary);">No package sales data yet.</p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table style="margin-top: 0; width: 100%;">
                    <thead>
                        <tr>
                            <th>Package</th>
                            <th>Bookings</th>
                            <th style="text-align: right;">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($salesByPackage as $row): ?>
                            <tr>
                                <td style="font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars($row['title']) ?></td>
                                <td><?= (int)$row['total_bookings'] ?></td>
                                <td style="text-align: right; font-family: var(--font-heading); font-weight: 700;">Rs. <?= number_format($row['revenue'] ?? 0, 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Monthly Revenue -->
    <div class="card" style="flex: 1 1 400px; padding: 2rem; border-radius: 20px; margin-bottom: 0;">
        <h3 style="font-family: var(--font-heading); font-size: 1.3rem; margin-bottom: 20px; font-weight: 700;">Monthly Revenue</h3>
        <?php
        $stmt = $pdo->query("SELECT DATE_FORMAT(booking_date, '%Y-%m') AS month,
                                     SUM(total_price) AS revenue,
                                     COUNT(*) AS bookings
                              FROM bookings
                              WHERE status = 'confirmed'
                              GROUP BY month
                              ORDER BY month DESC");
        $monthly = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <?php if (empty($monthly)): ?>
            <p style="color: var(--text-secondary);">No confirmed bookings yet.</p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table style="margin-top: 0; width: 100%;">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Bookings</th>
                            <th style="text-align: right;">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($monthly as $row): ?>
                            <tr>
                                <td style="font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars($row['month']) ?></td>
                                <td><?= (int)$row['bookings'] ?></td>
                                <td style="text-align: right; font-family: var(--font-heading); font-weight: 700;">Rs. <?= number_format($row['revenue'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div style="display: flex; flex-wrap: wrap; gap: 30px; margin-bottom: 40px;">
    <!-- Customer Report -->
    <div class="card" style="flex: 1 1 600px; padding: 2rem; border-radius: 20px; margin-bottom: 0;">
        <h3 style="font-family: var(--font-heading); font-size: 1.3rem; margin-bottom: 20px; font-weight: 700;">Client Leaderboard (Spendings)</h3>
        <?php
        $stmt = $pdo->query("SELECT u.name, u.email,
                                     COUNT(b.booking_id) AS total_bookings,
                                     SUM(CASE WHEN b.status = 'confirmed' THEN b.total_price ELSE 0 END) AS total_spent
                              FROM users u
                              LEFT JOIN bookings b ON u.user_id = b.customer_id
                              WHERE u.role = 'customer'
                              GROUP BY u.user_id
                              ORDER BY total_spent DESC");
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <?php if (empty($customers)): ?>
            <p style="color: var(--text-secondary);">No customers registered yet.</p>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table style="margin-top: 0; width: 100%;">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Bookings</th>
                            <th style="text-align: right;">Total Spent</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $c): ?>
                            <tr>
                                <td style="font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars($c['name']) ?></td>
                                <td><?= htmlspecialchars($c['email']) ?></td>
                                <td><?= (int)$c['total_bookings'] ?></td>
                                <td style="text-align: right; font-family: var(--font-heading); font-weight: 700;">Rs. <?= number_format($c['total_spent'] ?? 0, 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Customer Inquiries Stats -->
    <div class="card" style="flex: 1 1 300px; padding: 2rem; border-radius: 20px; margin-bottom: 0; display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            <h3 style="font-family: var(--font-heading); font-size: 1.3rem; margin-bottom: 20px; font-weight: 700;">Client Inquiries</h3>
            <?php
            $openCount = $pdo->query("SELECT COUNT(*) FROM queries WHERE status = 'open'")->fetchColumn();
            $answeredCount = $pdo->query("SELECT COUNT(*) FROM queries WHERE status = 'answered'")->fetchColumn();
            ?>
            <div style="display: flex; flex-direction: column; gap: 15px; margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; background: rgba(255, 255, 255, 0.02); padding: 12px 15px; border-radius: 8px; border: 1px solid var(--glass-border);">
                    <span style="color: var(--text-secondary); font-size: 0.95rem; font-weight: 500;">Open Queries</span>
                    <span style="font-size: 1.25rem; font-weight: 800; color: <?= $openCount > 0 ? '#f87171' : 'var(--text-primary)' ?>;"><?= $openCount ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; background: rgba(255, 255, 255, 0.02); padding: 12px 15px; border-radius: 8px; border: 1px solid var(--glass-border);">
                    <span style="color: var(--text-secondary); font-size: 0.95rem; font-weight: 500;">Answered Queries</span>
                    <span style="font-size: 1.25rem; font-weight: 800; color: #4ade80;"><?= $answeredCount ?></span>
                </div>
            </div>
        </div>
        
        <a href="staff_bookings.php" class="btn" style="margin-top: auto; padding: 1rem; width: 100%; text-align: center;">
            Manage Inquiries &rarr;
        </a>
    </div>
</div>

<?php require 'footer.php'; ?>