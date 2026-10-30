<?php
require 'db.php';
require 'header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO queries (customer_id, name, email, subject, message) VALUES (NULL, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $subject, $message]);
        $success = "Thank you, your query has been submitted. Our team will respond soon.";
    }
}
?>

<section class="contact-banner">
    <div class="hero-content">
        <span class="hero-pill">✦ Travel concierge support</span>
        <h1 class="hero-title">Let’s shape your <span class="gradient-text">perfect escape</span></h1>
        <p class="hero-subtitle">From boutique stays to unforgettable day trips, we help turn your ideas into a smooth Sri Lanka experience.</p>
        <div class="hero-highlights">
            <span>Fast replies</span>
            <span>Custom itineraries</span>
            <span>Local expertise</span>
        </div>
    </div>
</section>

<div class="contact-wrapper">
    <div class="contact-card">
        <div class="contact-sidebar">
            <div class="contact-sidebar-top">
                <p class="card-badge">Travel planning studio</p>
                <h3>We’re ready when you are</h3>
                <p>Share your travel ideas and let us create a seamless journey filled with comfort, culture, and unforgettable moments.</p>
            </div>

            <div class="contact-stats">
                <div class="contact-stat">
                    <strong>150+</strong>
                    <span>Trips crafted</span>
                </div>
                <div class="contact-stat">
                    <strong>24/7</strong>
                    <span>Support</span>
                </div>
                <div class="contact-stat">
                    <strong>4.9/5</strong>
                    <span>Guest rating</span>
                </div>
            </div>

            <div class="contact-details">
                <p><strong>Location:</strong> Negombo, Sri Lanka</p>
                <p><strong>Email:</strong> hello@globetrek.com</p>
                <p><strong>Phone:</strong> +94 77 123 4567</p>
            </div>
        </div>

        <div class="contact-form-panel">
            <?php if ($success): ?>
                <p class="contact-message success"><?= htmlspecialchars($success) ?></p>
            <?php endif; ?>
            <?php if ($error): ?>
                <p class="contact-message error"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <form method="POST" action="contact.php" class="contact-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" id="name" name="name" placeholder="Your full name" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" placeholder="you@example.com" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input type="text" id="subject" name="subject" placeholder="How can we help?" required>
                </div>

                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea id="message" name="message" rows="5" placeholder="Tell us about your travel plans, preferred dates, or dream destinations..." required></textarea>
                </div>

                <div class="contact-actions">
                    <button type="submit" class="btn">Send Inquiry</button>
                    <span class="contact-note">Usually replied within 1 business day</span>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require 'footer.php'; ?>