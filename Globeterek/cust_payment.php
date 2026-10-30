<?php
$allowed_roles = ['customer'];
require 'auth_check.php';
require 'db.php';
require 'header.php';

$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
$error = '';

if (!$booking_id) {
    echo "<div class='contact-message error' style='margin-top:40px;'>No booking specified. <a href='cust_bookings.php'>Go to my bookings</a></div>";
    require 'footer.php';
    exit;
}

// Fetch booking + package + destination + accommodation + transport
$stmt = $pdo->prepare("
    SELECT b.*,
           p.title AS package_title, p.price AS package_price, p.duration_days, p.image AS package_image,
           d.name AS destination_name,
           a.name AS acc_name, a.type AS acc_type, a.price_per_night,
           t.type AS trans_type, t.provider AS trans_provider, t.price AS trans_price
    FROM bookings b
    JOIN packages p ON b.package_id = p.package_id
    JOIN destinations d ON p.destination_id = d.destination_id
    LEFT JOIN accommodations a ON b.accommodation_id = a.accommodation_id
    LEFT JOIN transport t ON b.transport_id = t.transport_id
    WHERE b.booking_id = ? AND b.customer_id = ?
");
$stmt->execute([$booking_id, $_SESSION['user_id']]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    echo "<div class='contact-message error' style='margin-top:40px;'>Booking not found. <a href='cust_bookings.php'>Go to my bookings</a></div>";
    require 'footer.php';
    exit;
}

if ($booking['status'] === 'cancelled') {
    echo "<div class='contact-message error' style='margin-top:40px;'>This booking has been cancelled and cannot be paid for. <a href='cust_bookings.php'>Go to my bookings</a></div>";
    require 'footer.php';
    exit;
}

// Check if already paid
$payCheck = $pdo->prepare("SELECT * FROM payments WHERE booking_id = ? AND payment_status = 'completed'");
$payCheck->execute([$booking_id]);
$existingPayment = $payCheck->fetch(PDO::FETCH_ASSOC);

// ---------- HANDLE PAYMENT SUBMIT ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay'])) {
    $method = trim($_POST['payment_method'] ?? '');

    if (empty($method)) {
        $error = "Please select a payment method.";
    } else {
        try {
            // Insert payment record
            $ins = $pdo->prepare("INSERT INTO payments (booking_id, amount, payment_status) VALUES (?, ?, 'completed')");
            $ins->execute([$booking_id, $booking['total_price']]);

            // Confirm the booking
            $upd = $pdo->prepare("UPDATE bookings SET status = 'confirmed' WHERE booking_id = ?");
            $upd->execute([$booking_id]);

            header('Location: cust_bookings.php?paid=1');
            exit;
        } catch (PDOException $e) {
            $error = "Payment processing failed. Please try again.";
        }
    }
}

// Price breakdown values
$packagePrice = (float)$booking['package_price'];
$accTotal     = $booking['price_per_night'] ? (float)$booking['price_per_night'] * (int)$booking['duration_days'] : 0;
$transTotal   = $booking['trans_price'] ? (float)$booking['trans_price'] : 0;
?>

<div class="section-header" style="margin-top: 40px; margin-bottom: 30px;">
    <div>
        <h2>Complete Your Payment</h2>
        <p style="color: var(--text-secondary); margin-top: 5px;">Secure checkout &mdash; confirm your booking by completing payment below.</p>
    </div>
</div>

<?php if ($error): ?>
    <div class="contact-message error" style="margin-bottom: 25px;"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($existingPayment): ?>
    <div class="contact-message success" style="margin-bottom: 30px; font-size: 1rem; padding: 18px 20px;">
        ✅ This booking has already been paid. Your booking is confirmed!
        &nbsp;<a href="cust_bookings.php" style="font-weight: 700; color: #22c55e;">View My Bookings &rarr;</a>
    </div>
<?php else: ?>

