<?php
$allowed_roles = ['customer'];
require 'auth_check.php';
require 'db.php';
require 'header.php';

$error = '';
$success = '';
$action = $_GET['action'] ?? 'list';
$package_id = isset($_GET['package_id']) ? (int)$_GET['package_id'] : null;
$selected_package = null;
$accommodations = [];
$transports = [];

if (isset($_GET['new'])) {
    $success = "Booking created successfully! Status: pending confirmation.";
}
if (isset($_GET['paid'])) {
    $success = "🎉 Payment successful! Your booking is now confirmed.";
}

// ---------- CANCEL ----------
if ($action === 'cancel' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE booking_id = ? AND customer_id = ?");
    $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($booking) {
        $upd = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE booking_id = ?");
        $upd->execute([$_GET['id']]);
        header('Location: cust_bookings.php?cancelled=1');
        exit;
    } else {
        $error = "Booking not found.";
    }
}
if (isset($_GET['cancelled'])) {
    $success = "Booking cancelled.";
}

// ---------- CREATE BOOKING ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_booking'])) {
    $package_id = isset($_POST['package_id']) ? (int)$_POST['package_id'] : 0;
    $travel_date = trim($_POST['travel_date'] ?? '');
    $accommodation_id = !empty($_POST['accommodation_id']) ? (int)$_POST['accommodation_id'] : null;
    $transport_id = !empty($_POST['transport_id']) ? (int)$_POST['transport_id'] : null;
    $notes = trim($_POST['customization_notes'] ?? '');

    if (!$package_id) {
        $error = "Please select a package.";
    } elseif (empty($travel_date)) {
        $error = "Please select a travel date.";
    } elseif (strtotime($travel_date) < strtotime('today')) {
        $error = "Travel date cannot be in the past.";
    } else {
        $pkgStmt = $pdo->prepare("SELECT price, duration_days FROM packages WHERE package_id = ?");
        $pkgStmt->execute([$package_id]);
        $package = $pkgStmt->fetch(PDO::FETCH_ASSOC);

        if (!$package) {
            $error = "Selected package was not found.";
        } else {
            $total = (float)$package['price'];

            if ($accommodation_id) {
                $accStmt = $pdo->prepare("SELECT price_per_night FROM accommodations WHERE accommodation_id = ?");
                $accStmt->execute([$accommodation_id]);
                $accPrice = $accStmt->fetchColumn();
                if ($accPrice !== false) {
                    $total += (float)$accPrice * (int)$package['duration_days'];
                }
            }

            if ($transport_id) {
                $transStmt = $pdo->prepare("SELECT price FROM transport WHERE transport_id = ?");
                $transStmt->execute([$transport_id]);
                $transPrice = $transStmt->fetchColumn();
                if ($transPrice !== false) {
                    $total += (float)$transPrice;
                }
            }

            try {
                $insert = $pdo->prepare("INSERT INTO bookings (customer_id, package_id, accommodation_id, transport_id, travel_date, customization_notes, status, total_price)
                                         VALUES (?, ?, ?, ?, ?, ?, 'pending', ?)");
                $insert->execute([
                    $_SESSION['user_id'],
                    $package_id,
                    $accommodation_id,
                    $transport_id,
                    $travel_date,
                    $notes,
                    $total,
                ]);
                $newBookingId = $pdo->lastInsertId();
                header('Location: cust_payment.php?booking_id=' . $newBookingId);
                exit;
            } catch (PDOException $e) {
                $error = "Booking failed: " . $e->getMessage();
            }
        }
    }
}

// ---------- EDIT SUBMIT ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'])) {
    $booking_id = $_POST['booking_id'];

    $check = $pdo->prepare("SELECT * FROM bookings WHERE booking_id = ? AND customer_id = ?");
    $check->execute([$booking_id, $_SESSION['user_id']]);
    $existing = $check->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        $error = "Booking not found.";
    } elseif ($existing['status'] === 'cancelled') {
        $error = "Cancelled bookings cannot be edited.";
    } else {
        $travel_date = $_POST['travel_date'];
        $accommodation_id = !empty($_POST['accommodation_id']) ? (int)$_POST['accommodation_id'] : null;
        $transport_id = !empty($_POST['transport_id']) ? (int)$_POST['transport_id'] : null;
        $notes = trim($_POST['customization_notes']);

        if (empty($travel_date)) {
            $error = "Please select a travel date.";
        } elseif (strtotime($travel_date) < strtotime('today')) {
            $error = "Travel date cannot be in the past.";
        } else {
            $pkgStmt = $pdo->prepare("SELECT price, duration_days FROM packages WHERE package_id = ?");
            $pkgStmt->execute([$existing['package_id']]);
            $pkg = $pkgStmt->fetch(PDO::FETCH_ASSOC);

            $total = (float)$pkg['price'];

            if ($accommodation_id) {
                $accStmt = $pdo->prepare("SELECT price_per_night FROM accommodations WHERE accommodation_id = ?");
                $accStmt->execute([$accommodation_id]);
                $accPrice = $accStmt->fetchColumn();
                if ($accPrice !== false) {
                    $total += (float)$accPrice * (int)$pkg['duration_days'];
                }
            }
            if ($transport_id) {
                $transStmt = $pdo->prepare("SELECT price FROM transport WHERE transport_id = ?");
                $transStmt->execute([$transport_id]);
                $transPrice = $transStmt->fetchColumn();
                if ($transPrice !== false) {
                    $total += (float)$transPrice;
                }
            }

            try {
                $upd = $pdo->prepare("UPDATE bookings SET travel_date=?, accommodation_id=?, transport_id=?, customization_notes=?, total_price=?, status='pending' WHERE booking_id=?");
                $upd->execute([$travel_date, $accommodation_id, $transport_id, $notes, $total, $booking_id]);
                header('Location: cust_bookings.php?updated=1');
                exit;
            } catch (PDOException $e) {
                $error = "Update failed: " . $e->getMessage();
            }
        }
    }
}
if (isset($_GET['updated'])) {
    $success = "Booking updated. Status reset to pending for re-confirmation.";
}

