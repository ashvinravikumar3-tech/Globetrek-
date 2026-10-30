<?php
$allowed_roles = ['customer'];
require 'auth_check.php';
require 'db.php';
require 'header.php';

// quick overview stats for this customer
$bookingCount = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE customer_id = ?");
$bookingCount->execute([$_SESSION['user_id']]);
$totalBookings = $bookingCount->fetchColumn();

$pendingStmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE customer_id = ? AND status = 'pending'");
$pendingStmt->execute([$_SESSION['user_id']]);
$pendingCount = $pendingStmt->fetchColumn();

$confirmedStmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE customer_id = ? AND status = 'confirmed'");
$confirmedStmt->execute([$_SESSION['user_id']]);
$confirmedCount = $confirmedStmt->fetchColumn();

$queryStmt = $pdo->prepare("SELECT COUNT(*) FROM queries WHERE customer_id = ? AND status = 'open'");
$queryStmt->execute([$_SESSION['user_id']]);
$openQueries = $queryStmt->fetchColumn();

// recent bookings preview
$recentStmt = $pdo->prepare("SELECT b.*, p.title AS package_title
                              FROM bookings b
                              JOIN packages p ON b.package_id = p.package_id
                              WHERE b.customer_id = ?
                              ORDER BY b.booking_date DESC LIMIT 3");
$recentStmt->execute([$_SESSION['user_id']]);
$recentBookings = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

// profile update handling
$profileError = '';
$profileSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $newPassword = $_POST['new_password'] ?? '';

    if (empty($name) || empty($email)) {
        $profileError = "Name and email are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $profileError = "Please enter a valid email address.";
    } else {
        try {
            // check email not taken by someone else
            $check = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $check->execute([$email, $_SESSION['user_id']]);
            if ($check->fetch()) {
                $profileError = "That email is already in use by another account.";
            } else {
                if (!empty($newPassword)) {
                    if (strlen($newPassword) < 6) {
                        $profileError = "New password must be at least 6 characters.";
                    } else {
                        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
                        $upd = $pdo->prepare("UPDATE users SET name=?, email=?, password=? WHERE user_id=?");
                        $upd->execute([$name, $email, $hashed, $_SESSION['user_id']]);
                    }
                } else {
                    $upd = $pdo->prepare("UPDATE users SET name=?, email=? WHERE user_id=?");
                    $upd->execute([$name, $email, $_SESSION['user_id']]);
                }

                if (empty($profileError)) {
                    $_SESSION['name'] = $name;
                    $_SESSION['email'] = $email;
                    $profileSuccess = "Profile updated successfully.";
                }
            }
        } catch (PDOException $e) {
            $profileError = "Update failed: " . $e->getMessage();
        }
    }
}
?>

<div class="section-header" style="margin-top: 40px;">
    <h2>My Dashboard</h2>
    <p>Welcome back, <span class="gradient-text"><?= htmlspecialchars($_SESSION['name']) ?></span>!</p>
</div>

<div class="card-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 40px;">
    <div class="card" style="padding: 20px; text-align: center;">
        <h3 style="color: var(--accent-solid); font-size: 2rem;"><?= $totalBookings ?></h3>
        <p>Total Bookings</p>
    </div>
    <div class="card" style="padding: 20px; text-align: center;">
        <h3 style="color: var(--accent-solid); font-size: 2rem;"><?= $pendingCount ?></h3>
        <p>Pending Bookings</p>
    </div>
    <div class="card" style="padding: 20px; text-align: center;">
        <h3 style="color: var(--accent-solid); font-size: 2rem;"><?= $confirmedCount ?></h3>
        <p>Confirmed Bookings</p>
    </div>
    <div class="card" style="padding: 20px; text-align: center;">
        <h3 style="color: var(--accent-solid); font-size: 2rem;"><?= $openQueries ?></h3>
        <p>Open Queries</p>
    </div>
</div>

<div style="display: flex; gap: 15px; margin-bottom: 40px; justify-content: center;">
    <a href="packages.php" class="btn">Browse Packages</a>
    <a href="cust_bookings.php" class="btn" style="background: rgba(255,255,255,0.1);">My Bookings</a>
    <a href="cust_queries.php" class="btn" style="background: rgba(255,255,255,0.1);">My Queries</a>
</div>

<div class="card" style="padding: 2rem; margin-bottom: 40px;">
    <h3>Recent Bookings</h3>
    <?php if (empty($recentBookings)): ?>
        <p>No bookings yet. <a href="packages.php">Browse packages</a> to get started.</p>
    <?php else: ?>
        <table>
            <tr><th>Package</th><th>Travel Date</th><th>Status</th></tr>
            <?php foreach ($recentBookings as $b): ?>
                <tr>
                    <td><?= htmlspecialchars($b['package_title']) ?></td>
                    <td><?= htmlspecialchars($b['travel_date']) ?></td>
                    <td><span style="color: <?= $b['status'] === 'confirmed' ? 'green' : ($b['status'] === 'pending' ? 'orange' : 'red') ?>;"><?= htmlspecialchars($b['status']) ?></span></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <p style="margin-top: 15px;"><a href="cust_bookings.php">View all bookings &rarr;</a></p>
    <?php endif; ?>
</div>

<div class="card" style="padding: 2rem; max-width: 600px; margin: 0 auto;">
    <h3>Edit Profile</h3>

<?php if ($profileSuccess): ?>
    <p style="color:green;"><?= htmlspecialchars($profileSuccess) ?></p>
<?php endif; ?>
<?php if ($profileError): ?>
    <p style="color:red;"><?= htmlspecialchars($profileError) ?></p>
<?php endif; ?>

<form method="POST" action="cust_dashboard.php" style="display: flex; flex-direction: column; gap: 15px;">
    <input type="hidden" name="update_profile" value="1">

    <div>
        <label>Name:</label>
        <input type="text" name="name" value="<?= htmlspecialchars($_SESSION['name']) ?>" required style="width: 100%;">
    </div>

    <div>
        <label>Email:</label>
        <input type="email" name="email" value="<?= htmlspecialchars($_SESSION['email'] ?? '') ?>" required style="width: 100%;">
    </div>

    <div>
        <label>New Password <small>(leave blank to keep current)</small>:</label>
        <input type="password" name="new_password" style="width: 100%;">
    </div>

    <button type="submit" class="btn" style="align-self: flex-start;">Update Profile</button>
</form>
</div>

<?php require 'footer.php'; ?>
