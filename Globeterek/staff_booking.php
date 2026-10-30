<?php
$allowed_roles = ['staff', 'admin'];
require 'auth_check.php';
require 'db.php';
require 'header.php';

$error = '';
$success = '';

// ---------- UPDATE BOOKING STATUS ----------
if (isset($_GET['booking_action']) && isset($_GET['booking_id'])) {
    $newStatus = $_GET['booking_action'] === 'confirm' ? 'confirmed' : ($_GET['booking_action'] === 'cancel' ? 'cancelled' : null);
    if ($newStatus) {
        $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE booking_id = ?");
        $stmt->execute([$newStatus, $_GET['booking_id']]);
        header('Location: staff_bookings.php?msg=booking_updated');
        exit;
    }
}

// ---------- SUBMIT QUERY REPLY ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['query_id'])) {
    $reply = trim($_POST['staff_reply']);
    if (empty($reply)) {
        $error = "Reply cannot be empty.";
    } else {
        $stmt = $pdo->prepare("UPDATE queries SET staff_reply = ?, status = 'answered' WHERE query_id = ?");
        $stmt->execute([$reply, $_POST['query_id']]);
        header('Location: staff_bookings.php?msg=query_answered');
        exit;
    }
}

if (isset($_GET['msg'])) {
    $success = $_GET['msg'] === 'booking_updated' ? 'Booking status updated.' : 'Reply sent to customer.';
}
?>

<div class="section-header">
    <div>
        <h2>Manage Bookings & Queries</h2>
        <p style="color: var(--text-secondary); font-size: 0.95rem;">Review client bookings and respond to inquiries.</p>
    </div>
</div>