// ---------- EDIT: fetch booking to populate form ----------
$editing = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE booking_id = ? AND customer_id = ?");
    $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
    $editing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($editing && $editing['status'] === 'cancelled') {
        $error = "Cancelled bookings cannot be edited.";
        $editing = null;
    }
}

if ($editing || $action === 'new' || $package_id) {
    $accommodations = $pdo->query("SELECT * FROM accommodations ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    $transports = $pdo->query("SELECT * FROM transport ORDER BY type")->fetchAll(PDO::FETCH_ASSOC);
}

if ($package_id) {
    $pkgStmt = $pdo->prepare("SELECT p.*, d.name AS destination_name
                              FROM packages p
                              JOIN destinations d ON p.destination_id = d.destination_id
                              WHERE p.package_id = ?");
    $pkgStmt->execute([$package_id]);
    $selected_package = $pkgStmt->fetch(PDO::FETCH_ASSOC);
}
?>

<div class="section-header" style="margin-top: 40px;">
    <h2>My Bookings</h2>
</div>

<?php if ($success): ?>
    <p style="color:green;"><?= htmlspecialchars($success) ?></p>
<?php endif; ?>
<?php if ($error): ?>
    <p style="color:red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<?php if ($editing): ?>

    <div class="card" style="padding: 2rem; max-width: 600px; margin: 0 auto;">
    <h3>Edit Booking</h3>
    <form method="POST" action="cust_bookings.php" style="display: flex; flex-direction: column; gap: 15px; margin-top: 20px;">
        <input type="hidden" name="booking_id" value="<?= $editing['booking_id'] ?>">

        <div>
            <label>Travel Date:</label>
            <input type="date" name="travel_date" value="<?= htmlspecialchars($editing['travel_date']) ?>" required style="width:100%;">
        </div>

        <div>
            <label>Accommodation:</label>
            <select name="accommodation_id" style="width:100%;">
                <option value="">-- None --</option>
                <?php foreach ($accommodations as $acc): ?>
                    <option value="<?= $acc['accommodation_id'] ?>"
                        <?= $editing['accommodation_id'] == $acc['accommodation_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($acc['name']) ?> - Rs. <?= number_format($acc['price_per_night'], 2) ?>/night
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label>Transport:</label>
            <select name="transport_id" style="width:100%;">
                <option value="">-- None --</option>
                <?php foreach ($transports as $t): ?>
                    <option value="<?= $t['transport_id'] ?>"
                        <?= $editing['transport_id'] == $t['transport_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['type']) ?> (<?= htmlspecialchars($t['provider']) ?>) - Rs. <?= number_format($t['price'], 2) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label>Customization Notes:</label>
            <textarea name="customization_notes" rows="3" style="width:100%;"><?= htmlspecialchars($editing['customization_notes']) ?></textarea>
        </div>

        <div style="display: flex; gap: 15px; align-items: center;">
            <button type="submit" class="btn">Update Booking</button>
            <a href="cust_bookings.php">Cancel Edit</a>
        </div>
    </form>
    </div>

<?php elseif ($action === 'new' || $package_id): ?>

    <div class="card" style="padding: 2rem; max-width: 600px; margin: 0 auto;">
    <h3>Book a Package</h3>
    <?php if (!$selected_package): ?>
        <?php
        $pkgList = $pdo->query("SELECT package_id, title, price, duration_days FROM packages ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <?php if (empty($pkgList)): ?>
            <p>No packages are available right now.</p>
        <?php else: ?>
            <ul style="list-style: none; padding: 0; margin-top: 20px;">
                <?php foreach ($pkgList as $pkg): ?>
                    <li style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong style="font-size: 1.1rem; color: var(--accent-solid);"><?= htmlspecialchars($pkg['title']) ?></strong><br>
                            Rs. <?= number_format($pkg['price'], 2) ?>
                        </div>
                        <a href="cust_bookings.php?action=new&package_id=<?= $pkg['package_id'] ?>" class="btn" style="padding: 0.5rem 1rem;">Choose</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    <?php else: ?>
        <form method="POST" action="cust_bookings.php" style="display: flex; flex-direction: column; gap: 15px; margin-top: 20px;">
            <input type="hidden" name="create_booking" value="1">
            <input type="hidden" name="package_id" value="<?= (int)$selected_package['package_id'] ?>">

            <div style="background: rgba(0,0,0,0.3); padding: 15px; border-radius: 8px;">
                <p style="margin-bottom: 5px;"><strong>Package:</strong> <span style="color: var(--accent-solid);"><?= htmlspecialchars($selected_package['title']) ?></span></p>
                <p style="margin-bottom: 5px;"><strong>Destination:</strong> <?= htmlspecialchars($selected_package['destination_name']) ?> (<?= (int)$selected_package['duration_days'] ?> days)</p>
                <p><strong>Base Price:</strong> Rs. <?= number_format($selected_package['price'], 2) ?></p>
            </div>

            <div>
                <label>Travel Date:</label>
                <input type="date" name="travel_date" required style="width: 100%;">
            </div>

            <div>
                <label>Accommodation:</label>
                <select name="accommodation_id" style="width: 100%;">
                    <option value="">-- None --</option>
                    <?php foreach ($accommodations as $acc): ?>
                        <option value="<?= $acc['accommodation_id'] ?>">
                            <?= htmlspecialchars($acc['name']) ?> - Rs. <?= number_format($acc['price_per_night'], 2) ?>/night
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label>Transport:</label>
                <select name="transport_id" style="width: 100%;">
                    <option value="">-- None --</option>
                    <?php foreach ($transports as $t): ?>
                        <option value="<?= $t['transport_id'] ?>">
                            <?= htmlspecialchars($t['type']) ?> (<?= htmlspecialchars($t['provider']) ?>) - Rs. <?= number_format($t['price'], 2) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label>Customization Notes:</label>
                <textarea name="customization_notes" rows="3" style="width: 100%;"></textarea>
            </div>

            <div style="display: flex; gap: 15px; align-items: center;">
                <button type="submit" class="btn">Confirm Booking</button>
                <a href="cust_bookings.php">Cancel</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

<?php else: ?>

    <?php
    $stmt = $pdo->prepare("SELECT b.*, p.title AS package_title, p.duration_days
                            FROM bookings b
                            JOIN packages p ON b.package_id = p.package_id
                            WHERE b.customer_id = ?
                            ORDER BY b.booking_date DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <?php if (empty($bookings)): ?>
        <p>You have no bookings yet. <a href="cust_bookings.php?action=new">Book a package</a></p>
    <?php else: ?>
        <p style="margin-bottom: 20px;"><a href="cust_bookings.php?action=new" class="btn">Book a new package</a></p>
        <div style="overflow-x: auto;">
        <table>
            <tr>
                <th>Package</th><th>Travel Date</th><th>Total Price</th><th>Status</th><th>Actions</th>
            </tr>
            <?php foreach ($bookings as $b): ?>
                <tr>
                    <td><?= htmlspecialchars($b['package_title']) ?></td>
                    <td><?= htmlspecialchars($b['travel_date']) ?></td>
                    <td>Rs. <?= number_format($b['total_price'], 2) ?></td>
                    <td>
                        <span style="color: <?= $b['status'] === 'confirmed' ? 'green' : ($b['status'] === 'pending' ? 'orange' : 'red') ?>;">
                            <?= htmlspecialchars(ucfirst($b['status'])) ?>
                        </span>
                    </td>
                    <td>
                        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                        <?php if ($b['status'] !== 'cancelled'): ?>
                            <a href="cust_bookings.php?action=edit&id=<?= $b['booking_id'] ?>" style="color: var(--accent-solid);">Edit</a>
                            
                            <a href="cust_bookings.php?action=cancel&id=<?= $b['booking_id'] ?>"
                               onclick="return confirm('Cancel this booking?');" style="color: #ff4444;">Cancel</a>
                        <?php else: ?>
                            <em style="color: gray;">Cancelled</em>
                        <?php endif; ?>
                        <?php if ($b['status'] === 'confirmed'): ?>
                            <a href="cust_feedback.php?booking_id=<?= $b['booking_id'] ?>" class="btn" style="padding: 0.3rem 0.6rem; font-size: 0.8rem;">Leave Feedback</a>
                        <?php endif; ?>
                        <?php if ($b['status'] === 'pending'): ?>
                            <a href="cust_payment.php?booking_id=<?= $b['booking_id'] ?>" class="btn" style="padding: 0.3rem 0.8rem; font-size: 0.8rem; background: linear-gradient(135deg,#22c55e,#16a34a);">Pay Now</a>
                        <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        </div>
    <?php endif; ?>

<?php endif; ?>

<?php require 'footer.php'; ?>