<div style="display: grid; grid-template-columns: 1.1fr 1fr; gap: 30px; margin-bottom: 60px; align-items: start;">

    <!-- ===== LEFT: Booking Summary ===== -->
    <div style="display: flex; flex-direction: column; gap: 20px;">

        <!-- Package Header Card -->
        <div class="card" style="padding: 0; overflow: hidden;">
            <div style="width:100%; height:140px; background: linear-gradient(135deg, rgba(99,102,241,0.4), rgba(236,72,153,0.35)); display:flex; align-items:center; justify-content:center; position:relative;">
                <span style="font-size: 3rem; opacity: 0.5;">✈️</span>
                <div style="position:absolute; bottom:14px; left:18px;">
                    <span style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 2px; color: rgba(255,255,255,0.6); font-weight: 700;">Booking #<?= $booking_id ?></span>
                </div>
            </div>
            <div style="padding: 22px;">
                <h3 style="font-size: 1.35rem; margin-bottom: 6px;"><?= htmlspecialchars($booking['package_title']) ?></h3>
                <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 12px;">
                    📍 <?= htmlspecialchars($booking['destination_name']) ?> &nbsp;&bull;&nbsp; <?= (int)$booking['duration_days'] ?> days
                </p>
                <p style="font-size: 0.9rem; color: var(--text-secondary);">
                    🗓️ Travel date: <strong style="color: var(--text-primary);"><?= date('d M Y', strtotime($booking['travel_date'])) ?></strong>
                </p>
            </div>
        </div>

        <!-- Price Breakdown -->
        <div class="card" style="padding: 24px;">
            <h4 style="font-family: var(--font-heading); font-size: 1.1rem; margin-bottom: 18px; color: var(--text-primary);">Price Breakdown</h4>

            <div style="display: flex; flex-direction: column; gap: 14px;">

                <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 14px; border-bottom: 1px solid var(--glass-border);">
                    <span style="color: var(--text-secondary); font-size: 0.92rem;">📦 <?= htmlspecialchars($booking['package_title']) ?></span>
                    <span style="font-weight: 600;">Rs. <?= number_format($packagePrice, 2) ?></span>
                </div>

                <?php if ($accTotal > 0): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 14px; border-bottom: 1px solid var(--glass-border);">
                    <span style="color: var(--text-secondary); font-size: 0.92rem;">
                        🏨 <?= htmlspecialchars($booking['acc_name']) ?> &times; <?= (int)$booking['duration_days'] ?> nights
                    </span>
                    <span style="font-weight: 600;">Rs. <?= number_format($accTotal, 2) ?></span>
                </div>
                <?php endif; ?>

                <?php if ($transTotal > 0): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 14px; border-bottom: 1px solid var(--glass-border);">
                    <span style="color: var(--text-secondary); font-size: 0.92rem;">
                        🚗 <?= htmlspecialchars($booking['trans_type']) ?> (<?= htmlspecialchars($booking['trans_provider']) ?>)
                    </span>
                    <span style="font-weight: 600;">Rs. <?= number_format($transTotal, 2) ?></span>
                </div>
                <?php endif; ?>

                <!-- Total -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 4px;">
                    <span style="font-family: var(--font-heading); font-weight: 700; font-size: 1.05rem;">Total Due</span>
                    <span style="font-family: var(--font-heading); font-weight: 800; font-size: 1.4rem; background: var(--accent-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                        Rs. <?= number_format((float)$booking['total_price'], 2) ?>
                    </span>
                </div>
            </div>
        </div>

        <a href="cust_bookings.php" style="font-size: 0.9rem; color: var(--text-secondary); display: inline-flex; align-items: center; gap: 6px;">
            &larr; Back to My Bookings
        </a>
    </div>

    <!-- ===== RIGHT: Payment Form ===== -->
    <div class="card" style="padding: 30px; position: sticky; top: 130px;">
        <h3 style="font-family: var(--font-heading); font-size: 1.4rem; margin-bottom: 6px;">Payment Method</h3>
        <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 24px;">Choose how you would like to pay for your trip.</p>

        <form id="paymentForm" method="POST" action="cust_payment.php?booking_id=<?= $booking_id ?>" style="display: flex; flex-direction: column; gap: 20px;">
            <input type="hidden" name="pay" value="1">

            <!-- Payment Method Tabs -->
            <div id="methodTabs" style="display: grid; grid-template-columns: repeat(3,1fr); gap: 10px;">
                <?php
                $methods = [
                    ['card', '💳', 'Credit / Debit Card'],
                    ['bank', '🏦', 'Bank Transfer'],
                    ['cash', '💵', 'Cash on Arrival'],
                ];
                foreach ($methods as [$val, $icon, $label]):
                ?>
                <label id="tab-<?= $val ?>" for="method-<?= $val ?>" style="cursor: pointer; display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 14px 8px; border: 1px solid var(--glass-border); border-radius: 12px; transition: all 0.25s ease; font-size: 0.82rem; color: var(--text-secondary); text-align: center; user-select: none;">
                    <input type="radio" name="payment_method" id="method-<?= $val ?>" value="<?= $val ?>" style="display:none;">
                    <span style="font-size: 1.5rem;"><?= $icon ?></span>
                    <?= $label ?>
                </label>
                <?php endforeach; ?>
            </div>

            <!-- Card Fields -->
            <div id="cardFields" style="display: none; flex-direction: column; gap: 14px;">
                <div style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="font-size: 0.9rem; font-weight: 500;">Cardholder Name</label>
                    <input type="text" id="cardName" placeholder="e.g. John Silva" style="max-width:none; margin-bottom:0;">
                </div>
                <div style="display: flex; flex-direction: column; gap: 6px;">
                    <label style="font-size: 0.9rem; font-weight: 500;">Card Number</label>
                    <input type="text" id="cardNumber" placeholder="•••• •••• •••• ••••" maxlength="19" style="max-width:none; margin-bottom:0; letter-spacing:2px;">
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                    <div style="display: flex; flex-direction: column; gap: 6px;">
                        <label style="font-size: 0.9rem; font-weight: 500;">Expiry</label>
                        <input type="text" id="cardExpiry" placeholder="MM / YY" maxlength="7" style="max-width:none; margin-bottom:0;">
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 6px;">
                        <label style="font-size: 0.9rem; font-weight: 500;">CVV</label>
                        <input type="text" id="cardCvv" placeholder="•••" maxlength="4" style="max-width:none; margin-bottom:0;">
                    </div>
                </div>
                <p style="font-size: 0.78rem; color: var(--text-secondary);">🔒 Card details are for demo only and are not stored or processed.</p>
            </div>

            <!-- Bank Fields -->
            <div id="bankFields" style="display: none; flex-direction: column; gap: 10px;">
                <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--glass-border); border-radius: 12px; padding: 16px; font-size: 0.9rem; color: var(--text-secondary); line-height: 2;">
                    <p style="font-weight: 600; color: var(--text-primary); margin-bottom: 6px;">Bank Transfer Details</p>
                    <p>🏦 <strong>Bank:</strong> Bank of Ceylon</p>
                    <p>📁 <strong>Account Name:</strong> GlobeTrek Adventures (Pvt) Ltd</p>
                    <p>🔢 <strong>Account No:</strong> 0123456789</p>
                    <p>📌 <strong>Branch:</strong> Negombo Main Branch</p>
                    <p style="margin-top: 8px; font-size: 0.8rem; color: #f87171;">Use <strong>Booking #<?= $booking_id ?></strong> as your transfer reference.</p>
                </div>
            </div>

            <!-- Cash Fields -->
            <div id="cashFields" style="display: none; flex-direction: column; gap: 10px;">
                <div style="background: rgba(255,255,255,0.03); border: 1px solid var(--glass-border); border-radius: 12px; padding: 16px; font-size: 0.9rem; color: var(--text-secondary); line-height: 2;">
                    <p style="font-weight: 600; color: var(--text-primary); margin-bottom: 6px;">Cash on Arrival</p>
                    <p>💵 Full amount of <strong style="color: var(--accent-solid);">Rs. <?= number_format((float)$booking['total_price'], 2) ?></strong> will be collected on your travel date.</p>
                    <p style="font-size: 0.82rem;">Click <strong>Confirm</strong> to lock in your booking. Our team will contact you with details.</p>
                </div>
            </div>

            <button type="submit" class="btn" style="width: 100%; padding: 1rem; font-size: 1.05rem; justify-content: center; gap: 10px; margin-top: 5px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                Confirm &amp; Pay Rs. <?= number_format((float)$booking['total_price'], 2) ?>
            </button>
        </form>
    </div>