<?php if ($success): ?>
    <div class="contact-message success" style="margin-top: 20px;">
        <?= htmlspecialchars($success) ?>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="contact-message error" style="margin-top: 20px;">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="card" style="padding: 2.5rem; margin-top: 30px; margin-bottom: 40px; border-radius: 20px;">
    <h3 style="font-family: var(--font-heading); font-size: 1.5rem; margin-bottom: 25px; font-weight: 700;">All Bookings</h3>
    
    <?php
    $stmt = $pdo->query("SELECT b.*, u.name AS customer_name, p.title AS package_title
                          FROM bookings b
                          JOIN users u ON b.customer_id = u.user_id
                          JOIN packages p ON b.package_id = p.package_id
                          ORDER BY b.booking_date DESC");
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <?php if (empty($bookings)): ?>
        <p style="color: var(--text-secondary);">No bookings yet.</p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table style="margin-top: 0; width: 100%;">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Package</th>
                        <th>Travel Date</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $b): ?>
                        <tr>
                            <td style="font-weight: 600; color: var(--text-primary);"><?= htmlspecialchars($b['customer_name']) ?></td>
                            <td><?= htmlspecialchars($b['package_title']) ?></td>
                            <td><?= htmlspecialchars($b['travel_date']) ?></td>
                            <td style="font-family: var(--font-heading); font-weight: 700;">Rs. <?= number_format($b['total_price'], 2) ?></td>
                            <td>
                                <?php
                                $statusColor = 'var(--text-secondary)';
                                $statusBg = 'rgba(255, 255, 255, 0.05)';
                                $statusBorder = 'rgba(255, 255, 255, 0.1)';
                                if ($b['status'] === 'confirmed') {
                                    $statusColor = '#4ade80';
                                    $statusBg = 'rgba(74, 222, 128, 0.1)';
                                    $statusBorder = 'rgba(74, 222, 128, 0.2)';
                                } elseif ($b['status'] === 'pending') {
                                    $statusColor = '#fb923c';
                                    $statusBg = 'rgba(251, 146, 60, 0.1)';
                                    $statusBorder = 'rgba(251, 146, 60, 0.2)';
                                } elseif ($b['status'] === 'cancelled') {
                                    $statusColor = '#f87171';
                                    $statusBg = 'rgba(248, 113, 113, 0.1)';
                                    $statusBorder = 'rgba(248, 113, 113, 0.2)';
                                }
                                ?>
                                <span style="color: <?= $statusColor ?>; background: <?= $statusBg ?>; border: 1px solid <?= $statusBorder ?>; padding: 4px 10px; border-radius: 999px; font-size: 0.85rem; font-weight: 500;">
                                    <?= htmlspecialchars(ucfirst($b['status'])) ?>
                                </span>
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <?php if ($b['status'] === 'pending'): ?>
                                    <a href="staff_bookings.php?booking_action=confirm&booking_id=<?= $b['booking_id'] ?>" class="btn" style="margin-top: 0; padding: 6px 14px; font-size: 0.85rem; background: rgba(74, 222, 128, 0.1); border: 1px solid rgba(74, 222, 128, 0.3); color: #86efac; border-radius: 8px;">Confirm</a>
                                    <a href="staff_bookings.php?booking_action=cancel&booking_id=<?= $b['booking_id'] ?>" class="btn" style="margin-top: 0; padding: 6px 14px; font-size: 0.85rem; background: rgba(248, 113, 113, 0.1); border: 1px solid rgba(248, 113, 113, 0.3); color: #fca5a5; border-radius: 8px; margin-left: 8px;" onclick="return confirm('Cancel this booking?');">Cancel</a>
                                <?php else: ?>
                                    <span style="color: var(--text-secondary); font-size: 0.85rem; font-style: italic;">No actions</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="card" style="padding: 2.5rem; margin-bottom: 40px; border-radius: 20px;">
    <h3 style="font-family: var(--font-heading); font-size: 1.5rem; margin-bottom: 25px; font-weight: 700;">Customer Queries</h3>
    
    <?php
    $stmt = $pdo->query("SELECT * FROM queries ORDER BY (status = 'open') DESC, created_at DESC");
    $queries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <?php if (empty($queries)): ?>
        <p style="color: var(--text-secondary);">No queries submitted yet.</p>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <?php foreach ($queries as $q): ?>
                <div class="card" style="padding: 20px; background: rgba(255, 255, 255, 0.01); border: 1px solid var(--glass-border); margin-bottom: 0; border-radius: 14px; box-shadow: none;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 10px; border-bottom: 1px solid var(--glass-border); padding-bottom: 12px; margin-bottom: 15px;">
                        <div>
                            <strong style="color: var(--text-primary); font-size: 1rem;"><?= htmlspecialchars($q['name']) ?></strong>
                            <span style="color: var(--text-secondary); font-size: 0.85rem; margin-left: 5px;">(<?= htmlspecialchars($q['email']) ?>)</span>
                        </div>
                        <div>
                            <?php if ($q['status'] === 'answered'): ?>
                                <span style="color: #4ade80; background: rgba(74, 222, 128, 0.1); border: 1px solid rgba(74, 222, 128, 0.2); padding: 3px 9px; border-radius: 999px; font-size: 0.8rem; font-weight: 500;">Answered</span>
                            <?php else: ?>
                                <span style="color: #f87171; background: rgba(248, 113, 113, 0.1); border: 1px solid rgba(248, 113, 113, 0.2); padding: 3px 9px; border-radius: 999px; font-size: 0.8rem; font-weight: 500;">Open</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <p style="margin-bottom: 10px;"><strong style="color: var(--text-primary);">Subject:</strong> <?= htmlspecialchars($q['subject']) ?></p>
                    <p style="color: var(--text-secondary); line-height: 1.5; margin-bottom: 15px; background: rgba(0, 0, 0, 0.2); padding: 12px; border-radius: 8px; border-left: 3px solid var(--accent-solid);"><?= nl2br(htmlspecialchars($q['message'])) ?></p>

                    <?php if ($q['status'] === 'answered'): ?>
                        <div style="background: rgba(99, 102, 241, 0.05); border: 1px solid rgba(99, 102, 241, 0.15); padding: 12px 15px; border-radius: 8px; margin-top: 10px;">
                            <strong style="color: #818cf8; font-size: 0.9rem; display: block; margin-bottom: 4px;">Reply Sent:</strong>
                            <p style="color: var(--text-primary); margin-bottom: 0; font-size: 0.95rem;"><?= nl2br(htmlspecialchars($q['staff_reply'])) ?></p>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="staff_bookings.php" style="display: flex; flex-direction: column; gap: 10px; margin-top: 10px;">
                            <input type="hidden" name="query_id" value="<?= $q['query_id'] ?>">
                            <textarea name="staff_reply" rows="3" placeholder="Type your reply to this customer..." required style="margin-bottom: 0; max-width: 100%; width: 100%; border-radius: 10px; padding: 12px;"></textarea>
                            <div style="text-align: right;">
                                <button type="submit" class="btn" style="margin-top: 0; padding: 8px 20px; font-size: 0.9rem; border-radius: 8px;">Send Reply</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

<?php require 'footer.php'; ?>