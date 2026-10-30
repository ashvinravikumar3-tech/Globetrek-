<?php
$allowed_roles = ['customer'];
require 'auth_check.php';
require 'db.php';
require 'header.php';

$error = '';
$booking_id = $_GET['booking_id'] ?? $_POST['booking_id'] ?? null;

if (!$booking_id) {
    echo "<p>No booking specified. <a href='cust_bookings.php'>Go to my bookings</a></p>";
    require 'footer.php';
    exit;
}

$stmt = $pdo->prepare("SELECT b.*, p.title AS package_title FROM bookings b
                        JOIN packages p ON b.package_id = p.package_id
                        WHERE b.booking_id = ? AND b.customer_id = ?");
$stmt->execute([$booking_id, $_SESSION['user_id']]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    echo "<p>Booking not found.</p>";
    require 'footer.php';
    exit;
}

if ($booking['status'] !== 'confirmed') {
    echo "<p>Feedback can only be submitted for confirmed bookings.</p>";
    require 'footer.php';
    exit;
}

// check if feedback already exists
$fStmt = $pdo->prepare("SELECT * FROM feedback WHERE booking_id = ?");
$fStmt->execute([$booking_id]);
$existingFeedback = $fStmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$existingFeedback) {
    $rating = $_POST['rating'];
    $comment = trim($_POST['comment']);

    if (!is_numeric($rating) || $rating < 1 || $rating > 5) {
        $error = "Please select a rating between 1 and 5.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO feedback (customer_id, booking_id, rating, comment) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $booking_id, $rating, $comment]);
        header("Location: cust_feedback.php?booking_id=$booking_id&submitted=1");
        exit;
    }
}
?>

<h2>Feedback for: <?= htmlspecialchars($booking['package_title']) ?></h2>

<?php if (isset($_GET['submitted'])): ?>
    <p style="color:green;">Thank you for your feedback!</p>
<?php endif; ?>

<?php if ($error): ?>
    <p style="color:red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<?php if ($existingFeedback): ?>
    <p>You already submitted feedback for this booking:</p>
    <p><strong>Rating:</strong> <?= (int)$existingFeedback['rating'] ?>/5</p>
    <p><strong>Comment:</strong> <?= nl2br(htmlspecialchars($existingFeedback['comment'])) ?></p>
<?php else: ?>
    <form method="POST" action="cust_feedback.php">
        <input type="hidden" name="booking_id" value="<?= $booking['booking_id'] ?>">

        <label>Rating (1-5):</label><br>
        <select name="rating" required>
            <option value="">-- Select --</option>
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <option value="<?= $i ?>"><?= $i ?></option>
            <?php endfor; ?>
        </select><br><br>

        <label>Comment:</label><br>
        <textarea name="comment" rows="4" cols="40"></textarea><br><br>

        <button type="submit">Submit Feedback</button>
    </form>
<?php endif; ?>

<p><a href="cust_bookings.php">Back to My Bookings</a></p>

<?php require 'footer.php'; ?>