</div>

<?php endif; ?>

<style>
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to   { opacity: 1; transform: translateY(0); }
}
#cardFields, #bankFields, #cashFields {
    animation: fadeIn 0.3s ease;
}
@media (max-width: 900px) {
    div[style*="grid-template-columns: 1.1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
    .card[style*="position: sticky"] {
        position: static !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const allPanels = {
        card: document.getElementById('cardFields'),
        bank: document.getElementById('bankFields'),
        cash: document.getElementById('cashFields'),
    };

    function selectMethod(val) {
        // Update tab styles
        Object.keys(allPanels).forEach(key => {
            const lbl = document.getElementById('tab-' + key);
            if (!lbl) return;
            if (key === val) {
                lbl.style.borderColor = 'var(--accent-solid)';
                lbl.style.background  = 'rgba(236,72,153,0.08)';
                lbl.style.color       = 'var(--text-primary)';
            } else {
                lbl.style.borderColor = 'var(--glass-border)';
                lbl.style.background  = 'transparent';
                lbl.style.color       = 'var(--text-secondary)';
            }
        });

        // Show/hide panels
        Object.keys(allPanels).forEach(key => {
            const panel = allPanels[key];
            if (panel) panel.style.display = (key === val) ? 'flex' : 'none';
        });
    }

    // Attach click listeners to labels
    Object.keys(allPanels).forEach(val => {
        const lbl = document.getElementById('tab-' + val);
        if (lbl) {
            lbl.addEventListener('click', () => {
                const radio = document.getElementById('method-' + val);
                if (radio) radio.checked = true;
                selectMethod(val);
            });
        }
    });

    // Card number auto-formatting
    const cardNumber = document.getElementById('cardNumber');
    if (cardNumber) {
        cardNumber.addEventListener('input', () => {
            let v = cardNumber.value.replace(/\D/g, '').substring(0, 16);
            cardNumber.value = v.replace(/(.{4})/g, '$1 ').trim();
        });
    }

    // Expiry auto-formatting
    const cardExpiry = document.getElementById('cardExpiry');
    if (cardExpiry) {
        cardExpiry.addEventListener('input', () => {
            let v = cardExpiry.value.replace(/\D/g, '').substring(0, 4);
            if (v.length >= 3) v = v.substring(0, 2) + ' / ' + v.substring(2);
            cardExpiry.value = v;
        });
    }

    // Form validation before submit
    const form = document.getElementById('paymentForm');
    if (form) {
        form.addEventListener('submit', (e) => {
            const selected = document.querySelector('input[name="payment_method"]:checked');
            if (!selected) {
                e.preventDefault();
                alert('Please select a payment method to continue.');
                return;
            }
            if (selected.value === 'card') {
                const name   = document.getElementById('cardName')?.value.trim();
                const number = document.getElementById('cardNumber')?.value.replace(/\s/g, '');
                const expiry = document.getElementById('cardExpiry')?.value.trim();
                const cvv    = document.getElementById('cardCvv')?.value.trim();
                if (!name || number.length < 16 || expiry.length < 5 || cvv.length < 3) {
                    e.preventDefault();
                    alert('Please fill in all card details correctly before continuing.');
                    return;
                }
            }
        });
    }
});
</script>

<?php require 'footer.php'; ?>
